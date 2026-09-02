<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

class WordpressMySQLLockMutex implements Mutex {
	use WordpressWpdb;

	private const MAX_LOCK_NAME_BYTES = 64;

	private \wpdb $wpdb;

	private string $lockName;

	private int $waitForLockTimeout;

	private bool $acquired = false;

	public function __construct( string $lockName = '_mutex', int $waitForLockTimeout = 5, ?\wpdb $wpdb = null ) {
		if ( '' === $lockName || strlen( $lockName ) > self::MAX_LOCK_NAME_BYTES ) {
			throw new \InvalidArgumentException( 'A MySQL lock name must contain between 1 and 64 bytes.' );
		}

		if ( $waitForLockTimeout < 0 ) {
			throw new \InvalidArgumentException( 'The lock wait timeout cannot be negative.' );
		}

		$this->wpdb               = $wpdb ?? $this->getWpdbFromGlobal();
		$this->lockName           = $lockName;
		$this->waitForLockTimeout = $waitForLockTimeout;
	}

	public static function fromOrder( \WC_Order $order, string $lockName = '_mutex', int $waitForLockTimeout = 5 ): self {
		return new self( 'order' . (string) $order->get_id() . $lockName, $waitForLockTimeout );
	}

	public function acquireLock(): bool {
		if ( $this->acquired ) {
			return true;
		}

		$query  = $this->wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $this->lockName, $this->waitForLockTimeout );
		$result = $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( '1' === (string) $result ) {
			$this->acquired = true;

			return true;
		}

		if ( '0' === (string) $result ) {
			return false;
		}

		throw new MutexAcquireException( $this->databaseError( 'Unable to acquire the MySQL lock.' ) );
	}

	public function releaseLock(): void {
		if ( ! $this->acquired ) {
			return;
		}

		$query  = $this->wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $this->lockName );
		$result = $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( '1' !== (string) $result ) {
			throw new MutexReleaseException( $this->databaseError( 'Unable to release the MySQL lock.' ) );
		}

		$this->acquired = false;
	}

	private function databaseError( string $fallback ): string {
		return '' !== $this->wpdb->last_error ? $fallback . ' ' . $this->wpdb->last_error : $fallback;
	}
}

<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

class WordpressPostMutex implements ExpiringMutex {
	use WordpressWpdb;

	private const VALUE_DELIMITER = '|';

	/** @var \wpdb */
	private $wpdb;

	/** @var int */
	private $postId;

	/** @var int */
	private $timeout;

	/** @var int */
	private $waitForLockTimeout;

	/** @var LockKey */
	private $key;

	public function __construct( int $post_id, string $lock_name = '_mutex', int $timeout = 5, int $waitForLockTimeout = 5, ?\wpdb $wpdb = null ) {
		$this->initialize(
			$post_id,
			LockKey::create( $lock_name ),
			$timeout,
			$waitForLockTimeout,
			$wpdb
		);
	}

	public static function fromOrder( \WC_Order $order, string $lock_name = '_mutex', int $timeout = 5 ): self {
		return new self( $order->get_id(), $lock_name, $timeout );
	}

	public static function fromKey( int $postId, LockKey $key, int $timeout = 5, int $waitForLockTimeout = 5, ?\wpdb $wpdb = null ): self {
		$mutex      = new self( $postId, $key->getResource(), $timeout, $waitForLockTimeout, $wpdb );
		$mutex->key = $key;

		return $mutex;
	}

	public function getKey(): LockKey {
		return $this->key;
	}

	public function acquireLock(): bool {
		return $this->withGuard(
			function (): bool {
				$this->deleteExpiredLocks();

				$currentToken = $this->getActiveToken();
				if ( null !== $currentToken ) {
					return hash_equals( $currentToken, $this->key->getToken() );
				}

				$value = $this->formatValue( $this->key->getToken(), $this->timeout );
				$sql   = $this->wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"INSERT INTO {$this->wpdb->postmeta} (post_id, meta_key, meta_value) VALUES (%d, %s, %s)",
					$this->postId,
					$this->key->getResource(),
					$value
				);
				$result = $this->wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( 1 !== $result ) {
					throw new MutexAcquireException( $this->databaseError( 'Unable to persist the post lock.' ) );
				}

				return true;
			}
		);
	}

	public function refreshLock( int $ttl ): bool {
		if ( $ttl < 1 ) {
			throw new \InvalidArgumentException( 'The lock TTL must be greater than zero.' );
		}

		return $this->withGuard(
			function () use ( $ttl ): bool {
				$this->deleteExpiredLocks();
				if ( ! $this->isOwnedByCurrentKey() ) {
					return false;
				}

				$sql = $this->wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"UPDATE {$this->wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = %s AND meta_value LIKE %s",
					$this->formatValue( $this->key->getToken(), $ttl ),
					$this->postId,
					$this->key->getResource(),
					$this->wpdb->esc_like( $this->key->getToken() . self::VALUE_DELIMITER ) . '%'
				);
				$result = $this->wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( false === $result ) {
					throw new MutexException( $this->databaseError( 'Unable to refresh the post lock.' ) );
				}

				return 1 === $result;
			}
		);
	}

	public function releaseLock(): void {
		$released = $this->withGuard(
			function (): bool {
				$sql = $this->wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM {$this->wpdb->postmeta} WHERE post_id = %d AND meta_key = %s AND meta_value LIKE %s",
					$this->postId,
					$this->key->getResource(),
					$this->wpdb->esc_like( $this->key->getToken() . self::VALUE_DELIMITER ) . '%'
				);
				$result = $this->wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( false === $result ) {
					throw new MutexReleaseException( $this->databaseError( 'Unable to release the post lock.' ) );
				}

				return true;
			}
		);

		if ( ! $released ) {
			throw new MutexReleaseException( 'Unable to acquire the guard used to release the post lock.' );
		}
	}

	private function initialize( int $postId, LockKey $key, int $timeout, int $waitForLockTimeout, ?\wpdb $wpdb ): void {
		if ( $postId < 1 ) {
			throw new \InvalidArgumentException( 'The post ID must be greater than zero.' );
		}

		if ( $timeout < 1 || $waitForLockTimeout < 0 ) {
			throw new \InvalidArgumentException( 'The lease TTL must be positive and the guard wait timeout cannot be negative.' );
		}

		$this->wpdb               = $wpdb ?? $this->getWpdbFromGlobal();
		$this->postId             = $postId;
		$this->timeout            = $timeout;
		$this->waitForLockTimeout = $waitForLockTimeout;
		$this->key                = $key;
	}

	/** @return bool Result of the guarded operation, or false when the guard is contended. */
	private function withGuard( callable $operation ): bool {
		$guard = new WordpressMySQLLockMutex( $this->guardName(), $this->waitForLockTimeout, $this->wpdb );
		if ( ! $guard->acquireLock() ) {
			return false;
		}

		try {
			return (bool) $operation();
		} finally {
			$guard->releaseLock();
		}
	}

	private function guardName(): string {
		$database = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
		$scope    = $database . ':' . $this->wpdb->prefix . ':' . $this->postId . ':' . $this->key->getResource();

		return 'wpdesk-post-' . substr( hash( 'sha256', $scope ), 0, 48 );
	}

	private function getActiveToken(): ?string {
		$sql = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE post_id = %d AND meta_key = %s AND CAST(CASE WHEN LOCATE(%s, meta_value) > 0 THEN SUBSTRING_INDEX(meta_value, %s, -1) ELSE SUBSTRING_INDEX(meta_value, '_', -1) END AS UNSIGNED) >= UNIX_TIMESTAMP() ORDER BY meta_id ASC LIMIT 1",
			$this->postId,
			$this->key->getResource(),
			self::VALUE_DELIMITER,
			self::VALUE_DELIMITER
		);
		$value = $this->wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $value ) {
			if ( '' !== $this->wpdb->last_error ) {
				throw new MutexException( $this->databaseError( 'Unable to read the post lock.' ) );
			}

			return null;
		}

		$delimiter = false !== strpos( (string) $value, self::VALUE_DELIMITER ) ? self::VALUE_DELIMITER : '_';
		$parts     = explode( $delimiter, (string) $value, 2 );

		return 2 === count( $parts ) ? $parts[0] : null;
	}

	private function isOwnedByCurrentKey(): bool {
		$currentToken = $this->getActiveToken();

		return null !== $currentToken && hash_equals( $currentToken, $this->key->getToken() );
	}

	private function deleteExpiredLocks(): void {
		$sql = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DELETE FROM {$this->wpdb->postmeta} WHERE post_id = %d AND meta_key = %s AND CAST(CASE WHEN LOCATE(%s, meta_value) > 0 THEN SUBSTRING_INDEX(meta_value, %s, -1) ELSE SUBSTRING_INDEX(meta_value, '_', -1) END AS UNSIGNED) < UNIX_TIMESTAMP()",
			$this->postId,
			$this->key->getResource(),
			self::VALUE_DELIMITER,
			self::VALUE_DELIMITER
		);
		$result = $this->wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			throw new MutexException( $this->databaseError( 'Unable to clean expired post locks.' ) );
		}
	}

	private function formatValue( string $token, int $ttl ): string {
		$sql       = $this->wpdb->prepare( 'SELECT UNIX_TIMESTAMP() + %d', $ttl );
		$expiresAt = (int) $this->wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $expiresAt < 1 ) {
			throw new MutexException( $this->databaseError( 'Unable to calculate the post lock expiry.' ) );
		}

		return $token . self::VALUE_DELIMITER . $expiresAt;
	}

	private function databaseError( string $fallback ): string {
		return '' !== $this->wpdb->last_error ? $fallback . ' ' . $this->wpdb->last_error : $fallback;
	}
}

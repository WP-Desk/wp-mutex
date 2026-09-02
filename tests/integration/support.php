<?php

declare(strict_types=1);

if ( ! class_exists( 'WC_Order' ) ) {
	class WC_Order {
		/** @var int */
		private $id;

		public function __construct( int $id = 0 ) {
			$this->id = $id;
		}

		public function get_id(): int {
			return $this->id;
		}
	}
}

function wp_mutex_test_database_connection(): wpdb {
	$connection = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$connection->set_prefix( $GLOBALS['table_prefix'] );

	return $connection;
}

class WpMutexDatabaseWithoutAdvisoryLocks extends wpdb {
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		if ( is_string( $query ) && false !== stripos( $query, 'GET_LOCK' ) ) {
			throw new RuntimeException( 'Advisory locks are unavailable on this connection.' );
		}

		return parent::get_var( $query, $x, $y );
	}
}

function wp_mutex_test_database_without_advisory_locks(): wpdb {
	$connection = new WpMutexDatabaseWithoutAdvisoryLocks( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$connection->set_prefix( $GLOBALS['table_prefix'] );

	return $connection;
}

function wp_mutex_test_lock_name( string $prefix ): string {
	return $prefix . '-' . substr( bin2hex( random_bytes( 8 ) ), 0, 16 );
}

<?php

declare(strict_types=1);

namespace WPDesk\Mutex\Tests\Integration;

use WPDesk\Mutex\MutexAcquireException;
use WPDesk\Mutex\WordpressMySQLLockMutex;

final class WordpressMySQLLockMutexTest extends \WP_UnitTestCase {
	public function test_same_named_lock_is_exclusive_between_connections(): void {
		$name      = \wp_mutex_test_lock_name( 'mysql-exclusive' );
		$ownerDb   = \wp_mutex_test_database_connection();
		$otherDb   = \wp_mutex_test_database_connection();
		$owner     = new WordpressMySQLLockMutex( $name, 0, $ownerDb );
		$contender = new WordpressMySQLLockMutex( $name, 0, $otherDb );

		try {
			self::assertTrue( $owner->acquireLock() );
			self::assertFalse( $contender->acquireLock() );
			$owner->releaseLock();
			self::assertTrue( $contender->acquireLock() );
		} finally {
			$owner->releaseLock();
			$contender->releaseLock();
			$ownerDb->close();
			$otherDb->close();
		}
	}

	public function test_different_names_do_not_contend(): void {
		$firstDb = \wp_mutex_test_database_connection();
		$otherDb = \wp_mutex_test_database_connection();
		$first   = new WordpressMySQLLockMutex( \wp_mutex_test_lock_name( 'mysql-a' ), 0, $firstDb );
		$other   = new WordpressMySQLLockMutex( \wp_mutex_test_lock_name( 'mysql-b' ), 0, $otherDb );

		try {
			self::assertTrue( $first->acquireLock() );
			self::assertTrue( $other->acquireLock() );
		} finally {
			$first->releaseLock();
			$other->releaseLock();
			$firstDb->close();
			$otherDb->close();
		}
	}

	public function test_repeated_acquisition_on_one_object_needs_one_release(): void {
		$name      = \wp_mutex_test_lock_name( 'mysql-repeat' );
		$ownerDb   = \wp_mutex_test_database_connection();
		$otherDb   = \wp_mutex_test_database_connection();
		$owner     = new WordpressMySQLLockMutex( $name, 0, $ownerDb );
		$contender = new WordpressMySQLLockMutex( $name, 0, $otherDb );

		try {
			self::assertTrue( $owner->acquireLock() );
			self::assertTrue( $owner->acquireLock() );
			$owner->releaseLock();
			self::assertTrue( $contender->acquireLock() );
		} finally {
			$owner->releaseLock();
			$contender->releaseLock();
			$ownerDb->close();
			$otherDb->close();
		}
	}

	public function test_closing_owner_connection_releases_lock(): void {
		$name      = \wp_mutex_test_lock_name( 'mysql-close' );
		$ownerDb   = \wp_mutex_test_database_connection();
		$otherDb   = \wp_mutex_test_database_connection();
		$owner     = new WordpressMySQLLockMutex( $name, 0, $ownerDb );
		$contender = new WordpressMySQLLockMutex( $name, 0, $otherDb );

		try {
			self::assertTrue( $owner->acquireLock() );
			self::assertFalse( $contender->acquireLock() );
			$ownerDb->close();
			self::assertTrue( $contender->acquireLock() );
		} finally {
			$contender->releaseLock();
			$otherDb->close();
		}
	}

	public function test_database_failure_is_not_reported_as_contention(): void {
		$this->expectException( MutexAcquireException::class );

		$mutex = new WordpressMySQLLockMutex( 'database-failure', 0, new MutexAcquireFailureWpdb() );
		$mutex->acquireLock();
	}

	public function test_from_order_scopes_lock_to_order_id(): void {
		$order   = new \WC_Order( 321 );
		$otherDb = \wp_mutex_test_database_connection();
		$first   = WordpressMySQLLockMutex::fromOrder( $order, '-factory', 0 );
		$other   = new WordpressMySQLLockMutex( 'order321-factory', 0, $otherDb );

		try {
			self::assertTrue( $first->acquireLock() );
			self::assertFalse( $other->acquireLock() );
		} finally {
			$first->releaseLock();
			$other->releaseLock();
			$otherDb->close();
		}
	}
}

class MutexAcquireFailureWpdb extends \wpdb {
	public $last_error = 'simulated database failure';

	public function __construct() {
	}

	public function prepare( $query, ...$args ) {
		return $query;
	}

	public function get_var( $query = null, $x = 0, $y = 0 ) {
		return null;
	}
}

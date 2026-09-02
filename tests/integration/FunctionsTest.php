<?php

declare(strict_types=1);

namespace WPDesk\Mutex\Tests\Integration;

use WPDesk\Mutex\MutexNotFoundInStorage;
use WPDesk\Mutex\StaticMutexStorage;
use WPDesk\Mutex\WordpressMySQLLockMutex;

final class FunctionsTest extends \WP_UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		StaticMutexStorage::$mutexStorage = [];
	}

	protected function tearDown(): void {
		foreach ( array_keys( StaticMutexStorage::$mutexStorage ) as $name ) {
			\wpdesk_release_lock( $name );
		}

		parent::tearDown();
	}

	public function test_factory_returns_mysql_mutex(): void {
		self::assertInstanceOf( WordpressMySQLLockMutex::class, \wpdesk_create_mysql_lock( 'factory-lock', 0 ) );
	}

	public function test_helper_acquires_repeatedly_without_leaking_mysql_reference_count(): void {
		$name      = \wp_mutex_test_lock_name( 'helper-repeat' );
		$otherDb   = \wp_mutex_test_database_connection();
		$contender = new WordpressMySQLLockMutex( $name, 0, $otherDb );

		try {
			self::assertTrue( \wpdesk_acquire_lock( $name, 0 ) );
			self::assertTrue( \wpdesk_acquire_lock( $name, 0 ) );
			\wpdesk_release_lock( $name );
			self::assertTrue( $contender->acquireLock() );
		} finally {
			$contender->releaseLock();
			$otherDb->close();
		}
	}

	public function test_unsupported_helper_type_does_not_register_lock(): void {
		self::assertFalse( \wpdesk_acquire_lock( 'unsupported', 0, 'other' ) );
		self::assertSame( [], StaticMutexStorage::$mutexStorage );
	}

	public function test_releasing_unknown_helper_lock_throws(): void {
		$this->expectException( MutexNotFoundInStorage::class );

		\wpdesk_release_lock( 'missing' );
	}
}

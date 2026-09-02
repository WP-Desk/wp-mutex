<?php

declare(strict_types=1);

/**
 * Create MySQL lock.
 *
 * @param string $lockName Lock name.
 * @param int    $waitForLockTimeout Wait for lock timeout.
 *
 * @return \WPDesk\Mutex\WordpressMySQLLockMutex
 * @deprecated 2.0.0 Instantiate WordpressMySQLLockMutex directly.
 */
function wpdesk_create_mysql_lock( string $lockName, int $waitForLockTimeout = 5 ): \WPDesk\Mutex\WordpressMySQLLockMutex {
	return new \WPDesk\Mutex\WordpressMySQLLockMutex( $lockName, $waitForLockTimeout );
}

/**
 * Create MySQL Lock from order.
 *
 * @param WC_Order $order
 * @param string $lockName
 * @param int $waitForLockTimeout
 *
 * @return \WPDesk\Mutex\WordpressMySQLLockMutex
 * @deprecated 2.0.0 Use WordpressMySQLLockMutex::fromOrder().
 */
function wpdesk_create_mysql_lock_from_order( \WC_Order $order, string $lockName = '_mutex', int $waitForLockTimeout = 5 ): \WPDesk\Mutex\WordpressMySQLLockMutex {
	return \WPDesk\Mutex\WordpressMySQLLockMutex::fromOrder( $order, $lockName, $waitForLockTimeout );
}

/**
 * Acquire lock.
 *
 * @param string $lockName
 * @param int $waitForLockTimeout
 * @param string $lockType
 *
 * @return bool
 * @deprecated 2.0.0 Keep a Mutex object and call acquireLock() directly.
 */
function wpdesk_acquire_lock( string $lockName, int $waitForLockTimeout = 5, string $lockType = 'mysql' ): bool {
	if ( 'mysql' !== $lockType ) {
		return false;
	}

	$storage = new \WPDesk\Mutex\StaticMutexStorage();
	$mutex   = $storage->getFromStorage( $lockName );
	$mutex ??= wpdesk_create_mysql_lock( $lockName, $waitForLockTimeout );

	if ( ! $mutex->acquireLock() ) {
		return false;
	}

	$storage->addToStorage( $lockName, $mutex );

	return true;
}

/**
 * Release lock.
 *
 * @param string $lockName Lock name.
 * @throws \WPDesk\Mutex\MutexNotFoundInStorage Exception.
 * @deprecated 2.0.0 Keep the acquired Mutex object and call releaseLock() in a finally block.
 */
function wpdesk_release_lock( string $lockName ): void {
	$storage = new \WPDesk\Mutex\StaticMutexStorage();
	$mutex   = $storage->getFromStorage( $lockName );
	if ( null !== $mutex ) {
		$mutex->releaseLock();
		$storage->removeFromStorage( $lockName );
	} else {
		throw new \WPDesk\Mutex\MutexNotFoundInStorage();
	}
}

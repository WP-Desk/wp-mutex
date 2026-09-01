<?php

declare(strict_types=1);

/**
 * Create MySQL lock.
 *
 * @param string $lockName Lock name.
 * @param int    $waitForLockTimeout Wait for lock timeout.
 *
 * @return \WPDesk\Mutex\WordpressMySQLLockMutex
 */
function wpdesk_create_mysql_lock( $lockName, $waitForLockTimeout = 5 ) {
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
 */
function wpdesk_create_mysql_lock_from_order( \WC_Order $order, $lockName = '_mutex', $waitForLockTimeout = 5 ) {
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
 */
function wpdesk_acquire_lock( $lockName, $waitForLockTimeout = 5, $lockType = 'mysql' ) {
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
 */
function wpdesk_release_lock( $lockName ) {
	$storage = new \WPDesk\Mutex\StaticMutexStorage();
	$mutex   = $storage->getFromStorage( $lockName );
	if ( null !== $mutex ) {
		$mutex->releaseLock();
		$storage->removeFromStorage( $lockName );
	} else {
		throw new \WPDesk\Mutex\MutexNotFoundInStorage();
	}
}

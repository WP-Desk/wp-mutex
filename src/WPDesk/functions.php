<?php

/**
 * @param string $lockName Lock name.
 * @param int    $waitForLockTimeout Wait for lock timeout.
 *
 * @return \WPDesk\Mutex\WordpressMySQLLockMutex
 */
function wpdesk_mysql_lock($lockName, $waitForLockTimeout = 5)
{
    return new \WPDesk\Mutex\WordpressMySQLLockMutex($lockName, $waitForLockTimeout);
}

function wpdesk_mysql_lock_from_order(\WC_Order $order, $lockName = '_mutex', $waitForLockTimeout = 5)
{
    return \WPDesk\Mutex\WordpressMySQLLockMutex::fromOrder($order, $lockName, $waitForLockTimeout);
}

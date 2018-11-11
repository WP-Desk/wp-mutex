<?php

use WPDesk\Mutex\WordpressMySQLLockMutex;

class TestFunctions extends WP_UnitTestCase
{

    public function testWpdeskCreateMysqlLock()
    {
        $mysql_mutex = wpdesk_create_mysql_lock('test', 5);

        $this->assertInstanceOf(WordpressMySQLLockMutex::class, $mysql_mutex);
    }

    public function testWpdeskCreateMysqlLockFromOrder()
    {
        $order = new WC_Order();
        $order->save();

        $mysql_mutex = wpdesk_create_mysql_lock_from_order($order, 'test', 5);

        $this->assertInstanceOf(WordpressMySQLLockMutex::class, $mysql_mutex);
    }

    public function testWpdeskAcquireLock()
    {
        $this->assertTrue(wpdesk_acquire_lock('test'));
        wpdesk_release_lock('test');
    }

    public function testWpdeskReleaseLock()
    {
        $this->expectException(\WPDesk\Mutex\MutexNotFoundInStorage::class);
        wpdesk_release_lock('test');
    }

}

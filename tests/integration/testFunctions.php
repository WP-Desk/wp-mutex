<?php

use WPDesk\Mutex\WordpressMySQLLockMutex;

class TestFunctions extends WP_UnitTestCase
{

    public function testWpdeskMysqlLock()
    {
        $mysql_mutex = wpdesk_mysql_lock('test', 5);

        $this->assertInstanceOf(WordpressMySQLLockMutex::class, $mysql_mutex);
    }

    public function testWpdeskMysqlLockFromOrder()
    {
        $order = new WC_Order();
        $order->save();

        $mysql_mutex = wpdesk_mysql_lock_from_order($order, 'test', 5);

        $this->assertInstanceOf(WordpressMySQLLockMutex::class, $mysql_mutex);
    }

}

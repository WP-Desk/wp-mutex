<?php

use WPDesk\Mutex\WordpressMySQLLockMutex;

require_once 'wpdbTrait.php';

class TestWordpressMySQLLockMutex extends WP_UnitTestCase
{

    use wpdbTrait;

    public function testFromOrder()
    {
        $order = new WC_Order();
        $order->save();

        $fromOrder = \WPDesk\Mutex\WordpressMySQLLockMutex::fromOrder($order);

        $this->assertInstanceOf(WordpressMySQLLockMutex::class, $fromOrder);
    }

    public function testAcquireLock()
    {
        $order = new WC_Order();
        $order->save();

        $fromOrder = WordpressMySQLLockMutex::fromOrder($order);

        $this->assertTrue($fromOrder->acquireLock());

        $fromOrder->releaseLock();
    }

    public function testConcurrentLock()
    {
        $order = new WC_Order();
        $order->save();

        $fromOrder = WordpressMySQLLockMutex::fromOrder($order);
        $this->assertTrue($fromOrder->acquireLock());

        global $wpdb;

        $wpdb1 = $wpdb;

        $this->newWpdb();

        $wpdb2 = $wpdb;

        $fromOrder2 = WordpressMySQLLockMutex::fromOrder($order);
        $this->assertFalse($fromOrder2->acquireLock());

        $wpdb = $wpdb1;
        $fromOrder->releaseLock();
        $wpdb = $wpdb2;
        $this->assertTrue($fromOrder2->acquireLock());

        $fromOrder2->releaseLock();

        $wpdb = $wpdb1;
    }

}

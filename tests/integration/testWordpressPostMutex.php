<?php

use WPDesk\Mutex\WordpressPostMutex;

class TestWordpressPostMutex extends WP_UnitTestCase
{

    public function testFromOrder()
    {
        $order = new WC_Order();
        $order->save();

        $fromOrder = WordpressPostMutex::fromOrder($order);

        $this->assertInstanceOf(WordpressPostMutex::class, $fromOrder);
    }

    public function testAcquireLock()
    {
        $order = new WC_Order();
        $order->save();

        $fromOrder = WordpressPostMutex::fromOrder($order);

        $this->assertTrue($fromOrder->acquireLock());
    }


}

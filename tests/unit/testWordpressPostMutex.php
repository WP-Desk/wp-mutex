<?php

use WPDesk\Mutex\WordpressPostMutex;

class TestWordpressPostMutex extends \WP_Mock\Tools\TestCase
{

    /**
     * Set up.
     */
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testFromOrder()
    {

        global $wpdb;

        $wpdb = $this->getMockBuilder('wpdb')
                     ->disableOriginalConstructor()
                     ->setMethods(['_real_escape'])
                     ->getMock();

        $wpdb->method('_real_escape')
              ->willReturn('_mutex');

        $order = $this->getMockBuilder('WC_Order')
                      ->disableOriginalConstructor()
                      ->setMethods(['get_id'])
                      ->getMock();

        $order->method('get_id')
              ->willReturn(1);

        $fromOrder = WordpressPostMutex::fromOrder($order);

        $this->assertInstanceOf(WordpressPostMutex::class, $fromOrder);
    }


}

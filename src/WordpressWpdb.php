<?php

namespace WPDesk\Mutex;

trait WordpressWpdb
{

    /**
     * Get wpdb.
     *
     * @return \wpdb
     */
    private function getWpdb()
    {
        global $wpdb;
        return $wpdb;
    }

}


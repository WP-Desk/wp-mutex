<?php

trait wpdbTrait
{

    private function newWpdb()
    {
        global $wpdb;
        $wpdb = null;
        require_wp_db();
        wp_set_wpdb_vars();
    }

}

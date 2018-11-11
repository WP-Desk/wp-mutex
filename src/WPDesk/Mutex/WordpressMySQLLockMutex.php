<?php

namespace WPDesk\Mutex;


class WordpressMySQLLockMutex
{

    /**
     * Wordpress_Post_Mutex constructor.
     *
     * @param string $lock_name Name of the resource to lock
     * @param int $timeout Lock timeout in seconds
     */
    public function __construct($lock_name = '_mutex', $timeout = 5)
    {
        $wpdb           = $this->getWpdb();
        $this->lockName = $wpdb->_real_escape($lock_name);
        $this->timeout  = intval($timeout);
        $this->lockId   = uniqid('', true);
    }

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


    /**
     * Tries to set lock and returns true if successful
     *
     * @return bool
     */
    public function acquireLock()
    {
        $wpdb = $this->getWpdb();

        $wpdb->get_row($wpdb->prepare('',array($this->lockName,$this->timeout)));

    }

    /**
     * Releases all locks
     *
     * @return void
     */
    public function releaseLock()
    {
        $delimiter = self::LOCK_ID_DELIMITER;
        $sql       = "
DELETE FROM 
	{$this->wpdb->postmeta}
WHERE
	meta_key = '{$this->lockName}' AND 
	post_id = {$this->postId} AND 
	meta_value LIKE '{$this->lockId}{$delimiter}%'
";
        $this->wpdb->query($sql);
    }
}


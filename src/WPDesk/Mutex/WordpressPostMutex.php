<?php

namespace WPDesk\Mutex;

class WordpressPostMutex implements Mutex
{

    use WordpressWpdb;

    const LOCK_ID_DELIMITER = '_';

    /** @var int Post id */
    private $postId;

    /** @var string Name of the resource to lock */
    private $lockName;

    /** @var int Lock timeout in seconds */
    private $timeout;

    /** @var int Wait for lock timeout in seconds */
    private $waitForLockTimeout;

    /** @var string Unique lock id */
    private $lockId;

    /**
     * Wordpress_Post_Mutex constructor.
     *
     * @param int $post_id WordPress post id to serve as mutex data handle
     * @param string $lock_name Name of the resource to lock
     * @param int $timeout Lock timeout in seconds
     */
    public function __construct($post_id, $lock_name = '_mutex', $timeout = 5, $waitForLockTimeout = 5)
    {
        $this->wpdb               = $this->getWpdbFromGlobal();
        $this->postId             = intval($post_id);
        $this->lockName           = $this->wpdb->_real_escape($lock_name);
        $this->timeout            = intval($timeout);
        $this->waitForLockTimeout = intval($waitForLockTimeout);
        $this->lockId             = uniqid('', true);
    }

    /**
     * Factory method
     *
     * @param \WC_Order $order Order for which mutex will be prepared
     * @param string $lock_name Name of the resource to lock
     * @param int $timeout Lock timeout in seconds
     *
     * @return WordpressPostMutex
     */
    public static function fromOrder(\WC_Order $order, $lock_name = '_mutex', $timeout = 5)
    {
        return new self($order->get_id(), $lock_name, $timeout);
    }

    /**
     * Get meta value directly from database
     *
     * @return string|null
     */
    private function getActiveLockId()
    {
        $delimiter = self::LOCK_ID_DELIMITER;

        $sql = "
SELECT 
	meta_id, meta_value
FROM 
	{$this->wpdb->postmeta}
WHERE 
	meta_key = '{$this->lockName}' AND 
	post_id = {$this->postId} AND 
	SUBSTRING(meta_value, POSITION('{$delimiter}' IN meta_value) + 1) * 1 >= UNIX_TIMESTAMP()
ORDER BY
	meta_id ASC";

        $lockId = null;

        $colRowset = $this->wpdb->get_results($sql);

        $record = is_array($colRowset) ? reset($colRowset) : null;;
        if ( ! empty($record)) {
            $lock_with_timestamp = $record->meta_value;
            if ( ! empty($lock_with_timestamp)) {
                $lockId = explode(self::LOCK_ID_DELIMITER, $lock_with_timestamp);
                $lockId = $lockId[0];
                $this->cleanUnusedLocks($record->meta_id);
            }
        }

        return $lockId;
    }


    /**
     * If many locks are set, set only the meaningful
     *
     * @param int $used_lock Used lock meta_id
     */
    private function cleanUnusedLocks($used_lock)
    {
        $delimiter = self::LOCK_ID_DELIMITER;

        $sql = "
DELETE FROM 
	{$this->wpdb->postmeta}
WHERE
	meta_key = '{$this->lockName}' AND 
	post_id = {$this->postId} AND
	meta_value LIKE '{$this->lockId}{$delimiter}%' AND
	meta_id <> $used_lock";
        $this->wpdb->query($sql);
    }

    /**
     * Tries to set lock using atomic operation and return unique lock id
     *
     * @return void
     */
    private function tryLock()
    {
        $lock_id = $this->lockId . self::LOCK_ID_DELIMITER;

        $show_errors    = $this->wpdb->hide_errors();
        $lockTimeoutRow = $this->wpdb->get_row("SHOW VARIABLES LIKE 'innodb_lock_wait_timeout'");
        $this->wpdb->query($this->wpdb->prepare('SET innodb_lock_wait_timeout=%d', array($this->waitForLockTimeout)));

        $sql = "
INSERT INTO
	{$this->wpdb->postmeta}(`meta_key`, `post_id`, `meta_value`)
VALUES(
	'{$this->lockName}',
	{$this->postId},
	CONCAT('{$lock_id}', UNIX_TIMESTAMP() + {$this->timeout})
)";
        $this->wpdb->query($sql);
        $this->wpdb->show_errors($show_errors);
        $this->wpdb->query($this->wpdb->prepare('SET innodb_lock_wait_timeout=%d', array($lockTimeoutRow->Value)));
    }

    /**
     * Check if lock is properly set with given id
     *
     * @return bool
     */
    private function isLockSet()
    {
        return $this->getActiveLockId() === $this->lockId;
    }

    /**
     * Tries to set lock and returns true if successful
     *
     * @return bool
     */
    public function acquireLock()
    {
        $this->tryLock();

        return $this->isLockSet();
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
	meta_value LIKE '{$this->lockId}{$delimiter}%'";
        $this->wpdb->query($sql);
    }
}


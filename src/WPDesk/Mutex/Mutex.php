<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

interface Mutex {
	/** @return bool Whether this owner acquired the lock. */
	public function acquireLock();

	/** @return void */
	public function releaseLock();
}

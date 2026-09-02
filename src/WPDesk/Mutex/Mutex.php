<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

interface Mutex {
	public function acquireLock(): bool;

	public function releaseLock(): void;
}

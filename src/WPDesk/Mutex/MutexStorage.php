<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

interface MutexStorage {
	public function addToStorage( string $name, Mutex $mutex ): void;

	public function getFromStorage( string $name ): ?Mutex;

	public function removeFromStorage( string $name ): void;
}

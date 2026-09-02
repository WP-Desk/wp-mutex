<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

class StaticMutexStorage implements MutexStorage {

	/**
	 * @var Mutex[]
	 */
	public static $mutexStorage = [];

	public function addToStorage( string $name, Mutex $mutex ): void {
		self::$mutexStorage[ $name ] = $mutex;
	}

	public function getFromStorage( string $name ): ?Mutex {
		return self::$mutexStorage[ $name ] ?? null;
	}

	public function removeFromStorage( string $name ): void {
		if ( isset( self::$mutexStorage[ $name ] ) ) {
			unset( self::$mutexStorage[ $name ] );
		} else {
			throw new MutexNotFoundInStorage();
		}
	}
}

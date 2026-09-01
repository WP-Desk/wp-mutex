<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

trait WordpressWpdb {
	private function getWpdbFromGlobal(): \wpdb {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			throw new MutexException( 'WordPress database connection is unavailable.' );
		}

		return $wpdb;
	}
}

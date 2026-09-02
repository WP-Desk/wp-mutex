<?php

declare(strict_types=1);

namespace WPDesk\Mutex\Tests\Integration;

use WPDesk\Mutex\LockKey;
use WPDesk\Mutex\WordpressPostLease as WordpressPostMutex;

final class WordpressPostLeaseTest extends \WP_UnitTestCase {
	/** @var string[] */
	private $resources = [];

	protected function tearDown(): void {
		global $wpdb;

		foreach ( $this->resources as $resource ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $resource ) );
		}

		parent::tearDown();
	}

	public function test_same_post_and_resource_are_exclusive_between_connections(): void {
		$resource  = $this->resource( 'post-exclusive' );
		$ownerDb   = \wp_mutex_test_database_connection();
		$otherDb   = \wp_mutex_test_database_connection();
		$owner     = new WordpressPostMutex( 1, $resource, 30, 0, $ownerDb );
		$contender = new WordpressPostMutex( 1, $resource, 30, 0, $otherDb );

		try {
			self::assertTrue( $owner->acquireLock() );
			self::assertFalse( $contender->acquireLock() );
			$owner->releaseLock();
			self::assertTrue( $contender->acquireLock() );
		} finally {
			$owner->releaseLock();
			$contender->releaseLock();
			$ownerDb->close();
			$otherDb->close();
		}
	}

	public function test_lease_does_not_require_mysql_advisory_locks(): void {
		$resource = $this->resource( 'post-without-advisory-locks' );
		$wpdb     = \wp_mutex_test_database_without_advisory_locks();
		$lease    = new WordpressPostMutex( 1, $resource, 30, 0, $wpdb );

		try {
			self::assertTrue( $lease->acquireLock() );
			$lease->releaseLock();
		} finally {
			$wpdb->close();
		}
	}

	public function test_serialized_owner_can_release_in_another_request(): void {
		$resource = $this->resource( 'post-transfer' );
		$ownerDb  = \wp_mutex_test_database_connection();
		$laterDb  = \wp_mutex_test_database_connection();
		$owner    = new WordpressPostMutex( 1, $resource, 30, 0, $ownerDb );

		try {
			self::assertTrue( $owner->acquireLock() );
			$key     = LockKey::fromString( $owner->getKey()->toString() );
			$resumed = WordpressPostMutex::fromKey( 1, $key, 30, 0, $laterDb );
			$resumed->releaseLock();

			$next = new WordpressPostMutex( 1, $resource, 30, 0, $laterDb );
			self::assertTrue( $next->acquireLock() );
			$next->releaseLock();
		} finally {
			$owner->releaseLock();
			$ownerDb->close();
			$laterDb->close();
		}
	}

	public function test_stale_owner_cannot_release_replacement_lease(): void {
		global $wpdb;

		$resource = $this->resource( 'post-stale' );
		$owner    = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertTrue( $owner->acquireLock() );
		$staleKey = LockKey::fromString( $owner->getKey()->toString() );

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = CONCAT(SUBSTRING_INDEX(meta_value, '|', 1), '|0') WHERE post_id = %d AND meta_key = %s", 1, $resource ) );

		$replacement = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertTrue( $replacement->acquireLock() );

		$stale = WordpressPostMutex::fromKey( 1, $staleKey, 30, 0 );
		$stale->releaseLock();

		$contender = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertFalse( $contender->acquireLock() );
		$replacement->releaseLock();
	}

	public function test_only_owner_can_refresh_lease(): void {
		$resource  = $this->resource( 'post-refresh-owner' );
		$owner     = new WordpressPostMutex( 1, $resource, 2, 0 );
		$contender = new WordpressPostMutex( 1, $resource, 2, 0 );

		self::assertTrue( $owner->acquireLock() );
		self::assertTrue( $owner->refreshLock( 30 ) );
		self::assertFalse( $contender->refreshLock( 30 ) );
		$owner->releaseLock();
	}

	public function test_expired_rows_are_removed_on_next_acquisition(): void {
		global $wpdb;

		$resource = $this->resource( 'post-cleanup' );
		$expired  = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertTrue( $expired->acquireLock() );

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = CONCAT(SUBSTRING_INDEX(meta_value, '|', 1), '|0') WHERE post_id = %d AND meta_key = %s", 1, $resource ) );

		$replacement = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertTrue( $replacement->acquireLock() );
		self::assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", 1, $resource ) ) );
		$replacement->releaseLock();
	}

	public function test_active_version_one_row_blocks_new_owner_until_it_expires(): void {
		global $wpdb;

		$resource = $this->resource( 'post-v1-active' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES (%d, %s, CONCAT(%s, UNIX_TIMESTAMP() + 30))",
				1,
				$resource,
				'legacy-owner_'
			)
		);

		$contender = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertFalse( $contender->acquireLock() );
	}

	public function test_expired_version_one_row_is_cleaned_during_acquisition(): void {
		global $wpdb;

		$resource = $this->resource( 'post-v1-expired' );
		$wpdb->insert(
			$wpdb->postmeta,
			[
				'post_id'    => 1,
				'meta_key'   => $resource,
				'meta_value' => 'legacy-owner_0',
			]
		);

		$replacement = new WordpressPostMutex( 1, $resource, 30, 0 );
		self::assertTrue( $replacement->acquireLock() );
		self::assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", 1, $resource ) ) );
		$replacement->releaseLock();
	}

	public function test_from_order_preserves_existing_factory(): void {
		$resource = $this->resource( 'post-order' );
		$order    = new \WC_Order( 456 );
		$first    = WordpressPostMutex::fromOrder( $order, $resource, 30 );
		$other    = WordpressPostMutex::fromOrder( $order, $resource, 30 );

		try {
			self::assertTrue( $first->acquireLock() );
			self::assertFalse( $other->acquireLock() );
		} finally {
			$first->releaseLock();
			$other->releaseLock();
		}
	}

	private function resource( string $prefix ): string {
		$resource         = '_' . \wp_mutex_test_lock_name( $prefix );
		$this->resources[] = $resource;

		return $resource;
	}
}

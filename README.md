# WP Mutex

`wpdesk/wp-mutex` provides two small concurrency primitives for WordPress:

- a connection-owned MySQL advisory mutex for work completed in one request;
- an expiring postmeta lease whose ownership can be transferred to deferred work.

## Requirements

- PHP 7.4 or later;
- WordPress with a working `$wpdb` connection;
- MySQL or MariaDB with `GET_LOCK()` and `RELEASE_LOCK()` support.

The integration suite runs against WordPress 6.9 and MariaDB 10.11.

## Installation

```bash
composer require wpdesk/wp-mutex:^2.0
```

The library is loaded through Composer autoloading.

## Choosing an implementation

### `WordpressMySQLLockMutex`

Use it when acquisition, protected work, and release happen on the same database connection and in the same PHP request. `GET_LOCK()` is an advisory lock for one name: it does not lock tables or block ordinary queries.

MySQL lock names are global to the database server and limited to 64 bytes. Include a stable application or site namespace when multiple installations may use the same server.

```php
use WPDesk\Mutex\WordpressMySQLLockMutex;

$mutex = new WordpressMySQLLockMutex( 'store-42:order-123:payment', 0 );

if ( ! $mutex->acquireLock() ) {
	return;
}

try {
	// Complete the critical operation.
} finally {
	$mutex->releaseLock();
}
```

The second constructor argument is the maximum number of seconds to wait. Use `0` for non-blocking webhook and callback handling. Repeated acquisition on the same mutex object is idempotent and requires one release. Locks are released automatically by MySQL when their owning connection closes.

### `WordpressPostLease`

Use it for persistent, best-effort deduplication when ownership must survive beyond the acquiring request. The lease is stored in postmeta with an opaque owner token and database-time expiry.

WordPress postmeta does not enforce uniqueness for a post and meta key. Two first acquisitions executing simultaneously may therefore both succeed. Use `WordpressMySQLLockMutex` when strict mutual exclusion is required.

```php
use WPDesk\Mutex\WordpressPostLease;

$lease = new WordpressPostLease(
	123,                 // Storage post/order ID.
	'price-import',      // Resource name.
	300                  // Lease TTL in seconds.
);

if ( ! $lease->acquireLock() ) {
	return;
}

try {
	// Complete work in this request.
} finally {
	$lease->releaseLock();
}
```

Always check the result of `acquireLock()` and release from `finally` for same-request work.

## Passing a lease to deferred work

`LockKey` contains the resource and opaque owner token. It can be serialized into an Action Scheduler argument and reconstructed by the later request.

```php
use WPDesk\Mutex\LockKey;
use WPDesk\Mutex\WordpressPostLease;

$lease = new WordpressPostLease( 1, 'omnibus-batch', DAY_IN_SECONDS );

if ( $lease->acquireLock() ) {
	$queue->add(
		'run_batch',
		[
			'lock_key' => $lease->getKey()->toString(),
		]
	);
}
```

The deferred handler resumes the same owner:

```php
$key   = LockKey::fromString( $lock_key );
$lease = WordpressPostLease::fromKey( 1, $key, DAY_IN_SECONDS );

try {
	// Process deferred work.
	$lease->refreshLock( DAY_IN_SECONDS );
} finally {
	$lease->releaseLock();
}
```

Release and refresh match both the resource and owner token. A delayed stale worker cannot release a newer lease created after its lease expired. `LockKey` is an ownership handle, not an authentication credential; do not expose it publicly or put secrets in the resource name.

## Failures and contention

`acquireLock()` returns `false` when it observes another active owner. Database/query failures throw a `MutexException` subtype:

- `MutexAcquireException` for acquisition failures;
- `MutexReleaseException` for release failures.

Callers should treat storage failures differently from ordinary contention, especially in payment callbacks and migrations.

## WooCommerce order factories

The existing order factories remain available:

```php
$mysql_mutex = WordpressMySQLLockMutex::fromOrder( $order, ':payment', 0 );
$post_lease  = WordpressPostLease::fromOrder( $order, '_background_job', 300 );
```

The global `wpdesk_create_mysql_lock*()`, `wpdesk_acquire_lock()`, and `wpdesk_release_lock()` helpers are deprecated compatibility shims. New code must keep the mutex object explicitly so ownership and `finally` release remain visible.

`WordpressPostMutex` is also deprecated and remains as a compatibility subclass. For source compatibility, the former guard-wait constructor argument is accepted but ignored by both names.

## Development

Start the single test database and run all checks:

```bash
docker compose up -d --wait
composer test
composer lint
composer analyse
composer rector:check
```

The concurrency integration tests open independent `wpdb` connections to the same MariaDB container.

## License

MIT. See [LICENSE.md](LICENSE.md).

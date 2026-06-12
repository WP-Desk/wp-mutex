# WP Mutex

[![pipeline status](https://gitlab.com/wpdesk/wp-mutex/badges/master/pipeline.svg)](https://gitlab.com/wpdesk/wp-mutex/pipelines) 
[![coverage report](https://gitlab.com/wpdesk/wp-mutex/badges/master/coverage.svg)](https://gitlab.com/wpdesk/wp-mutex/commits/master) 
[![Latest Stable Version](https://poser.pugx.org/wpdesk/wp-mutex/v/stable)](https://packagist.org/packages/wpdesk/wp-mutex) 
[![Total Downloads](https://poser.pugx.org/wpdesk/wp-mutex/downloads)](https://packagist.org/packages/wpdesk/wp-mutex) 
[![Latest Unstable Version](https://poser.pugx.org/wpdesk/wp-mutex/v/unstable)](https://packagist.org/packages/wpdesk/wp-mutex) 
[![License](https://poser.pugx.org/wpdesk/wp-mutex/license)](https://packagist.org/packages/wpdesk/wp-mutex)

`wp-mutex` is a robust and lightweight PHP library designed for WordPress plugins to handle concurrency and prevent race conditions. It ensures that critical sections of your code (such as checkout operations, payment webhook processing, or API syncs) are executed by only one process at a time.

---

## Requirements

* PHP 5.6 or later.
* WordPress environment (depends on the `$wpdb` global object and database tables).

## Installation

### Via Composer (Recommended)

Run the following command in your project directory:

```bash
composer require wpdesk/wp-mutex
```

To load the library in your plugin, use the Composer autoloader:

```php
require_once 'vendor/autoload.php';
```

### Manual Installation

If you prefer not to use Composer, you can download the latest release and include the `src/init.php` file manually:

```php
require_once '/path/to/wp-mutex/src/init.php';
```

---

## How It Works

The library offers two main mutex implementations depending on your concurrency requirements:

### 1. MySQL Session Lock (`WordpressMySQLLockMutex`)
* **Under the hood:** Uses MySQL's native `GET_LOCK()` and `RELEASE_LOCK()` functions.
* **Best for:** Server-wide locks on abstract resources or operations.
* **Behavior:** Locks are tied to the active MySQL connection session. If the PHP process crashes or the database connection drops, MySQL automatically releases the lock, preventing deadlocks.

### 2. WordPress Postmeta Lock (`WordpressPostMutex`)
* **Under the hood:** Inserts atomic lock records into the WordPress `wp_postmeta` table.
* **Best for:** Operations associated with a specific WordPress Post ID (e.g., preventing double-processing on a WooCommerce Order).
* **Behavior:** Locks are persistent and exist until they expire (based on the defined timeout) or are explicitly released. This is useful for scenarios where database connections might drop but the resource must remain locked.

---

## Usage Guide

### 1. Using Global Helper Functions

For quick integration, you can use the global procedural helper functions. These use static storage (`StaticMutexStorage`) to track active locks.

```php
$lock_name = 'my_critical_operation';

// Try to acquire the lock (defaults to MySQL lock with a 5-second wait timeout)
if ( wpdesk_acquire_lock( $lock_name, $waitForLockTimeout = 5 ) ) {
    try {
        // Do your concurrency-sensitive tasks here
    } finally {
        // Always release the lock in a finally block to ensure it's freed
        wpdesk_release_lock( $lock_name );
    }
} else {
    // Handle lock acquisition failure
    echo 'Unable to acquire lock!';
}
```

### 2. Object-Oriented Usage (MySQL Lock)

You can instantiate the `WordpressMySQLLockMutex` directly for more fine-grained control:

```php
use WPDesk\Mutex\WordpressMySQLLockMutex;

$mutex = new WordpressMySQLLockMutex( 'my_unique_lock_key', $waitForLockTimeout = 10 );

if ( $mutex->acquireLock() ) {
    try {
        // Critical logic
    } finally {
        $mutex->releaseLock();
    }
}
```

### 3. Object-Oriented Usage (WordPress Postmeta Lock)

Use `WordpressPostMutex` when you need to lock operations related to a specific Post ID:

```php
use WPDesk\Mutex\WordpressPostMutex;

$post_id = 123; // ID of the WordPress post or WooCommerce order
$lock_name = 'payment_processing';
$lock_expiry_timeout = 30; // Lock expires automatically in 30 seconds
$wait_for_lock_timeout = 5; // Wait up to 5 seconds to acquire the lock

$mutex = new WordpressPostMutex( $post_id, $lock_name, $lock_expiry_timeout, $wait_for_lock_timeout );

if ( $mutex->acquireLock() ) {
    try {
        // Process order / post changes securely
    } finally {
        $mutex->releaseLock();
    }
}
```

### 4. Locking WooCommerce Orders

Both mutex implementations support helper factory methods/functions for WooCommerce orders.

#### Using MySQL Lock from WooCommerce Order:
```php
use WPDesk\Mutex\WordpressMySQLLockMutex;

// Using the global helper:
$mutex = wpdesk_create_mysql_lock_from_order( $order, $lockName = '_mutex', $waitForLockTimeout = 5 );

// Or using the class factory directly:
$mutex = WordpressMySQLLockMutex::fromOrder( $order, $lockName = '_mutex', $waitForLockTimeout = 5 );

if ( $mutex->acquireLock() ) {
    try {
        // Safe order processing
    } finally {
        $mutex->releaseLock();
    }
}
```

#### Using Postmeta Lock from WooCommerce Order:
```php
use WPDesk\Mutex\WordpressPostMutex;

// $order is an instance of \WC_Order
$mutex = WordpressPostMutex::fromOrder( $order, $lock_name = '_mutex', $timeout = 5 );

if ( $mutex->acquireLock() ) {
    try {
        // Safe order processing
    } finally {
        $mutex->releaseLock();
    }
}
```

---

## Advanced: Static Storage

When using helper functions (`wpdesk_acquire_lock` / `wpdesk_release_lock`), the library keeps track of active locks in a static storage class: `WPDesk\Mutex\StaticMutexStorage`.

If you try to call `wpdesk_release_lock( $lockName )` for a lock that has not been stored or has already been released, the library will throw a `\WPDesk\Mutex\MutexNotFoundInStorage` exception.

---

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

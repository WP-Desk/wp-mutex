# Changelog

## 2.0.0 - Unreleased

### Changed

- Raised the minimum PHP version from 5.6 to 7.4.
- Added native parameter and return types to the public mutex and storage contracts.
- Deprecated the global helper functions in favor of the object API.
- Removed the legacy manual `src/init.php` loader; Composer autoloading is required.
- Modernized the WordPress/PHPUnit test environment and moved CI to GitHub Actions with MariaDB 10.11.
- Added WP Desk coding standards, PHPStan, and Rector checks.
- Made MySQL mutex acquisition idempotent per object and distinguished contention from database failure.
- Preserved the database connection that owns a MySQL advisory lock through release.
- Added `WordpressPostLease` for persistent, best-effort postmeta deduplication without an advisory-lock dependency.
- Deprecated `WordpressPostMutex`; it remains as a compatibility subclass of `WordpressPostLease`.
- Made postmeta lease expiry use database time and retained compatibility with active version 1 lock rows.
- Fixed helper storage so failed acquisitions are not recorded and repeated helper acquisition does not leak MySQL lock reference counts.

### Added

- `LockKey`, an opaque serializable ownership handle for deferred work.
- `ExpiringMutex` and owner-aware lease refresh.
- Dedicated mutex acquisition and release exceptions.
- Behavioral tests using independent database connections.

### Compatibility

- Existing mutex classes, constructors, WooCommerce order factories, interface methods, and deprecated global helper names remain available.
- `WordpressPostMutex` remains available for backward compatibility; new code should use `WordpressPostLease`.
- Consumers must now handle database failures separately from ordinary contention.

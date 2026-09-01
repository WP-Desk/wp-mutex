<?php

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/vendor/wordpress/' );
define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'wptest' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'wptest' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: 'wptest' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1:33060' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'WP Mutex Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );

define( 'AUTH_KEY', 'test' );
define( 'SECURE_AUTH_KEY', 'test' );
define( 'LOGGED_IN_KEY', 'test' );
define( 'NONCE_KEY', 'test' );
define( 'AUTH_SALT', 'test' );
define( 'SECURE_AUTH_SALT', 'test' );
define( 'LOGGED_IN_SALT', 'test' );
define( 'NONCE_SALT', 'test' );

<?php

declare(strict_types=1);

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

require_once dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit/includes/functions.php';
require_once dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php';
require_once __DIR__ . '/support.php';

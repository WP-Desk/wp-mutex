[![pipeline status](https://gitlab.com/wpdesk/wp-mutex/badges/master/pipeline.svg)](https://gitlab.com/wpdesk/wp-mutex/pipelines) 
[![coverage report](https://gitlab.com/wpdesk/wp-mutex/badges/master/coverage.svg)](https://gitlab.com/wpdesk/wp-mutex/commits/master) 
[![Latest Stable Version](https://poser.pugx.org/wpdesk/wp-mutex/v/stable)](https://packagist.org/packages/wpdesk/wp-mutex) 
[![Total Downloads](https://poser.pugx.org/wpdesk/wp-mutex/downloads)](https://packagist.org/packages/wpdesk/wp-mutex) 
[![Latest Unstable Version](https://poser.pugx.org/wpdesk/wp-mutex/v/unstable)](https://packagist.org/packages/wpdesk/wp-mutex) 
[![License](https://poser.pugx.org/wpdesk/wp-mutex/license)](https://packagist.org/packages/wpdesk/wp-mutex) 


WordPress Library to display notices in admin area.
===================================================

wp-mutex is a simple library for WordPress plugins to help executing critical code in concurrent situations.

## Requirements

PHP 5.5 or later.

## Composer

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require wpdesk/wp-mutex
```

To use the bindings, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once 'vendor/autoload.php';
```

## Manual installation

If you do not wish to use Composer, you can download the latest release. Then, to use the Notices, include the init.php file.

```php
require_once('/path/to/notice/src/init.php');
```

## Getting Started

Simple usage looks like:

```php
$lock_name = 'my_lock_name';
if ( wpdesk_acquire_lock( $lock_name ) ) {
    // do critical stuff
    wpdesk_release_lock( $lock_name );
} else {
    echo 'Unable to acquire lock!';
}
```

## Project documentation

PHPDoc: https://wpdesk.gitlab.io/wp-mutex/index.html 

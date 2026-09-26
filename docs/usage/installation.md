# Installation

Gauss is distributed as a Composer package.

## Install via Composer

```bash
composer require pandu/gauss
```

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$price = Number::of('19.99');
$tax = Number::of('0.10');

$total = $price->mul(Number::of('1')->add($tax));

echo $total->value();
```

## Autoloading

Composer’s autoloader is the normal entry point. Once loaded, you can use Gauss namespaces directly.

## Requirements

- PHP 8.2+
- Composer

## Notes

This documentation does not describe the package development process. It focuses on how users interact with the library after install.

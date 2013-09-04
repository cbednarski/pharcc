# pharcc

A phar compiler library for PHP, based on the phar compiler code in [composer](https://github.com/composer/composer).

## Usage

### Composer

Include `cbednarski/pharcc` in your `composer.json`:

    "require": {
        "cbednarski/pharcc":"dev-master",
    },

### Compiling

I recommend using a `PRS-0` project layout and placing your compiler file in `bin/compile`

```php
#!/usr/bin/env php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$compiler = new cbednarski\pharcc\Compiler('path/to/your/project', 'target.phar');
$compiler->setMain('bin/app');
$compiler->ignore('tests');
$compiler->ignore('doc');
$compiler->compile();
```

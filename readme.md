# pharcc

A phar compiler library for PHP, based on the phar compiler code in [composer](https://github.com/composer/composer).

## Usage

### Composer

Include `cbednarski/pharcc` in your `composer.json`:

    "require": {
        "cbednarski/pharcc":"dev-master",
    },

### Compiling

Create a file called `bin/compile` in your project and add the following contents:

```php
#!/usr/bin/env php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$compiler = new cbednarski\pharcc\Compiler(
    realpath(__DIR__ . '/../'),
    'target.phar' # Change this to whatever you like (has to end in .phar, though)
);
$compiler->setMain('bin/app'); # If you're compiling a CLI tool this file
                               # should hold your main application / executable

$compiler->ignore('tests'); # Paths matching these names will be ignored
$compiler->ignore('doc');

$compiler->compile();
```

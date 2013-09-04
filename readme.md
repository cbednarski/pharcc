# pharcc

A phar compiler library for PHP, based on the phar compiler code in [composer](https://github.com/composer/composer).

## Dependencies

- Install [composer](http://getcomposer.com)
- `$ composer install`

## Usage

$compiler = new Pharcc();
$compiler->setTarget('myphar.php');
$compiler->addDirectory('src/');
$compiler->compile();
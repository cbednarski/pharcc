<?php

use cbednarski\Pharcc\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadConfig()
    {
        $file = __DIR__ . '/../../../src/cbednarski/Pharcc/Resources/pharcc.yml';

        Config::loadFile($file);
    }
}
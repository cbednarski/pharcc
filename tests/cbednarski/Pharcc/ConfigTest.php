<?php

use cbednarski\Pharcc\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testSetName()
    {
        $config = new Config(__DIR__);
        $config->setName('pie');
    }

    public function testConfig()
    {
        $config = new Config(__DIR__);
        $this->assertEquals(__DIR__, $config->getBasePath());

        $name = 'my-app.phar';
        $config->setName($name);
        $this->assertEquals($name, $config->getName());

        $main = 'bin/some_executable';
        $config->setMain($main);
        $this->assertEquals($main, $config->getMain());

        $includes = array('src/', 'vendor/');
        $config->setIncludes($includes);
        $this->assertEquals($includes, $config->getIncludes());

        $excludes = array('*Test.php');
        $config->setExcludes($excludes);
        $this->assertEquals($excludes, $config->getExcludes());
    }

    public function testLoadConfig()
    {
        $file = __DIR__ . '/../../../src/cbednarski/Pharcc/Resources/pharcc.yml';

        $config = Config::loadFile($file);

        $this->assertEquals('bin/app', $config->getMain());
        $this->assertEquals('app.phar', $config->getName());

        $includes = array('src/', 'vendor/');
        $this->assertEquals($includes, $config->getIncludes());

        $excludes = array('*Test.php');
        $this->assertEquals($excludes, $config->getExcludes());
    }
}
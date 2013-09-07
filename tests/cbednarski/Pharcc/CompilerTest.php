<?php

use cbednarski\Pharcc\Compiler;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testStripShebang()
    {
        $sample1 = <<<'HEREDOC'
#!/usr/bin/env php
<?php
require_once(__DIR__ . '/../vendor/autoload.php');
$application = new Symfony\Component\Console\Application();
HEREDOC;

        $sample2 = <<<'HEREDOC'
<?php
require_once(__DIR__ . '/../vendor/autoload.php');
$application = new Symfony\Component\Console\Application();
HEREDOC;

        $this->assertEquals($sample2, Compiler::stripShebang($sample1));
    }
}
<?php

namespace cbednarski\pharcc;

class Compiler
{
    public function compile()
    {
        if(!Phar::canWrite()) {
            throw new \Exception(
                'Unable to compile a phar because of php\'s security settings.'
                . 'phar.readonly must be disabled. Details here:'
                . 'http://php.net/manual/en/phar.configuration.php');
        }


    }
}
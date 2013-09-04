<?php

namespace cbednarski\pharcc;

use Phar;
use PharException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use cbednarski\FileUtils\FileUtils;

/**
 * The Compiler class compiles your library into a phar
 * 
 * This class is based on composer's phar compiler class
 * @link https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 *
 * @author Chris Bednarski <banzaimonkey@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Compiler
{
    protected $phar = null;
    protected $base_dir = null;
    protected $finders = array();
    protected $version = null;
    protected $license = null;

    /** The target name for your phar app */
    protected $target = null;
    /** The main executable for your app, if you have one */
    protected $main = 'vendor/autoload.php';

    protected $default_includes = array(
        'src',
        'lib',
        'vendor',
    );

    protected $default_excludes = array(
        'test',
        'Test',
        'tests',
        'Tests',
        'phpunit',
        'doc',
        'docs',
        'pharcc',
    );

    protected $stub = null;

    /**
     * Returns a boolean indicating whether or not you're allowed to compile a
     * phar, based on the phar.readonly ini setting
     *
     * @link http://php.net/manual/en/phar.configuration.php
     *
     * @return bool true if we can compile
     */
    public static function canCompile()
    {
        if (!Phar::canWrite()) {
            throw new PharException(
                'Unable to compile a phar because of php\'s security settings. '
                . 'phar.readonly must be disabled in php.ini. Details here: '
                . 'http://php.net/manual/en/phar.configuration.php');
        }
    }

    public function __construct($base_dir, $target = 'target.phar')
    {
        self::canCompile();

        $this->base_dir = realpath($base_dir);
        if (!$this->base_dir) {
            throw new RuntimeException('The compiler target directory does not exist');
        }

        $this->target = $target;
        $this->addFinder(self::initializeFinder($this->base_dir));

        $this->phar = new Phar($this->getTargetPath(), 0, $target);
    }

    public static function initializeFinder($base_dir = null)
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs(true);

        if ($base_dir !== null) {
            $finder->in($base_dir);
        }

        return $finder;
    }

    public function setMain($main)
    {
        $this->main = $main;

        return $this;
    }

    public function getMain()
    {
        return $this->main;
    }

    public function getTarget()
    {
        return $this->target();
    }

    public function getTargetPath()
    {
        return $this->base_dir . DIRECTORY_SEPARATOR . $this->target;
    }

    /** @link http://php.net/manual/en/phar.fileformat.stub.php */
    public function setStub($stub)
    {
        $this->stub = $stub;

        return $this;
    }

    private function getStub()
    {
        if ($this->stub === null) {
            $stub = <<<"HEREDOC"
#!/usr/bin/env php
<?php

Phar::mapPhar('%target%');

require 'phar://%target%/%main%';

__HALT_COMPILER();\n
HEREDOC;
            $stub = str_replace('%target%', $this->target, $stub);
            $stub = str_replace('%main%', $this->main, $stub);
        } else {
            $stub = $this->stub;
        }

        return $stub;
    }

    public function addFinder(Finder $finder)
    {
        $this->finders[] = $finder;

        return $this;
    }

    public function getFinders()
    {
        return $this->finders;
    }

    public function compile()
    {
        if (file_exists($this->getTargetPath())) {
            unlink($this->getTargetPath());
        }

        //@TODO add version stuff here

        $this->phar->setSignatureAlgorithm(Phar::SHA1);

        /** Optional, but improves performance for this type of operation.
         *  For an example library, the compile time goes from 154s to 40s
         *  when buffering is used.
         *  @link http://php.net/manual/en/phar.startbuffering.php */
        $this->phar->startBuffering();

        foreach($this->default_excludes as $exclude) {
            $this->exclude("/$exclude/");
        }

        foreach ($this->getFinders() as $finder) {
            foreach($finder as $file) {
                $this->addFile($file);
            }
        }

        $this->phar->setStub($this->getStub());
        $this->phar->stopBuffering();

        unset($this->phar);
    }

    public function addFile($file)
    {
        $content = file_get_contents($file->getPathName());
        $content = self::stripWhitespace($content);
        $content = str_replace('@package_version@', $this->version, $content);

        $this->phar->addFromString($file->getRelativePathname(), $content);
    }

    public function addDirectory($path)
    {
        $this->finders[0]->in($path);
    }

    public function exclude($pattern)
    {
        $this->finders[0]->notPath($pattern);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    public static function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }
}

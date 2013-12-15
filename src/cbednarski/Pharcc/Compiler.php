<?php

namespace cbednarski\Pharcc;

use Phar;
use PharException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use cbednarski\FileUtils\FileUtils;
use cbednarski\Pharcc\Git;

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
    protected $config = null;
    protected $finders = array();
    protected $version = null;
    protected $license = null;
    protected $stub = null;

    public function __construct(Config $config)
    {
        self::canCompile();

        $this->config = $config;

        if (!realpath($this->config->getBasePath())) {
            throw new RuntimeException('The compiler target directory does not exist');
        }

        $this->addFinder(self::initializeFinder($this->config->getBasePath()));

        $this->phar = new Phar($this->getTargetPath(), 0, $this->config->getName());
    }

    public function getMain()
    {
        return $this->config->getMain();
    }

    public function getTarget()
    {
        return $this->config->getName();
    }

    public function getTargetPath()
    {
        return $this->config->getBasePath() . DIRECTORY_SEPARATOR . $this->config->getName();
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

Phar::mapPhar('%name%');

require 'phar://%name%/%main%';

__HALT_COMPILER();\n
HEREDOC;
            $stub = str_replace('%name%', $this->config->getName(), $stub);
            $stub = str_replace('%main%', $this->config->getMain(), $stub);
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

    public function fetchFiles()
    {
        $files = array();
        $paths = array();

        // Add included directories and files to search path
        foreach ($this->config->getIncludes() as $include) {
            if (is_file($include)) {
                $files[] = $include;
            } elseif (is_dir($include)) {
                $files = array_merge($files, FileUtils::listFilesInDir(
                    $this->config->getBasePath() . DIRECTORY_SEPARATOR . $include));
            } else {
                throw new \Exception('You\'ve asked pharcc to include a path that is missing,'
                    . ' unreadable, or is not a file or directory: ' . PHP_EOL . PHP_EOL
                    . '   ' . $include . PHP_EOL . PHP_EOL
                    . 'Check your pharcc.yml or filesystem permissions to fix this.');
            }
        }

        foreach ($files as $file) {
            if (!$this->isExcluded($file)) {
                $relative_path = (FileUtils::pathDiff($this->config->getBasePath(), $file, true));
                $paths[$relative_path] = $file;
            }
        }

        unset($files);
        asort($paths);

        return $paths;
    }

    public function isExcluded($path)
    {
        foreach ($this->config->getExcludes() as $exclude) {
            if (preg_match("@$exclude@", $path) && !preg_match("@.+SimpleTest\.php@", $path)) {
                return true;
            }
        }

        return false;
    }

    public function compile()
    {

        if (file_exists($this->getTargetPath())) {
            if (is_writable($this->getTargetPath())) {
                unlink($this->getTargetPath());
            } else {
                throw new Exception('Unable to overwrite target file.');
            }
        }

        //@TODO add version stuff here

        // Optional, but improves performance for this type of operation.
        // For an example library, the compile time goes from 154s to 40s
        // when buffering is used.
        // @link http://php.net/manual/en/phar.startbuffering.php
        $this->phar->startBuffering();
        $this->phar->setStub($this->getStub());

        $this->phar->buildFromIterator(new \ArrayIterator($this->fetchFiles()));
        $this->addMain($this->config->getBasePath() . DIRECTORY_SEPARATOR . $this->config->getMain());

        $this->phar->setSignatureAlgorithm(Phar::SHA1);
        $this->phar->stopBuffering();

        unset($this->phar);

        if (is_writable($this->getTargetPath())) {
            chmod($this->getTargetPath(), 0755);
        }
    }

    public function addFile($file)
    {
        $content = file_get_contents($file->getPathName());
        $content = self::stripWhitespace(self::stripShebang($content));
        $content = str_replace('@package_version@', $this->version, $content);

        $this->phar->addFromString($file->getRelativePathname(), $content);
    }

    public function addMain($file)
    {
        $content = file_get_contents($file);
        $content = self::stripWhitespace(self::stripShebang($content));

        $content = preg_replace(
            "/(.+new[\w \\\\]+Application\\('\\w+',).+?(\\);.+)/s",
            '$1\'' . Git::getVersion($this->config->getBasePath()) . '\'$2',
            $content
        );

        $this->phar->addFromString(FileUtils::pathDiff($this->config->getBasePath(), $file, true), $content);
    }

    public function addDirectory($path)
    {
        $this->finders[0]->in($path);
    }

    public function exclude($pattern)
    {
        $this->finders[0]->notPath($pattern);
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

    /**
     * Returns a boolean indicating whether or not you're allowed to compile a
     * phar, based on the phar.readonly ini setting
     *
     * @link http://php.net/manual/en/phar.configuration.php
     *
     * @throws \PharException
     * @return bool           true if we can compile
     */
    public static function canCompile()
    {
        if (!Phar::canWrite()) {
            throw new PharException(
                'Unable to compile a phar because of php\'s security settings. '
                . 'phar.readonly must be disabled in php.ini. ' . PHP_EOL . PHP_EOL
                . 'You will need to edit ' . php_ini_loaded_file() . ' and add or set'
                . PHP_EOL . PHP_EOL . "    phar.readonly = Off" . PHP_EOL . PHP_EOL
                . 'to continue. Details here: http://php.net/manual/en/phar.configuration.php');
        }
    }

    public static function stripShebang($source)
    {
        $stripped = preg_replace('@#!/.+\n@', '', $source);

        return $stripped ? $stripped : $source;
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

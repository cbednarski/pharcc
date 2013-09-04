<?php

namespace cbednarski\pharcc;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * The Compiler class compiles your library into a phar
 *
 * @author Chris Bednarski <banzaimonkey@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Compiler
{
    protected $finders;
    protected $version;

    protected $target = 'pharcc-target.phar';
    protected $main = ''

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
    )

    protected $stub = <<<"HEREDOC"
#!/usr/bin/env php
<?php

Phar::mapPhar('$target');

require 'phar://$target/$main';

__HALT_COMPILER();
HEREDOC;

    public function __construct()
    {

    }

    /** @link http://php.net/manual/en/phar.fileformat.stub.php */
    public function setStub($stub)
    {
        $this->stub = $stub;
    }

    private function getStub()
    {
        return $this->stub;
    }

    public function addFinder(Finder $finder)
    {
        $this->finders[] = $finder;
    }

    public function getFinders()
    {
        return $this->finders;
    }

    public function compile()
    {
        if(!Phar::canWrite()) {
            throw new \RuntimeException(
                'Unable to compile a phar because of php\'s security settings.'
                . 'phar.readonly must be disabled. Details here:'
                . 'http://php.net/manual/en/phar.configuration.php');
        }

        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        //@TODO add version stuff here

        $phar = new \Phar($pharFile, 0, $this->target);
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        //@TODO remove composer-specific stuff
        //@TODO generalize

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->in(__DIR__.'/..')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/Autoload/ClassLoader.php'), false);

        $finder = new Finder();
        $finder->files()
            ->name('*.json')
            ->in(__DIR__ . '/../../res')
        ;
        foreach ($finder as $file) {
            $this->addFile($phar, $file, false);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_real.php'));
        if (file_exists(__DIR__.'/../../vendor/composer/include_paths.php')) {
            $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/include_paths.php'));
        }
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/ClassLoader.php'));
        $this->addComposerBin($phar);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        // disabled for interoperability with systems without gzip ext
        // $phar->compressFiles(\Phar::GZ);

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../LICENSE'), false);

        unset($phar);
    }

    private function addFile($phar, $file, $strip = true)
    {
        $path = str_replace(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n".$content."\n";
        }

        $content = str_replace('@package_version@', $this->version, $content);

        $phar->addFromString($path, $content);
    }

    public function addBin($phar)
    {
        $content = file_get_contents(__DIR__.'/../../bin/composer');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/composer', $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
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

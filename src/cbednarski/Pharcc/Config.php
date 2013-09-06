<?php

namespace cbednarski\Pharcc;

use Symfony\Component\Yaml\Yaml;

class Config
{
    protected $base_path;
    protected $ignores;
    protected $includes;
    protected $main;
    protected $name;

    /**
     * Load configuration data from the specified file
     *
     * @param  string            $path Read this config file
     * @return self
     * @throws \RuntimeException
     */
    public static function loadFile($path)
    {
        if (!realpath($path)) {
            throw new \RuntimeException('Unable to load configuration file from ' . $path);
        }

        $data = Yaml::parse($path);

        return new static(pathinfo($path, PATHINFO_DIRNAME), $data);
    }

    public function __construct($base_path, $data = array())
    {
        $this->base_path = $base_path;
        $this->data = $data;

        var_dump($data);
    }

    public function getBasePath()
    {
        return $this->base_path;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
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

    public function setIncludes($includes)
    {
        $this->includes = $includes;

        return $this;
    }

    public function addInclude($include)
    {
        $this->includes[] = $include;

        return $this;
    }

    public function getIncludes()
    {
        return $this->includes;
    }

    public function setIgnores($ignores)
    {
        $this->ignores = $ignores;

        return $this;
    }

    public function addIgnore($ignore)
    {
        $this->ignore = $ignore;

        return $this;
    }

    public function getIgnores()
    {
        return $this->ignores;
    }    

}
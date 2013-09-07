<?php

namespace cbednarski\Pharcc;

use Symfony\Component\Yaml\Yaml;

class Config
{
    protected $base_path;
    protected $excludes;
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

        if ($data) {
            $this->setName($data['name']);
            $this->setMain($data['main']);
            $this->setIncludes($data['include']);
            $this->setExcludes($data['exclude']);
        }
    }

    public function getBasePath()
    {
        return $this->base_path;
    }

    public function setName($name)
    {
        if (substr($name, -5) !== '.phar') {
            throw new \RuntimeException('Name must end in .phar');
        }

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

    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;

        return $this;
    }

    public function addExclude($exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    public function getExcludes()
    {
        return $this->excludes;
    }

}

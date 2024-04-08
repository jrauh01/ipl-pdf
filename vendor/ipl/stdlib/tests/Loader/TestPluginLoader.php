<?php

namespace ipl\Tests\Stdlib\Loader;

use ipl\Stdlib\Contract\PluginLoader;

/**
 * Plugin loader for tests
 */
class TestPluginLoader implements PluginLoader
{
    protected $canLoad;

    public function __construct($canLoad = true)
    {
        $this->canLoad = $canLoad;
    }

    public function load($name)
    {
        if (! $this->canLoad) {
            return false;
        }

        return $name;
    }
}

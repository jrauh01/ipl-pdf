<?php

namespace ipl\Tests\Stdlib\Loader;

use ipl\Stdlib\Loader\AutoloadingPluginLoader;
use ipl\Tests\Stdlib\TestCase;

class AutoloadingPluginLoaderTest extends TestCase
{
    public function testLoadReturnsFalseIfPluginDoesNotExist()
    {
        $loader = new AutoloadingPluginLoader(__NAMESPACE__);

        $this->assertFalse($loader->load('none'));
    }

    public function testLoadReturnsFullyQualifiedClassNameIfPluginExists()
    {
        $loader = new AutoloadingPluginLoader(__NAMESPACE__);

        $this->assertSame(TestPlugin::class, $loader->load('TestPlugin'));
    }

    public function testLoadReturnsFullyQualifiedClassNameWithPostfixIfPluginExistsAndPostfixIsSet()
    {
        $loader = new AutoloadingPluginLoader(__NAMESPACE__, 'Plugin');

        $this->assertSame(TestPlugin::class, $loader->load('Test'));
    }
}

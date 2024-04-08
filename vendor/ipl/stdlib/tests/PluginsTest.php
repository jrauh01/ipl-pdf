<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Loader\AutoloadingPluginLoader;
use ipl\Stdlib\Plugins;
use ipl\Tests\Stdlib\Loader\TestPlugin;
use ipl\Tests\Stdlib\Loader\TestPluginLoader;

class PluginsTest extends TestCase
{
    public function testHasPluginLoaderReturnsFalseIfNoPluginLoaderIsSet()
    {
        $this->assertFalse($this->getPluginsMock()->hasPluginLoader('test'));
    }

    public function testHasPluginLoaderReturnsTrueIfAPluginPloaderIsSet()
    {
        $plugins = $this->getPluginsMock();

        $plugins->addPluginLoader('test', new TestPluginLoader());

        $this->assertTrue($plugins->hasPluginLoader('test'));
    }

    public function testWantPluginLoaderDefaultsToCreateAnInstanceOfAutoloadingPluginLoader()
    {
        $plugins = $this->getPluginsMock();

        $this->assertInstanceOf(AutoloadingPluginLoader::class, $plugins::wantPluginLoader('test'));
    }

    public function testWantPluginLoaderReturnsThePassedLoaderIfItsAlreadyAnInstanceOfPluginLoader()
    {
        $loader = new TestPluginLoader();
        $plugins = $this->getPluginsMock();

        $this->assertSame($loader, $plugins::wantPluginLoader($loader));
    }

    public function testLoadPluginReturnsFalseIfNoPluginLoaderIsSet()
    {
        $this->assertFalse($this->getPluginsMock()->loadPlugin('test', 'plugin'));
    }

    public function testLoadPluginUsesTheRegisteredPluginLoader()
    {
        $plugins = $this->getPluginsMock();

        $plugins->addPluginLoader('test', new TestPluginLoader());

        $this->assertSame('plugin', $plugins->loadPlugin('test', 'plugin'));
    }

    public function testLoadPluginUsesRegisteredPluginLoaders()
    {
        $plugins = $this->getPluginsMock();

        $plugins->addPluginLoader('test', 'ipl\\Tests\\Stdlib\\Loader', 'Plugin');
        $plugins->addPluginLoader('test', new TestPluginLoader(false));

        $this->assertSame(TestPlugin::class, $plugins->loadPlugin('test', 'Test'));
    }

    /**
     * @return Plugins|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPluginsMock()
    {
        return $this->getMockForTrait(Plugins::class);
    }
}

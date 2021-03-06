<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Plugin;

use Piwik\Cache\PersistentCache;
use Piwik\Db;
use Piwik\Plugin;
use Piwik\Settings\Storage;
use Piwik\Tests\Integration\Settings\IntegrationTestCase;

/**
 * @group Plugin
 * @group PluginManager
 */
class ManagerTest extends IntegrationTestCase
{
    /**
     * @var Plugin\Manager
     */
    private $manager;

    public function setUp()
    {
        parent::setUp();
        $this->manager = Plugin\Manager::getInstance();
    }

    public function test_loadTrackerPlugins_shouldDetectTrackerPlugins()
    {
        $this->assertGreaterThan(50, count($this->manager->getLoadedPlugins())); // make sure all plugins are loaded

        $pluginsToLoad = $this->manager->loadTrackerPlugins();

        $this->assertOnlyTrackerPluginsAreLoaded($pluginsToLoad);
    }

    public function test_loadTrackerPlugins_shouldCacheListOfPlugins()
    {
        $cache = $this->getCacheForTrackerPlugins();
        $this->assertFalse($cache->has());

        $pluginsToLoad = $this->manager->loadTrackerPlugins();

        $this->assertTrue($cache->has());
        $this->assertEquals($pluginsToLoad, $cache->get());
    }

    public function test_loadTrackerPlugins_shouldBeAbleToLoadPluginsCorrectWhenItIsCached()
    {
        $pluginsToLoad = array('CoreHome', 'UserSettings', 'Login', 'CoreAdminHome');
        $this->getCacheForTrackerPlugins()->set($pluginsToLoad);

        $pluginsToLoad = $this->manager->loadTrackerPlugins();

        $this->assertCount(4, $this->manager->getLoadedPlugins());
        $this->assertEquals($pluginsToLoad, array_keys($this->manager->getLoadedPlugins()));
    }

    public function test_loadTrackerPlugins_shouldUnloadAllPlugins_IfThereAreNoneToLoad()
    {
        $pluginsToLoad = array();
        $this->getCacheForTrackerPlugins()->set($pluginsToLoad);

        $pluginsToLoad = $this->manager->loadTrackerPlugins();

        $this->assertEquals(array(), $pluginsToLoad);
        $this->assertEquals(array(), $this->manager->getLoadedPlugins());
    }

    private function getCacheForTrackerPlugins()
    {
        return new PersistentCache('PluginsTracker');
    }

    private function assertOnlyTrackerPluginsAreLoaded($expectedPluginNamesLoaded)
    {
        // should currently load between 10 and 25 plugins
        $this->assertLessThan(25, count($this->manager->getLoadedPlugins()));
        $this->assertGreaterThan(10, count($this->manager->getLoadedPlugins()));

        // we need to make sure it actually only loaded the correct ones
        $this->assertEquals($expectedPluginNamesLoaded, array_keys($this->manager->getLoadedPlugins()));
    }
}

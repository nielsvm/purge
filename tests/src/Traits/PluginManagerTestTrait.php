<?php

namespace Drupal\Tests\purge\Traits;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Testing helpers DIC plugin manager derivatives.
 *
 * @see \Drupal\Tests\purge\Kernel\KernelPluginManagerTestBase
 */
trait PluginManagerTestTrait {

  /**
   * The name of the service as defined in services.yml.
   *
   * @var string
   */
  protected $pluginManagerClass = '';

  /**
   * Instance of the service being tested, instantiated by the container.
   *
   * @var null|\Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Set up the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->pluginManager = new $this->pluginManagerClass(
      $this->container->get('container.namespaces'),
      $this->container->get('cache.discovery'),
      $this->container->get('module_handler')
    );
  }

  /**
   * Test if the plugin manager complies to the basic requirements.
   */
  public function testCodeContract(): void {
    $this->assertTrue($this->pluginManager instanceof $this->pluginManagerClass);
    $this->assertTrue($this->pluginManager instanceof PluginManagerInterface);
    $this->assertTrue($this->pluginManager instanceof DefaultPluginManager);
    $this->assertTrue($this->pluginManager instanceof CachedDiscoveryInterface);
  }

}

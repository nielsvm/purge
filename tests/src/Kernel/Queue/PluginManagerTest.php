<?php

namespace Drupal\Tests\purge\Kernel\Queue;

use Drupal\purge\Plugin\Purge\Queue\PluginManager;
use Drupal\Tests\purge\Kernel\KernelPluginManagerTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\PluginManager.
 *
 * @group purge
 */
class PluginManagerTest extends KernelPluginManagerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginManagerClass = PluginManager::class;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queue_test'];

  /**
   * All metadata from \Drupal\purge\Annotation\PurgeQueue.
   *
   * @var string[]
   */
  protected $annotationFields = [
    'provider',
    'class',
    'id',
    'label',
    'description',
  ];

  /**
   * All bundled plugins in purge core, including in the test module.
   *
   * @var string[]
   */
  protected $plugins = [
    'file',
    'database',
    'memory',
    'null',
    'a',
    'b',
    'c',
  ];

  /**
   * Test the plugins we expect to be available.
   */
  public function testDefinitions(): void {
    $definitions = $this->pluginManager->getDefinitions();
    foreach ($this->plugins as $plugin_id) {
      $this->assertTrue(isset($definitions[$plugin_id]));
    }
    foreach ($definitions as $plugin_id => $md) {
      $this->assertTrue(in_array($plugin_id, $this->plugins));
    }
    foreach ($definitions as $plugin_id => $md) {
      foreach ($md as $field => $value) {
        $this->assertTrue(in_array($field, $this->annotationFields));
      }
      foreach ($this->annotationFields as $field) {
        $this->assertTrue(isset($md[$field]));
      }
    }
  }

}

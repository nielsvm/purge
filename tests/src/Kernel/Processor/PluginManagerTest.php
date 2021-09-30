<?php

namespace Drupal\Tests\purge\Kernel\Processor;

use Drupal\purge\Plugin\Purge\Processor\PluginManager;
use Drupal\Tests\purge\Kernel\KernelPluginManagerTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Processor\PluginManager.
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
  protected static $modules = ['purge_processor_test'];

  /**
   * All metadata from \Drupal\purge\Annotation\PurgeProcessor.
   *
   * @var string[]
   */
  protected $annotationFields = [
    'provider',
    'class',
    'id',
    'label',
    'description',
    'enable_by_default',
    'configform',
  ];

  /**
   * All bundled plugins in purge core, including in the test module.
   *
   * @var string[]
   */
  protected $plugins = [
    'a',
    'b',
    'c',
    'withform',
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

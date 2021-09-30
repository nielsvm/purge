<?php

namespace Drupal\Tests\purge\Kernel\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginManager;
use Drupal\Tests\purge\Kernel\KernelPluginManagerTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginManager.
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
  protected static $modules = [
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * All metadata from \Drupal\purge\Annotation\PurgeDiagnosticCheck.
   *
   * @var string[]
   */
  protected $annotationFields = [
    'provider',
    'class',
    'id',
    'title',
    'description',
    'dependent_purger_plugins',
    'dependent_queue_plugins',
  ];

  /**
   * All bundled plugins in purge core, including in the test module.
   *
   * @var string[]
   */
  protected $plugins = [
    'capacity',
    'maxage',
    'memoryqueuewarning',
    'processorsavailable',
    'purgersavailable',
    'queuersavailable',
    'alwayswarning',
    'alwayserror',
    'alwaysinfo',
    'alwaysok',
    'purgerwarning',
    'queuewarning',
    'queue_size',
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

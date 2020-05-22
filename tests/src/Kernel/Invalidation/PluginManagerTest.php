<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\PluginManager;
use Drupal\Tests\purge\Kernel\KernelPluginManagerTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\PluginManager.
 *
 * @group purge
 */
class PluginManagerTest extends KernelPluginManagerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginManagerClass = PluginManager::class;

  /**
   * All metadata from \Drupal\purge\Annotation\PurgeInvalidation.
   *
   * @var string[]
   */
  protected $annotationFields = [
    'provider',
    'class',
    'id',
    'label',
    'description',
    'examples',
    'expression_required',
    'expression_can_be_empty',
    'expression_must_be_string',
  ];

  /**
   * All bundled plugins.
   *
   * @var string[]
   */
  protected $plugins = [
    'domain',
    'everything',
    'path',
    'regex',
    'tag',
    'url',
    'wildcardpath',
    'wildcardurl',
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

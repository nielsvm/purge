<?php

/**
 * @file
 * Contains \Drupal\purge\RuntimeTest\RuntimeTestManager.
 */

namespace Drupal\purge\RuntimeTest;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * The runtime test plugin manager.
 */
class RuntimeTestManager extends DefaultPluginManager {

  /**
   * Constructs the RuntimeTestManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/PurgeRuntimeTest',
      $namespaces,
      $module_handler,
      'Drupal\purge\RuntimeTest\RuntimeTestInterface',
      'Drupal\purge\Annotation\PurgeRuntimeTest');
    $this->setCacheBackend($cache_backend, 'purge_runtimetest_plugins');
  }
}

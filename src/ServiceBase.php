<?php

/**
 * @file
 * Contains \Drupal\purge\ServiceBase.
 */

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\purge\ServiceInterface;

/**
 * Provides a generic service for all DIC-registered service classes by Purge.
 */
abstract class ServiceBase extends ServiceProviderBase implements ServiceInterface {

  /**
   * The plugin manager for the given service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (!$simple) {
      return $this->pluginManager->getDefinitions();
    }
    $plugins = array();
    foreach ($this->pluginManager->getDefinitions() as $plugin) {
      $plugins[$plugin['id']] = $plugin['label'];
    }
    return $plugins;
  }
}
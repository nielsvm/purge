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
   * The list of all available plugins and their definitions.
   *
   * @var array
   */
  protected $plugins = [];

  /**
   * The list of all enabled plugins and their definitions.
   *
   * @var array
   */
  protected $plugins_enabled = [];

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    if (empty($this->plugins)) {
      $this->plugins = $this->pluginManager->getDefinitions();
    }
    return $this->plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (empty($this->plugins_enabled)) {
      $this->plugins_enabled = array_keys($this->getPlugins());
    }
    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    $this->plugins = [];
    $this->plugins_enabled = [];
  }

}

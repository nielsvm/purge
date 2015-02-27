<?php

/**
 * @file
 * Contains \Drupal\purge\ModifiableServiceInterface.
 */

namespace Drupal\purge;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\ServiceInterface;

/**
 * Describes a container service of which its back-end plugins can be changed.
 */
interface ModifiableServiceInterface {

  /**
   * Configure the plugins to be used by the service and reload the service.
   *
   * @param string[] $plugin_ids
   *   Array with the plugin ids to be enabled in its value.
   *
   * @return void
   */
  public function setPluginsEnabled(array $plugin_ids);

  /**
   * Configure the plugins to be used by the service when it isn't started yet.
   *
   * @param string[] $plugin_ids
   *   Array with the plugin ids to be enabled in its value.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The plugin manager for the service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *
   * @return void
   */
  public static function setPluginsStatic(array $plugin_ids, PluginManagerInterface $plugin_manager = NULL, ConfigFactoryInterface $config_factory = NULL);

}

<?php

/**
 * @file
 * Contains \Drupal\purge\ServiceInterface.
 */

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Describes a generic service for all DIC-registered service classes by Purge.
 */
interface ServiceInterface extends ServiceProviderInterface, ServiceModifierInterface {

  /**
   * Retrieve a list of all available plugins providing the service.
   *
   * @param bool $simple
   *   When provided TRUE the returned values should provide plugin name strings.
   *
   * @return array
   *   Associative array with the machine names as key and the additional plugin
   *   metadata as another associative array in the value.
   */
  public function getPlugins($simple = FALSE);

  /**
   * Retrieve all the plugin ID's of the plugins the service actually uses.
   *
   * @return array
   *   Non-associative array with the plugin ID's of the loaded/used plugins.
   */
  public function getPluginsLoaded();
}

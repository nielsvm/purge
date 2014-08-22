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
   *   When $simple is TRUE the returned array will use user interface readable
   *   strings as element values instead of plugin definition arrays.
   *
   * @return array
   *   Associative array with the plugin ID's as key and the additional plugin
   *   metadata as another associative array in the value.
   */
  public function getPlugins($simple = FALSE);

  /**
   * Retrieve a list of plugin ID's that are enabled.
   *
   * @return array
   *   Non-associative array with the plugin ID's of the enabled plugins.
   */
  public function getPluginsEnabled();

  /**
   * Reload the service and reinstantiate all enabled plugins.
   *
   * @warning
   *   Reloading a service implies that all cached data will be reset and that
   *   plugins get reinstantiated during the current request, which should
   *   normally not be used. This method is specifically used in unit tests. 
   */
  public function reload();
}

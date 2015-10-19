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
   * @throws \LogicException
   *   Thrown when the parameter $plugin_ids doesn't make any sense.
   *
   * @return void
   */
  public function setPluginsEnabled(array $plugin_ids);

}

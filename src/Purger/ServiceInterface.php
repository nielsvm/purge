<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\ServiceInterface
 */

namespace Drupal\purge\Purger;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;
use Drupal\purge\ModifiableServiceInterface;
use Drupal\purge\Purger\PurgerLookalikeInterface;

/**
 * Describes a service that distributes access to one or more purgers.
 */
interface ServiceInterface extends PurgeServiceInterface, ModifiableServiceInterface, PurgerLookalikeInterface {

  /**
   * Retrieve the plugin_ids of purgers that can be enabled.
   *
   * This method takes into account that purger plugins that are not
   * multi-instantiable, can only be loaded once and are no longer available if
   * they are already available. Plugins that are multi-instantiable, will
   * always be listed.
   *
   * @return string[]
   *   Array with the plugin_ids of the plugins that can be enabled.
   */
  public function getPluginsAvailable();

  /**
   * Disable the given purger plugin instances.
   *
   * Just before, it calls \Drupal\purge\Purger\PluginInterface::delete()
   * on the purger(s) being disabled allowing the plugin to clean up.
   *
   * @param string[] $ids
   *   Non-associative array of instance ids that are about to be uninstalled.
   *
   * @throws \LogicException
   *   Thrown when any of the ids given isn't valid or when $ids is empty.
   *
   * @see \Drupal\purge\Purger\PluginInterface::delete()
   *
   * @return void
   */
  public function deletePluginsEnabled(array $ids);

  /**
   * Create a unique instance ID for new purger instances.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. This helper creates a unique,
   * random string, 10 characters long.
   *
   * @see \Drupal\purge\Purger\PluginInterface::getId()
   *
   * @return string
   */
  public function createId();

}

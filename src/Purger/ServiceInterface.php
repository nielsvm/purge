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

}

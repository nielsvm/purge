<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\SettingsInterface.
 */

namespace Drupal\purge\Purger;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for purgers storing settings through config entities.
 */
interface SettingsInterface extends ConfigEntityInterface {

  /**
   * Either loads or creates the settings entity depending its existence.
   *
   * @param string $id
   *   Unique instance ID of the purger.
   *
   * @return \Drupal\purge\Purger\SettingsInterface.
   */
  public static function load($id);

}

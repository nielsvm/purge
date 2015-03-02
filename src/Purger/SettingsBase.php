<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\SettingsBase.
 */

namespace Drupal\purge\Purger;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\purge\Purger\SettingsInterface;

/**
 * Provides an interface for purgers storing settings through config entities.
 */
abstract class SettingsBase extends ConfigEntityBase implements SettingsInterface {

  /**
   * Unique purger instance ID.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    if (!($settings = parent::load($id))) {
      $settings = self::create(['id' => $id]);
    }
    return $settings;
  }

}

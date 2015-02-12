<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PluginInterface.
 */

namespace Drupal\purge\Purger;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge\Purger\PurgerLookalikeInterface;

/**
 * Describes a purger plugin: the executor that takes purgeable instruction
 * objects and wipes the described things from an external cache system.
 */
interface PluginInterface extends ContainerFactoryPluginInterface, PurgerLookalikeInterface {

}

<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PluginInterface.
 */

namespace Drupal\purge\Purger;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge\Purger\PurgerLookalikeInterface;

/**
 * Describes a purger - the cache invalidation executor.
 */
interface PluginInterface extends ContainerFactoryPluginInterface, PurgerLookalikeInterface {

  /**
   * Retrieve the unique instance ID for this purger.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. Plugins with 'multi_instance' set
   * to TRUE in their annotations, are likely to require the use of this method
   * to differentiate their purger instance (e.g. through configuration).
   *
   * @return string
   *   The unique identifier for this purger instance.
   */
  public function getId();

}

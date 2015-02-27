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
interface PluginInterface extends ContainerFactoryPluginInterface, PurgerLookalikeInterface {}

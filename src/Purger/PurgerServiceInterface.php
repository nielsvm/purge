<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PurgerServiceInterface
 */

namespace Drupal\purge\Purger;

use Drupal\purge\ServiceInterface;
use Drupal\purge\Purger\PurgerInterface;

/**
 * Describes a service that allows transparent access to one or more purgers.
 */
interface PurgerServiceInterface extends ServiceInterface, PurgerInterface {
}

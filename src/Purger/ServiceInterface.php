<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\ServiceInterface
 */

namespace Drupal\purge\Purger;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;
use Drupal\purge\Purger\PurgerLookalikeInterface;

/**
 * Describes a service that distributes access to one or more purgers.
 */
interface ServiceInterface extends PurgeServiceInterface, PurgerLookalikeInterface {}

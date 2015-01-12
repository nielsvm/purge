<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\ServiceInterface
 */

namespace Drupal\purge\Purger;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;
use Drupal\purge\Purger\PluginInterface;

/**
 * Describes a service that allows transparent access to one or more purgers.
 */
interface ServiceInterface extends PurgeServiceInterface, PluginInterface {
}

<?php

/**
 * @file
 * Contains \Drupal\purge\ServiceInterface.
 */

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Describes a generic service for all DIC-registered service classes by Purge.
 */
interface ServiceInterface extends ServiceProviderInterface, ServiceModifierInterface {
}

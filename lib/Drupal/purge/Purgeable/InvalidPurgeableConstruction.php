<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\InvalidPurgeableConstruction.
 */

namespace Drupal\purge\Purgeable;

/**
 * Exception thrown when the factory did not receive a valid $configuration or
 * $option array with one representation string in it.
 */
class InvalidPurgeableConstruction extends \Exception {}
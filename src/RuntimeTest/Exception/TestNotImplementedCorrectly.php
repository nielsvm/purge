<?php

/**
 * @file
 * Contains \Drupal\purge\RuntimeTest\Exception\TestNotImplementedCorrectly.
 */

namespace Drupal\purge\RuntimeTest\Exception;

/**
 * Thrown when \Drupal\purge\RuntimeTest\RuntimeTestInterface::run is not
 * returning a severity integer as described in the API.
 */
class TestNotImplementedCorrectly extends \Exception {}

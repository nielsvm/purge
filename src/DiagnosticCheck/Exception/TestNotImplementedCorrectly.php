<?php

/**
 * @file
 * Contains \Drupal\purge\DiagnosticCheck\Exception\TestNotImplementedCorrectly.
 */

namespace Drupal\purge\DiagnosticCheck\Exception;

/**
 * Thrown when \Drupal\purge\DiagnosticCheck\PluginInterface::run is not
 * returning a severity integer as described in the API.
 */
class TestNotImplementedCorrectly extends \Exception {}

<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\DiagnosticCheck\Exception\CheckNotImplementedCorrectly.
 */

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck\Exception;

/**
 * Thrown when \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginInterface::run is not
 * returning a severity integer as mandated by the API.
 */
class CheckNotImplementedCorrectly extends \Exception {}

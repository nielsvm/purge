<?php

/**
 * @file
 * Contains \Drupal\purge\DiagnosticCheck\Exception\CheckNotImplementedCorrectly.
 */

namespace Drupal\purge\DiagnosticCheck\Exception;

/**
 * Thrown when \Drupal\purge\DiagnosticCheck\PluginInterface::run is not
 * returning a severity integer as mandated by the API.
 */
class CheckNotImplementedCorrectly extends \Exception {}

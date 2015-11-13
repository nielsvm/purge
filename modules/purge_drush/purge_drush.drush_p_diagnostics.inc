<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;

/**
 * Generate a diagnostic self-service report.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface $purgeDiagnostics
 *   The purge diagnostics service.
 *
 * @return array
 *   Drush render/format array.
 */
function drush_purge_drush_p_diagnostics($format, DiagnosticsServiceInterface $purgeDiagnostics) {
  $args = func_get_args();
  foreach ($args as $i => $arg) {
    if (is_object($arg)) {
      var_dump(get_class($arg));
    }
    else {
      var_dump($arg);
    }
  }
  return [];
}

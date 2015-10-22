<?php

/**
 * @file
 * Contains \Drupal\purge_check_error_test\Plugin\PurgeDiagnosticCheck\AlwaysErrorDiagnosticCheck.
 */

namespace Drupal\purge_check_error_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Checks if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "alwayserror",
 *   title = @Translation("Always an error"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysErrorDiagnosticCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = $this->t("This is an error for testing.");
    return SELF::SEVERITY_ERROR;
  }
}

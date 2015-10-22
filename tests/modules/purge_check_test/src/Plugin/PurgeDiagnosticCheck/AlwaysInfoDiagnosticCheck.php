<?php

/**
 * @file
 * Contains \Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck\AlwaysInfoDiagnosticCheck.
 */

namespace Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Checks if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "alwaysinfo",
 *   title = @Translation("Always informational"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysInfoDiagnosticCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = $this->t("This is info for testing.");
    return SELF::SEVERITY_INFO;
  }

}

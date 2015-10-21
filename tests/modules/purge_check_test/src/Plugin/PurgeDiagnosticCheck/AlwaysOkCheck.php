<?php

/**
 * @file
 * Contains \Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck\AlwaysOkCheck.
 */

namespace Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Checks if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "alwaysok",
 *   title = @Translation("Always ok"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysOkCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = $this->t("This is an ok for testing.");
    return SELF::SEVERITY_OK;
  }

}

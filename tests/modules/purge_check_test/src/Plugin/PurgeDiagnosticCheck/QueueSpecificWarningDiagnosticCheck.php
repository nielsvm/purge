<?php

/**
 * @file
 * Contains \Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck\QueueSpecificWarningDiagnosticCheck.
 */

namespace Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Checks if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "queuewarning",
 *   title = @Translation("Queue specific warning"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {"b"},
 *   dependent_purger_plugins = {}
 * )
 */
class QueueSpecificWarningDiagnosticCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = $this->t("This is a queue warning for testing.");
    return SELF::SEVERITY_WARNING;
  }

}

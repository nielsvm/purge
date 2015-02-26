<?php

/**
 * @file
 * Contains \Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck\PurgerSpecificWarningCheck.
 */

namespace Drupal\purge_check_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "purgerwarning",
 *   title = @Translation("Purger specific warning"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"purger_b"}
 * )
 */
class PurgerSpecificWarningCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is a purger warning for testing.";
    return SELF::SEVERITY_WARNING;
  }

}

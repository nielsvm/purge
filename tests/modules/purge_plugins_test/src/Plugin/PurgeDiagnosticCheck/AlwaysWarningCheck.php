<?php

/**
 * @file
 * Contains \Drupal\purge_plugins_test\Plugin\PurgeDiagnosticCheck\AlwaysWarningCheck.
 */

namespace Drupal\purge_plugins_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "alwayswarning",
 *   title = @Translation("Always a warning"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysWarningCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is a warning for testing.";
    return SELF::SEVERITY_WARNING;
  }
}

<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeDiagnosticCheck\MemoryQueueCheck.
 */

namespace Drupal\purge\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Issues a warning on how unreliable the memory queue is for day-day use.
 *
 * @PurgeDiagnosticCheck(
 *   id = "memoryqueuewarning",
 *   title = @Translation("Memory queue"),
 *   description = @Translation("Checks when the memory queue is in use."),
 *   dependent_queue_plugins = {"memory"},
 *   dependent_purger_plugins = {}
 * )
 */
class MemoryQueueCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {

    // There's nothing to test for here, as this check only gets loaded when
    // the memory queue is active, so we can jump straight to conclusions.
    $this->recommendation = $this->t("You are using the memory queue, which is not recommend for day to day use. Anything stored in this queue, gets lost if it doesn't get processed during the same request.");
    return SELF::SEVERITY_WARNING;
  }
}

<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeRuntimeTest\MemoryQueueWarning.
 */

namespace Drupal\purge\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\PluginInterface as RuntimeTest;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Issues a warning on how unreliable the memory queue is for day-day use.
 *
 * @PurgeRuntimeTest(
 *   id = "memoryqueuewarning",
 *   title = @Translation("Memory queue"),
 *   description = @Translation("Warns when the memory queue is in use."),
 *   service_dependencies = {},
 *   dependent_queue_plugins = {"memory"},
 *   dependent_purger_plugins = {}
 * )
 */
class MemoryQueueWarning extends PluginBase implements RuntimeTest {

  /**
   * {@inheritdoc}
   */
  public function run() {

    // There's nothing to test for here, as this test only gets loaded when
    // the memory queue is active, so we can jump straight to conclusions.
    $this->recommendation = $this->t("You are using the memory queue, which ".
      "is not recommend for day to day use. Items stored in its queue will ".
      "not get stored after each request, so unless a module is processing ".
      "purges in-request, its better not to use it (outside development).");
    return SELF::SEVERITY_WARNING;
  }
}

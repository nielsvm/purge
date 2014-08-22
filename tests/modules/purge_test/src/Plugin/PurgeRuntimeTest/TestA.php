<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgeRuntimeTest\TestA.
 */

namespace Drupal\purge_test\Plugin\PurgeRuntimeTest;

use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\RuntimeTest\RuntimeTestInterface;
use Drupal\purge\RuntimeTest\RuntimeTestBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "test_a",
 *   title = @Translation("Test A"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   service_dependencies = {},
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"nonexistent"}
 * )
 */
class TestA extends RuntimeTestBase implements RuntimeTestInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendation() {
  }

}

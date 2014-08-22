<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgeRuntimeTest\AlwaysErrorTest.
 */

namespace Drupal\purge_test\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\RuntimeTestInterface;
use Drupal\purge\RuntimeTest\RuntimeTestBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "alwayserror",
 *   title = @Translation("Always an error"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   service_dependencies = {},
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysErrorTest extends RuntimeTestBase implements RuntimeTestInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is an error for unit testing.";
    return SELF::SEVERITY_ERROR;
  }
}

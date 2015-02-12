<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgeRuntimeTest\AlwaysErrorTest.
 */

namespace Drupal\purge_testplugins\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\PluginInterface;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "alwayserror",
 *   title = @Translation("Always an error"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysErrorTest extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is an error for unit testing.";
    return SELF::SEVERITY_ERROR;
  }
}

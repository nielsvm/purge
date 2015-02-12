<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgeRuntimeTest\AlwaysInfoTest.
 */

namespace Drupal\purge_testplugins\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\PluginInterface;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "alwaysinfo",
 *   title = @Translation("Always informational"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysInfoTest extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is info for unit testing.";
    return SELF::SEVERITY_INFO;
  }
}

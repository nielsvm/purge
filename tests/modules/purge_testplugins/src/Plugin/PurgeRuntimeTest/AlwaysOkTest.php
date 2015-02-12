<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgeRuntimeTest\AlwaysOkTest.
 */

namespace Drupal\purge_testplugins\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\PluginInterface;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "alwaysok",
 *   title = @Translation("Always ok"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysOkTest extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is an ok for unit testing.";
    return SELF::SEVERITY_OK;
  }
}

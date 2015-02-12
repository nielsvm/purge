<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgeRuntimeTest\AlwaysWarningTest.
 */

namespace Drupal\purge_testplugins\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\PluginInterface;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "alwayswarning",
 *   title = @Translation("Always a warning"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysWarningTest extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is a warning for unit testing.";
    return SELF::SEVERITY_WARNING;
  }
}

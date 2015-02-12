<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgeRuntimeTest\PurgerSpecificWarningTest.
 */

namespace Drupal\purge_testplugins\Plugin\PurgeRuntimeTest;

use Drupal\purge\RuntimeTest\PluginInterface;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "purgerwarning",
 *   title = @Translation("Purger specific warning"),
 *   description = @Translation("A fake test to test the runtime tests api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"purger_b"}
 * )
 */
class PurgerSpecificWarningTest extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is a purger warning for unit testing.";
    return SELF::SEVERITY_WARNING;
  }
}

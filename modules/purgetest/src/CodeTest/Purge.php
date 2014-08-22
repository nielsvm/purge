<?php

/**
 * @file
 * Contains \Drupal\purgetest\CodeTest\Purge.
 */

namespace Drupal\purgetest\CodeTest;

use \Drupal\purgetest\CodeTest\CodeTestBase;

// Show off features of the main module.
class Purge extends CodeTestBase {

  public function home($purger, $queue, $purgeables, $diagnostics) {
    return 'Welcome!';
  }

  /**
   * $plugins = array(
   *   'purger' => $purger->getPlugins(TRUE),
   *   'queue' => $queue->getPlugins(TRUE),
   *   'purgeables' => $purgeables->getPlugins(TRUE),
   *   'diagnostics' => $diagnostics->getPlugins(TRUE)
   * );
   */
  public function pluginList($purger, $queue, $purgeables, $diagnostics) {
    $plugins = array(
      'purger' => $purger->getPlugins(TRUE),
      'queue' => $queue->getPlugins(TRUE),
      'purgeables' => $purgeables->getPlugins(TRUE),
      'diagnostics' => $diagnostics->getPlugins(TRUE)
    );
    return $plugins;
  }

  /**
   * $plugins = array(
   *   'purger' => $purger->getPluginsEnabled(),
   *   'queue' => $queue->getPluginsEnabled(),
   *   'purgeables' => $purgeables->getPluginsEnabled(),
   *   'diagnostics' => $diagnostics->getPluginsEnabled(TRUE)
   * );
   */
  public function pluginListLoaded($purger, $queue, $purgeables, $diagnostics) {
    $plugins = array(
      'purger' => $purger->getPluginsEnabled(),
      'queue' => $queue->getPluginsEnabled(),
      'purgeables' => $purgeables->getPluginsEnabled(),
      'diagnostics' => $diagnostics->getPluginsEnabled()
    );
    return $plugins;
  }
}

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
   *   'purger' => array(),
   *   'queue' => $queue->getPlugins(TRUE),
   *   'purgeables' => $purgeables->getPlugins(TRUE),
   *   'diagnostics' => array()
   * );
   */
  public function pluginList($purger, $queue, $purgeables, $diagnostics) {
    $plugins = array(
      'purger' => array(),
      'queue' => $queue->getPlugins(),
      'purgeables' => $purgeables->getPlugins(TRUE),
      'diagnostics' => array()
    );
    return $plugins;
  }
}
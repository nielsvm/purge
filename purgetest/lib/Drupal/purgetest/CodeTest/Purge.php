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

  public function pluginList($purger, $queue, $purgeables, $diagnostics) {
    return __METHOD__;
  }
}
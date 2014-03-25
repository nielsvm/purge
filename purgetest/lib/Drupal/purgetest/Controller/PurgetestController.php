<?php

/**
 * @file
 * Contains \Drupal\purgetest\Controller\PurgetestController.
 */

namespace Drupal\purgetest\Controller;

use \Drupal\purgetest\Controller\PurgetestControllerBase;

/**
 * Contains callbacks with simple API tests.
 */
class PurgetestController extends PurgetestControllerBase {

  public function home() {
    return 'Welcome!';
  }

  public function pluginList() {
    return __METHOD__;
  }
}
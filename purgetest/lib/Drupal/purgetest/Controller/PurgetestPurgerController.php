<?php

/**
 * @file
 * Contains \Drupal\purgetest\Controller\PurgetestPurgerController.
 */

namespace Drupal\purgetest\Controller;

use \Drupal\purgetest\Controller\PurgetestControllerBase;

/**
 * Contains callbacks with simple API tests.
 */
class PurgetestPurgerController extends PurgetestControllerBase {

  /**
   * // Retrieve access to the purger service.
   * $queue = \Drupal::service('purge.purger');
   */
  public function home() {
    return $this->reflectionResponse('(nothing called)');
  }

  /**
   * $purger = \Drupal::service('purge.purger');
   * $claims = $queue->claimMultiple(5, 15);
   * if ($purger->purgeMultiple($claims)) {
   *   $queue->deleteMultiple($claims);
   * }
   * else {
   *   $queue->releaseMultiple($claims);
   * }
   */
  public function testBatch() {
    return $this->reflectionResponse('(UNIMPLEMENTED)');
//     $claims = $this->purgeQueue->claimMultiple(5, 15);
//     if ($this->purgePurger->purgeMultiple($claims)) {
//       $this->purgeQueue->deleteMultiple($claims);
//     }
//     else {
//       $this->purgeQueue->releaseMultiple($claims);
//     }
  }
}
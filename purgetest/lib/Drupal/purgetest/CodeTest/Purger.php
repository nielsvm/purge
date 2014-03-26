<?php

/**
 * @file
 * Contains \Drupal\purgetest\CodeTest\Purger.
 */

namespace Drupal\purgetest\CodeTest;

use \Drupal\purgetest\CodeTest\CodeTestBase;

// Show interactions with the purger API.
class Purger extends CodeTestBase {

  /**
   * @services
   */
  public function home($purger, $queue, $purgeables, $diagnostics) {
    return __METHOD__;
  }

  /**
   * @services
   * $claims = $queue->claimMultiple(5, 15);
   * if ($purger->purgeMultiple($claims)) {
   *   $queue->deleteMultiple($claims);
   * }
   * else {
   *   $queue->releaseMultiple($claims);
   * }
   */
  public function testBatch($purger, $queue, $purgeables, $diagnostics) {
    return __METHOD__;
//     $claims = $this->purgeQueue->claimMultiple(5, 15);
//     if ($this->purgePurger->purgeMultiple($claims)) {
//       $this->purgeQueue->deleteMultiple($claims);
//     }
//     else {
//       $this->purgeQueue->releaseMultiple($claims);
//     }
  }
}
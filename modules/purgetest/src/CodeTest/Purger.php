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
   *
   * // Purge a single claimed purgeable from the queue.
   * if ($p = $queue->claim(180)) {
   *   if ($purger->purge($p)) {
   *     $queue->delete($p);
   *   }
   *   else {
   *     $queue->release($p);
   *   }
   * }
   *
   * // Claim and purge several.
   * if ($p = $queue->claimMultiple(5,360)) {
   *   if ($purger->purgeMultiple($p)) {
   *     $queue->deleteMultiple($p);
   *   }
   *   else {
   *     $queue->releaseMultiple($p);
   *   }
   * }
   */
  public function testBatch($purger, $queue, $purgeables, $diagnostics) {

    // Purge a single claimed purgeable from the queue.
    if ($p = $queue->claim(180)) {
      if ($purger->purge($p)) {
        $queue->delete($p);
      }
      else {
        $queue->release($p);
      }
    }

    // Claim and purge several.
    if ($p = $queue->claimMultiple(5,360)) {
      if ($purger->purgeMultiple($p)) {
        $queue->deleteMultiple($p);
      }
      else {
        $queue->releaseMultiple($p);
      }
    }
  }
}
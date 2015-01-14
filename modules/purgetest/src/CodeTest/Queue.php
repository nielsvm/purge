<?php

/**
 * @file
 * Contains \Drupal\purgetest\CodeTest\Queue.
 */

namespace Drupal\purgetest\CodeTest;

use \Drupal\purgetest\CodeTest\CodeTestBase;

// Demonstrate how our queue API works.
class Queue extends CodeTestBase {

  /**
   * @services
   * $queue = \Drupal::service('purge.queue');
   */
  public function home($purger, $queue, $purgeables, $diagnostics) {}

  /**
   * @services
   * $queue->emptyQueue();
   */
  public function queueEmpty($purger, $queue, $purgeables, $diagnostics) {
    return $queue->emptyQueue();
  }

  /**
   * @services
   * // We would like to purge '/news/*', create a purgeable and add it to the queue.
   * $purgeable = $purgeables->fromRepresentation('/news/*');
   * $queue->add($purgeable);
   */
  public function queueAddSingle($purger, $queue, $purgeables, $diagnostics) {
    $purgeable = $purgeables->fromRepresentation('/news/*');
    $queue->add($purgeable);
    return $purgeable;
  }

  /**
   * @services
   * // Create a 1000 random purgeables and add them to the queue.
   * for ($i = 1; $i <= 333; $i++) {
   *
   *   // Wipe cache tags that are known at the external cache.
   *   $queue->add($purgeables->fromRepresentation("sometag:$i"));
   *
   *   // These will result in old-school PathPurgeables.
   *   $queue->add($purgeables->fromRepresentation('/random/' . $i));
   *
   *   // These will result in WildcardPathPurgeables, not necessarily supported by
   *   // every purger of course. The asterisk causes a different purgeable to respond.
   *   $queue->add($purgeables->fromRepresentation('/random/' . $i . '/*'));
   * }
   */
  public function queueAddThousand($purger, $queue, $purgeables, $diagnostics) {
    for ($i = 1; $i <= 333; $i++) {
      $queue->add($purgeables->fromRepresentation("sometag:$i"));
      $queue->add($purgeables->fromRepresentation('/random/' . $i));
      $queue->add($purgeables->fromRepresentation('/random/' . $i . '/*'));
    }
    return $i*3;
  }

  /**
   * @services
   * $purgeable = $queue->claim();
   */
  public function claimSingle($purger, $queue, $purgeables, $diagnostics) {
    return $queue->claim();
  }

  /**
   * @services
   * $claims = $queue->claimMultiple(2, 15);
   */
  public function claimMultiple($purger, $queue, $purgeables, $diagnostics) {
    return $queue->claimMultiple(2, 15);
  }
}

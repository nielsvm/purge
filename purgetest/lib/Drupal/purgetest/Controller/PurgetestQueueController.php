<?php

/**
 * @file
 * Contains \Drupal\purgetest\Controller\PurgetestQueueController.
 */

namespace Drupal\purgetest\Controller;

use \Drupal\purgetest\Controller\PurgetestControllerBase;

/**
 * Contains callbacks with simple API tests.
 */
class PurgetestQueueController extends PurgetestControllerBase {

  /**
   * // Retrieve access to the queue service.
   * $queue = \Drupal::service('purge.queue');
   */
  public function home() {
    return $this->reflectionResponse('(nothing called)');
  }

  /**
   * $queue = \Drupal::service('purge.queue');
   * $queue->emptyQueue();
   */
  public function queueEmpty() {
    return $this->reflectionResponse($this->purgeQueue->emptyQueue());
  }

  /**
   * // We would like to purge 'news/*', create a purgeable and add it to the queue.
   * $purgeable = $this->purgePurgeables->matchFromStringRepresentation('news/*');
   * $this->purgeQueue->add($purgeable);
   */
  public function queueAddSingle() {
    $purgeable = $this->purgePurgeables->matchFromStringRepresentation('news/*');
    $this->purgeQueue->add($purgeable);
    return $this->reflectionResponse($purgeable);
  }

  /**
   * $queue = \Drupal::service('purge.queue');
   * $purgeables = \Drupal::service('purge.purgeables');
   *
   * // Create a 1000 random purgeables and add them to the queue.
   * for ($i = 1; $i <= 333; $i++) {
   *
   *   // Wipe cache tags that are known at the external cache.
   *   $queue->add($purgeables->matchFromStringRepresentation("sometag:$i"));
   *
   *   // These will result in old-school PathPurgeables.
   *   $queue->add($purgeables->matchFromStringRepresentation('random/' . $i));
   *
   *   // These will result in WildcardPathPurgeables, not necessarily supported by
   *   // every purger of course. The asterisk causes a different purgeable to respond.
   *   $queue->add($purgeables->matchFromStringRepresentation('random/' . $i . '/*'));
   * }
   */
  public function queueAddThousand() {
    for ($i = 1; $i <= 333; $i++) {
      $this->purgeQueue->add($this->purgePurgeables->matchFromStringRepresentation("sometag:$i"));
      $this->purgeQueue->add($this->purgePurgeables->matchFromStringRepresentation('random/' . $i));
      $this->purgeQueue->add($this->purgePurgeables->matchFromStringRepresentation('random/' . $i . '/*'));
    }
    return $this->reflectionResponse($i*3);
  }

  /**
   * $queue = \Drupal::service('purge.queue');
   * $purgeable = $queue->claim();
   */
  public function claimSingle() {
    return $this->reflectionResponse($this->purgeQueue->claim());
  }

  /**
   * $queue = \Drupal::service('purge.queue');
   * $claims = $queue->claimMultiple(2, 15);
   */
  public function claimMultiple() {
    return $this->reflectionResponse($this->purgeQueue->claimMultiple(2, 15));
  }
}
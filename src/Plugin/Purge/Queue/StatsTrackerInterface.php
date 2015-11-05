<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface.
 */

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\DestructableInterface;

/**
 * Describes the statistics tracker.
 *
 * The statistics tracker keeps track of queue activity by actively counting how
 * many items the queue currently holds and how many have been deleted or
 * released back to it. This data can be used to report progress on the queue
 * and is easily retrieved, the data resets when the queue is emptied.
 */
interface StatsTrackerInterface extends DestructableInterface {

  /**
   * Retrieve the counter tracking the amount of failed invalidations.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   *   The counter object.
   */
  public function counterFailed();

  /**
   * Retrieve the counter tracking failed invalidations that were not supported.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   *   The counter object.
   */
  public function counterNotSupported();

  /**
   * Retrieve the counter tracking currently purging multi-step invalidations.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   *   The counter object.
   */
  public function counterProcessing();

  /**
   * Retrieve the counter tracking the amount of succeeded invalidations.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   *   The counter object.
   */
  public function counterSucceeded();

}

<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\DestructableInterface;

/**
 * Describes the queue statistics tracker.
 *
 * Classes implementing this interface provide several numeric counters which
 * represent operational and statistical information related to the queue.
 */
interface StatsTrackerInterface extends DestructableInterface, \Iterator, \Countable {

  /**
   * Array index for ::numberOfItems().
   *
   * @var int
   */
  const NUMBER_OF_ITEMS = 0;

  /**
   * Array index for ::processing().
   *
   * @var int
   */
  const PROCESSING = 1;

  /**
   * Array index for ::totalFailures().
   *
   * @var int
   */
  const TOTAL_FAILURES = 2;

  /**
   * Array index for ::totalSuccesses().
   *
   * @var int
   */
  const TOTAL_SUCCESSES = 3;

  /**
   * Array index for ::totalUnsupported().
   *
   * @var int
   */
  const TOTAL_UNSUPPORTED = 4;

  /**
   * The number of items currently in the queue.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\numberOfItemsStatistic
   */
  public function numberOfItems();

  /**
   * The number of queue items actively being processed at the moment.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\processingStatistic
   */
  public function processing();

  /**
   * Total number of failed queue items since the last statistics reset.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\totalFailuresStatistic
   */
  public function totalFailures();

  /**
   * Total number of succeeded queue items since the last statistics reset.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\totalSuccessesStatistic
   */
  public function totalSuccesses();

  /**
   * Total number of unsupported invalidations since the last statistics reset.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\totalUnsupportedStatistic
   */
  public function totalUnsupported();

  /**
   * Reset the total counters, short-hand for:
   *  - ::totalFailures()->set(0)
   *  - ::totalSuccesses()->set(0)
   *  - ::totalUnsupported()->set(0)
   */
  public function resetTotals();

}

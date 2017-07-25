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
   * Array index for ::totalFailures().
   *
   * @var int
   */
  const TOTAL_FAILURES = 1;

  /**
   * Array index for ::totalSuccesses().
   *
   * @var int
   */
  const TOTAL_SUCCESSES = 2;

  /**
   * Array index for ::totalUnsupported().
   *
   * @var int
   */
  const TOTAL_UNSUPPORTED = 3;

  /**
   * The number of items currently in the queue.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\numberOfItemsStatistic
   */
  public function numberOfItems();

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

  /**
   * Automatically update the total counters for the given invalidations.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   A non-associative array with invalidation objects regardless of the state
   *   they're in. Their state will determine which counter will be updated.
   */
  public function updateTotals(array $invalidations);

}

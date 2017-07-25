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
   * The number of items currently in the queue.
   *
   * This counter is not a true statistic, but instead a maintained copy of the
   * number of items in the queue. This exists to prevent potentially expensive
   * code paths to QueueServiceInterface::numberOfItems() and it is recommended
   * to poll this counter instead.
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface::numberOfItems()
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function numberOfItems();

  /**
   * The number of queue items actively being processed at the moment.
   *
   * This counter is not a true statistic, but reflects how many queue items are
   * marked as 'claimed' at the moment, which means that a processor is
   * currently busy processing these items. In most circumstances, this number
   * will be 0 as queues usually empty fast, but you can always catch a moment
   * when items are being processed. Quickly after such moment, the number
   * should be 0 again.
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface::claim()
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function processing();

  /**
   * Total number of failed queue items since the last statistics reset.
   *
   * Whenever a purger returns a queue item as failed, we're keeping track
   * of these failures via this statistic. However, failing items fail for
   * various reasons and are usually expected to still succeed in the
   * future. The total number of failures happening over time, should be seen
   * as indicator whether a few incidents took place versus sky-rocketing
   * failure rates because of some structural problem.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function totalFailures();

  /**
   * Total number of succeeded queue items since the last statistics reset.
   *
   * When queue items are successfully processed, they are deleted from the
   * queue to make space for new items. This statistic represents all of the
   * successful cache invalidations that happened over time.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function totalSuccesses();

  /**
   * Total number of unsupported invalidations since the last statistics reset.
   *
   * Queue items can be unsupported at any point in time when no configured
   * purgers supported the type of cache invalidation requested. For example,
   * when your purger only supports 'tag' but a 'url' item ended up in the
   * queue and got offered to the purger, this statistic is updated. However,
   * it is totally possible that this same queue item later succeeds because
   * you updated the module providing the purger which now supports that type.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function totalUnsupported();

  /**
   * Reset the total counters, short-hand for:
   *  - ::totalFailures()->set(0)
   *  - ::totalSuccesses()->set(0)
   *  - ::totalUnsupported()->set(0)
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function resetTotals();

}

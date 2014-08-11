<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PurgerInterface.
 */

namespace Drupal\purge\Purger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * Describes a purger: the executor that takes purgeable instruction objects and
 * wipes the described things from an external cache system.
 */
interface PurgerInterface {

  /**
   * Wipe the given purgeable from the external cache system.
   *
   * @param \Drupal\purge\Purgeable\PurgeableInterface $purgeable
   *   A purgeable describes a single item to be purged and can be created using
   *   the 'purge.purgeables' service, either directly or through a queue claim.
   *
   * @warning
   *   Some purgers can set the purgeable's state to STATE_PURGING and return
   *   FALSE here, indicating that the item needs to be fed to the purger once
   *   again later.
   *
   * @return
   *   Returns TRUE on full success and FALSE in any other case. In addition it
   *   always calls \Drupal\purge\Purgeable\PurgeableInterface::setState() on
   *   the $purgeable instance, setting it to STATE_PURGED or STATE_PURGEFAILED.
   */
  public function purge(PurgeableInterface $purgeable);

  /**
   * Wipe all given purgeables from the external cache system.
   *
   * @param array $purgeables
   *   Non-associative array with purgeable object instances compliant with
   *   \Drupal\purge\Purgeable\PurgeableInterface, either directly generated
   *   through the 'purge.purgeables' service or claimed from 'purge.queue'.
   *
   * @return
   *   Returns TRUE if all were successfully purged but FALSE if just one of
   *   them failed. The \Drupal\purge\Purgeable\PurgeableInterface::setState()
   *   method is being called on each of them and states are set to either
   *   STATE_PURGED, STATE_PURGING or STATE_PURGEFAILED. Both failed purges as
   *   active purges will result in a FALSE and its being assumed that they
   *   will be fed to the purger again later, e.g. by releasing back to the
   *   queue service.
   */
  public function purgeMultiple(array $purgeables);

  /**
   * Calculate how many purges this purger thinks it can process.
   *
   * This method helps consumers putting the purger to work to determine how
   * many items can be claimed from the queue or processed in total during the
   * full runtime period of the script. The purger can take environmental data
   * like whether it is running on the CLI or not and combine these with other
   * facts it knows about itself, e.g. the way purging is implemented. Based on
   * these the purger should give a safe indication of how many items it can
   * purge per script without exposing the API user to the risk of the current
   * PHP request/process to crash.
   *
   * @warning
   *   Multiple purgers can be active per Drupal installation which affects the
   *   total amount of purges that can be processed per run. As PurgerService
   *   takes this into account already, purgers should always assume that they
   *   are the only active purger.
   *
   * @return int
   *   Integer, the number of purgeable objects it can process during runtime.
   */
  public function getCapacityLimit();

  /**
   * Gets a reasonable number of seconds that this purger thinks it needs per purge.
   *
   * The 'purge.queue' service accepts a expiry time in seconds when one or more
   * purgeables are being claimed for immediate purging. This method is supposed
   * to give a indication of how many seconds this purger thinks it will need
   * for the execution of one single purge. This estimation is then multiplied
   * for every claimed queue item: so if this method returns 5 (int) and 4 items
   * are claimed from the queue at once, the total lease expiry time is 20
   * seconds. If the purger fails to execute all 4 within those 20 seconds, the
   * queue will release the claimed items and another purger instance may get in
   * and try them again.
   *
   * @warning
   *   Every implemented purger should implement this method and take it very
   *   seriously, as it strongly influences the performance of the system. Too
   *   low will result in double purging and many retries, too high might result
   *   in capacity assumptions being lower than actually true. For a purger
   *   wiping items on localhost (e.g.: UNIX socket) two seconds can be
   *   realistic whereas a CDN with a remote purge web-service (+ network
   *   latency) needs at least 120 seconds for full purge executions. All the
   *   time taken by your implementation details, need to be taken into account.
   *
   * @warning
   *   Users can configure multiple active purgers at once, for instance one to
   *   clear a CDN while the other clears a local caching Nginx instance. There
   *   is however no necessity for implementations of this method to incorporate
   *   that, as PurgerService::getClaimTimeHint() will automatically add up all
   *   estimations returned by each individual purger.
   *
   * @return int
   *   Integer, a safe number of seconds where in which one purgeable could be processed.
   */
  public function getClaimTimeHint();

  /**
   * Reports the number of successful purges that this purger did.
   *
   * @return int
   *   Integer, defaults to 0 if nothing was successfully purged during runtime.
   */
  public function getNumberPurged();

  /**
   * Reports the number of failed attempts that this purger tried purging.
   *
   * @return int
   *   Integer, defaulting to 0 if nothing was purged during runtime.
   */
  public function getNumberFailed();

  /**
   * Reports how many items are *currently* actively being purged.
   *
   * @warning
   *   This method will - for most transactional purgers - return 0 and is
   *   intended for complex external cache systems (e.g. CDN's) that process
   *   wipe-requests on thousands of servers and therefore take longer than a
   *   few seconds to process each. The purger can change the state of a
   *   purgeable to STATE_PURGING, return FALSE on them and cause them to be
   *   released back to the queue. During the next processing iteration these
   *   purgers can mark these as STATE_PURGED with a resulting TRUE. Purgers can
   *   for instance use Drupal's state API to track this kind of information.
   *
   * @return int
   *   Integer, the current number of purgeables being processed.
   */
  public function getNumberPurging();
}

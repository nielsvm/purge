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
   * full runtime period of the script. The purger can take environment info
   * like whether it is running on the CLI or not and combine these with other
   * things it knows about itself, e.g. the way purging is implemented. Based on
   * these kind of factors the purger can give a safe hint of how many things
   * it can purge per script run preventing PHP to crash suddenly.
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
   * purgeables are being claimed for immediate purging. This method gives a fair
   * indication of how many seconds this purger thinks it will need per item and
   * can therefore be used to safely determine the total amount of seconds that
   * are given back to the queue as expiry time. Let's assume this function returns
   * 5 seconds for this purger and 10 items are being claimed from the queue, that
   * means that the queue API should release the claimed items after 50 seconds.
   *
   * @warning
   *   Every purger should implement this and return a number of seconds that is
   *   on the safe side but not too overexaggerated, so could 3 seconds be fair for
   *   an external cache system running on this server and would 10 be better for
   *   a remotely hosted server with network latency in between.
   *
   * @warning
   *   Multiple purgers can be active per Drupal installation which will affect the
   *   needed claim time, however, implementors should not take this into account. In
   *   PurgerService::getClaimTimeHint() all values are added up so each purger
   *   plugin should only return what it thinks it needs.
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

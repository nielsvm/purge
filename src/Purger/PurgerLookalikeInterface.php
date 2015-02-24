<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PurgerLookalikeInterface.
 */

namespace Drupal\purge\Purger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Describes a purger or service transparently acting as one.
 */
interface PurgerLookalikeInterface {

  /**
   * Invalidate $invalidation from the external cache and update its state.
   *
   * Implementations of this method have the responsibility of invalidating the
   * given $invalidation object from the external cache. In addition to the work
   * itself, it also has to call $invalidation->setState() and set it to the
   * situation that applies after its attempt. In the case the purge succeeded,
   * this has to be STATE_PURGED and if it failed, its STATE_FAILED.
   *
   * Some external caching platforms - think CDNs - need more time to finish
   * invalidations and require later confirmation. In these cases, the state has
   * to be set to STATE_PURGING so that it gets fed to this method again in the
   * future (via the queue presumably). That means that incoming $invalidation
   * objects have to be checked for the STATE_PURGING in these cases as well.
   *
   * @param \Drupal\purge\Invalidation\PluginInterface $invalidation
   *   The invalidation object describes what needs to be invalidated from the
   *   external caching system, and gets instantiated by the service
   *   'purge.invalidation.factory', either directly or through a queue claim.
   *
   * @throws \Drupal\purge\Purger\Exception\InvalidStateException
   *   Exception thrown by \Drupal\purge\Purger\ServiceInterface::invalidate
   *   when the incoming $invalidation object's state is not any of these:
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_NEW
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGING
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_FAILED
   *
   * @throws \Drupal\purge\Purger\Exception\InvalidStateException
   *   Exception thrown by \Drupal\purge\Purger\ServiceInterface::invalidate
   *   when the invalidation object processed by the purger plugin, is not in
   *   any of the following states:
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGED
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGING
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_FAILED
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::setState()
   * @see \Drupal\purge\Invalidation\PluginInterface::getState()
   *
   * @return void
   */
  public function invalidate(Invalidation $invalidation);

  /**
   * Invalidate all $invalidations from the external cache and update states.
   *
   * Implementations of this method have the responsibility of invalidating the
   * given list of invalidation objects from the external cache. In addition to
   * the work itself, it also has to call $invalidation->setState() on each one
   * of them and set it to the situation that applies for each object. In the
   * case an individual invalidation succeeded, its state becomes STATE_PURGED
   * or STATE_FAILED when it failed.
   *
   * Some external caching platforms - think CDNs - need more time to finish
   * invalidations and require later confirmation. In these cases, the state has
   * to be set to STATE_PURGING so that it gets fed to this method again in the
   * future (via the queue presumably). That means that incoming $invalidation
   * objects have to be checked for the STATE_PURGING in these cases as well.
   *
   * @param \Drupal\purge\Invalidation\PluginInterface[] $invalidations
   *   Non-associative array of invalidation objects that each describe what
   *   needs to be invalidated by the external caching system. These objects can
   *   come from the queue or from the 'purge.invalidation.factory' service.
   *
   * @throws \Drupal\purge\Purger\Exception\InvalidStateException
   *   Thrown by \Drupal\purge\Purger\ServiceInterface::invalidateMultiple when
   *   any of the incoming invalidation objects does not have any of the
   *   following states:
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_NEW
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGING
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_FAILED
   *
   * @throws \Drupal\purge\Purger\Exception\InvalidStateException
   *   Thrown by \Drupal\purge\Purger\ServiceInterface::invalidateMultiple when
   *   any of the invalidation objects returning from the purger plugin are not
   *   in one of these states:
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGED
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGING
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_FAILED
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::setState()
   * @see \Drupal\purge\Invalidation\PluginInterface::getState()
   *
   * @return void
   */
  public function invalidateMultiple(array $invalidations);

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
   *   The number of invalidation objects it can process during runtime.
   */
  public function getCapacityLimit();

  /**
   * Gets a reasonable number of seconds that this purger thinks it needs per purge.
   *
   * The 'purge.queue' service accepts a expiry time in seconds when one or more
   * invalidations are being claimed for immediate purging. This method is
   * supposed to give a indication of how many seconds this purger thinks it
   * will need for the execution of one single purge. This estimation is then
   * multiplied for every claimed queue item: so if this method returns 5 (int)
   * and 4 items are claimed from the queue at once, the total lease expiry time
   * is 20 seconds. If the purger fails to execute all 4 within those 20
   * seconds, the queue could release the claimed items to another purger
   * instance that might be running.
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
   *   that, as \Drupal\purge\Purger\Service::getClaimTimeHint() will
   *   automatically add up all estimations returned by each individual purger.
   *
   * @return int
   *   A safe number of seconds in which one invalidation could be processed.
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
   *   intended for complex external cache systems (e.g. CDNs) that process
   *   wipe-requests on thousands of servers and therefore take longer than a
   *   few seconds to process each. The purger changes the state of the
   *   invalidation object to STATE_PURGING and cause them to be released back
   *   to the queue. During the next processing iteration these purgers can mark
   *   these as STATE_PURGED.
   *
   * @return int
   *   The current number of invalidation objects being processed.
   */
  public function getNumberPurging();

}

<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\SharedInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Describes a purger or service transparently acting as one.
 */
interface SharedInterface {

  /**
   * Invalidate $invalidation from the external cache and update its state.
   *
   * Implementations of this method have the responsibility of invalidating the
   * given $invalidation object from the external cache. In addition to the work
   * itself, it also has to call $invalidation->setState() and set it to the
   * situation that applies after its attempt. In the case the purge succeeded,
   * this has to be SUCCEEDED and if it failed, its FAILED.
   *
   * Some external caching platforms - think CDNs - need more time to finish
   * invalidations and require later confirmation. In these cases, the state has
   * to be set to PROCESSING so that it gets fed to this method again in the
   * future (via the queue presumably). That means that incoming $invalidation
   * objects have to be checked for the PROCESSING in these cases as well.
   *
   * Implementations of \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate() can
   * also set the state to NOT_SUPPORTED when available purgers cannot
   * invalidate the type of $invalidation given.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   The invalidation object describes what needs to be invalidated from the
   *   external caching system, and gets instantiated by the service
   *   'purge.invalidation.factory', either directly or through a queue claim.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException
   *   When the capacity tracker's global resource limit returns zero, it is no
   *   longer allowed to conduct cache invalidations. Any claimed objects should
   *   be released back to the queue (or will expire naturally) and your code
   *   should depend on the next processing window.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Exception thrown by \Drupal\purge\Plugin\Purge\Purger\SharedInterface::invalidate
   *   when the incoming $invalidation object's state is not any of these:
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FRESH
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::NOT_SUPPORTED
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Exception thrown by \Drupal\purge\Plugin\Purge\Purger\SharedInterface::invalidate
   *   when the invalidation object processed by the purger plugin, is not in
   *   any of the following states:
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::SUCCEEDED
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState()
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getState()
   *
   * @return void
   */
  public function invalidate(InvalidationInterface $invalidation);

  /**
   * Invalidate all $invalidations from the external cache and update states.
   *
   * Implementations of this method have the responsibility of invalidating the
   * given list of invalidation objects from the external cache. In addition to
   * the work itself, it also has to call $invalidation->setState() on each one
   * of them and set it to the situation that applies for each object. In the
   * case an individual invalidation succeeded, its state becomes SUCCEEDED
   * or FAILED when it failed.
   *
   * Some external caching platforms - think CDNs - need more time to finish
   * invalidations and require later confirmation. In these cases, the state has
   * to be set to PROCESSING so that it gets fed to this method again in the
   * future (via the queue presumably). That means that incoming $invalidation
   * objects have to be checked for the PROCESSING in these cases as well.
   *
   * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidateMultiple() implementations
   * can also set the state to NOT_SUPPORTED when available purgers cannot
   * invalidate the type of $invalidation given.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   Non-associative array of invalidation objects that each describe what
   *   needs to be invalidated by the external caching system. These objects can
   *   come from the queue or from the 'purge.invalidation.factory' service.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when the $invalidations parameter is empty.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException
   *   Thrown when the capacity tracker's global resource limit returns zero or
   *   when more $invalidations are given exceeding this limit. Any claimed
   *   objects should be released back to the queue (or will expire naturally)
   *   and your code should depend on the next processing window.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown by \Drupal\purge\Plugin\Purge\Purger\SharedInterface::invalidateMultiple when
   *   any of the incoming invalidation objects does not have any of the
   *   following states:
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FRESH
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown by \Drupal\purge\Plugin\Purge\Purger\SharedInterface::invalidateMultiple when
   *   any of the invalidation objects returning from the purger plugin are not
   *   in one of these states:
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::SUCCEEDED
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   *    - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState()
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getState()
   *
   * @return void
   */
  public function invalidateMultiple(array $invalidations);

  /**
   * Retrieve the list of supported invalidation types.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$types.
   *
   * @return string[]
   *   List of supported invalidation type plugins.
   */
  public function getTypes();

}

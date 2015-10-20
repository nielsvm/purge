<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\SharedInterface.
 */

namespace Drupal\purge\Purger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

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
   * this has to be STATE_PURGED and if it failed, its STATE_FAILED.
   *
   * Some external caching platforms - think CDNs - need more time to finish
   * invalidations and require later confirmation. In these cases, the state has
   * to be set to STATE_PURGING so that it gets fed to this method again in the
   * future (via the queue presumably). That means that incoming $invalidation
   * objects have to be checked for the STATE_PURGING in these cases as well.
   *
   * Implementations of \Drupal\purge\Purger\ServiceInterface::invalidate() can
   * also set the state to STATE_UNSUPPORTED when available purgers cannot
   * invalidate the type of $invalidation given.
   *
   * @param \Drupal\purge\Invalidation\PluginInterface $invalidation
   *   The invalidation object describes what needs to be invalidated from the
   *   external caching system, and gets instantiated by the service
   *   'purge.invalidation.factory', either directly or through a queue claim.
   *
   * @throws \Drupal\purge\Purger\Exception\BadPluginBehaviorException
   *   Exception thrown by \Drupal\purge\Purger\SharedInterface::invalidate
   *   when the incoming $invalidation object's state is not any of these:
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_NEW
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGING
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_FAILED
   *
   * @throws \Drupal\purge\Purger\Exception\BadPluginBehaviorException
   *   Exception thrown by \Drupal\purge\Purger\SharedInterface::invalidate
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
   * \Drupal\purge\Purger\ServiceInterface::invalidateMultiple() implementations
   * can also set the state to STATE_UNSUPPORTED when available purgers cannot
   * invalidate the type of $invalidation given.
   *
   * @param \Drupal\purge\Invalidation\PluginInterface[] $invalidations
   *   Non-associative array of invalidation objects that each describe what
   *   needs to be invalidated by the external caching system. These objects can
   *   come from the queue or from the 'purge.invalidation.factory' service.
   *
   * @throws \Drupal\purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown by \Drupal\purge\Purger\SharedInterface::invalidateMultiple when
   *   any of the incoming invalidation objects does not have any of the
   *   following states:
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_NEW
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_PURGING
   *    - \Drupal\purge\Invalidation\PluginInterface::STATE_FAILED
   *
   * @throws \Drupal\purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown by \Drupal\purge\Purger\SharedInterface::invalidateMultiple when
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
   * Retrieve the list of supported invalidation types.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$types.
   *
   * @return string[]
   *   List of supported invalidation type plugins.
   */
  public function getTypes();

}

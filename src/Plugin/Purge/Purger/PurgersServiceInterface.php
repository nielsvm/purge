<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Invalidation\PluginInterface as Invalidation;
use Drupal\purge\ServiceInterface;
use Drupal\purge\ModifiableServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\SharedInterface;

/**
 * Describes a service that distributes access to one or more purgers.
 */
interface PurgersServiceInterface extends ServiceInterface, ModifiableServiceInterface, SharedInterface {

  /**
   * Get the capacity tracker.
   *
   * Implementations of \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface always hold a
   * single capacity tracker instance. The capacity tracker tracks runtime
   * resource consumption and maintains activity counters.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface;
   */
  public function capacityTracker();

  /**
   * Create a unique instance ID for new purger instances.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. This helper creates a unique,
   * random string, 10 characters long.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId()
   *
   * @return string
   */
  public function createId();

  /**
   * Disable the given purger plugin instances.
   *
   * Just before, it calls \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::delete()
   * on the purger(s) being disabled allowing the plugin to clean up.
   *
   * @param string[] $ids
   *   Non-associative array of instance ids that are about to be uninstalled.
   *
   * @throws \LogicException
   *   Thrown when any of the ids given isn't valid or when $ids is empty.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::delete()
   *
   * @return void
   */
  public function deletePluginsEnabled(array $ids);

  /**
   * Retrieve all user-readable labels for all enabled purger instances.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getLabel()
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper[]
   *   Associative array with instance ID's in the key and the label as value.
   */
  public function getLabels();

  /**
   * Retrieve the plugin_ids of purgers that can be enabled.
   *
   * This method takes into account that purger plugins that are not
   * multi-instantiable, can only be loaded once and are no longer available if
   * they are already available. Plugins that are multi-instantiable, will
   * always be listed.
   *
   * @return string[]
   *   Array with the plugin_ids of the plugins that can be enabled.
   */
  public function getPluginsAvailable();

  /**
   * Retrieve the configured plugin_ids that the service will use.
   *
   * @return string[]
   *   Array with the plugin_ids of the enabled plugins.
   */
  public function getPluginsEnabled();

  /**
   * Retrieve the list of supported invalidation types per purger instance.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$types.
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId().
   *
   * @return string[]
   *   Array with the purger instance ID as key, and list of invalidation types.
   */
  public function getTypesByPurger();

  /**
   * Set the final invalidation state after one or more purgers invalidated it.
   *
   * Callers of \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate() and
   * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidateMultiple() do not know
   * that multiple purgers can invalidate their objects. This is by design and
   * allows very flexible and powerful configuration. However, it also leads to
   * a problem. What if one purger fails to invalidate a tag invalidation while
   * two other purgers successfully purge it?
   *
   * Implementations of this method accept the resulting states that one or more
   * purger plugins returned and decide what the final state becomes. Once
   * decided, it sets the final state on the invalidation object.
   *
   * This method should not be called directly from outside this service.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidateMultiple()
   * @see \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface::setState()
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   The invalidation object as just returned by one or more purgers.
   * @param int[] $states
   *   One or more \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface::STATE_* contants.
   *
   * @return void
   */
  public function resolveInvalidationState(Invalidation $invalidation, array $states);

}

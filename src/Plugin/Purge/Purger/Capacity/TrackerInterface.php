<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Capacity;

use Drupal\Core\State\StateInterface;

/**
 * Describes the capacity tracker API.
 *
 * The capacity tracker is the central orchestrator between limited system
 * resources and an ever growing queue of invalidation objects.
 *
 * The tracker aggregates capacity hints given by loaded purgers and sets
 * uniformized purging capacity boundaries. It tracks how much purges are taking
 * place - counts successes and failures - and actively protects the set
 * limits. This protects end-users against requests exceeding resource limits
 * such as maximum execution time and memory exhaustion. At the same time it
 * aids queue processors by dynamically giving the number of items that can
 * be processed in one go.
 */
interface TrackerInterface {

  /**
   * Construct a capacity tracker.
   *
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[] $purgers
   *   All purger plugins instantiated by \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(array $purgers, StateInterface $state);

  /**
   * Retrieve the counter tracking the amount of failed invalidations.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   *   The counter object.
   */
  public function counterFailed();

  /**
   * Retrieve the counter tracking failed invalidations that were not supported.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   *   The counter object.
   */
  public function counterNotSupported();

  /**
   * Retrieve the counter tracking currently purging multi-step invalidations.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   *   The counter object.
   */
  public function counterProcessing();

  /**
   * Retrieve the counter tracking the amount of succeeded invalidations.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   *   The counter object.
   */
  public function counterSucceeded();

  /**
   * Decrease the number of remaining purges that can happen in this request.
   *
   * External cache invalidation is expensive and can become exponentially more
   * expensive when multiple platforms are being invalidated. To assure that we
   * don't purge more than Drupal's request lifetime allows for, ::getTimeHint()
   * gives us the highest number of seconds a cache invalidation could take.
   *
   * A call to ::getRemainingInvalidationsLimit() calculates how many cache
   * invalidations are left to be processed during this request. It bases its
   * decision on ::getMaxExecutionTime() and ::getIdealConditionsLimit() and
   * information tracked during request lifetime. When it returns zero, no more
   * items can be claimed from the queue or fed to the purgers service.
   *
   * In order to track this global limit, ::decrementLimit() gets called every
   * time the purgers service attempted one or more invalidations until the
   * value becomes zero.
   *
   * @param int $amount
   *   Numeric amount to subtract from the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a integer or when it is zero/negative.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::getRemainingInvalidationsLimit()
   */
  public function decrementLimit($amount = 1);

  /**
   * Get the time in seconds to wait after invalidation for a specific purger.
   *
   * @param string $purger_instance_id
   *   The instance ID of the purger from which to return the cooldown time.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown when the returned floating point value is lower than 0.0, higher
   *   than 3.0 or is not returned as floating point value.
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $purger_instance_id doesn't exist.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerPurgerInterface::getCooldownTime()
   *
   * @return float
   *   The maximum number of seconds - as a float - to wait after invalidation.
   */
  public function getCooldownTime($purger_instance_id);

  /**
   * Get the maximum number of invalidations that can be processed.
   *
   * External cache invalidation is expensive and can become exponentially more
   * expensive when multiple platforms are being invalidated. To assure that we
   * don't purge more than Drupal's request lifetime allows for, ::getTimeHint()
   * gives us the highest number of seconds a cache invalidation could take.
   *
   * A call to ::getRemainingInvalidationsLimit() calculates how many cache
   * invalidations are left to be processed during this request. It bases its
   * decision on ::getMaxExecutionTime() and ::getIdealConditionsLimit() and
   * information tracked during request lifetime. When it returns zero, no more
   * items can be claimed from the queue or fed to the purgers service.
   *
   * In order to track this global limit, ::decrementLimit() gets called every
   * time the purgers service attempted one or more invalidations until the
   * value becomes zero.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown when a returned value is not a integer or when it equals to 0.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerPurgerInterface::getIdealConditionsLimit()
   *
   * @return int
   *   The number of invalidations that can be processed under ideal conditions.
   */
  public function getIdealConditionsLimit();

  /**
   * Get the maximum PHP execution time that is available to cache invalidation.
   *
   * @return int
   *   The maximum number of seconds available to cache invalidation. Zero means
   *   that PHP has no fixed execution time limit, for instance on the CLI.
   */
  public function getMaxExecutionTime();

  /**
   * Get the remaining number of allowed cache invalidations for this request.
   *
   * External cache invalidation is expensive and can become exponentially more
   * expensive when multiple platforms are being invalidated. To assure that we
   * don't purge more than Drupal's request lifetime allows for, ::getTimeHint()
   * gives us the highest number of seconds a cache invalidation could take.
   *
   * A call to ::getRemainingInvalidationsLimit() calculates how many cache
   * invalidations are left to be processed during this request. It bases its
   * decision on ::getMaxExecutionTime() and ::getIdealConditionsLimit() and
   * information tracked during request lifetime. When it returns zero, no more
   * items can be claimed from the queue or fed to the purgers service.
   *
   * In order to track this global limit, ::decrementLimit() gets called every
   * time the purgers service attempted one or more invalidations until the
   * value becomes zero.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::decrementLimit()
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::getTimeHint()
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::getIdealConditionsLimit()
   *
   * @return int
   *   The remaining number of allowed cache invalidations during the remainder
   *   of Drupal's request lifetime. When 0 is returned, no more can take place.
   */
  public function getRemainingInvalidationsLimit();

  /**
   * Get the maximum number of seconds, processing a single invalidation takes.
   *
   * The capacity tracker calls getTimeHint on all loaded purger plugins and
   * uses the highest outcome as global estimate. When multiple loaded purger
   * plugins support the same type of invalidation (for instance 'tag'), these
   * values will be added up. This means that if 3 plugins all purge tags, this
   * will cause purge to take it a lot easier and to pull less items from the
   * queue per request.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown when a returned floating point value is lower than 0.1, higher
   *   than 10 or is not returned as float.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerPurgerInterface::getTimeHint()
   *
   * @return float
   *   The maximum number of seconds - as a float - it takes all purgers to
   *   process a single cache invalidation (regardless of type).
   */
  public function getTimeHint();

}

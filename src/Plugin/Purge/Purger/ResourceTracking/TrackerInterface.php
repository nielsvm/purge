<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\ResourceTracking\TrackerInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger\ResourceTracking;

/**
 * Describes the resource tracker API.
 *
 * The resource tracker is the central orchestrator between limited system
 * resources and an ever growing queue of invalidation objects.
 *
 * The resource tracker aggregates capacity hints given by loaded purgers and
 * sets uniformized purging capacity boundaries. It tracks how much purges are
 * taking place - counts successes and failures - and actively protects the set
 * capacity boundaries. This protects end-users against requests exceeding
 * resource limits such as maximum execution time and memory exhaustion. At the
 * same time it aids queue processors by dynamically giving them the number of
 * items that can be processed in one go.
 */
interface TrackerInterface {

  /**
   * Retrieve the counter for multi-step invalidation objects currently purging.
   *
   * @return float
   *   The maximum number of seconds - as a float - it takes all purgers to
   *   process a single cache invalidation (regardless of type).
   */
  public function counterPurging();

  // /**
  //  * Get the maximum number of seconds, processing a single invalidation takes.
  //  *
  //  * The service implementation of getClaimTimeHint() aggregates all individual
  //  * purger plugin implementations and uses the highest outcome as global
  //  * estimate. When multiple loaded purger plugins support the same type of
  //  * invalidation (for instance 'tag'), these values will be added up. This
  //  * means that if 3 plugins all purge tags, this will cause purge to take it a
  //  * lot easier and to pull less items from the queue per request.
  //  *
  //  * @throws \Drupal\purge\Purger\Exception\BadPluginBehaviorException
  //  *   Thrown when the returned floating point value is lower than 0.2, higher
  //  *   than 10 or is not returned as float.
  //  *
  //  * @see \Drupal\purge\Purger\PluginInterface::getClaimTimeHint()
  //  *
  //  * @return float
  //  *   The maximum number of seconds - as a float - it takes all purgers to
  //  *   process a single cache invalidation (regardless of type).
  //  */
  public function getTimeHint();

  // /**
  //  * Get the maximum number of seconds, processing a single invalidation takes.
  //  *
  //  * The service implementation of getClaimTimeHint() aggregates all individual
  //  * purger plugin implementations and uses the highest outcome as global
  //  * estimate. When multiple loaded purger plugins support the same type of
  //  * invalidation (for instance 'tag'), these values will be added up. This
  //  * means that if 3 plugins all purge tags, this will cause purge to take it a
  //  * lot easier and to pull less items from the queue per request.
  //  *
  //  * @throws \Drupal\purge\Purger\Exception\BadPluginBehaviorException
  //  *   Thrown when the returned floating point value is lower than 0.2, higher
  //  *   than 10 or is not returned as float.
  //  *
  //  * @see \Drupal\purge\Purger\PluginInterface::getClaimTimeHint()
  //  *
  //  * @return int
  //  *   The maximum number of seconds - as a float - it takes all purgers to
  //  *   process a single cache invalidation (regardless of type).
  //  */
  public function getLimit();
}

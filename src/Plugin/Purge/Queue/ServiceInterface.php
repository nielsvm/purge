<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queue\ServiceInterface.
 */

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;
use Drupal\purge\ModifiableServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\PluginInterface as Invalidation;

/**
 * Describes a service that lets invalidations interact with a queue backend.
 */
interface ServiceInterface extends PurgeServiceInterface, ModifiableServiceInterface {

  /**
   * Add a invalidation object to the queue, schedule it for later purging.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   A invalidation object describes a single item to be invalidated and can
   *   be created using the 'purge.invalidation.factory' service. The object
   *   instance added to the queue can be claimed and executed by the
   *   'purge.purgers' service later.
   *
   * @return void
   */
  public function add(Invalidation $invalidation);

  /**
   * Add multiple invalidation objects to the queue, schedule for later purging.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface[] $invalidations
   *   A non-associative array with \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface
   *   objects to be added to the queue. The invalidations can later be claimed
   *   from the queue and fed to the 'purge.purgers' executor.
   *
   * @return void
   */
  public function addMultiple(array $invalidations);

  /**
   * Claims a invalidation object from the queue for immediate purging.
   *
   * @param int $lease_time
   *   The expected time needed to process this claim. Use the mandatory
   *   \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::getTimeHint()
   *   as input parameter as it gives the best informed number of seconds.
   *
   *   After the lease_time expires, another running request or CLI process can
   *   also claim this item and process it, therefore too short lease times are
   *   dangerous as it could lead to double processing.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface|false
   *   Returned will be a fully instantiated invalidation object or FALSE when
   *   the queue is empty. Be aware that its expected that the claimed item
   *   needs to be fed to the purger within the specified $lease_time, else they
   *   will become available again.
   */
  public function claim($lease_time = 30);

  /**
   * Claim multiple invalidations for immediate purging from the queue at once.
   *
   * @param int $claims
   *   Determines how many claims at once should be claimed from the queue. When
   *   the queue is unable to return as many items as requested it will return
   *   as much items as it can.
   * @param int $lease_time
   *   The expected (maximum) time needed per claim, which will get multiplied
   *   for you by the number of claims you request. It is mandatory to use
   *   \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::getTimeHint()
   *   as input parameter as it gives the best informed number of seconds.
   *
   *   After the lease_time expires, another running request or CLI process can
   *   also claim the items and process them, therefore too short lease times
   *   are dangerous as it could lead to double processing.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface[]|array
   *   Returned will be a non-associative array with the given amount of
   *   \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface objects as claimed. Be aware
   *   that its expected that the claimed invalidations will need to be
   *   processed by the purger within the given $lease_time, else they will
   *   become available again. The returned array is empty when the queue is.
   */
  public function claimMultiple($claims = 10, $lease_time = 30);

  /**
   * Release a invalidation object that couldn't be purged, back to the queue.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   The invalidation that couldn't be held for longer or that failed
   *   processing, to be marked as free for processing in the queue. Once
   *   released, other consumers can claim and attempt purging it again.
   *
   * @return void
   */
  public function release(Invalidation $invalidation);

  /**
   * Release invalidations that couldn't be purged, back to the queue.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface[] $invalidations
   *   A non-associative array with \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface
   *   objects to released and marked as available in the queue. Once released,
   *   other consumers can claim them again and attempt purging them.
   *
   * @return void
   */
  public function releaseMultiple(array $invalidations);

  /**
   * Delete a purged invalidation object from the queue.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   The invalidation that was successfully purged and that should be removed
   *   from the queue. The object instance might remain to exist but should not
   *   be accessed anymore, cleanup might occur later during runtime.
   *
   * @return void
   */
  public function delete(Invalidation $invalidation);

  /**
   * Delete multiple invalidations from the queue at once.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface[] $invalidations
   *   A non-associative array with \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface
   *   objects to be removed from the queue. Once called, the instance might
   *   still exists but should not be accessed anymore, cleanup might occur
   *   later during runtime.
   *
   * @return void
   */
  public function deleteMultiple(array $invalidations);

  /**
   * Release the item to, or delete it from the queue depending its state.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   The invalidation object after the 'purge.purgers' service attempted
   *   invalidation.
   *
   * @throws \Drupal\purge\Plugin\Purge\Queue\Exception\UnexpectedServiceConditionException
   *   Exception thrown when the object state doesn't make any sense.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersService::invalidate
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersService::invalidateMultiple
   *
   * @return void
   */
  public function deleteOrRelease(Invalidation $invalidation);

  /**
   * Release the items to, or delete them from the queue depending their state.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface[] $invalidations
   *   The invalidation objects after the 'purge.purgers' service attempted
   *   their invalidation.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersService::invalidate
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersService::invalidateMultiple
   *
   * @return void
   */
  public function deleteOrReleaseMultiple(array $invalidations);

  /**
   * Empty the entire queue and reset all statistics.
   *
   * @return void
   */
  public function emptyQueue();

}

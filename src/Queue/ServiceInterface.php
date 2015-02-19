<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\ServiceInterface.
 */

namespace Drupal\purge\Queue;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * Describes a service that lets purgeables interact with the underlying queue.
 */
interface ServiceInterface extends PurgeServiceInterface {

  /**
   * Add a purgeable to the queue, schedule it for later purging.
   *
   * @param \Drupal\purge\Purgeable\PluginInterface $purgeable
   *   A purgeable describes a single item to be purged and can be created using
   *   the 'purge.purgeables' service. The object instance added to the queue
   *   can be claimed and executed by the 'purge.purgers' service later.
   */
  public function add(Purgeable $purgeable);

  /**
   * Add multiple purgeables to the queue, schedule them for later purging.
   *
   * @param array $purgeables
   *   A non-associative array with \Drupal\purge\Purgeable\PluginInterface
   *   objects to be added to the queue. The purgeables can later be claimed
   *   from the queue and fed to the 'purge.purgers' executor.
   */
  public function addMultiple(array $purgeables);

  /**
   * Claims a purgeable from the queue for immediate purging.
   *
   * @param $lease_time
   *   The lease time determines how long the processing is expected to take
   *   place in seconds, defaults to an hour. After this lease expires, the item
   *   will be reset and another consumer can claim the purgeable. Very short
   *   lease times can result in purgeables being purged twice by parallel
   *   processes, due this inefficiency the one-hour default is recommended for
   *   most purgers.
   *
   * @return \Drupal\purge\Purgeable\PluginInterface
   *   Returned will be a fully instantiated purgeable object or FALSE when the
   *   queue is empty. Be aware that its expected that the claimed item needs
   *   to be fed to the purger within the specified $lease_time, else they will
   *   become available again.
   */
  public function claim($lease_time = 3600);

  /**
   * Claim multiple purgeables for immediate purging from the queue at once.
   *
   * @param $claims
   *   Determines how many claims at once should be claimed from the queue. When
   *   the queue is unable to return as many items as requested it will return
   *   as much items as it can.
   * @param $lease_time
   *   The lease time determines how long the processing is expected to take
   *   place in seconds, defaults to an hour. After this lease expires, the item
   *   will be reset and another consumer can claim the purgeable. Very short
   *   lease times can result in purgeables being purged twice by parallel
   *   processes, due this inefficiency the one-hour default is recommended for
   *   most purgers.
   *
   * @return array
   *   Returned will be a non-associative array with the given amount of
   *   \Drupal\purge\Purgeable\PluginInterface objects as claimed. Be aware
   *   that its expected that the claimed purgeables will need to be processed
   *   by the purger within the given $lease_time, else they will become
   *   available again. The returned array might be empty when the queue is.
   */
  public function claimMultiple($claims = 10, $lease_time = 3600);

  /**
   * Release a purgeable that couldn't be purged, back to the queue.
   *
   * @param \Drupal\purge\Purgeable\PluginInterface $purgeable
   *   The purgeable that couldn't be held for longer or that failed processing,
   *   to be marked as free for processing in the queue. Once released, other
   *   consumers can claim and attempt purging it again.
   */
  public function release(Purgeable $purgeable);

  /**
   * Release purgeables that couldn't be purged, back to the queue.
   *
   * @param array $purgeables
   *   A non-associative array with \Drupal\purge\Purgeable\PluginInterface
   *   objects to released and marked as available in the queue. Once released,
   *   other consumers can claim them again and attempt purging them.
   */
  public function releaseMultiple(array $purgeables);

  /**
   * Delete a purged purgeable from the queue.
   *
   * @param \Drupal\purge\Purgeable\PluginInterface $purgeable
   *   The purgeable that was successfully purged and that should be removed
   *   from the queue. The object instance might remain to exist but should not
   *   be accessed anymore, cleanup might occur later during runtime.
   */
  public function delete(Purgeable $purgeable);

  /**
   * Delete multiple purgeables from the queue at once.
   *
   * @param array $purgeables
   *   A non-associative array with \Drupal\purge\Purgeable\PluginInterface
   *   objects to be removed from the queue. Once called, the instance might
   *   still exists but should not be accessed anymore, cleanup might occur
   *   later during runtime.
   */
  public function deleteMultiple(array $purgeables);

  /**
   * Empty the entire queue and reset all statistics.
   */
  function emptyQueue();
}

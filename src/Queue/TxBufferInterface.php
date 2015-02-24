<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\TxBufferInterface.
 */

namespace Drupal\purge\Queue;

use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Describes the transaction buffer.
 */
interface TxBufferInterface extends \Countable, \Iterator {

  /**
   * Freshly claimed objects.
   */
  const CLAIMED = 0;

  /**
   * Objects in the process of being added to the queue.
   */
  const ADDING = 1;

  /**
   * Objects that just got added to the queue.
   */
  const ADDED = 2;

  /**
   * Objects in the process of being released back to the queue.
   */
  const RELEASING = 3;

  /**
   * Objects that just got released back to the queue.
   */
  const RELEASED = 4;

  /**
   * Objects in the process of being deleted from the queue.
   */
  const DELETING = 5;

  /**
   * Constructs the TxBuffer object.
   *
   * The transaction buffer is used internally by \Drupal\purge\Queue\Service
   * and holds \Drupal\purge\Invalidation\PluginInterface objects. For each
   * object, it maintains state information about the object in relation to the
   * queue. This helps \Drupal\purge\Queue\Service to commit objects as rarely
   * and efficiently as possible to its underlying back-end.
   */
  public function __construct();

  /**
   * Delete the given invalidation object from the buffer.
   *
   * @param array|\Drupal\purge\Invalidation\PluginInterface $invalidations
   *   Invalidation object or array with objects.
   *
   * @return void
   */
  public function delete($invalidations);

  /**
   * Delete everything in the buffer.
   *
   * @return void
   */
  public function deleteEverything();

  /**
   * Only retrieve items from the buffer in a particular given state(s).
   *
   * @param int|array $states
   *   Individual state or array with one of the following states:
   *     - \Drupal\purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Queue\TxBufferInterface::DELETING
   *
   * @return \Drupal\purge\Invalidation\PluginInterface[]
   */
  public function getFiltered($states);

  /**
   * Request the in-buffer set state for the given invalidation object.
   *
   * @param \Drupal\purge\Invalidation\PluginInterface $invalidation
   *   Invalidation object.
   *
   * @return int|null
   *   The state of the given object or NULL when not found.
   *     - \Drupal\purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Queue\TxBufferInterface::DELETING
   */
  public function getState(Invalidation $invalidation);

  /**
   * Check if the given object is already in buffer our not.
   *
   * @param \Drupal\purge\Invalidation\PluginInterface $invalidation
   *   Invalidation object.
   *
   * @return TRUE|FALSE
   */
  public function has(Invalidation $invalidation);

  /**
   * Set the given state on one or multiple invalidation objects.
   *
   * @param array|\Drupal\purge\Invalidation\PluginInterface $invalidations
   *   Invalidation object or array with objects.
   * @param int $state
   *   One of the following states:
   *     - \Drupal\purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Queue\TxBufferInterface::DELETING
   *
   * @return void
   */
  public function set($invalidations, $state);

}

<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface.
 */

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\PluginInterface as Invalidation;

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
   * The transaction buffer is used internally by \Drupal\purge\Plugin\Purge\Queue\Service
   * and holds \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface objects. For each
   * object, it maintains state and properties about the object in relation to
   * the queue. This helps \Drupal\purge\Plugin\Purge\Queue\Service to commit objects as
   * rarely and efficiently as possible to its underlying back-end.
   */
  public function __construct();

  /**
   * Delete the given invalidation object from the buffer.
   *
   * @param array|\Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidations
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
   * Retrieve a buffered object by property=value combination.
   *
   * @param string $property
   *   The name of the property you want to look for.
   * @param mixed $value
   *   The (unique) value of the property that has to be stored in the buffer
   *   in order to return the object.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface|false
   *   The matched invalidation object or FALSE when there was no combination
   *   found of the property and value.
   */
  public function getByProperty($property, $value);

  /**
   * Only retrieve items from the buffer in a particular given state(s).
   *
   * @param int|array $states
   *   Individual state or array with one of the following states:
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::DELETING
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface[]
   */
  public function getFiltered($states);

  /**
   * Request the in-buffer set state for the given invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   Invalidation object.
   *
   * @return int|null
   *   The state of the given object or NULL when not found.
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::DELETING
   */
  public function getState(Invalidation $invalidation);

  /**
   * Retrieve a stored property for the given invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   Invalidation object.
   * @param string $property
   *   The string key of the stored property you want to receive.
   * @param mixed $default
   *   The return value for when the property is not found.
   *
   * @return mixed|null
   *   The stored property value or the value of the $default argument.
   */
  public function getProperty(Invalidation $invalidation, $property, $default = NULL);

  /**
   * Check if the given object is already in buffer our not.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   Invalidation object.
   *
   * @return TRUE|FALSE
   */
  public function has(Invalidation $invalidation);

  /**
   * Set the given state on one or multiple invalidation objects.
   *
   * @param array|\Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidations
   *   Invalidation object or array with objects.
   * @param int $state
   *   One of the following states:
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::DELETING
   *
   * @return void
   */
  public function set($invalidations, $state);

  /**
   * Store a named property for the given invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface $invalidation
   *   Invalidation object.
   * @param string $property
   *   The string key of the property you want to store.
   * @param mixed $value
   *   The value of the property you want to set.
   *
   * @return void
   */
  public function setProperty(Invalidation $invalidation, $property, $value);

}

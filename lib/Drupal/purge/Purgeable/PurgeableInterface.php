<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableInterface.
 */

namespace Drupal\purge\Purgeable;

/**
 * Provides an interface defining a purgeable.
 */
interface PurgeableInterface {

  /**
   * Purgeable state: the purgeable object is just instantiated.
   */
  const STATE_NEW = 0;

  /**
   * Purgeable state: the purgeable object is being added to the queue.
   */
  const STATE_ADDING = 1;

  /**
   * Purgeable state: the purgeable object is added to the queue.
   */
  const STATE_ADDED = 2;

  /**
   * Purgeable state: the purgeable object is claimed.
   */
  const STATE_CLAIMED = 3;

  /**
   * Purgeable state: the purgeable object is being purged by the purger.
   */
  const STATE_PURGING = 4;

  /**
   * Purgeable state: the purgeable object has been purged successfully.
   */
  const STATE_PURGED = 5;

  /**
   * Purgeable state: the purgeable object failed purging, needs to be released.
   */
  const STATE_PURGEFAILED = 6;

  /**
   * Purgeable state: the purgeable object is releasing back to the queue.
   */
  const STATE_RELEASING = 7;

  /**
   * Purgeable state: the purgeable object is released back to the queue.
   */
  const STATE_RELEASED = 8;

  /**
   * Purgeable state: the purgeable object is being deleted from the queue.
   */
  const STATE_DELETING = 9;

  /**
   * Purgeable state: the purgeable object is deleted and should be unset.
   */
  const STATE_DELETED = 10;

  /**
   * Instantiate a new purgeable.
   *
   * @param string $representation
   *   A string representing this type of purgeable, e.g. "node/1" for a
   *   path purgeable and "*" for a full domain purgeable.
   * @warning
   *   Will throw a InvalidStringRepresentationException when the given string
   *   does not match the format for this type of purgeable. For instance when
   *   a path with wildcard ('news/*') is given to the PathPurgeable, it will
   *   not instantiate.
   */
  function __construct($representation);

  /**
   * Return the serialized string representation of the purgeable.
   *
   * @return string
   *   Returns the string serialization, e.g. "node/1".
   */
  public function __toString();

  /**
   * Disallow writing to any non-existent object properties. A purgeable is by
   * definition a read-only object and requires the setter methods to be called.
   *
   * @param string $name
   *   The property name that PHP was not able to find on this object.
   * @param mixed $value
   *   The value the caller is trying to set the property to.
   *
   * @return
   *   Nothing, it throws a \Drupal\purge\Purgeable\InvalidPurgeablePropertyException.
   */
  public function __set($name, $value);

  /**
   * Provide the virtual Queue API properties: item_id, data, created.
   *
   * @param string $name
   *   The property name that PHP was not able to find on this object. Only the
   *   properties $p->item_id, $p->data, $p->created are recognized.
   * @return
   *   The requested value. When a item is being requested that does not exist
   *   it will throw \Drupal\purge\Purgeable\InvalidPurgeablePropertyException.
   */
  public function __get($name);

  /**
   * Set the plugin ID of this purgeable object, done by the Purgeable Factory.
   *
   * @param $plugin_id
   *   The unique ID of this purgeable as found in the plugin's main class doc.
   */
  public function setPluginId($plugin_id);

  /**
   * Set all Queue API properties on the purgeable, in one call.
   *
   * @param $item_id
   *   The unique ID returned from \Drupal\Core\Queue\QueueInterface::createItem().
   * @param $created
   *   The timestamp when the queue item was put into the queue.
   */
  public function setQueueItemInfo($item_id, $created);

  /**
   * Set the unique ID of the associated queue item on this purgeable object.
   *
   * @param $item_id
   *   The unique ID returned from \Drupal\Core\Queue\QueueInterface::createItem().
   */
  public function setQueueItemId($item_id);

  /**
   * Set the created timestamp of the associated queue item on this purgeable.
   *
   * @param $created
   *   The timestamp when the queue item was put into the queue.
   */
  public function setQueueItemCreated($created);

  /**
   * Set the state of the purgeable.
   *
   * @param $state
   *   Integer matching to any of the PurgeableInterface::STATE_* constants.
   */
  public function setState($state);

  /**
   * Get the current state of the purgeable.
   *
   * @return
   *   Integer matching to one of the PurgeableInterface::STATE_* constants.
   */
  public function getState();

  /**
   * Get the current state as string.
   *
   * @return
   *   The string can be prefixed with 'STATE_' to let it match a constant.
   */
  public function getStateString();
}

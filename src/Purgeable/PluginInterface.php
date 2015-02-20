<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PluginInterface.
 */

namespace Drupal\purge\Purgeable;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes the purgeable: which instructs the purger what to wipe.
 */
interface PluginInterface extends PluginInspectionInterface {

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
   * Return the string expression of the purgeable.
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
   *   Nothing, it throws a \Drupal\purge\Purgeable\Exception\InvalidPropertyException.
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
   *   it will throw \Drupal\purge\Purgeable\Exception\InvalidPropertyException.
   */
  public function __get($name);

  /**
   * Set all Queue API properties on the purgeable, in one call.
   *
   * @param $item_id
   *   The unique ID returned from \Drupal\Core\Queue\PluginInterface::createItem().
   * @param $created
   *   The timestamp when the queue item was put into the queue.
   */
  public function setQueueItemInfo($item_id, $created);

  /**
   * Set the unique ID of the associated queue item on this purgeable object.
   *
   * @param $item_id
   *   The unique ID returned from \Drupal\Core\Queue\PluginInterface::createItem().
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
   *   Integer matching to any of the PluginInterface::STATE_* constants.
   */
  public function setState($state);

  /**
   * Get the current state of the purgeable.
   *
   * @return
   *   Integer matching to one of the PluginInterface::STATE_* constants.
   */
  public function getState();

  /**
   * Get the current state as string.
   *
   * @return
   *   The string comes without the 'STATE_' prefix as on the constants.
   */
  public function getStateString();

  /**
   * Validate the expression given to the purgeable during instantiation.
   *
   * @throws \Drupal\purge\Purgeable\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Purgeable\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of purgeable.
   *
   * @see \Drupal\purge\Annotation\PurgePurgeable::$expression_required
   * @see \Drupal\purge\Annotation\PurgePurgeable::$expression_can_be_empty
   *
   * @return void
   */
  public function validateExpression();
}

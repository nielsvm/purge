<?php

/**
 * @file
 * Contains \Drupal\purge\Invalidation\PluginInterface.
 */

namespace Drupal\purge\Invalidation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes the invalidation: which instructs the purger what to invalidate.
 */
interface PluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Invalidation object just got instantiated.
   */
  const STATE_NEW = 0;

  /**
   * Invalidation is on-going and requires later confirmation by the purger
   * whether it is finished or not, turns into STATE_PURGED.
   */
  const STATE_PURGING = 1;

  /**
   * The invalidation succeeded.
   */
  const STATE_PURGED = 2;

  /**
   * The invalidation failed.
   */
  const STATE_FAILED = 3;

  /**
   * Return the string expression of the invalidation.
   *
   * @return string
   *   Returns the string serialization, e.g. "node/1".
   */
  public function __toString();

  /**
   * Disallow writing to any non-existent object properties. A invalidation is
   * by definition a read-only object and requires setter methods to be called.
   *
   * @param string $name
   *   The property name that PHP was not able to find on this object.
   * @param mixed $value
   *   The value the caller is trying to set the property to.
   *
   * @return
   *   Nothing, it throws a \Drupal\purge\Invalidation\Exception\InvalidPropertyException.
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
   *   it will throw \Drupal\purge\Invalidation\Exception\InvalidPropertyException.
   */
  public function __get($name);

  /**
   * Set all Queue API properties on the invalidation, in one call.
   *
   * @param $item_id
   *   The unique ID returned from \Drupal\Core\Queue\PluginInterface::createItem().
   * @param $created
   *   The timestamp when the queue item was put into the queue.
   */
  public function setQueueItemInfo($item_id, $created);

  /**
   * Set the unique ID of the associated queue item on this invalidation object.
   *
   * @param $item_id
   *   The unique ID returned from \Drupal\Core\Queue\PluginInterface::createItem().
   */
  public function setQueueItemId($item_id);

  /**
   * Set the created timestamp of the associated queue item on the invalidation.
   *
   * @param $created
   *   The timestamp when the queue item was put into the queue.
   */
  public function setQueueItemCreated($created);

  /**
   * Set the state of the invalidation.
   *
   * @param $state
   *   Integer matching to any of the PluginInterface::STATE_* constants.
   */
  public function setState($state);

  /**
   * Get the current state of the invalidation.
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
   * Validate the expression given to the invalidation during instantiation.
   *
   * @throws \Drupal\purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @see \Drupal\purge\Annotation\PurgeInvalidation::$expression_required
   * @see \Drupal\purge\Annotation\PurgeInvalidation::$expression_can_be_empty
   *
   * @return void
   */
  public function validateExpression();
}

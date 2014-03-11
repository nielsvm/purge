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
   * Instantiate a new purgeable.
   *
   * @param string $representation
   *   A string representing this type of purgeable, e.g. "node/1" for a
   *   path purgeable and "*" for a full domain purgeable.
   */
  public function __construct($representation);

  /**
   * Return the serialized string representation of the purgeable.
   *
   * @return string
   *   Returns the string serialization, e.g. "node/1".
   */
  public function __toString();

  /**
   * Write the purgeable and its status to the logs.
   */
  public function toWatchdog();

  /**
   * Serialize the purgeable to be stored in a queue item.
   *
   *
   */
  public function toQueueItemData();
}

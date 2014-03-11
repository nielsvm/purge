<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableFactoryInterface.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * Provides an interface defining a purgeables factory.
 */
interface PurgeableFactoryInterface {

  /**
   * Instantiate a purgeable based upon a serialized queue item.
   *
   * @param string $data
   *   The serialized string representing the purgeable to be instantiated.
   *
   * @return \Drupal\purge\Purgeable\PurgeableInterface
   */
  public function fromQueueItemData($data);

  /**
   * Instantiate a purgeable based upon arbitrary user input strings.
   *
   * @param string $representation
   *   The input string could be a path like "node/1", a full domain "*"
   *   or anything else that purgeables could respond to. All purgeable
   *   types are queried for their support.
   *
   * @return \Drupal\purge\Purgeable\PurgeableInterface
   */
  public function matchFromUserInputLine($representation);
}

<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\ServiceInterface.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;

/**
 * Describes a service that instantiates purgeable objects on-demand.
 */
interface ServiceInterface extends PurgeServiceInterface {

  /**
   * Instantiate a purgeable based upon a serialized queue item.
   *
   * @param string $data
   *   Arbitrary PHP data structured that was stored into the queue.
   *
   * @see \Drupal\purge\Purgeable\PluginBase::toQueueItemData()
   *
   * @return \Drupal\purge\Purgeable\PluginInterface
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
   * @return \Drupal\purge\Purgeable\PluginInterface
   */
  public function matchFromStringRepresentation($representation);
}

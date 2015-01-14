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
   * Replicate a purgeable object from serialized queue item data.
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
   * Instantiate a purgeable object based upon a plugin ID and representation.
   *
   * @param string $plugin_id
   *   The id of the purgeable plugin being instantiated.
   * @param string $representation
   *   String that describes what is being purged, specific format
   *   characteristics determine the Purgeable object type requested. Each
   *   plugin providing a type tests the string on validity and will throw a
   *   \Drupal\purge\Purgeable\Exception\InvalidRepresentationException
   *   for representations it does not support.
   *
   *   Representation examples:
   *    - Full domain: *
   *    - Drupal cache tags: user:1, menu:footer, rendered
   *    - HTTP paths: /, /<front>, /news, /news?page=0
   *    - HTTP wildcard paths: /*, /news/*
   *
   *   Since purgeable objects are 'messages', it will also depend on the purger
   *   executing your requests whether they're supported, as not every platform
   *   supports universally everything.
   *
   * @return \Drupal\purge\Purgeable\PurgeableInterface
   */
  public function fromNamedRepresentation($plugin_id, $representation);

  /**
   * Probes all purgeable object types and returns the first matching instance.
   *
   * @param string $representation
   *   String that describes what is being purged, specific format
   *   characteristics determine the Purgeable object type requested. Each
   *   plugin providing a type tests the string on validity and will throw a
   *   \Drupal\purge\Purgeable\Exception\InvalidRepresentationException
   *   for representations it does not support.
   *
   *   Representation examples:
   *    - Full domain: *
   *    - Drupal cache tags: user:1, menu:footer, rendered
   *    - HTTP paths: /, /<front>, /news, /news?page=0
   *    - HTTP wildcard paths: /*, /news/*
   *
   *   Since purgeable objects are 'messages', it will also depend on the purger
   *   executing your requests whether they're supported, as not every platform
   *   supports universally everything.
   *
   * @return \Drupal\purge\Purgeable\PluginInterface
   */
  public function fromRepresentation($representation);
}

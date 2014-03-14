<?php

/**
 * @file
 * Contains \Drupal\purge_fakerefbackend\Plugin\Purgeable\NodePurgeable.
 */

namespace Drupal\purge_fakerefbackend\Plugin\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Wipe a node by its path from the cache, e.g 'node/5'.
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "NodePurgeable",
 *   label = @Translation("Node Purgeable")
 * )
 */
class NodePurgeable extends PurgeableBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    throw new InvalidStringRepresentationException('Not yet implemented');
  }
}

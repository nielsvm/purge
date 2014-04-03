<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurgeable\Node.
 */

namespace Drupal\purgetest\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Wipe a node by its path from the cache, e.g 'node/5'.
 *
 * @PurgePurgeable(
 *   id = "Node",
 *   label = @Translation("Node Purgeable")
 * )
 */
class Node extends PurgeableBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    throw new InvalidStringRepresentationException('Not yet implemented');
  }
}

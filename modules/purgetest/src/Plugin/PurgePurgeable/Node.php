<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurgeable\Node.
 */

namespace Drupal\purgetest\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Wipe a node by its path from the cache, e.g 'node/5'.
 *
 * @PurgePurgeable(
 *   id = "node",
 *   label = @Translation("Node Purgeable")
 * )
 */
class Node extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    throw new InvalidStringRepresentationException('Not yet implemented');
  }
}

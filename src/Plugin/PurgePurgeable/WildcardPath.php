<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\WildcardPath.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Plugin\PurgePurgeable\Path;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;
use Drupal\purge\Purgeable\Exception\InvalidRepresentationException;

/**
 * Describes a path based cache wipe with wildcard, e.g. "/news/*".
 *
 * @PurgePurgeable(
 *   id = "wildcardpath",
 *   label = @Translation("Wildcard Path Purgeable")
 * )
 */
class WildcardPath extends Path implements Purgeable {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    parent::__construct($representation, FALSE);
    if (strpos($representation, '*') === FALSE) {
      throw new InvalidRepresentationException(
        'A wildcard purgeable should contain asterisk at all times.');
    }
  }
}

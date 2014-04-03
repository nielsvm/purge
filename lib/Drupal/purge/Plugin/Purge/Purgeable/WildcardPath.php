<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purgeable\WildcardPath.
 */

namespace Drupal\purge\Plugin\Purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Plugin\Purge\Purgeable\Path;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Describes a path based cache wipe with wildcard, e.g. "news/*".
 *
 * @PurgePurgeable(
 *   id = "wildcardpath",
 *   label = @Translation("Wildcard Path Purgeable")
 * )
 */
class WildcardPath extends Path {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    PurgeableBase::__construct($representation);
    if ($representation === '*') {
      throw new InvalidStringRepresentationException(
        'Only an asterisk is not a valid wildcard path.');
    }
    if (strpos($representation, '*') === FALSE) {
      throw new InvalidStringRepresentationException(
        'A wildcard purgeable should contain a *.');
    }
    if (preg_match('/[A-Za-z]/', $representation) === 0) {
      throw new InvalidStringRepresentationException(
        'A HTTP path should have alphabet characters in it.');
    }
  }
}

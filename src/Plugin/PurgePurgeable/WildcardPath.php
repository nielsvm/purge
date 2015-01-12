<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\WildcardPath.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Plugin\PurgePurgeable\Path;
use Drupal\purge\Purgeable\PluginInterace as Purgeable;
use Drupal\purge\Purgeable\Exception\InvalidStringRepresentationException;

/**
 * Describes a path based cache wipe with wildcard, e.g. "news/*".
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

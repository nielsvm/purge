<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purgeable\WildcardPathPurgeable.
 */

namespace Drupal\purge\Plugin\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Plugin\Purgeable\PathPurgeable;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Describes a path based cache wipe with wildcard, e.g. "news/*".
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "WildcardPathPurgeable",
 *   label = @Translation("Wildcard Path Purgeable")
 * )
 */
class WildcardPathPurgeable extends PathPurgeable {

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

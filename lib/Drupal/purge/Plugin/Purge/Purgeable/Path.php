<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purgeable\Path.
 */

namespace Drupal\purge\Plugin\Purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Describes a path based cache wipe, e.g. "news/article-1".
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "Path",
 *   label = @Translation("Path Purgeable")
 * )
 */
class Path extends PurgeableBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    parent::__construct($representation);
    if (empty($representation)) {
      throw new InvalidStringRepresentationException(
        'This does not look like a ordinary HTTP path element.');
    }
    if (strpos($representation, ' ') !== FALSE) {
      throw new InvalidStringRepresentationException(
        'A HTTP path element should not contain a space.');
    }
    if (strpos($representation, '*') !== FALSE) {
      throw new InvalidStringRepresentationException(
        'A HTTP path should not contain a *.');
    }
    if (preg_match('/[A-Za-z]/', $representation) === 0) {
      throw new InvalidStringRepresentationException(
        'A HTTP path should have alphabet characters in it.');
    }
  }
}

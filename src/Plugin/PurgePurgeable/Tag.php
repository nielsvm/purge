<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Tag.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\Exception\InvalidStringRepresentationException;

/**
 * Describes a cache wipe by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @see \Drupal\Core\Cache\DatabaseBackend::flattenTags()
 *
 * @PurgePurgeable(
 *   id = "tag",
 *   label = @Translation("Tag Purgeable")
 * )
 */
class Tag extends PurgeableBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    parent::__construct($representation);
    if ($representation === ':') {
      throw new InvalidStringRepresentationException(
        'This does not look like a valid cache tag.');
    }
    if (strpos($representation, ':') === FALSE) {
      throw new InvalidStringRepresentationException(
        'The given string is not a flattened cache tag.');
    }
  }
}

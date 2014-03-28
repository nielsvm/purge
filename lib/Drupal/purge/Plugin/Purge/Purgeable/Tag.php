<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purgeable\Tag.
 */

namespace Drupal\purge\Plugin\Purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Describes a cache wipe by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @ingroup purge_purgeable_types
 *
 * @see \Drupal\Core\Cache\DatabaseBackend::flattenTags()
 *
 * @Plugin(
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

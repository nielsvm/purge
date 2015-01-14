<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Tag.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface as Purgeable;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidRepresentationException;

/**
 * Describes a cache wipe by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @PurgePurgeable(
 *   id = "tag",
 *   label = @Translation("Tag Purgeable")
 * )
 */
class Tag extends PluginBase implements Purgeable {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    parent::__construct($representation);
    if (strpos($representation, '/') !== FALSE) {
      throw new InvalidRepresentationException(
      'Tag purgeables cannot contain slashes.');
    }
    if (strpos($representation, '*') !== FALSE) {
      throw new InvalidRepresentationException(
        'Tag purgeables do not contain asterisks.');
    }
  }
}

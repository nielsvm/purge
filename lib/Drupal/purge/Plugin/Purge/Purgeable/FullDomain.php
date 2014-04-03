<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purgeable\FullDomain.
 */

namespace Drupal\purge\Plugin\Purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Instructs a full domain or full cache clear, string representation: "*".
 *
 * @PurgePurgeable(
 *   id = "fulldomain",
 *   label = @Translation("Full Domain Purgeable")
 * )
 */
class FullDomain extends PurgeableBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    parent::__construct($representation);
    if ($representation !== '*') {
      throw new InvalidStringRepresentationException(
        'A full domain wipe is always simply represented as "*".');
    }
  }
}

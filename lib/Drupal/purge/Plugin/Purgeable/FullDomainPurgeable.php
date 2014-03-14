<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purgeable\FullDomainPurgeable.
 */

namespace Drupal\purge\Plugin\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Instructs a full domain or full cache clear, string representation: "*".
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "FullDomainPurgeable",
 *   label = @Translation("Full Domain Purgeable")
 * )
 */
class FullDomainPurgeable extends PurgeableBase {

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

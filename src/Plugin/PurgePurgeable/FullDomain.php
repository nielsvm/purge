<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\FullDomain.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface as Purgeable;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidRepresentationException;

/**
 * Instructs a full domain or full cache clear, string representation: "*".
 *
 * @PurgePurgeable(
 *   id = "fulldomain",
 *   label = @Translation("Full Domain Purgeable")
 * )
 */
class FullDomain extends PluginBase implements Purgeable {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    parent::__construct($representation);
    if ($representation !== '*') {
      throw new InvalidRepresentationException(
        'A full domain(s) wipe is always represented as "*".');
    }
  }
}

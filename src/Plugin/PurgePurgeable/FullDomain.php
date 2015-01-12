<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\FullDomain.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterace as Purgeable;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidStringRepresentationException;

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
      throw new InvalidStringRepresentationException(
        'A full domain wipe is always simply represented as "*".');
    }
  }
}

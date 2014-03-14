<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableBase.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableInterface;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Provides an interface defining a purgeable.
 */
abstract class PurgeableBase implements PurgeableInterface {

  /**
   * Arbitrary string representing the thing that needs to be purged.
   *
   * @var \Drupal\purge\Purgeable\PurgeableBase
   */
  protected $representation;

  /**
   * Reflects the last known status of this purgeable. TRUE for successfully
   * purged, FALSE for failure and NULL for untried.
   *
   * @var \Drupal\purge\Purgeable\PurgeableBase
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    $this->representation = $representation;
    if (!is_string($representation)) {
      throw new InvalidStringRepresentationException(
        'The representation of the thing you want to purge is not a string.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return sprintf('{%s:%s}', get_class($this), $this->representation);
  }

  /**
   * {@inheritdoc}
   */
  public function toWatchdog() {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function toQueueItemData() {
    return array(
      get_class($this),
      $this->representation
    );
  }
}

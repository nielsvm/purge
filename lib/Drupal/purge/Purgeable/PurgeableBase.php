<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableBase.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * Provides an interface defining a purgeable.
 */
abstract class PurgeableBase implements PurgeableInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
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
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }
}

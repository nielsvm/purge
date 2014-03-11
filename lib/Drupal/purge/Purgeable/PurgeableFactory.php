<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableFactory.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableFactoryInterface;

/**
 * Factory class creating purgeable objects.
 */
class PurgeableFactory implements PurgeableFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function fromQueueItemData($data) {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function matchFromUserInputLine($representation) {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }
}

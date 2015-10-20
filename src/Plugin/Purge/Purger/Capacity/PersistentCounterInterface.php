<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Capacity;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface;

/**
 * Describes a numeric counter stored in state storage.
 */
interface PersistentCounterInterface extends CounterInterface {

  /**
   * Overwrite the counter value if the object already exists in state storage.
   */
  public function setFromState();

  /**
   * Inject the state API and its storage key.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param string $id
   *   A unique identifier which describes this counter.
   */
  public function setStateAndId(StateInterface $state, $id);

}

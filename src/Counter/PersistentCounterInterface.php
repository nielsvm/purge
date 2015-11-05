<?php

/**
 * @file
 * Contains \Drupal\purge\Counter\PersistentCounterInterface.
 */

namespace Drupal\purge\Counter;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Counter\CounterInterface;

/**
 * Describes a numeric counter stored in state storage.
 */
interface PersistentCounterInterface extends CounterInterface {

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

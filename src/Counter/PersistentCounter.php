<?php

/**
 * @file
 * Contains \Drupal\purge\Counter\PersistentCounter.
 */

namespace Drupal\purge\Counter;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Counter\PersistentCounterInterface;
use Drupal\purge\Counter\Counter;

/**
 * Provides a numeric counter stored in state storage.
 */
class PersistentCounter extends Counter implements PersistentCounterInterface {

  /**
   * A unique identifier which describes this counter.
   *
   * @var string
   */
  protected $id;

  /**
   * The state key value store.
   *
   * @var NULL|\Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct($value = 0.0, $decrement = TRUE, $increment = TRUE, $set = TRUE) {
    $this->permission_decrement = $decrement;
    $this->permission_increment = $increment;
    $this->permission_set = $set;

    // Set the initial starting value, for which we cannot use ::set() as there
    // is no state and ID yet, so we just start with a configured default.
    if (!(is_float($value) || is_int($value))) {
      throw new BadBehaviorException('Given $value is not a integer or float.');
    }
    if (is_int($value)) {
      $value = (float) $value;
    }
    if ($value < 0.0) {
      throw new BadBehaviorException('Given $value can only be zero or positive.');
    }
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    return parent::get();
  }

  /**
   * {@inheritdoc}
   */
  protected function setDirectly($value) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    parent::setDirectly($value);
    $this->state->set($this->id, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setStateAndId(StateInterface $state, $id) {
    if (empty($id)) {
      throw new BadBehaviorException('Given $id parameter is empty.');
    }
    $this->state = $state;
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($amount = 1.0) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    parent::decrement($amount);
  }

  /**
   * {@inheritdoc}
   */
  public function increment($amount = 1.0) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    parent::increment($amount);
  }

}

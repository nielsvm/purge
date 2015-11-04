<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounter.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Capacity;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface;
use Drupal\purge\Plugin\Purge\Purger\Capacity\Counter;

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
  public function get() {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    return parent::get();
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    parent::set($value);
    $this->state->set($this->id, $value);
  }

  /**
   * Overwrite the counter value if the object already exists in state storage.
   */
  public function setFromState() {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setStateAndId() has to be called!');
    }
    $this->value = (int) $this->state->get($this->id, $this->value);
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

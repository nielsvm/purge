<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\ResourcePersistingCounter.
 */

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface;
use Drupal\purge\Plugin\Purge\Purger\ResourceCounter;

/**
 * Provides a numeric counter stored in state storage.
 */
class ResourcePersistingCounter extends ResourceCounter implements ResourceCounterInterface {

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
      throw new BadBehaviorException('::setState() has to be called first!');
    }
    return parent::get();
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setState() has to be called first!');
    }
    parent::set($value);
    $this->state->set($this->id, $value);
  }

  /**
   * Overwrite the counter value if the object already exists in state storage.
   */
  public function setFromState() {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setState() has to be called first!');
    }
    $this->value = (int) $this->state->get($this->id, $this->value);
  }

  /**
   * Inject the state API for storing the counter persistently.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function setState(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($amount = 1) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setState() has to be called first!');
    }
    parent::decrement($amount);
  }

  /**
   * {@inheritdoc}
   */
  public function increment($amount = 1) {
    if (is_null($this->state)) {
      throw new BadBehaviorException('::setState() has to be called first!');
    }
    parent::increment($amount);
  }

}

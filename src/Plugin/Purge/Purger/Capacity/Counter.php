<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Capacity\Counter.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Capacity;

use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface;

/**
 * Provides a numeric counter.
 */
class Counter implements CounterInterface {

  /**
   * The value of the counter.
   *
   * @var float
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function __construct($value = 0.0) {
    $this->set($value);
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getInteger() {
    return (int) $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
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
  public function decrement($amount = 1.0) {
    if (!(is_float($amount) || is_int($amount))) {
      throw new BadBehaviorException('Given $amount is not a integer or float.');
    }
    if (is_int($amount)) {
      $amount = (float) $amount;
    }
    if (!($amount > 0.0)) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    $new = $this->value - $amount;
    if ($new < 0.0) {
      throw new BadBehaviorException('Given $amount causes negative counter.');
    }
    $this->set($new);
  }

  /**
   * {@inheritdoc}
   */
  public function increment($amount = 1.0) {
    if (!(is_float($amount) || is_int($amount))) {
      throw new BadBehaviorException('Given $amount is not a integer or float.');
    }
    if (is_int($amount)) {
      $amount = (float) $amount;
    }
    if (!($amount > 0.0)) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    $this->set($this->value + $amount);
  }

}

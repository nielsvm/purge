<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\ResourceCounter.
 */

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;

/**
 * Provides a numeric counter.
 */
class ResourceCounter implements ResourceCounterInterface {

  /**
   * A unique identifier which describes this counter.
   *
   * @var string
   */
  protected $id;

  /**
   * The value of the counter.
   *
   * @var int
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function __construct($id, $value = 0) {
    if (empty($id)) {
      throw new BadBehaviorException('Given $id parameter is empty.');
    }
    if (!is_int($value)) {
      throw new BadBehaviorException('Given $value is not a integer.');
    }
    if ($value < 0) {
      throw new BadBehaviorException('Given $value can only be positive.');
    }
    $this->value = $value;
    $this->id = $id;
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
  public function set($value) {
    if (!is_int($value)) {
      throw new BadBehaviorException('Given $value is not a integer.');
    }
    if ($value < 0) {
      throw new BadBehaviorException('Given $value can only be positive.');
    }
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($amount = 1) {
    if (!is_int($amount)) {
      throw new BadBehaviorException('Given $amount is not a integer.');
    }
    if ($amount < 1) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    $new = $this->value - $amount;
    if ($new < 0) {
      throw new BadBehaviorException('Given $amount causes negative counter.');
    }
    $this->set($new);
  }

  /**
   * {@inheritdoc}
   */
  public function increment($amount = 1) {
    if (!is_int($amount)) {
      throw new BadBehaviorException('Given $amount is not a integer.');
    }
    if ($amount < 1) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    $this->set($this->value + $amount);
  }

}

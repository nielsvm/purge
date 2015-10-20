<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Capacity;

/**
 * Describes a numeric counter.
 */
interface CounterInterface {

  /**
   * Construct a counter object.
   *
   * @param int $value
   *   The initial positive number the counter starts its life with.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $value is negative or not a integer.
   */
  public function __construct($value = 0);

  /**
   * Get the current value.
   *
   * @return int
   *   The numeric value of the counter.
   */
  public function get();

  /**
   * Overwrite the counter value.
   *
   * @param int $value
   *   The new value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $value is not a integer or when it is negative.
   */
  public function set($value);

  /**
   * Decrease the counter.
   *
   * @param int $amount
   *   Numeric amount to subtract from the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a integer, when it is zero or when the current
   *   counter value becomes negative.
   */
  public function decrement($amount = 1);

  /**
   * Increase the counter.
   *
   * @param int $amount
   *   Numeric amount to add up to the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a integer or when it is invalid.
   */
  public function increment($amount = 1);

}

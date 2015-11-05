<?php

/**
 * @file
 * Contains \Drupal\purge\Counter\CounterInterface.
 */

namespace Drupal\purge\Counter;

/**
 * Describes a numeric counter.
 */
interface CounterInterface {

  /**
   * Construct a counter object.
   *
   * @param int|float $value
   *   The initial positive number the counter starts its life with.
   * @param bool $decrement
   *   Whether it is possible to call ::decrement() or not.
   * @param bool $increment
   *   Whether it is possible to call ::increment() or not.
   * @param bool $set
   *   Whether it is possible to call ::set() or not.
   */
  public function __construct($value = 0.0, $decrement = TRUE, $increment = TRUE, $set = TRUE);

  /**
   * Get the current value.
   *
   * @return float
   *   The numeric value of the counter.
   */
  public function get();

  /**
   * Get the current value as integer.
   *
   * @return int
   *   The numeric value of the counter, typecasted as int.
   */
  public function getInteger();

  /**
   * Overwrite the counter value.
   *
   * @param int|float $value
   *   The new value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $value is not a integer, float or when it is negative.
   * @throws \LogicException
   *   Thrown when the object got created without set permission.
   */
  public function set($value);

  /**
   * Decrease the counter.
   *
   * @param int|float $amount
   *   Numeric amount to subtract from the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a float, integer, when it is zero/negative or
   *   when the current counter value becomes negative.
   * @throws \LogicException
   *   Thrown when the object got created without decrement permission.
   */
  public function decrement($amount = 1.0);

  /**
   * Increase the counter.
   *
   * @param int|float $amount
   *   Numeric amount to add up to the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a float, integer, when it is zero/negative.
   * @throws \LogicException
   *   Thrown when the object got created without increment permission.
   */
  public function increment($amount = 1.0);

}

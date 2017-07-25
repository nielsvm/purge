<?php

namespace Drupal\purge\Counter;

use Drupal\purge\Counter\CounterInterface;

/**
 * Describes a numeric counter that can be stored elsewhere.
 */
interface PersistentCounterInterface extends CounterInterface {

  /**
   * Set the callback that gets called when writes occur.
   *
   * The callback is called every time the counter changes value. The single
   * only parameter passed to your callable is $value, you can use PHP's use
   * statement to make any local variables available to it.
   *
   * @param callable $callback
   *   Any PHP callable.
   */
  public function setWriteCallback(callable $callback);

}

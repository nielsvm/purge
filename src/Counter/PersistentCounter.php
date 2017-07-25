<?php

namespace Drupal\purge\Counter;

use Drupal\purge\Counter\PersistentCounterInterface;
use Drupal\purge\Counter\Counter;

/**
 * Provides a numeric counter that can be stored elsewhere.
 */
class PersistentCounter extends Counter implements PersistentCounterInterface {

  /**
   * The callback that gets called when writes occur.
   *
   * @var null|callable
   */
  protected $callback;

  /**
   * {@inheritdoc}
   */
  protected function setDirectly($value) {
    parent::setDirectly($value);
    if (!is_null($this->callback)) {
      $callback = $this->callback;
      $callback($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setWriteCallback(callable $callback) {
    $this->callback = $callback;
  }

}

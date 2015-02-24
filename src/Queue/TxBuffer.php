<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\TxBuffer.
 */

namespace Drupal\purge\Queue;

use Drupal\purge\Invalidation\PluginInterface as Invalidation;
use Drupal\purge\Queue\TxBufferInterface;

/**
 * Provides the transaction buffer.
 */
class TxBuffer implements TxBufferInterface {

  /**
   * Instances listing holding copies of each Invalidation object.
   *
   * @var \Drupal\purge\Invalidation\PluginInterface[]
   */
  private $instances = [];

  /**
   * Instance<->state map of each object in the buffer.
   *
   * @var \Drupal\purge\Invalidation\PluginInterface[]
   */
  private $states = [];

  /**
   * {@inheritdoc}
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/countable.count.php
   */
  public function count() {
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/class.iterator.php
   */
  public function current() {
    return current($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($invalidations) {
    if (!is_array($invalidations)) {
      $invalidations = [$invalidations];
    }
    foreach ($invalidations as $i) {
      unset($this->instances[$i->instance_id]);
      unset($this->states[$i->instance_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEverything() {
    $this->instances = [];
    $this->states = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFiltered($states) {
    if (!is_array($states)) {
      $states = [$states];
    }
    $results = [];
    foreach ($this->states as $instance_id => $state) {
      if (in_array($state, $states)) {
        $results[] = $this->instances[$instance_id];
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(Invalidation $invalidation) {
    if (!$this->has($invalidation)) {
      return NULL;
    }
    return $this->states[$invalidation->instance_id];
  }

  /**
   * {@inheritdoc}
   */
  public function has(Invalidation $invalidation) {
    return isset($this->instances[$invalidation->instance_id]);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.key.php
   */
  public function key() {
    return key($this->instances);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.next.php
   */
  public function next() {
    return next($this->instances);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.rewind.php
   */
  public function rewind() {
    return reset($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function set($invalidations, $state) {
    if (!is_array($invalidations)) {
      $invalidations = [$invalidations];
    }
    foreach ($invalidations as $i) {
      if (!$this->has($i)) {
        $this->instances[$i->instance_id] = $i;
      }
      $this->states[$i->instance_id] = $state;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.valid.php
   */
  public function valid() {
    return is_null(key($this->instances)) ? FALSE : TRUE;
  }

}

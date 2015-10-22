<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeQueue\MemoryQueue.
 */

namespace Drupal\purge\Plugin\PurgeQueue;

use Drupal\purge\Plugin\Purge\Queue\PluginInterface;
use Drupal\purge\Plugin\Purge\Queue\PluginBase;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\PluginInterface compliant volatile memory buffer queue.
 *
 * @warning
 * This queue does not extend core's Memory queue on purpose, as it does not
 * suit extending it very well nor does its lease time handling work.
 *
 * @PurgeQueue(
 *   id = "memory",
 *   label = @Translation("Memory"),
 *   description = @Translation("A non-persistent, per-request memory queue (not useful on production systems)."),
 * )
 */
class MemoryQueue extends PluginBase implements PluginInterface {

  /**
   * Whether the buffer has been initialized or not.
   */
  protected $bufferInitialized;

  /**
   * The internal buffer where all data is copied in.
   */
  protected $buffer;

  /**
   * Define constants for the array indiced in our buffer.
   */
  const DATA = 0;
  const EXPIRE = 1;
  const CREATED = 2;

  /**
   * Initialize the buffer.
   */
  private function bufferInitialize() {
    if (!$this->bufferInitialized) {
      $this->bufferInitialized = TRUE;
      $this->buffer = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $this->bufferInitialize();
    end($this->buffer);
    $id = key($this->buffer) + 1;
    $this->buffer[$id] = [
      SELF::DATA => serialize($data),
      SELF::EXPIRE => 0,
      SELF::CREATED => time(),
    ];
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $this->bufferInitialize();
    end($this->buffer);
    $id = key($this->buffer) + 1;
    $ids = [];
    foreach ($items as $data) {
      $this->buffer[$id] = [
        SELF::DATA => serialize($data),
        SELF::EXPIRE => 0,
        SELF::CREATED => time(),
      ];
      $ids[] = $id;
      $id++;
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    $this->bufferInitialize();
    return count($this->buffer);
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 3600, $id = NULL) {
    if ($id == NULL) {
      $this->bufferInitialize();
      reset($this->buffer);
      $id = key($this->buffer);
    }
    if (empty($this->buffer)) {
      return FALSE;
    }
    if (!isset($this->buffer[$id])) {
      return FALSE;
    }
    if (
      ($this->buffer[$id][SELF::EXPIRE] === 0)
      || ( ($this->buffer[$id][SELF::EXPIRE] !== 0)
        && (time() > $this->buffer[$id][SELF::EXPIRE]))
      ) {
      $this->buffer[$id][SELF::EXPIRE] = time() + $lease_time;
      $item = new \stdClass();
      $item->item_id = $id;
      $item->data = unserialize($this->buffer[$id][SELF::DATA]);
      $item->expire = $this->buffer[$id][SELF::EXPIRE];
      $item->created = $this->buffer[$id][SELF::CREATED];
      return $item;
    }
    else {
      $id++;
      return $this->claimItem($lease_time, $id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $items = [];
    for ($i = 1; $i <= $claims; $i++) {
      if (($item = $this->claimItem($lease_time, NULL)) === FALSE) {
        break;
      }
      $items[] = $item;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    $this->bufferInitialize();
    if (!isset($this->buffer[$item->item_id])) {
      return FALSE;
    }
    $this->buffer[$item->item_id][SELF::EXPIRE] = 0;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    $this->bufferInitialize();
    foreach ($items as $item) {
      $this->releaseItem($item);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    $this->bufferInitialize();
    if (!isset($this->buffer[$item->item_id])) {
      return FALSE;
    }
    unset($this->buffer[$item->item_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {
    $this->bufferInitialize();
    foreach ($items as $item) {
      $this->deleteItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    $this->bufferInitialize();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    $this->bufferInitialize();
    $this->buffer = [];
  }

}

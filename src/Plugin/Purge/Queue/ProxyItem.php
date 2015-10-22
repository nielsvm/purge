<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queue\ProxyItem.
 */

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\Exception\InvalidPropertyException;
use Drupal\purge\Plugin\Purge\Queue\TxBufferInterface;

/**
 * Provides a ProxyItem.
 */
class ProxyItem {

  /**
   * The proxied invalidation object.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   */
  protected $invalidation;

  /**
   * The actively used TxBuffer object by \Drupal\purge\Plugin\Purge\Queue\QueueService.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface
   */
  protected $buffer;

  /**
   * Describes the accessible properties and if they're RO (FALSE) or RW (TRUE).
   *
   * @var bool[string]
   */
  protected $properties = [
    'item_id' => TRUE,
    'created' => TRUE,
    'data' => FALSE,
  ];

  /**
   * The unique ID from \Drupal\Core\Queue\QueueInterface::createItem().
   *
   * @var mixed|null
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  private $item_id;

  /**
   * Purge specific data to be associated with the new task in the queue.
   *
   * @var mixed
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  private $data;

  /**
   * Timestamp when the item was put into the queue.
   *
   * @var mixed|null
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  private $created;

  /**
   * Constructs the ProxyItem object.
   *
   * Once constructed, these objects act as if they were natively created by
   * any of the \Drupal\Core\Queue\QueueInterface methods. The data properties
   * such as 'data', 'item_id' and 'created' are writeable and readable, but
   * under the hood interfacing with \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   * and \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface takes place.
   *
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   * @see \Drupal\Core\Queue\QueueInterface::deleteItem
   * @see \Drupal\Core\Queue\QueueInterface::releaseItem
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   Invalidation object being proxied.
   * @param \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface $buffer
   *   The actively used TxBuffer object by \Drupal\purge\Plugin\Purge\Queue\QueueService.
   */
  public function __construct(InvalidationInterface $invalidation, TxBufferInterface $buffer) {
    $this->invalidation = $invalidation;
    $this->buffer = $buffer;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php#object.get
   */
  public function __get($name) {
    if (!isset($this->properties[$name])) {
      throw new InvalidPropertyException("The property '$name' does not exist.");
    }

    // The 'data' property describes the purge queue item in such a way that
    // \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface is able to recreate it.
    if ($name === 'data') {
      return [
        $this->invalidation->getPluginId(),
        $this->invalidation->getState(),
        $this->invalidation->getExpression(),
      ];
    }

    // Else look up the properties using the buffer's property store.
    else {
      return $this->buffer->getProperty($this->invalidation, $name, NULL);
    }
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php#object.set
   */
  public function __set($name, $value) {
    if (!isset($this->properties[$name])) {
      throw new InvalidPropertyException("The property '$name' does not exist.");
    }
    if (!$this->properties[$name]) {
      throw new InvalidPropertyException("The property '$name' is read-only.");
    }
    $this->buffer->setProperty($this->invalidation, $name, $value);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableBase.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableInterface;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;
use Drupal\purge\Purgeable\InvalidPurgeablePropertyException;

/**
 * Provides an interface defining a purgeable.
 */
abstract class PurgeableBase implements PurgeableInterface {

  /**
   * Arbitrary string representing the thing that needs to be purged.
   *
   * @var \Drupal\purge\Purgeable\PurgeableBase
   */
  protected $representation;

  /**
   * The plugin @id found by the annotation scanner.
   *
   * @var \Drupal\purge\Purgeable\PurgeableBase
   */
  protected $pluginId;

  /**
   * A enumerator that describes the current state of this purgeable.
   */
  private $state = NULL;

  /**
   * Holds the virtual Queue API properties 'item_id', 'data', 'created'.
   */
  private $queueItemInfo = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($representation) {
    $this->representation = $representation;
    if (!is_string($representation)) {
      throw new InvalidStringRepresentationException(
        'The representation of the thing you want to purge is not a string.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->representation;
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    throw new InvalidPurgeablePropertyException(
      "You can not set '$name', use the setter methods.");
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (is_null($this->queueItemInfo)) {
      $this->initiatlizeQueueItemArray();
    }
    if (!in_array($name, $this->queueItemInfo['keys'])) {
      throw new InvalidPurgeablePropertyException(
        "The property '$name' does not exist.");
    }
    else {
      return $this->queueItemInfo[$name];
    }
  }

  /**
   * Initialize $this->queueItemInfo with its standard data.
   */
  private function initiatlizeQueueItemArray() {
    $this->queueItemInfo = array(
      'data' => array(
        $this->pluginId,
        $this->representation,
      ),
      'dedupeid' => $this->pluginId . ':' . $this->representation,
      'item_id' => NULL,
      'created' => NULL,
    );
    $this->queueItemInfo['keys'] = array_keys($this->queueItemInfo);
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemInfo($item_id, $created) {
    if (is_null($this->queueItemInfo)) {
      $this->initiatlizeQueueItemArray();
    }
    $this->queueItemInfo['item_id'] = $item_id;
    $this->queueItemInfo['created'] = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemId($item_id) {
    if (is_null($this->queueItemInfo)) {
      $this->initiatlizeQueueItemArray();
    }
    $this->queueItemInfo['item_id'] = $item_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemCreated($created) {
    if (is_null($this->queueItemInfo)) {
      $this->initiatlizeQueueItemArray();
    }
    $this->queueItemInfo['created'] = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    if (is_null($this->state)) {
      $this->state = SELF::STATE_NEW;
    }
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateString() {
    $mapping = array(
      SELF::STATE_NEW           => 'NEW',
      SELF::STATE_ADDING        => 'ADDING',
      SELF::STATE_ADDED         => 'ADDED',
      SELF::STATE_CLAIMED       => 'CLAIMED',
      SELF::STATE_PURGING       => 'PURGING',
      SELF::STATE_PURGED        => 'PURGED',
      SELF::STATE_PURGEFAILED   => 'PURGEFAILED',
      SELF::STATE_RELEASING     => 'RELEASING',
      SELF::STATE_RELEASED      => 'RELEASED',
      SELF::STATE_DELETING      => 'DELETING',
      SELF::STATE_DELETED       => 'DELETED',
    );
    return $mapping[$this->getState()];
  }
}

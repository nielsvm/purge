<?php

/**
 * @file
 * Contains \Drupal\purge\Invalidation\PluginBase.
 */

namespace Drupal\purge\Invalidation;

use Drupal\Core\Plugin\PluginBase as CorePluginBase;
use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Invalidation\Exception\InvalidPropertyException;
use Drupal\purge\Invalidation\Exception\InvalidStateException;

/**
 * Base invalidation type: which instructs the purger what to invalidate.
 */
abstract class PluginBase extends CorePluginBase implements PluginInterface {

  /**
   * Mixed expression (or NULL) that describes what needs to be invalidated.
   *
   * @var mixed|null
   */
  protected $expression;

  /**
   * A enumerator that describes the current state of this invalidation.
   */
  private $state = NULL;

  /**
   * Holds the virtual Queue API properties 'item_id', 'data', 'created'.
   */
  private $queueItemInfo = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct([], $plugin_id, $plugin_definition);

    // Store the given expression key (can be NULL) and validate it thereafter.
    $this->expression = $configuration['expression'];
    $this->validateExpression();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return is_null($this->expression) ? '' : $this->expression;
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    throw new InvalidPropertyException(
      "You can not set '$name', use the setter methods.");
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (is_null($this->queueItemInfo)) {
      $this->initializeQueueItemArray();
    }
    if (!in_array($name, $this->queueItemInfo['keys'])) {
      throw new InvalidPropertyException(
        "The property '$name' does not exist.");
    }
    else {
      return $this->queueItemInfo[$name];
    }
  }

  /**
   * Initialize $this->queueItemInfo with its standard data.
   */
  private function initializeQueueItemArray() {
    $this->queueItemInfo = [
      'data' => sprintf('%s>%s', $this->pluginId, $this->expression),
      'item_id' => NULL,
      'created' => NULL,
    ];
    $this->queueItemInfo['keys'] = array_keys($this->queueItemInfo);
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemInfo($item_id, $created) {
    if (is_null($this->queueItemInfo)) {
      $this->initializeQueueItemArray();
    }
    $this->queueItemInfo['item_id'] = $item_id;
    $this->queueItemInfo['created'] = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemId($item_id) {
    if (is_null($this->queueItemInfo)) {
      $this->initializeQueueItemArray();
    }
    $this->queueItemInfo['item_id'] = $item_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemCreated($created) {
    if (is_null($this->queueItemInfo)) {
      $this->initializeQueueItemArray();
    }
    $this->queueItemInfo['created'] = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    if (!is_int($state)) {
      throw new InvalidStateException('$state not an integer!');
    }
    if (($state < 0) || ($state > 10)) {
      throw new InvalidStateException('$state is out of range!');
    }
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
    $mapping = [
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
    ];
    return $mapping[$this->getState()];
  }

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $plugin_id = $this->getPluginId();
    $d = $this->getPluginDefinition();
    if ($d['expression_required'] && is_null($this->expression)) {
      throw new MissingExpressionException("Invalidating by $plugin_id requires an expression.");
    }
    elseif ($d['expression_required'] && empty($this->expression) && !$d['expression_can_be_empty']) {
      throw new InvalidExpressionException("Cannot invalidate by $plugin_id with empty expression.");
    }
    elseif (!$d['expression_required'] && !is_null($this->expression)) {
      throw new InvalidExpressionException("Invalidating by $plugin_id requires no expression.");
    }
    elseif (!is_null($this->expression) && !is_string($this->expression) && $d['expression_must_be_string']) {
      throw new InvalidExpressionException("Cannot invalidate by $plugin_id without string expression.");
    }
  }
}

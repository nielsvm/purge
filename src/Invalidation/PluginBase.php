<?php

/**
 * @file
 * Contains \Drupal\purge\Invalidation\PluginBase.
 */

namespace Drupal\purge\Invalidation;

use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * Unique integer ID for this object instance (during runtime).
   *
   * @var int
   */
  protected $instance_id;

  /**
   * Holds the virtual Queue API properties 'item_id', 'data', 'created'.
   */
  private $queue_info = NULL;

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
   * Constructs a \Drupal\purge\Invalidation\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param int $instance_id
   *   Unique integer ID for this object instance (during runtime).
   * @param mixed|null $expression
   *   Value - usually string - that describes the kind of invalidation, NULL
   *   when the type of invalidation doesn't require $expression. Types usually
   *   validate the given expression and throw exceptions for bad input.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $instance_id, $expression) {
    parent::__construct([], $plugin_id, $plugin_definition);
    $this->instance_id = $instance_id;
    $this->expression = $expression;
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
    if (is_null($this->queue_info)) {
      $this->initializeQueueItemArray();
    }
    if ($name === 'instance_id') {
      return $this->instance_id;
    }
    if (!in_array($name, $this->queue_info['keys'])) {
      throw new InvalidPropertyException(
        "The property '$name' does not exist.");
    }
    else {
      return $this->queue_info[$name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      [],
      $plugin_id,
      $plugin_definition,
      $configuration['instance_id'],
      $configuration['expression']
    );
  }

  /**
   * Initialize $this->queue_info with its standard data.
   */
  private function initializeQueueItemArray() {
    $this->queue_info = [
      'data' => sprintf('%s>%s', $this->pluginId, $this->expression),
      'item_id' => NULL,
      'created' => NULL,
    ];
    $this->queue_info['keys'] = array_keys($this->queue_info);
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemInfo($item_id, $created) {
    if (is_null($this->queue_info)) {
      $this->initializeQueueItemArray();
    }
    $this->queue_info['item_id'] = $item_id;
    $this->queue_info['created'] = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemId($item_id) {
    if (is_null($this->queue_info)) {
      $this->initializeQueueItemArray();
    }
    $this->queue_info['item_id'] = $item_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueItemCreated($created) {
    if (is_null($this->queue_info)) {
      $this->initializeQueueItemArray();
    }
    $this->queue_info['created'] = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    if (!is_int($state)) {
      throw new InvalidStateException('$state not an integer!');
    }
    if (($state < 0) || ($state > 3)) {
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
      SELF::STATE_PURGING       => 'PURGING',
      SELF::STATE_PURGED        => 'PURGED',
      SELF::STATE_FAILED        => 'FAILED',
    ];
    return $mapping[$this->getState()];
  }

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $plugin_id = $this->getPluginId();
    $d = $this->getPluginDefinition();
    $topt = ['@type' => strtolower($d['label'])];
    if ($d['expression_required'] && is_null($this->expression)) {
      throw new MissingExpressionException($this->t("Argument required for @type invalidation.", $topt));
    }
    elseif ($d['expression_required'] && empty($this->expression) && !$d['expression_can_be_empty']) {
      throw new InvalidExpressionException($this->t("Argument required for @type invalidation.", $topt));
    }
    elseif (!$d['expression_required'] && !is_null($this->expression)) {
      throw new InvalidExpressionException($this->t("Argument given for @type invalidation.", $topt));
    }
    elseif (!is_null($this->expression) && !is_string($this->expression) && $d['expression_must_be_string']) {
      throw new InvalidExpressionException($this->t("String argument required for @type invalidation.", $topt));
    }
  }
}

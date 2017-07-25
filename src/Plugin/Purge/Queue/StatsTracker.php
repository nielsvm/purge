<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Counter\PersistentCounter;
use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;

/**
 * Provides the statistics tracker.
 */
class StatsTracker implements StatsTrackerInterface {

  /**
   * Buffer of values that need to be written back to state storage. Items
   * present in the buffer take priority over state data.
   *
   * @var float[]
   */
  protected $buffer = [];

  /**
   * Loaded counter instances.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface[]
   */
  protected $counters = [];

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Mapping of counter objects and state key names.
   *
   * @var string[]
   */
  protected $stateKeys = [
    'claimed' => 'purge_queue_claimed',
    'deleted' => 'purge_queue_deleted',
    'total' => 'purge_queue_total',
  ];

  /**
   * Construct a statistics tracker.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Initialize the counter instances.
   */
  protected function initializeCounters() {
    if (!empty($this->counters)) {
      return;
    }

    // Prefetch counter values from either the local buffer or the state API.
    $values = $this->state->getMultiple($this->stateKeys);
    foreach ($this->stateKeys as $counter => $key) {
      if (isset($this->buffer[$key])) {
        $values[$key] = $this->buffer[$key];
      }
      if (!isset($values[$key])) {
        $values[$key] = 0;
      }

      // Instantiate (or overwrite) the counter objects and pass a closure as
      // write callback. The closure writes changed values to $this->buffer.
      $this->counters[$counter] = new PersistentCounter($values[$key]);
      $this->counters[$counter]->disableSet();
      $this->counters[$counter]->setWriteCallback($key, function ($id, $value) {
        $this->buffer[$id] = $value;
      });
    }

    // As deleted and total can only increase, disable decrementing on them.
    $this->counters['deleted']->disableDecrement();
    $this->counters['total']->disableDecrement();
  }

  /**
   * {@inheritdoc}
   */
  public function claimed() {
    $this->initializeCounters();
    return $this->counters['claimed'];
  }

  /**
   * {@inheritdoc}
   */
  public function deleted() {
    $this->initializeCounters();
    return $this->counters['deleted'];
  }

  /**
   * {@inheritdoc}
   */
  public function total() {
    $this->initializeCounters();
    return $this->counters['total'];
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {

    // When the buffer contains changes, write them to the state API in one go.
    if (count($this->buffer)) {
      $this->state->setMultiple($this->buffer);
      $this->buffer = [];
    }
  }

  /**
   * Wipe all statistics data.
   */
  public function wipe() {
    $this->buffer = [];
    $this->state->deleteMultiple($this->stateKeys);
    $this->counters = [];
    $this->initializeCounters();
  }

}

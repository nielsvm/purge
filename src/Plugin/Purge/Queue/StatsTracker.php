<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Counter\Counter;
use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;

/**
 * Provides the queue statistics tracker.
 */
class StatsTracker implements StatsTrackerInterface {

  /**
   * Loaded statistical counters.
   *
   * @var \Drupal\purge\Counter\CounterInterface[]
   */
  protected $instances = [];

  /**
   * Current iterator position.
   *
   * @var int
   * @ingroup iterator
   */
  protected $position = 0;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Buffer of counter values that need to be written back to state storage.
   *
   * @var float[]
   */
  protected $state_buffer = [];

  /**
   * Non-associative but keyed layout of the statistical counters loaded.
   *
   * @var string[]
   */
  protected $stats = [
    self::NUMBER_OF_ITEMS   => 'purge_queue_number_of_items',
    self::PROCESSING        => 'purge_queue_processing',
    self::TOTAL_FAILURES    => 'purge_queue_failures',
    self::TOTAL_SUCCESSES   => 'purge_queue_successes',
    self::TOTAL_UNSUPPORTED => 'purge_queue_unsupported',
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
  protected function initializeStatistics() {
    if (!empty($this->instances)) {
      return;
    }

    // Fetch all statistic values from the state API at once.
    $values = $this->state->getMultiple($this->stats);

    // Instantiate the persistent counters with the given values.
    foreach ($this->stats as $i => $statekey) {

      // Set a default as CounterInterface only understands integers.
      if ((!isset($values[$statekey])) || is_null($values[$statekey])) {
        $values[$statekey] = 0;
      }

      // Instantiate the counter and pass a write callback that puts written
      // values directly back into $this->state_buffer. At the end of this
      // request, ::destruct() will pick them up and save the values.
      $this->instances[$i] = new Counter($values[$statekey]);
      $this->instances[$i]->setWriteCallback(
        function ($value) use ($statekey) {
          $this->state_buffer[$statekey] = $value;
        }
      );
    }

    // Disable decrementing the totals, which only ever increase until reset.
    $this->instances[self::TOTAL_FAILURES]->disableDecrement();
    $this->instances[self::TOTAL_SUCCESSES]->disableDecrement();
    $this->instances[self::TOTAL_UNSUPPORTED]->disableDecrement();
  }

  /**
   * {@inheritdoc}
   * @ingroup countable
   */
  public function count() {
    $this->initializeStatistics();
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    $this->initializeStatistics();
    return $this->instances[self::NUMBER_OF_ITEMS];
  }

  /**
   * {@inheritdoc}
   */
  public function processing() {
    $this->initializeStatistics();
    return $this->instances[self::PROCESSING];
  }

  /**
   * {@inheritdoc}
   */
  public function totalFailures() {
    $this->initializeStatistics();
    return $this->instances[self::TOTAL_FAILURES];
  }

  /**
   * {@inheritdoc}
   */
  public function totalSuccesses() {
    $this->initializeStatistics();
    return $this->instances[self::TOTAL_SUCCESSES];
  }

  /**
   * {@inheritdoc}
   */
  public function totalUnsupported() {
    $this->initializeStatistics();
    return $this->instances[self::TOTAL_UNSUPPORTED];
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {

    // When the buffer contains changes, write them to the state API in one go.
    if (count($this->state_buffer)) {
      print_r($this->state_buffer);
      $this->state->setMultiple($this->state_buffer);
      $this->state_buffer = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetTotals() {
    $this->totalFailures()->set(0);
    $this->totalSuccesses()->set(0);
    $this->totalUnsupported()->set(0);
  }

  /**
   * @ingroup iterator
   */
  public function current() {
    $this->initializeStatistics();
    if ($this->valid()) {
      return $this->instances[$this->position];
    }
    return FALSE;
  }

  /**
   * @ingroup iterator
   */
  public function key() {
    $this->initializeStatistics();
    return $this->position;
  }

  /**
   * @ingroup iterator
   */
  public function next() {
    $this->initializeStatistics();
    ++$this->position;
  }

  /**
   * @ingroup iterator
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * @ingroup iterator
   */
  public function valid() {
    $this->initializeStatistics();
    return isset($this->instances[$this->position]);
  }
}

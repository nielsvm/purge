<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queue\StatsTracker.
 */

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Counter\PersistentCounter;
use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;

/**
 * Provides the statistics tracker.
 */
class StatsTracker implements StatsTrackerInterface {

  /**
   * The counter tracking the amount of failed invalidations.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $counterFailed;

  /**
   * The counter tracking invalidations that were not supported.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $counterNotSupported;

  /**
   * The counter tracking currently active multi-step invalidations.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $counterProcessing;

  /**
   * The counter tracking the amount of succeeded invalidations.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $counterSucceeded;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * {@inheritdoc}
   */
  public function counterFailed() {
    $this->initializeCounters();
    return $this->counterFailed;
  }

  /**
   * {@inheritdoc}
   */
  public function counterNotSupported() {
    $this->initializeCounters();
    return $this->counterNotSupported;
  }

  /**
   * {@inheritdoc}
   */
  public function counterProcessing() {
    $this->initializeCounters();
    return $this->counterProcessing;
  }

  /**
   * {@inheritdoc}
   */
  public function counterSucceeded() {
    $this->initializeCounters();
    return $this->counterSucceeded;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    // to be implemented.
  }

  /**
   * Initialize the persistent counters.
   */
  protected function initializeCounters() {
    if (is_null($this->counterFailed)) {

      // Mapping of counter variables and their state API ID's.
      $counters = [
        'counterFailed' => 'purge_counter_failed',
        'counterSucceeded' => 'purge_counter_succeeded',
        'counterProcessing' => 'purge_counter_processing',
        'counterNotSupported' => 'purge_counter_notsupported'];
      $values = $this->state->getMultiple($counters);

      // Spin up the instances and pass on the state object.
      foreach ($counters as $counter => $id) {
        if (isset($values[$id])) {
          $this->$counter = new PersistentCounter($values[$id]);
        }
        else {
          $this->$counter = new PersistentCounter();
        }
        $this->$counter->setStateAndId($this->state, $id);
      }
    }
  }

  /**
   * In case PHP's destructor gets called, call our own destruct.
   */
  function __destruct() {
    $this->destruct();
  }

}

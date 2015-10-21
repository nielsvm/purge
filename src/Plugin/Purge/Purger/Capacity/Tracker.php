<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Capacity\Tracker.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Capacity;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface;
use Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounter;
use Drupal\purge\Plugin\Purge\Purger\Capacity\Counter;

/**
 * Provides a capacity tracker.
 */
class Tracker implements TrackerInterface {

  /**
   * The counter tracking the remaining number of allowed cache invalidations
   * during the remainder of Drupal's request lifetime. When it holds 0, no more
   * cache invalidations can take place.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface
   */
  protected $counterLimit;

  /**
   * The counter tracking the amount of failed invalidations.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterFailed;

  /**
   * The counter tracking the amount of succeeded invalidations.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterPurged;

  /**
   * The counter tracking currently purging multi-step invalidations.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterPurging;

  /**
   * The counter tracking failed invalidations that weren't supported.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterUnsupported;

  /**
   * The number of invalidations that can be processed under ideal conditions.
   *
   * @var int
   */
  protected $idealConditionsLimit;

  /**
   * The maximum number of seconds available to cache invalidation. Zero means
   * that PHP has no fixed execution time limit, for instance on the CLI.
   *
   * @var int
   */
  protected $maxExecutionTime;

  /**
   * Holds all loaded purgers plugins.
   *
   * @var \Drupal\purge\Purger\PluginInterface[]
   */
  protected $purgers;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The maximum number of seconds - as a float - it takes all purgers to
   * process a single cache invalidation (regardless of type).
   *
   * @var float
   */
  protected $timeHint;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $purgers, StateInterface $state) {
    $this->purgers = $purgers;
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
  public function counterPurged() {
    $this->initializeCounters();
    return $this->counterPurged;
  }

  /**
   * {@inheritdoc}
   */
  public function counterPurging() {
    $this->initializeCounters();
    return $this->counterPurging;
  }

  /**
   * {@inheritdoc}
   */
  public function counterUnsupported() {
    $this->initializeCounters();
    return $this->counterUnsupported;
  }

  /**
   * {@inheritdoc}
   */
  public function decrementLimit($amount = 1) {
    $this->getLimit();
    if (!is_int($amount)) {
      throw new BadBehaviorException('Given $amount is not a integer.');
    }
    if ($amount < 1) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    try {
      $this->counterLimit->decrement($amount);
    } catch (BadBehaviorException $e) {
      $this->counterLimit->set(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    if (is_null($this->idealConditionsLimit)) {

      // Fail early when no purgers are loaded.
      if (empty($this->purgers)) {
        $this->idealConditionsLimit = 0;
        return $this->idealConditionsLimit;
      }

      // Find the lowest emitted ideal conditions limit.
      $this->idealConditionsLimit = [];
      foreach ($this->purgers as $purger) {
        $this->idealConditionsLimit[] = $purger->getIdealConditionsLimit();
      }
      $this->idealConditionsLimit = (int) min($this->idealConditionsLimit);
    }
    return $this->idealConditionsLimit;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit() {
    if (is_null($this->counterLimit)) {

      // Fail early when no purgers are loaded.
      if (empty($this->purgers)) {
        $this->counterLimit = new Counter(0);
        return $this->counterLimit->get();
      }

      // When the maximum execution time is zero, Drupal is given a lot more
      // power to finish its request. However, we cannot just run for several
      // hours, therefore we take the lowest ideal conditions limit as value.
      $max_execution_time = $this->getMaxExecutionTime();
      if ($max_execution_time === 0) {
        $this->counterLimit = new Counter($this->getIdealConditionsLimit());
        return $this->counterLimit->get();
      }

      // Though in most conditions, we do have a max execution time to deal with
      // and therefore we divide it through the time hint we calculated.
      $runtime_limit = intval($max_execution_time / $this->getTimeHint());

      // In the rare case the runtime limit exceeds the ideal conditions limit,
      // we lower the runtime limit to the ideal conditions limit.
      if ($runtime_limit > $this->getIdealConditionsLimit()) {
        $runtime_limit = $this->getIdealConditionsLimit();
      }

      // Wrap the runtime limit into a (non-persistent) counter object.
      $this->counterLimit = new Counter($runtime_limit);
    }

    // We don't expose the object but just return its value. This protects us
    // from public calls attempting to overwrite or reset our limit.
    return $this->counterLimit->get();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxExecutionTime() {
    if (is_null($this->maxExecutionTime)) {
      $this->maxExecutionTime = (int) ini_get('max_execution_time');
      // When the limit isn't infinite, chop 20% off for the rest of Drupal.
      if ($this->maxExecutionTime !== 0) {
        $this->maxExecutionTime = intval(0.8 * $this->maxExecutionTime);
      }
    }
    return $this->maxExecutionTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    if (is_null($this->timeHint)) {

      // Fail early when no purgers are loaded.
      if (empty($this->purgers)) {
        $this->timeHint = 1.0;
        return $this->timeHint;
      }

      // Iterate the purgers and gather ::getTimeHint()'s results.
      $hint_per_type = [];
      foreach ($this->purgers as $id => $purger) {
        $plugin_id = $purger->getPluginId();
        $hint = $purger->getTimeHint();

        // Be strict about what values are accepted, better throwing exceptions
        // than having a crashing website because it is trashing.
        if (!is_float($hint)) {
          $method = sprintf("%s::getTimeHint()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method did not return a floating point value.");
        }
        if ($hint < 0.2) {
          $method = sprintf("%s::getTimeHint()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $hint, a value lower than 0.2.");
        }
        if ($hint > 10.0) {
          $method = sprintf("%s::getTimeHint()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $hint, a value higher than 10.0.");
        }

        // Group the values by invalidation type and add up.
        foreach ($purger->getTypes() as $type) {
          if (!isset($hint_per_type[$type])) {
            $hint_per_type[$type] = 0.0;
          }
          $hint_per_type[$type] = $hint_per_type[$type] + $hint;
        }
      }

      // Take the highest time hint, which means we take the least risk.
      $this->timeHint = max($hint_per_type);
    }
    return $this->timeHint;
  }

  /**
   * Initialize the persistent counters.
   */
  protected function initializeCounters() {
    if (is_null($this->counterFailed)) {

      // Mapping of counter variables and their state API ID's.
      $counters = [
        'counterFailed' => 'purge_counter_failed',
        'counterPurged' => 'purge_counter_purged',
        'counterPurging' => 'purge_counter_purging',
        'counterUnsupported' => 'purge_counter_unsupported'];
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

}

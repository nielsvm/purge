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
   * The counter tracking the amount of failed invalidations.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterFailed;

  /**
   * The counter tracking invalidations that were not supported.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterNotSupported;

  /**
   * The counter tracking currently active multi-step invalidations.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterProcessing;

  /**
   * The counter tracking the amount of succeeded invalidations.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\PersistentCounterInterface
   */
  protected $counterSucceeded;

  /**
   * Associative array of cooldown times per purger, as int values.
   *
   * @var float[]
   */
  protected $cooldownTimes;

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
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[]
   */
  protected $purgers;

  /**
   * The counter tracking the remaining number of allowed cache invalidations
   * during the remainder of Drupal's request lifetime. When it holds 0, no more
   * cache invalidations can take place.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface
   */
  protected $remainingInvalidationsLimit;

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
   * The maximum number of seconds - as a float - it takes each purger to
   * process a single cache invalidation.
   *
   * @var float[]
   */
  protected $timeHints;

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
  public function decrementLimit($amount = 1) {
    $this->getRemainingInvalidationsLimit();
    if (!is_int($amount)) {
      throw new BadBehaviorException('Given $amount is not a integer.');
    }
    if ($amount < 1) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    try {
      $this->remainingInvalidationsLimit->decrement($amount);
    } catch (BadBehaviorException $e) {
      $this->remainingInvalidationsLimit->set(0);
    }
  }

  /**
   * Iterate all purgers and gather ::getTimeHint() information.
   */
  protected function gatherTimeHints() {
    if (is_null($this->timeHints)) {
      $this->timeHints = [];
      if (count($this->purgers)) {
        foreach ($this->purgers as $id => $purger) {
          $hint = $purger->getTimeHint();

          // Be strict about what values are accepted, better throwing exceptions
          // than having a crashing website because it is trashing.
          if (!is_float($hint)) {
            $method = sprintf("%s::getTimeHint()", get_class($purger));
            throw new BadPluginBehaviorException(
              "$method did not return a floating point value.");
          }
          if ($hint < 0.1) {
            $method = sprintf("%s::getTimeHint()", get_class($purger));
            throw new BadPluginBehaviorException(
              "$method returned $hint, a value lower than 0.1.");
          }
          if ($hint > 10.0) {
            $method = sprintf("%s::getTimeHint()", get_class($purger));
            throw new BadPluginBehaviorException(
              "$method returned $hint, a value higher than 10.0.");
          }
          $this->timeHints[$id] = $hint;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCooldownTime($purger_instance_id) {
    if (is_null($this->cooldownTimes)) {
      $this->cooldownTimes = [];
      foreach ($this->purgers as $id => $purger) {
        $cooldown_time = $purger->getCooldownTime();
        if (!is_float($cooldown_time)) {
          $method = sprintf("%s::getCooldownTime()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method did not return a floating point value.");
        }
        if ($cooldown_time < 0.0) {
          $method = sprintf("%s::getCooldownTime()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $cooldown_time, a value lower than 0.0.");
        }
        if ($cooldown_time > 3.0) {
          $method = sprintf("%s::getCooldownTime()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $cooldown_time, a value higher than 3.0.");
        }
        $this->cooldownTimes[$id] = $cooldown_time;
      }
    }
    if (!isset($this->cooldownTimes[$purger_instance_id])) {
      throw new BadBehaviorException("Instance id '$purger_instance_id' does not exist!");
    }
    return $this->cooldownTimes[$purger_instance_id];
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
        $limit = $purger->getIdealConditionsLimit();
        if ((!is_int($limit)) || ($limit < 1)) {
          $method = sprintf("%s::getIdealConditionsLimit()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $limit, which has to be a integer higher than 0.");
        }
        $this->idealConditionsLimit[] = $limit;
      }
      $this->idealConditionsLimit = (int) min($this->idealConditionsLimit);
    }
    return $this->idealConditionsLimit;
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
  public function getRemainingInvalidationsLimit() {
    if (is_null($this->remainingInvalidationsLimit)) {

      // Fail early when no purgers are loaded.
      if (empty($this->purgers)) {
        $this->remainingInvalidationsLimit = new Counter(0, TRUE, FALSE, FALSE);
        return $this->remainingInvalidationsLimit->getInteger();
      }

      // When the maximum execution time is zero, Drupal is given a lot more
      // power to finish its request. However, we cannot just run for several
      // hours, therefore we take the lowest ideal conditions limit as value.
      $max_execution_time = $this->getMaxExecutionTime();
      if ($max_execution_time === 0) {
        $limit = $this->getIdealConditionsLimit();
        $this->remainingInvalidationsLimit = new Counter($limit, TRUE, FALSE, FALSE);
        return $this->remainingInvalidationsLimit->getInteger();
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
      $this->remainingInvalidationsLimit = new Counter($runtime_limit, TRUE, FALSE, FALSE);
    }

    // We don't expose the object but just return its value. This protects us
    // from public calls attempting to overwrite or reset our limit.
    return $this->remainingInvalidationsLimit->getInteger();
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    if (is_null($this->timeHint)) {
      $this->gatherTimeHints();
      $this->timeHint = 1.0;
      if (count($this->timeHints)) {
        $hints_per_type = [];

        // Iterate all hints and group the values by invalidation type.
        foreach ($this->timeHints as $id => $hint) {
          foreach ($this->purgers[$id]->getTypes() as $type) {
            if (!isset($hint_per_type[$type])) {
              $hints_per_type[$type] = 0.0;
            }
            $hints_per_type[$type] = $hints_per_type[$type] + $hint;
          }
        }

        // Find the highest time, so that the system takes the least risk.
        $this->timeHint = max($hints_per_type);
      }
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

}

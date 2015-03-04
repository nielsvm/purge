<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PluginBase.
 */

namespace Drupal\purge\Purger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase as CorePluginBase;
use Drupal\purge\Purger\PluginInterface;

/**
 * Provides a base class for all purgers - the cache invalidation executors.
 */
abstract class PluginBase extends CorePluginBase implements PluginInterface {

  /**
   * Unique instance ID for this purger.
   *
   * @var string
   */
  protected $id;

  /**
   * The number of successfully processed invalidations for this instance.
   *
   * @var int
   */
  protected $numberPurged = 0;

  /**
   * The number of actively on-going purges.
   *
   * @var int
   */
  protected $numberPurging = 0;

  /**
   * The number of failed invalidations for this instance.
   *
   * @var int
   */
  protected $numberFailed = 0;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase derivative.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @throws \LogicException
   *   Thrown if $configuration['id'] is missing, see Purger\Service::createId.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!is_string($configuration['id']) || empty($configuration['id'])) {
      throw new \LogicException('Purger cannot be constructed without ID.');
    }
    $this->id = $configuration['id'];
    unset($configuration['id']);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    $max_execution_time = (int) ini_get('max_execution_time');
    $time_per_invalidation = $this->getClaimTimeHint();

    // Since we are in PluginBase, this limit is a wild guess as we have no idea
    // what derived purgers do and how they perform. Plugins that wipe directly
    // from memory on localhost, can do with much higher limits whereas slow
    // CDNs are likely to lower this quite a bit. Derivatives do have to
    // provide ::getClaimTimeHint, which is far more important and indicative.
    $limit = 100;

    // When PHP's max_execution_time equals 0, the system is given carte blanche
    // for how long it can run. Since looping endlessly is out of the question,
    // use a hard fixed limit.
    if ($max_execution_time === 0) {
      return $limit;
    }

    // But when it is not, we have to lower expectations to protect stability.
    $max_execution_time = intval(0.75 * $max_execution_time);

    // Now calculate the minimum of invalidations we should be able to process.
    $suggested = intval($max_execution_time / $time_per_invalidation);

    // In the case our conservative calculation would be higher than the set
    // limit, return the hard limit as our capacity limit.
    if ($suggested > $limit) {
      return (int) $limit;
    }
    else {
      return (int) $suggested;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    return $this->numberPurged;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    return $this->numberPurging;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFailed() {
    return $this->numberFailed;
  }

}

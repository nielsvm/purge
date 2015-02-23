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

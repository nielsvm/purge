<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PluginBase.
 */

namespace Drupal\purge\Purger;

use Drupal\purge\Purger\PluginInterface;

/**
 * Base class for all purgers.
 */
abstract class PluginBase implements PluginInterface {

  /**
   * The number of successfully processed purgeables for this instance.
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
   * The number of failed purgeables for this instance.
   *
   * @var int
   */
  protected $numberFailed = 0;

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

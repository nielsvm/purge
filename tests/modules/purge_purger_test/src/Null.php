<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Null.
 */

namespace Drupal\purge_purger_test;

use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Ever failing null purger backend.
 */
abstract class Null extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function delete() {}

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    $invalidation->setState(Invalidation::STATE_FAILED);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(Invalidation::STATE_FAILED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    return 1.0;
  }

}

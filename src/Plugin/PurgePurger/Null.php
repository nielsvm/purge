<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurger\Null.
 */

namespace Drupal\purge\Plugin\PurgePurger;

use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * API-compliant null purger back-end.
 *
 * This plugin is not intended for usage but gets loaded during module
 * installation, when configuration rendered invalid or when no other plugins
 * are available. Because its API compliant, Drupal won't crash visibly.
 *
 * @PurgePurger(
 *   id = "null",
 *   label = @Translation("Null"),
 *   description = @Translation("API-compliant null purger back-end."),
 * )
 */
class Null extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    $this->numberFailed += 1;
    $invalidation->setState(Invalidation::STATE_FAILED);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      $this->numberFailed += 1;
      $invalidation->setState(Invalidation::STATE_FAILED);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    return 1;
  }
}

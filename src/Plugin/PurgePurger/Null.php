<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurger\Dummy.
 */

namespace Drupal\purge\Plugin\PurgePurger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface as Purger;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * A \Drupal\purge\Purger\PluginInterface compliant dummy purger. This purger is
 * only loaded when no other purgers exist and serves as fall back plugin.
 *
 * @PurgePurger(
 *   id = "null",
 *   label = @Translation("Null backup"),
 *   description = @Translation("A purger that doesn't do anything."),
 * )
 */
class Null extends PluginBase implements Purger {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function purge(Purgeable $purgeable) {
    $this->numberFailed += 1;
    $purgeable->setState(Purgeable::STATE_PURGEFAILED);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    foreach ($purgeables as $purgeable) {
      $this->numberFailed += 1;
      $purgeable->setState(Purgeable::STATE_PURGEFAILED);
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

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    return 0;
  }
}

<?php

/**
 * @file
 * Contains \Drupal\purge_noqueuer_test\PurgeNoqueuerTestServiceProvider.
 */

namespace Drupal\purge_noqueuer_test;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Removes Purge's built-in cache tags queuer when it causes hassle to tests.
 */
class PurgeNoqueuerTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('purge.queuers.cache_tags');
  }

}

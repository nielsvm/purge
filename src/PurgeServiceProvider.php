<?php

/**
 * @file
 * Contains \Drupal\purge\PurgeServiceProvider.
 */

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\purge\Processor\RegisterProcessorsCompilerPass;
use Drupal\purge\Queuer\RegisterQueuersCompilerPass;

/**
 * The Purge service provider.
 */
class PurgeServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Registers services tagged 'purge_processor' that process invalidations.
    $container->addCompilerPass(new RegisterProcessorsCompilerPass());
    // Registers services tagged 'purge_queuer' that propagate the queue.
    $container->addCompilerPass(new RegisterQueuersCompilerPass());
  }

}

<?php

/**
 * @file
 * Contains \Drupal\purge\PurgeServiceProvider.
 */

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\purge\Queuer\RegisterQueuersCompilerPass;

/**
 * The Purge service provider.
 */
class PurgeServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // RegisterQueuersCompilerPass registers services tagged 'purge_queuer'.
    $container->addCompilerPass(new RegisterQueuersCompilerPass());
  }

}

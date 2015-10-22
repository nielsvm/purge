<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queuer\RegisterQueuersCompilerPass.
 */

namespace Drupal\purge\Plugin\Purge\Queuer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Registers services tagged 'purge_queuer' as queuers.
 */
class RegisterQueuersCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('purge.queuers')) {
      return;
    }
    $queuers = [];
    foreach ($container->findTaggedServiceIds('purge_queuer') as $id => $attributes) {
      $queuers[] = $id;
    }
    $container->setParameter('purge_queuers', $queuers);
  }

}

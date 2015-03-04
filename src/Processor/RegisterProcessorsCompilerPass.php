<?php

/**
 * @file
 * Contains \Drupal\purge\Processor\RegisterProcessorsCompilerPass.
 */

namespace Drupal\purge\Processor;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Registers services tagged 'purge_processor' that process invalidations.
 */
class RegisterProcessorsCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('purge.processors')) {
      return;
    }
    $processors = [];
    foreach ($container->findTaggedServiceIds('purge_processor') as $id => $attributes) {
      $processors[] = $id;
    }
    $container->setParameter('purge_processors', $processors);
  }

}

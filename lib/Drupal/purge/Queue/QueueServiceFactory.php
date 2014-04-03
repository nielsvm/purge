<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueServiceFactory.
 */

namespace Drupal\purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Queue\QueueServiceFactoryInterface;
use Drupal\purge\Queue\InvalidQueueConfiguredException;
use Drupal\purge\Queue\QueueService;

/**
 * Provides the factory that creates the QueueService (holding queue plugin).
 */
class QueueServiceFactory extends PluginManagerBase implements QueueServiceFactoryInterface {

  /**
   * var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * An object containing the namespaces to look for plugin implementations.
   *
   * @var \Traversable
   */
  protected $namespaces;

  /**
   * The QueueService instance holding the loaded queue plugin.
   *
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $service;

  /**
   * The 'plugin' setting from 'purge.queue.yml' determining the queue to use.
   *
   * @var String
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $service_container) {
    $this->container = $service_container;
    $this->namespaces = $this->container->get('container.namespaces');
    $this->discovery = new AnnotatedClassDiscovery('Plugin/PurgeQueue', $this->namespaces, 'Drupal\purge\Annotation\PurgeQueue');
    $this->discovery = new CacheDecorator($this->discovery, 'purge_queue_types');
    $this->factory = new DefaultFactory($this->discovery);
    $this->plugin = $this->container
      ->get('config.factory')
      ->get('purge.queue')
      ->get('plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    $queue = new $plugin_class($this->container);
    $purge_purgeables = $this->container->get('purge.purgeables');
    return new QueueService($queue, $this->discovery, $purge_purgeables);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {

    // The first time this function is called we initialize the queue object.
    if (is_null($this->service)) {

      // Load the queue plugin as configured in purge.queue.yml.
      try {
        $this->service = $this->createInstance($this->plugin);
      } catch (PluginException $e) {
        throw new InvalidQueueConfiguredException(
          "The queue plugin '" . $this->plugin . "' does not exist.");
      }
    }

    // Return the instance as stored inside the factory.
    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  static public function getServiceInstance(ContainerInterface $service_container) {
    $factory = new QueueServiceFactory($service_container);
    return $factory->getInstance(array());
  }
}

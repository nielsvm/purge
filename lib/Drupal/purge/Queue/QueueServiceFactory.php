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
   * The plugin_detection setting for the default queue service.
   *
   * @var String
   */
  protected $plugin_detection;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $service_container) {
    $this->container = $service_container;
    $this->namespaces = $this->container->get('container.namespaces');
    $this->discovery = new AnnotatedClassDiscovery('Plugin/PurgeQueue', $this->namespaces);
    $this->discovery = new CacheDecorator($this->discovery, 'purge_queue_types');
    $this->factory = new DefaultFactory($this->discovery);
    $this->plugin_detection = $this->container
      ->get('config.factory')
      ->get('purge.plugin_detection')
      ->get('queue');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    $queue = new $plugin_class($this->container);
    $purgeable_factory = $this->container->get('purge.purgeable_factory');
    return new QueueService($queue, $purgeable_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {

    // The first time this function is called we initialize the queue object.
    if (is_null($this->service)) {

      // By default Purge is set to automatic detection and will pick the first.
      if ($this->plugin_detection === 'automatic') {
        $first_plugin = current($this->getDefinitions());
        $this->service = $this->createInstance($first_plugin['id']);
      }
      else {
        $plugin_name = $this->plugin_detection;
        try {
          $this->service = $this->createInstance($plugin_name);
        } catch (PluginException $e) {
          throw new InvalidQueueConfiguredException("The plugin \"$plugin_name\" does not exist.");
        }
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

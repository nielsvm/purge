<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\PurgePurger\Http.
 */

namespace Drupal\purge_purger_http\Plugin\PurgePurger;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Generic and highly configurable purger making HTTP requests.
 *
 * This purger best suits custom situations where reverse proxies or CDNs are
 * not supported by any other purger, or situations requiring very specific
 * HTTP request based actions to remotely wipe objects.
 *
 * @PurgePurger(
 *   id = "http",
 *   label = @Translation("HTTP Purger"),
 *   configform = "\Drupal\purge_purger_http\Form\ConfigurationForm",
 *   description = @Translation("Generic and highly configurable purger making HTTP requests, best suits custom configurations."),
 * )
 */
class Http extends PluginBase implements PluginInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Constructs the HTTP purger.
   *
   * @param \GuzzleHttp\Client $http_client
   *   An HTTP client that can perform remote requests.
   */
  function __construct(Client $http_client) {
    $this->client = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    throw new \Exception("Sorry, Not yet implemented!");
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    throw new \Exception("Sorry, Not yet implemented!");
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    throw new \Exception("Sorry, Not yet implemented!");
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    throw new \Exception("Sorry, Not yet implemented!");
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    throw new \Exception("Sorry, Not yet implemented!");
  }
}

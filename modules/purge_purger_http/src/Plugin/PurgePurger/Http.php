<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\PurgePurger\Http.
 */

namespace Drupal\purge_purger_http\Plugin\PurgePurger;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
 *   description = @Translation("Generic and highly configurable purger making HTTP requests, best suits custom configurations."),
 *   configform = "\Drupal\purge_purger_http\Form\ConfigurationForm",
 *   multi_instance = TRUE,
 * )
 */
class Http extends PluginBase implements PluginInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The ImmutableConfig object 'purge_purger_http.settings'.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs the HTTP purger.
   *
   * @param \GuzzleHttp\Client $http_client
   *   An HTTP client that can perform remote requests.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  function __construct(Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->client = $http_client;
    $this->config = $config_factory->get('purge_purger_http.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory')
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
    $exec_time_consumption = $this->config->get('execution_time_consumption');
    $time_per_request = $this->getClaimTimeHint();
    $max_execution_time = (int) ini_get('max_execution_time');
    $max_requests = $this->config->get('max_requests');

    // When PHP's max_execution_time equals 0, the system is given carte blanche
    // for how long it can run. Since looping endlessly is out of the question,
    // the capacity then limits at what $max_requests is set to.
    if ($max_execution_time === 0) {
      return $max_requests;
    }

    // But when it is not, we have to lower expectations to protect stability.
    $max_execution_time = intval($exec_time_consumption * $max_execution_time);

    // Now calculate the minimum of invalidations we should be able to process.
    $suggested = intval($max_execution_time / $time_per_request);

    // In the case our conservative calculation would be higher than the set
    // limit of requests, return the hard limit as our capacity limit.
    if ($suggested > $max_requests) {
      return (int) $max_request;
    }
    else {
      return (int) $suggested;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {

    // Take the HTTP timeout configured, add 10% margin and round up to seconds.
    return (int) ceil($this->config->get('timeout') * 1.1);
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    throw new \Exception("Sorry, Not yet implemented!");
  }

}

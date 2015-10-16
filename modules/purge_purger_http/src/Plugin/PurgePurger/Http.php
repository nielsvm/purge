<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\PurgePurger\Http.
 */

namespace Drupal\purge_purger_http\Plugin\PurgePurger;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Generic and highly configurable purger making HTTP requests.
 *
 * This purger best suits custom situations where reverse proxies or CDNs are
 * not supported by any other purger, or situations requiring very specific
 * HTTP request based actions to remotely wipe objects.
 *
 * @PurgePurger(
 *   id = "http",
 *   label = @Translation("HTTP"),
 *   description = @Translation("Generic and highly configurable purger making HTTP requests, best suits custom configurations."),
 *   configform = "\Drupal\purge_purger_http\Form\ConfigurationForm",
 *   types = {},
 *   multi_instance = TRUE,
 * )
 */
class Http extends PluginBase implements PluginInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The settings entity holding all configuration.
   *
   * @var \Drupal\purge_purger_http\Entity\HttpPurgerSettings
   */
  protected $settings;

  /**
   * Constructs the HTTP purger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = HttpPurgerSettings::load($this->getId());
    $this->client = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    HttpPurgerSettings::load($this->getId())->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    // @todo: this obviously needs to be implemented.
    $this->numberFailed += 1;
    $invalidation->setState(Invalidation::STATE_FAILED);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    // @todo: this obviously needs to be implemented.
    foreach ($invalidations as $invalidation) {
      $this->numberFailed += 1;
      $invalidation->setState(Invalidation::STATE_FAILED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    $exec_time_consumption = $this->settings->execution_time_consumption;
    $time_per_request = $this->getClaimTimeHint();
    $max_execution_time = (int) ini_get('max_execution_time');
    $max_requests = $this->settings->max_requests;

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
      return (int) $max_requests;
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
    return (int) ceil($this->settings->timeout * 1.1);
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    throw new \Exception("Sorry, Not yet implemented!");
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return [$this->settings->invalidationtype];
  }

}

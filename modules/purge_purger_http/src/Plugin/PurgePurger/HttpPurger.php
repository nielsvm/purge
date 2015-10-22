<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\PurgePurger\HttpPurger.
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
class HttpPurger extends PluginBase implements PluginInterface {

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
    $invalidation->setState(Invalidation::STATE_FAILED);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    // @todo: this obviously needs to be implemented.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(Invalidation::STATE_FAILED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    return $this->settings->max_requests;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    // Theoretically connection timeouts and general timeouts can add up, so
    // we add up our assumption of the worst possible time it takes as well.
    return $this->settings->connect_timeout + $this->settings->timeout;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return [$this->settings->invalidationtype];
  }

}

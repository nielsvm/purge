<?php

/**
 * @file
 * Contains \Drupal\purge_purger_varnishpoc\Plugin\PurgePurger\VarnishTagPurger.
 */

namespace Drupal\purge_purger_varnishpoc\Plugin\PurgePurger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Plugin\PurgeInvalidation\Tag;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Varnish cache tags purger.
 *
 * Requires the associated Varnish server to have a VCL configured to accept
 * BAN requests with a X-Drupal-Cache-Tags-Banned header.
 * See the README for details on the required VCL configuration.
 *
 * Drupal sends X-Drupal-Cache-Tags headers. Varnish caches Drupal's responses
 * with those headers. This purger sends X-Drupal-Cache-Tags-Banned headers (the
 * same header name, but with '-Banned' suffixed) to the desired Varnish
 * instances to invalidate the responses with those cache tags.
 *
 * @PurgePurger(
 *   id = "varnish_tag",
 *   label = @Translation("Varnish (cache tags)"),
 *   description = @Translation("Cache tags purger for Varnish."),
 *   configform = "Drupal\purge_purger_varnishpoc\Form\VarnishTagConfigForm",
 *   multi_instance = TRUE,
 * )
 */
class VarnishTagPurger extends PluginBase implements PluginInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The ImmutableConfig object 'purge_purger_varnishpoc.settings'.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs the Varnish purger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $http_client;
    $this->config = $config_factory->get('purge_purger_varnishpoc.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Sadly, Guzzle doesn't support detection of timeouts, its exceptions are not
   * granular enough.
   *
   * @see https://github.com/guzzle/guzzle/blob/5.0.0/src/Exception/RequestException.php
   * @see http://stackoverflow.com/questions/25661591/php-how-to-check-for-timeout-exception-in-guzzle-4
   */
  public function invalidate(Invalidation $invalidation) {

    // For now - until Purge only sends supported invalidation objects - mark
    // anything besides a tag as immediately failed.
    if (!$invalidation instanceof Tag) {
      $invalidation->setState(Invalidation::STATE_FAILED);
      $this->numberFailed += 1;
      return;
    }

    // When the URL setting is still empty, we also fail.
    if (empty($this->config->get('url'))) {
      $invalidation->setState(Invalidation::STATE_FAILED);
      $this->numberFailed += 1;
      return;
    }

    // Construct a Guzzle request.
    $options = [
      'timeout' => $this->config->get('timeout'),
      'connect_timeout' => $this->config->get('connect_timeout'),
    ];
    $request = $this->client->createRequest('BAN', $this->config->get('url'), $options)
      ->addHeader($this->config->get('header'), static::toClearRegex($invalidation));

    // Purge.
    $invalidation->setState(Invalidation::STATE_PURGING);
    try {
      $this->numberPurging++;
      $this->client->send($request);
      $invalidation->setState(Invalidation::STATE_PURGED);
      $this->numberPurging--;
      $this->numberPurged += 1;
    }
    catch (RequestException $e) {
      $invalidation->setState(Invalidation::STATE_FAILED);
      $this->numberFailed += 1;
    }
  }

  /**
   * Converts an array of cache tags to a Varnish-compatible regex.
   *
   * @param string[] $tags
   *   Cache tags.
   *
   * @return string
   *   String in the form of "(\Dtag1\D|\tag2\D|\D…\D|\DtagN\D)".
   *   e.g. given the tags array ['node:1', 'node_list'], returns the string
   *     "(\Dnode:1\D|\Dnode_list\D)"
   *
   * @see http://www.smashingmagazine.com/2014/04/23/cache-invalidation-strategies-with-varnish-cache/
   */
  protected static function convertCacheTagsToClearRegex(array $tags) {
    return '(\D' . implode('\D|\D', $tags) . '\D)';
  }

  /**
   * {@inheritdoc}
   *
   * @todo this is a expensive and non-efficient cheat-implementation.
   */
  public function invalidateMultiple(array $invalidations) {
    $results = [];
    foreach ($invalidations as $invalidation) {
      $results[] = $this->invalidate($invalidation);
    }
    if (in_array(FALSE, $results)) {
      return FALSE;
    }
    return TRUE;
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

}

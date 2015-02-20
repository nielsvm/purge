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
 * )
 */
class VarnishTagPurger extends PluginBase implements PluginInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the HTTP purger.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->client = $http_client;
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
   *
   * Sadly, Guzzle doesn't support detection of timeouts, its exceptions are not
   * granular enough.
   *
   * @see https://github.com/guzzle/guzzle/blob/5.0.0/src/Exception/RequestException.php
   * @see http://stackoverflow.com/questions/25661591/php-how-to-check-for-timeout-exception-in-guzzle-4
   */
  public function invalidate(Invalidation $invalidation) {
    $config = $this->configFactory->get('purge_purger_varnishpoc.conf');

    // For now - until Purge only sends supported invalidation objects - mark
    // anything besides a tag as immediately failed.
    if (!$invalidation instanceof Tag) {
      $invalidation->setState(Invalidation::STATE_PURGEFAILED);
      $this->numberFailed += 1;
      return;
    }

    // When the URL setting is still empty, we also fail.
    if (empty($config->get('url'))) {
      $invalidation->setState(Invalidation::STATE_PURGEFAILED);
      $this->numberFailed += 1;
      return;
    }

    // Construct a Guzzle request.
    $options = [
      'timeout' => $config->get('timeout'),
      'connect_timeout' => $config->get('connect_timeout'),
    ];
    $request = $this->client->createRequest('BAN', $config->get('url'), $options)
      ->addHeader($config->get('header'), static::toClearRegex($invalidation));

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
      $invalidation->setState(Invalidation::STATE_PURGEFAILED);
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
    throw new \Exception("Sorry, Not yet implemented!");
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    throw new \Exception("Sorry, Not yet implemented!");
  }
}

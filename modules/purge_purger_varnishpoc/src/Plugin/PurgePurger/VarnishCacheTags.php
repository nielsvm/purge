<?php

/**
 * @file
 * Contains \Drupal\purge_purger_varnishpoc\Plugin\PurgePurger\VarnishCacheTags.
 */

namespace Drupal\purge_purger_varnishpoc\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurgeable\Tag;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface as PurgerInterface;
use Drupal\purge\Purgeable\PluginInterface as PurgeableInterface;

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
 *   id = "varnish_cache_tags",
 *   label = @Translation("Varnish (cache tags)"),
 *   description = @Translation("Cache tags purger for Varnish, recommended for most sites."),
 *   configform = "Drupal\purge_purger_varnishpoc\Form\VarnishCacheTagsConfigForm",
 * )
 */
class VarnishCacheTags extends PluginBase implements PurgerInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs the HTTP purger.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   */
  function __construct(ClientInterface $http_client) {
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
   *
   * Sadly, Guzzle doesn't support detection of timeouts, its exceptions are not
   * granular enough.
   *
   * @see https://github.com/guzzle/guzzle/blob/5.0.0/src/Exception/RequestException.php
   * @see http://stackoverflow.com/questions/25661591/php-how-to-check-for-timeout-exception-in-guzzle-4
   */
  public function purge(PurgeableInterface $purgeable) {
    // @todo Until Purge doesn't only send us the Purgeables we support (Tag
    //    Purgeables), we'll have to just return FALSE when we encounter others.
    if (!$purgeable instanceof Tag) {
      $purgeable->setState(PurgeableInterface::STATE_PURGEFAILED);
      return FALSE;
    }

    // @todo: don't rely on settings but on CMI.
    if ($varnish_url = Settings::get('varnish_url')) {
      $options = [
        'timeout' => 1,
        'connect_timeout' => 0.5,
      ];
      $request = $this->client->createRequest('BAN', $varnish_url, $options)
        ->addHeader('X-Drupal-Cache-Tags-Banned', static::toClearRegex($purgeable));

      // Purge.
      $purgeable->setState(PurgeableInterface::STATE_PURGING);
      try {
        $this->client->send($request);
      }
      catch (RequestException $e) {
        $purgeable->setState(PurgeableInterface::STATE_PURGEFAILED);
        return FALSE;
      }

      $purgeable->setState(PurgeableInterface::STATE_PURGED);

      return TRUE;
    }

    return FALSE;
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
   */
  public function purgeMultiple(array $purgeables) {
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

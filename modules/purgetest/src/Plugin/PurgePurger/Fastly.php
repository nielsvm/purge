<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurger\Fastly.
 */

namespace Drupal\purgetest\Plugin\PurgePurger;

use Drupal\Core\Http\Client as HttpClient;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * A purger that purges the Fastly CDN.
 *
 * @PurgePurger(
 *   id = "fastly",
 *   label = @Translation("Fastly"),
 *   description = @Translation("A purger that purges the Fastly CDN."),
 *   service_dependencies = {"http_client"}
 * )
 */
class Fastly extends PluginBase {

  /**
   * Instantiate the fastly purger.
   *
   * @param \Drupal\Core\Http\Client $http_client
   *   The default HTTP client service.
   */
  function __construct(HttpClient $http_client) {
  }

  /**
   * {@inheritdoc}
   */
  public function purge(Purgeable $purgeable) {
    $purgeable->setState(Purgeable::STATE_PURGED);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFailed() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    throw new \Exception('Not yet implemented');
  }
}

<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurger\Fastly.
 */

namespace Drupal\purgetest\Plugin\PurgePurger;

use Drupal\Core\Http\Client as HttpClient;
use Drupal\purge\Purger\PurgerBase;
use Drupal\purge\Purgeable\PurgeableInterface;

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
class Fastly extends PurgerBase {

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
  public function purge(PurgeableInterface $purgeable) {
    $purgeable->setState(PurgeableInterface::STATE_PURGED);
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
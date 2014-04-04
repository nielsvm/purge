<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurger\Fastly.
 */

namespace Drupal\purgetest\Plugin\PurgePurger;

use Guzzle\Http\Client;
use Drupal\purge\Purger\PurgerBase;
use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * A purger that purges the Fastly CDN.
 *
 * @PurgePurger(
 *   id = "fastly",
 *   label = @Translation("Fastly"),
 *   description = @Translation("A purger that purges the Fastly CDN."),
 *   service_dependencies = {"http_default_client"}
 * )
 */
class Fastly extends PurgerBase {
  
  /**
   * Instantiate the fastly purger.
   * 
   * @param Guzzle\Http\Client $http_default_client
   *   The default HTTP client service.
   */
  function __construct(Client $http_default_client) {
  }
  
  /**
   * {@inheritdoc}
   */
  public function purge(PurgeableInterface $purgeable) {
    throw new \Exception('Not yet implemented');
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
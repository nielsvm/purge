<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\PurgeTestBaseTrait.
 */

namespace Drupal\purge\Tests;

/**
 * Several helper properties and methods for purge tests.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 * @see \Drupal\purge\Tests\WebTestBase
 */
trait PurgeTestBaseTrait {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Invalidation\ServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\DiagnosticCheck\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * Make $this->purgePurgers available.
   *
   * @param $plugin_ids
   *   Array of plugin ids to be enabled.
   */
  protected function initializePurgersService($plugin_ids = []) {
    if (!empty($plugin_id)) {
      $this->configFactory->getEditable('purge.plugins')
        ->set('purgers', $plugin_ids)->save();
    }
    if (is_null($this->purgePurgers)) {
      $this->purgePurgers = $this->container->get('purge.purgers');
    }
    else {
      $this->purgePurgers->reload();
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
   * Make $this->purgeInvalidationFactory available.
   */
  protected function initializeInvalidationFactoryService() {
    if (is_null($this->purgeInvalidationFactory)) {
      $this->purgeInvalidationFactory = $this->container->get('purge.invalidation.factory');
    }
  }

  /**
   * Make $this->purgeQueue available.
   *
   * @param $plugin_id
   *   The plugin ID of the queue to be configured.
   */
  protected function initializeQueueService($plugin_id = NULL) {
    if (!is_null($plugin_id)) {
      $this->configFactory->getEditable('purge.plugins')
        ->set('queue', $plugin_id)->save();
    }
    if (is_null($this->purgeQueue)) {
      $this->purgeQueue = $this->container->get('purge.queue');
    }
    else {
      $this->purgeQueue->reload();
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
   * Make $this->purgeDiagnostics available.
   */
  protected function initializeDiagnosticsService() {
    if (is_null($this->purgeDiagnostics)) {
      $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    }
    else {
      $this->purgeDiagnostics->reload();
    }
  }

  /**
   * Create $number requested invalidation objects.
   *
   * @param int $number
   *   The number of objects to generate.
   *
   * @return \Drupal\purge\Invalidation\PluginInterface[]
   */
  public function getInvalidations($number) {
    $set = [];
    for ($i = 0; $i < $number; $i++) {
      $set[] = $this->purgeInvalidationFactory->get('everything');
    }
    return $set;
  }

}

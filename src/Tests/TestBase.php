<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\TestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Thin and generic test base for purge tests.
 *
 * @group purge
 * @see \Drupal\simpletest\KernelTestBase
 */
abstract class TestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('purge');

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurger;

  /**
   * @var \Drupal\purge\Purgeable\ServiceInterface
   */
  protected $purgePurgeables;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\RuntimeTest\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
  * Set up the test object.
  */
  function setUp() {
    parent::setUp();
    $this->installConfig(array('purge'));
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
  * Make all purge services available.
  */
  protected function initializeAllServices() {
    $this->initializePurgerService();
    $this->initializePurgeablesService();
    $this->initializeQueueService();
    $this->initializeDiagnosticsService();
  }

  /**
  * Make $this->purgePurger available.
  *
  * @param $plugin_id
  *   The plugin ID of the purger to be configured.
  */
  protected function initializePurgerService($plugin_id = NULL) {
    if (!is_null($plugin_id)) {
      $this->configFactory->get('purge.purger')
        ->set('plugins', $plugin_id)->save();
    }
    if (is_null($this->purgePurger)) {
      $this->purgePurger = $this->container->get('purge.purger');
    }
    else {
      $this->purgePurger->reload();
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
  * Make $this->purgePurgeables available.
  */
  protected function initializePurgeablesService() {
    if (is_null($this->purgePurgeables)) {
      $this->purgePurgeables = $this->container->get('purge.purgeables');
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
      $this->configFactory->get('purge.queue')
        ->set('plugin', $plugin_id)->save();
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
}

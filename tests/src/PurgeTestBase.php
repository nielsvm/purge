<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\PurgeTestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Generic base test for purge unit tests.
 *
 * @group purge
 * @see \Drupal\simpletest\KernelTestBase
 */
abstract class PurgeTestBase extends KernelTestBase {

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
   * @var \Drupal\purge\Purger\PurgerServiceInterface
   */
  protected $purgePurger;

  /**
   * @var \Drupal\purge\Purgeable\PurgeableServiceInterface
   */
  protected $purgePurgeables;

  /**
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\RuntimeTest\RuntimeTestServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->installConfig(array('purge'));
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Configure the queue plugin.
   *
   * @param $plugin_id
   *   The plugin ID of the queue to be configured.
   */
  protected function setUpQueue($plugin_id) {
    $this->configFactory->get('purge.queue')->set('plugin', $plugin_id)->save();
    if (!is_null($this->purgeQueue)) {
      $this->purgeQueue->reload();
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
   * Configure the purger plugin.
   *
   * @param $plugin_id
   *   The plugin ID of the purger to be configured.
   */
  protected function setUpPurger($plugin_id) {
    $this->configFactory->get('purge.purger')->set('plugins', $plugin_id)->save();
    if (!is_null($this->purgePurger)) {
      $this->purgePurger->reload();
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
   * Make all purge services available.
   */
  protected function initialize() {
    $this->initializePurger();
    $this->initializePurgeables();
    $this->initializeQueue();
    $this->initializeDiagnostics();
  }

  /**
   * Make $this->purgePurger available.
   */
  protected function initializePurger() {
    $this->purgePurger = $this->container->get('purge.purger');
  }

  /**
   * Make $this->purgePurgeables available.
   */
  protected function initializePurgeables() {
    $this->purgePurgeables = $this->container->get('purge.purgeables');
  }

  /**
   * Make $this->purgeQueue available.
   */
  protected function initializeQueue() {
    $this->purgeQueue = $this->container->get('purge.queue');
  }

  /**
   * Make $this->purgeDiagnostics available.
   */
  protected function initializeDiagnostics() {
    $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
  }
}

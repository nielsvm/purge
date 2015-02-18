<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\ServiceTestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\ServiceBase;
use Drupal\purge\ServiceInterface;

/**
 * Generic test base for \Drupal\purge\ServiceInterface derivatives.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 */
abstract class ServiceTestBase extends KernelTestBase {

  /**
   * The name of the service as defined in services.yml.
   */
  protected $serviceId;

  /**
   * Instance of the service being tested, instantiated by the dependency
   * injection container.
   *
   * @var \Drupal\purge\ServiceInterface
   */
  protected $service;

  /**
   * Initialize the requested service as $this->$variable (or reload).
   *
   * @param string $variable
   *   The place to put the loaded/reloaded service, defaults to $this->service.
   * @param string $service
   *   The name of the service to load, defaults to $this->serviceId.
   */
  protected function initializeService($variable = 'service', $service = NULL) {
    if (is_null($this->$variable)) {
      if (is_null($service)) {
        $this->$variable = $this->container->get($this->serviceId);
      }
      else {
        $this->$variable = $this->container->get($service);
      }
    }
    $this->$variable->reload();
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->initializeService();
  }

  /**
   * test for \Drupal\purge\ServiceBase and \Drupal\purge\ServiceInterface.
   */
  public function testCodeContract() {
    $this->assertTrue($this->service instanceof ServiceInterface,
      'Object complies to \Drupal\purge\ServiceInterface.');
    $this->assertTrue($this->service instanceof ServiceBase,
      'Object complies to \Drupal\purge\ServiceBase.');
  }
}

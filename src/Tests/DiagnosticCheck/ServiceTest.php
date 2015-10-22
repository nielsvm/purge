<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\DiagnosticCheck\ServiceTest.
 */

namespace Drupal\purge\Tests\DiagnosticCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\ServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginInterface as Check;

/**
 * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service
 * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\ServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.diagnostics';
  public static $modules = [
    'purge_noqueuer_test',
    'purge_purger_test',
    'purge_processor_test',
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test'
  ];

  /**
   * The supported test severities.
   *
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginInterface::SEVERITY_INFO
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginInterface::SEVERITY_OK
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginInterface::SEVERITY_WARNING
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginInterface::SEVERITY_ERROR
   */
  protected $severities = [
    Check::SEVERITY_INFO,
    Check::SEVERITY_OK,
    Check::SEVERITY_WARNING,
    Check::SEVERITY_ERROR,
  ];

  /**
   * The hook_requirements() severities from install.inc.
   *
   * @see REQUIREMENT_INFO
   * @see REQUIREMENT_OK
   * @see REQUIREMENT_WARNING
   * @see REQUIREMENT_ERROR
   */
  protected $requirementSeverities = [];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelServiceTestBase::setUp();
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Include install.inc and initialize $this->requirementSeverities.
   */
  protected function initializeRequirementSeverities() {
    if (empty($this->requirementSeverities)) {
      include_once DRUPAL_ROOT . '/core/includes/install.inc';
      $this->requirementSeverities[] = REQUIREMENT_INFO;
      $this->requirementSeverities[] = REQUIREMENT_OK;
      $this->requirementSeverities[] = REQUIREMENT_WARNING;
      $this->requirementSeverities[] = REQUIREMENT_ERROR;
    }
  }

  /**
   * Tests lazy loading of the 'purge.purger' and 'purge.queue' services.
   *
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::__construct
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::initializeChecks
   */
  public function testLazyLoadingOfDependencies() {
    $this->assertFalse($this->container->initialized('purge.purgers'));
    $this->assertFalse($this->container->initialized('purge.queue'));
    $this->initializeService();
    $this->assertFalse($this->container->initialized('purge.purgers'));
    $this->assertFalse($this->container->initialized('purge.queue'));

    // All the helpers on the service - except the constructor - lazy load the
    // services, but only when any of the check plugins require them. In this
    // case the 'memoryqueuewarning' plugin will trigger the queue and the
    // 'capacity' and 'purgersavailable' plugins will load 'purge.purgers'.
    $this->service->isSystemOnFire();
    $this->assertTrue($this->container->initialized('purge.purgers'));
    $this->assertTrue($this->container->initialized('purge.queue'));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::count
   */
  public function testCount() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEqual(9, count($this->service));
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::current
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::key
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::next
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::rewind
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::valid
   */
  public function testIteration() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $check) {
      $this->assertTrue($check instanceof Check);
      $items++;
    }
    $this->assertEqual(9, $items);
    $this->assertFalse($this->service->current());
    $this->assertFalse($this->service->valid());
    $this->assertNull($this->service->rewind());
    $this->assertEqual('capacity', $this->service->current()->getPluginId());
    $this->assertNull($this->service->next());
    $this->assertEqual('processorsavailable', $this->service->current()->getPluginId());
    $this->assertTrue($this->service->valid());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::getHookRequirementsArray
   */
  public function testGetHookRequirementsArray() {
    $this->initializeRequirementSeverities();
    $this->initializeService();
    $requirements = $this->service->getHookRequirementsArray();
    $this->assertEqual(9, count($requirements));
    foreach ($requirements as $id => $requirement) {
      $this->assertTrue(is_string($id));
      $this->assertFalse(empty($id));
      $this->assertTrue($requirement['title'] instanceof TranslatableMarkup, "$id's title is a TranslatableMarkup object.");
      $this->assertFalse(empty($requirement['title']));
      $this->assertTrue($requirement['description'] instanceof TranslatableMarkup, "$id's description is a TranslatableMarkup object.");
      $this->assertFalse(empty($requirement['description']));
      $this->assertTrue(in_array($requirement['severity'], $this->requirementSeverities));
    }
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::isSystemOnFire.
   */
  public function testIsSystemOnFire() {
    $this->initializePurgersService(['ida' => 'a']);
    $this->service->reload();
    $this->assertTrue($this->service->isSystemOnFire() instanceof Check);
    $this->assertEqual('alwayserror', $this->service->isSystemOnFire()->getPluginId());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\Service::isSystemShowingSmoke.
   */
  public function testIsSystemShowingSmoke() {
    $this->assertTrue($this->service->isSystemShowingSmoke() instanceof Check);
    $possibilities = ['alwayswarning', 'capacity', 'queuersavailable'];
    $this->assertTrue(in_array($this->service->isSystemShowingSmoke()->getPluginId(), $possibilities));
  }

}

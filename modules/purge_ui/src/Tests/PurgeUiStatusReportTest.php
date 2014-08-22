<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgeUiStatusReportTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that RuntimeTest's are showing on Drupal's status report and that
 * thus the hook_requirements() implementation does its job as it should.
 *
 * @group purge
 * @see purge_ui_requirements()
 * @see \Drupal\purge\RuntimeTest\RuntimeTestServiceInterface
 */
class PurgeUiStatusReportTest extends WebTestBase {

  /**
   * @var \Drupal\purge\RuntimeTest\RuntimeTestServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * @var string
   */
  protected $path = 'admin/reports/status';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('purge_ui', 'purge_test');

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));
  }

  /*
   * Test if the form is at its place and has the right permissions.
   */
  public function testWarningRuntimeTestPresent() {
    $this->drupalLogin($this->admin_user);
    $this->assertResponse(200);
    $this->drupalGet($this->path);
    $this->assertText('This is a warning for unit testing.', "Find AlwaysWarningTest.");
  }
}

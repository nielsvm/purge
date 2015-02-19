<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgeStatusReportTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that RuntimeTest's are showing on Drupal's status report and that
 * thus the hook_requirements() implementation does its job as it should.
 *
 * @group purge
 * @see purge_ui_requirements()
 * @see \Drupal\purge\RuntimeTest\ServiceInterface
 */
class PurgeStatusReportTest extends WebTestBase {

  /**
   * @var \Drupal\purge\RuntimeTest\ServiceInterface
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
  public static $modules = ['purge_ui', 'purge_plugins_test'];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
  }

  /*
   * Test if the form is at its place and has the right permissions.
   */
  public function testWarningAndErrorRuntimeTestPresent() {
    $this->drupalLogin($this->admin_user);
    $this->assertResponse(200);
    $this->drupalGet($this->path);

    // @see \Drupal\purge_plugins_test\Plugin\PurgeRuntimeTest\AlwaysWarningTest
    $this->assertText('Purge - Always a warning');
    $this->assertText('This is a warning for unit testing.');

    // @see \Drupal\purge_plugins_test\Plugin\PurgeRuntimeTest\AlwaysErrorTest
    $this->assertText('Purge - Always an error');
    $this->assertText('This is an error for unit testing.');
  }
}

<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormDiagnosticReportTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\ConfigFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm - diagnostics section.
 *
 * @group purge_ui
 */
class ConfigFormDiagnosticReportTest extends ConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * Test the visual status report on the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormDiagnosticReport
   */
  public function testFormDiagnosticReport() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('edit-diagnostics');
    $this->assertRaw('system-status-report');
    $this->assertRaw('open="open"');
    $this->assertText('Status');
    $this->assertText('Capacity');
    $this->assertText('Queuers');
    $this->assertText('Always a warning');
    $this->assertText('Always informational');
    $this->assertText('Always ok');
    $this->assertText('Always an error');
  }

}

<?php

namespace Drupal\Tests\purge_ui\Functional;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildDiagnosticReport.
 *
 * @group purge
 */
class DashboardDiagnosticsTest extends DashboardTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * Test the visual status report.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildDiagnosticReport
   */
  public function testDiagnosticReport(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('purge-ui-diagnostic-report');
    $this->assertSession()->responseContains('purge-ui-diagnostic-report__entry--warning');
    $this->assertSession()->responseContains('purge-ui-diagnostic-report__entry--error');
    $this->assertSession()->responseContains('purge-ui-diagnostic-report__status-title');
    $this->assertSession()->responseContains('purge-ui-diagnostic-report__entry__value');
    $this->assertSession()->responseContains('purge-ui-diagnostic-report__entry__description');
    $this->assertSession()->responseContains('open="open"');
    $this->assertSession()->pageTextContains('Status');
    $this->assertSession()->pageTextContains('Capacity');
    $this->assertSession()->pageTextContains('Queuers');
    $this->assertSession()->pageTextContains('Always a warning');
    $this->assertSession()->pageTextContains('Always informational');
    $this->assertSession()->pageTextContains('Always ok');
    $this->assertSession()->pageTextContains('Always an error');
  }

}

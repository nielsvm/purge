<?php

namespace Drupal\Tests\purge_ui\Functional;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController in no modules state.
 *
 * @group purge
 */
class DashboardEmptyTest extends DashboardTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui_remove_block_plugins_test'];

  /**
   * Test the logging section.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildLoggingSection
   */
  public function testFormLoggingSection(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Logging');
    $this->assertSession()->responseContains('Configure logging behavior');
  }

  /**
   * Test the visual status report.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildDiagnosticReport
   */
  public function testFormDiagnosticReport(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('You have no queuers populating the queue!');
    $this->assertSession()->responseContains('There is no purging capacity available.');
    $this->assertSession()->responseContains('There is no purger loaded which means that you need a module enabled to provide a purger plugin to clear your external cache or CDN.');
    $this->assertSession()->responseContains('You have no processors, the queue can now build up because of this.');
  }

  /**
   * Test that a unconfigured pipeline results in 'nothing available' messages.
   */
  public function testMissingMessages(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Please install a module to add at least one queuer.');
    $this->assertSession()->responseNotContains('Add queuer');
    $this->assertSession()->responseContains('Please install a module to add at least one purger.');
    $this->assertSession()->responseNotContains('Add purger');
    $this->assertSession()->responseContains('Please install a module to add at least one processor.');
    $this->assertSession()->responseNotContains('Add processor');
  }

}

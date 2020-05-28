<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildLoggingSection().
 *
 * @group purge
 */
class DashboardLoggingTest extends DashboardTestBase {

  /**
   * Test the logging section of the configuration form.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildLoggingSection
   */
  public function testLoggingSection(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Logging');
    $this->assertSession()->responseContains('Configure logging behavior');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.logging_config_form')->toString());
  }

}

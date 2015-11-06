<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\DashboardQueueTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController - queue section.
 *
 * @group purge_ui
 */
class DashboardQueueTest extends DashboardTestBase {

  /**
   * Test the queue section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormQueue
   */
  public function testFormQueueSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw("Memory");
    $this->assertText("Inspect data");
    $this->assertText("Empty the queue");
  }

}

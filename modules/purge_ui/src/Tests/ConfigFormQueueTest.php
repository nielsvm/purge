<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormQueueTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\ConfigFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm - queue section.
 *
 * @group purge_ui
 */
class ConfigFormQueueTest extends ConfigFormTestBase {

  /**
   * Test the queue section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormQueue
   */
  public function testFormQueueSection() {
    $this->initializeQueueService();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw("Memory");
    $this->assertText("Inspect data");
    $this->assertText("Empty the queue");
  }

}

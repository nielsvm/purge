<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\Processor.
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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_queue_test'];

  /**
   * Test the queue section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormQueue
   */
  public function testFormQueueSection() {
    $this->initializeQueueService();
    $this->drupalLogin($this->admin_user);
    // Assert that the configured queue is selected on page load.
    $this->drupalGet($this->route);
    $this->assertFieldChecked('edit-queue-plugin-memory');
    // Test that just submitting the form, results in the exact same config.
    $this->drupalPostForm($this->route, [], t('Change'));
    $this->purgeQueue->reload();
    $this->assertTrue(in_array('memory', $this->purgeQueue->getPluginsEnabled()));
    // Test that changing the queue plugin, gets reflected in the config.
    $this->drupalPostForm($this->route, ['queue_plugin' => 'b'], t('Change'));
    $this->purgeQueue->reload();
    $this->assertTrue(in_array('b', $this->purgeQueue->getPluginsEnabled()));
    $this->assertRaw("Change");
    $this->assertText("Inspect data");
    $this->assertText("Empty the queue");
  }

}

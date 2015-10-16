<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormQueuersTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\ConfigFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm - queuers section.
 *
 * @group purge
 */
class ConfigFormQueuersTest extends ConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_queuer_test'];

  /**
   * Test the queuers section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormQueuers
   */
  public function testFormQueuersSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('Queuers queue items in the queue upon certain events.');
    $this->assertRaw('Add queuer');
    $this->assertRaw('href="/admin/config/development/performance/purge/queuer/purge_queuer_test.queuera/disable"');
    $this->assertRaw('purge_queuer_test.queuera');
    $this->assertRaw('Queuer A');
    $this->assertRaw('A test queuer that adds a path when you enable it.');
    $this->assertNoRaw('Queuer B');
    $this->assertNoRaw('Queuer C');
  }

}

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
 * @group purge_ui
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
    $this->assertRaw('href="/admin/config/development/performance/purge/queuer/a/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/queuer/a/dialog"');
    $this->assertRaw('Queuer A');
    $this->assertRaw('Test queuer A.');
    $this->assertRaw('href="/admin/config/development/performance/purge/queuer/b/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/queuer/b/dialog"');
    $this->assertRaw('Queuer B');
    $this->assertRaw('Test queuer B.');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/queuer/c/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/queuer/c/dialog"');
    $this->assertNoRaw('Queuer C');
    $this->assertNoRaw('Test queuer C.');
    $this->initializeQueuersService(['withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('href="/admin/config/development/performance/purge/queuer/withform/delete"');
    $this->assertRaw('href="/admin/config/development/performance/purge/queuer/withform/dialog"');
    $this->assertRaw('Queuer with form');
    $this->assertRaw('Test queuer with a configuration form.');
  }

}

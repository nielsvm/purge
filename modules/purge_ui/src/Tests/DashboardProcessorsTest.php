<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\DashboardProcessorsTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController - processors section.
 *
 * @group purge_ui
 */
class DashboardProcessorsTest extends DashboardTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_processor_test'];

  /**
   * Test the processors section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormProcessors
   */
  public function testFormProcessorsSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('Processors queue items in the queue upon certain events.');
    $this->assertRaw('Add processor');
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/a/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/a/config/dialog"');
    $this->assertRaw('Processor A');
    $this->assertRaw('Test processor A.');
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/b/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/b/config/dialog"');
    $this->assertRaw('Processor B');
    $this->assertRaw('Test processor B.');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/c/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/c/config/dialog"');
    $this->assertNoRaw('Processor C');
    $this->assertNoRaw('Test processor C.');
    $this->initializeProcessorsService(['withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/withform/delete"');
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/withform/config/dialog"');
    $this->assertRaw('Processor with form');
    $this->assertRaw('Test processor with a configuration form.');
  }

}

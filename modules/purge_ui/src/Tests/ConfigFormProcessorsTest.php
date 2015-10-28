<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormProcessorsTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\ConfigFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm - processors section.
 *
 * @group purge_ui
 */
class ConfigFormProcessorsTest extends ConfigFormTestBase {

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
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/a/dialog"');
    $this->assertRaw('Processor A');
    $this->assertRaw('Test processor A.');
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/b/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/b/dialog"');
    $this->assertRaw('Processor B');
    $this->assertRaw('Test processor B.');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/c/delete"');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/processor/c/dialog"');
    $this->assertNoRaw('Processor C');
    $this->assertNoRaw('Test processor C.');
    $this->initializeProcessorsService(['withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/withform/delete"');
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/withform/dialog"');
    $this->assertRaw('Processor with form');
    $this->assertRaw('Test processor with a configuration form.');
  }

}

<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\DashboardEmptyTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController in almost default (no modules state).
 *
 * @group purge_ui
 */
class DashboardEmptyTest extends DashboardTestBase {

  /**
   * Test the visual status report on the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormDiagnosticReport
   */
  public function testFormDiagnosticReport() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('There are no queuing services enabled, this means that you can only invalidate external caches manually or programmatically.');
    $this->assertRaw('There is no purging capacity available.');
    $this->assertRaw('There is no purger loaded which means that you need a module enabled to provide a purger plugin to clear your external cache or CDN.');
    $this->assertRaw('There are no processors enabled, which means that your queue can build up and grow quickly.');
  }

  /**
   * Test that a unconfigured pipeline results in 'nothing available' messages.
   */
  public function testMissingMessages() {
    $this->assertRaw('No queuers available, install module(s) that provide them!');
    $this->assertNoRaw('Add queuer');
    $this->assertRaw('No purgers available, install module(s) that provide them!');
    $this->assertNoRaw('Add purger');
    $this->assertRaw('No processors available, install module(s) that provide them!');
    $this->assertNoRaw('Add processor');
  }

}

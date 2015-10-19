<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormPurgersTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\ConfigFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm - purgers section.
 *
 * @group purge_ui
 */
class ConfigFormPurgersTest extends ConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_purger_test'];

  /**
   * Test the queue section of the configuration form.
   *
   * @warning
   *   This test depends on raw HTML, which is a bit of a maintenance cost. At
   *   the same time, core's markup guarantees should keep us safe. Having that
   *   said, for the purpose of testing, raw HTML checking is very accurate :-).
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormPurgers
   */
  public function testFormPurgersSection() {
    $this->drupalLogin($this->admin_user);
    // Assert that without any enabled purgers, the form stays empty.
    $this->initializePurgersService();
    $this->drupalGet($this->route);
    $this->assertRaw('Purgers are provided by third-party modules and clear content from external caching systems.');
    $this->assertNoRaw('Purger A</th>');
    $this->assertNoRaw('Purger B</th>');
    $this->assertNoRaw('Purger C</th>');
    $this->assertNoRaw('Configurable purger</th>');
    // Assert that enabled purgers show up and have the right buttons attached.
    $this->initializePurgersService(['id1' => 'purger_a', 'id2' => 'purger_withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('<th title="Test purger A." class="priority-medium">Purger A</th>');
    $this->assertRaw('<th title="Test purger with a form attached." class="priority-medium">Configurable purger</th>');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/purger/id1/dialog"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id1/delete"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id2/dialog"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id2/delete"');
    // Assert that the purger-type supportability matrix shows the checkmarks.
    $padding = str_repeat(' ', 22);
    $unchecked = "\n$padding<td class=\"priority-medium\">&nbsp;</td>";
    $checked = "\n$padding<td class=\"priority-medium\"><img src=";
    $this->assertRaw('core/misc/icons/73b355/check.svg');
    $this->assertRaw('width="18" height="18" alt="Supported" title="Supported" />');
    $this->assertRaw("Domain</td>$unchecked");
    $this->assertRaw("Path</td>$unchecked");
    $this->assertRaw("Tag</td>$unchecked");
    $this->assertRaw("Regular expression</td>$unchecked");
    $this->assertRaw("Path with wildcard</td>$unchecked");
    $this->assertRaw("Url with wildcard</td>$unchecked");
    $this->assertRaw("Everything</td>$checked");
    // Assert that the 'Add purger' button only shows up when it actually should.
    $this->assertRaw(t('Add purger'));
    $this->initializePurgersService(['id1' => 'purger_a', 'id2' => 'purger_b', 'id3' => 'purger_c', 'id4' => 'purger_withform', 'id5' => 'goodpurger']);
    $this->drupalGet($this->route);
    $this->assertNoRaw(t('Add purger'));
  }

}

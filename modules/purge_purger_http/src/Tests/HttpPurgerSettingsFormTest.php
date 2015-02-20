<?php

/**
 * @file
 * Test case for testing the HTTP Purger module.
 */

namespace Drupal\purge_purger_http\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the HTTP Purger admin settings form.
 *
 * @group purge_purger_http
 */
class HttpPurgerSettingsFormTest extends WebTestBase {
  /**
   * User account with purge_purger_http permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $privilegedUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('purge_ui', 'purge_purger_http');

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->privilegedUser = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Test the HTTP Purger settings form.
   */
  public function testHttpPurgerSettingsForm() {
    // Verify if we can successfully access the HTTP Purger form.
    $this->drupalGet('admin/config/development/performance/purge/http');
    $this->assertResponse(200, 'The HTTP Purger settings page is available.');
    $this->assertTitle(t('Configure HTTP Purger | Drupal'), 'The title on the page is "Configure HTTP Purger".');

    // Verify every field exists.
    $this->assertField('edit-hostname', 'edit-hostname field exists');
    $this->assertField('edit-port', 'edit-port field exists');
    $this->assertField('edit-path', 'edit-path field exists');
    $this->assertField('edit-request-method', 'edit-request-method field exists');

    // Validate default form values.
    $this->assertFieldById('edit-hostname', 'localhost', 'The edit-hostname field has the value "localhost".');
    $this->assertFieldById('edit-port', '80', 'The edit-port field has the value "80".');
    //$this->assertFieldChecked('edit-compact-forms-descriptions');
    //$this->assertNoFieldChecked('edit-compact-forms-stars-0');
    //$this->assertNoFieldChecked('edit-compact-forms-stars-1');
    //$this->assertFieldChecked('edit-compact-forms-stars-2');

    //$this->pass('Field edit-compact-forms-field-size always passes with empty string.', 'Debug');
    //$this->assertFieldById('edit-compact-forms-field-size', '', 'The edit-compact-forms-field-size field is empty.');

    // Verify that there's no access bypass.
    $this->drupalLogout();
    $this->drupalGet('admin/config/development/performance/purge/http');
    $this->assertResponse(403, 'Access denied for anonymous user.');
  }

  /**
   * Test posting data to the purge_purger_http settings form.
   */
  public function testHttpPurgerFormPost() {
    // Post form with new values.
    $edit = array(
      'edit-hostname' => 'example.com',
      'edit-port' => 8080,
      'edit-path' => 'node/1',
      'edit-request-method' => 1,
    );
    $this->drupalPostForm('admin/config/development/performance/purge/http', $edit, t('Save configuration'));

    // Load settings form page and test for new values.
    $this->drupalGet('admin/config/development/performance/purge/http');
    $this->assertFieldById('edit-hostname', $edit['edit-hostname'],
      format_string('The edit-hostname field has the value %val.', array('%val' => $edit['edit-hostname'])));
    //$this->assertNoFieldChecked('edit-compact-forms-descriptions');
    //$this->assertFieldChecked('edit-compact-forms-stars-0');
    //$this->assertNoFieldChecked('edit-compact-forms-stars-1');
    //$this->assertNoFieldChecked('edit-compact-forms-stars-2');
    //$this->assertFieldById('edit-compact-forms-field-size', $edit['compact_forms_field_size'],
    // format_string('The edit-compact-forms-field-size field has the value %val.', array('%val' => $edit['compact_forms_field_size'])));
  }
}

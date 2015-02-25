<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Tests\ConfigurationFormTest;
 */

namespace Drupal\purge_purger_http\Tests;

use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_purger_http\Form\ConfigurationForm.
 *
 * @group purge
 */
class ConfigurationFormTest extends WebTestBase {

  /**
   * User account with purge_purger_http permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_noqueuer_test', 'purge_ui', 'purge_purger_http'];

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
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);

  }

  /**
   * Test the HTTP Purger settings form.
   */
  public function testHttpPurgerSettings() {
    $this->drupalLogin($this->admin_user);

    // Verify if we can successfully access the HTTP Purger form.
    $this->drupalGet('admin/config/development/performance/purge/http');
    $this->assertResponse(200, 'The HTTP Purger settings page is available.');
    $this->assertTitle(t('Configure HTTP Purger | Drupal'), 'The title on the page is "Configure HTTP Purger".');

    // Verify every field exists.
    $this->assertField('edit-hostname');
    $this->assertField('edit-port');
    $this->assertField('edit-path');
    $this->assertField('edit-request-method');

    // Validate default form values.
    $this->assertFieldById('edit-hostname', 'localhost');
    $this->assertFieldById('edit-port', '80');
    $this->assertFieldById('edit-path', '');
    $this->assertOptionSelected('edit-request-method', 0);

    // Verify that there's no access bypass.
    $this->drupalLogout();
    $this->drupalGet('admin/config/development/performance/purge/http');
    $this->assertResponse(403, 'Access denied for anonymous user.');
  }

  /**
   * Test posting data to the HTTP Purger settings form.
   */
  public function testHttpPurgerSettingsPost() {
    $this->drupalLogin($this->admin_user);

    // Post form with new values.
    $edit = [
      'hostname' => 'example.com',
      'port' => 8080,
      'path' => 'node/1',
      'request_method' => 1,
    ];
    $this->drupalPostForm('admin/config/development/performance/purge/http', $edit, t('Save configuration'));

    // Load settings form page and test for new values.
    $this->drupalGet('admin/config/development/performance/purge/http');
    $this->assertFieldById('edit-hostname', $edit['hostname'],
      format_string('The hostname field has the value %val.', ['%val' => $edit['hostname']]));
    $this->assertFieldById('edit-port', $edit['port'],
     format_string('The port field has the value %val.', ['%val' => $edit['port']]));
    $this->assertFieldById('edit-path', $edit['path'],
      format_string('The path field has the value %val.', ['%val' => $edit['port']]));
    $this->assertFieldById('edit-request-method', $edit['request_method'],
      format_string('The request_method field has the value %val.', ['%val' => $edit['request_method']]));
  }

}

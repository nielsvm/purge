<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormTestBase.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Testbase for tests testing \Drupal\purge_ui\Form\ConfigForm.
 */
abstract class ConfigFormTestBase extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * Route providing the main configuration form of the purge module.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.config_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_ui'];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route);
    }
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess() {
    $this->drupalGet($this->route);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('<form class="purge-uiconfig-form"');
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute('system.performance_settings'));
  }

}

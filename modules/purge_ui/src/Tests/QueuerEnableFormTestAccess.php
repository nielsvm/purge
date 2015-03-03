<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\QueuerEnableFormTestAccess.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueuerEnableForm.
 *
 * @group purge
 */
class QueuerEnableFormTestAccess extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

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
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess() {
    $this->initializeQueuersService();
    $this->purgeQueuers->get('purge.queuers.cache_tags')->disable();
    $this->drupalGet(Url::fromRoute('purge_ui.queuer_enable_form'));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute('purge_ui.queuer_enable_form'));
    $this->assertResponse(200);
    $this->purgeQueuers->get('purge.queuers.cache_tags')->enable();
    $this->drupalGet(Url::fromRoute('purge_ui.queuer_enable_form'));
    $this->assertResponse(404);
  }

}

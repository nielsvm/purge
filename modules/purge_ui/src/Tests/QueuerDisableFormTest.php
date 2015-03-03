<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\QueuerDisableFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueuerDisableForm.
 *
 * @group purge
 */
class QueuerDisableFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queuer_disable_form';

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
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge.queuers.cache_tags']));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge.queuers.cache_tags']));
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertResponse(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerDisableForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge.queuers.cache_tags']));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'purge.queuers.cache_tags']), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[0]['command']);
    $this->assertEqual(1, count($json));
  }

  /**
   * Tests that 'Yes, disable..', disables the queuer and closes the window.
   *
   * @see \Drupal\purge_ui\Form\QueuerDisableForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::disableQueuer
   */
  public function testDisableQueuer() {
    $this->initializeQueuersService();
    $this->purgeQueuers->get('purge.queuers.cache_tags')->enable();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge.queuers.cache_tags']));
    $this->assertRaw(t('Yes, disable this queuer!'));
    $this->assertTrue($this->purgeQueuers->get('purge.queuers.cache_tags')->isEnabled());
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'purge.queuers.cache_tags']), [], ['op' => t('Yes, disable this queuer!')]);
    $this->assertEqual('closeDialog', $json[0]['command']);
    $this->assertEqual('redirect', $json[1]['command']);
    $this->purgeQueuers->reload();
  }

}

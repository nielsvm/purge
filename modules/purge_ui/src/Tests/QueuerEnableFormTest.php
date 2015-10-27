<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\QueuerAddFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueuerAddForm.
 *
 * @group purge_ui
 */
class QueuerAddFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queuer_add_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'purge_ui',
    'purge_noqueuer_test',
    'purge_queuer_test',
  ];


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
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->purgeQueuers->get('purge_queuer_test.queuerb')->enable();
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->purgeQueuers->get('purge_queuer_test.queuerc')->enable();
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(404);
    $this->purgeQueuers->get('purge_queuer_test.queuerb')->disable();
    $this->purgeQueuers->get('purge_queuer_test.queuerc')->disable();
  }

  /**
   * Tests that the "Cancel" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancel() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute('purge_ui.queuer_add_form'));
    $this->assertRaw(t('Cancel'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testEnableQueuer() {
    $this->drupalLogin($this->admin_user);
    // Assert the default state of purge_queuer_test's configuration.
    $this->assertTrue($this->configFactory->get('purge_queuer_test.status')->get('a'));
    $this->assertFalse($this->configFactory->get('purge_queuer_test.status')->get('b'));
    $this->assertFalse($this->configFactory->get('purge_queuer_test.status')->get('c'));
    // Then confirm that the enable dialog reflects this.
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->assertRaw(t('Add'));
    $this->assertNoRaw(t('Queuer A'));
    $this->assertRaw(t('Queuer B'));
    $this->assertRaw(t('Queuer C'));
    // Enable queuers B and C and assert the right AJAX commands for both.
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'purge_queuer_test.queuerb'], ['op' => t('Add')]);
    $this->assertResponse(200);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'purge_queuer_test.queuerc'], ['op' => t('Add')]);
    $this->assertResponse(200);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    // Assert that all configuration has been updated accordingly.
    $this->assertTrue($this->configFactory->get('purge_queuer_test.status')->get('a'));
    $this->assertTrue($this->configFactory->get('purge_queuer_test.status')->get('b'));
    $this->assertTrue($this->configFactory->get('purge_queuer_test.status')->get('c'));
    // Now that everything is enabled, confirm the dialog becoming inaccessible.
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(404);
  }

}

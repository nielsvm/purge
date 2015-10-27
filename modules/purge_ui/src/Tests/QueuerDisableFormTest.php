<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\QueuerDeleteFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueuerDeleteForm.
 *
 * @group purge_ui
 */
class QueuerDeleteFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queuer_delete_form';

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
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuera']));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuera']));
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuerb']));
    $this->assertResponse(404);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertResponse(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuera']));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuera'])->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that 'Yes, disable..', disables the queuer and closes the window.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDisableQueuer() {
    $this->initializeQueuersService();
    // Assert that disabling directly through the service works as expected.
    $this->assertEqual(1, count($this->purgeQueuers->getEnabled()));
    $this->purgeQueuers->get('purge_queuer_test.queuera')->disable();
    $this->assertEqual(0, count($this->purgeQueuers->getEnabled()));
    // Test disabling the queuer.
    $this->purgeQueuers->get('purge_queuer_test.queuera')->enable();
    $this->assertTrue($this->purgeQueuers->get('purge_queuer_test.queuera')->isEnabled());
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuera']));
    $this->assertRaw(t('Yes, disable this queuer!'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'purge_queuer_test.queuera'])->toString(), [], ['op' => t('Yes, disable this queuer!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->assertFalse($this->configFactory->get('purge_queuer_test.status')->get('a'));
  }

}

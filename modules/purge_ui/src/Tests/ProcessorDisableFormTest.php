<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ProcessorDisableFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorDisableForm.
 *
 * @group purge_ui
 */
class ProcessorDisableFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.processor_disable_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'purge_ui',
    'purge_noqueuer_test',
    'purge_processor_test'
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
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_processor_test.a']));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_processor_test.a']));
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertResponse(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorDisableForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_processor_test.a']));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'purge_processor_test.a'])->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that 'Yes, disable..', disables the processor and closes the window.
   *
   * @see \Drupal\purge_ui\Form\ProcessorDisableForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::disableProcessor
   */
  public function testDisableProcessor() {
    $this->initializeProcessorsService();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'purge_processor_test.a']));
    $this->assertRaw(t('Yes, disable this processor!'));
    $this->assertTrue($this->purgeProcessors->get('purge_processor_test.a')->isEnabled());
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'purge_processor_test.a'])->toString(), [], ['op' => t('Yes, disable this processor!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    // The redirect command proves that its submit disabled the processor.
    $this->assertEqual('redirect', $json[2]['command']);
    $this->assertFalse($this->configFactory->get('purge_processor_test.status')->get('a'));
  }

}

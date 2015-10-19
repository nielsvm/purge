<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ProcessorEnableFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorEnableForm.
 *
 * @group purge_ui
 */
class ProcessorEnableFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.processor_enable_form';

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
    $this->initializeProcessorsService();
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->purgeProcessors->get('purge_processor_test.c')->enable();
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->purgeProcessors->get('purge_processor_test.d')->enable();
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(404);
    $this->purgeProcessors->get('purge_processor_test.c')->disable();
    $this->purgeProcessors->get('purge_processor_test.d')->disable();
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorEnableForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelAndEnabling() {
    // Tests the cancel button.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Cancel'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
    // Tests adding processors.
    $this->assertFalse($this->configFactory->get('purge_processor_test.status')->get('c'));
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Add'));
    $this->assertNoRaw(t('Processor A'));
    $this->assertNoRaw(t('Processor B'));
    $this->assertRaw(t('Processor C'));
    $this->assertRaw(t('Processor D'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'purge_processor_test.c'], ['op' => t('Add')]);
    $this->assertTrue($this->configFactory->get('purge_processor_test.status')->get('c'));
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->assertTrue($this->configFactory->get('purge_processor_test.status')->get('c'));
  }

}

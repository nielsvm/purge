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
 * @group purge
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
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorEnableForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelAndEnabling() {
    $this->initializeProcessorsService();
    $this->assertEqual(2, count($this->purgeProcessors->getDisabled()));
    $this->purgeProcessors->get('purge_processor_test.a')->disable();
    $this->assertEqual(3, count($this->purgeProcessors->getDisabled()));
    $this->assertFalse($this->configFactory->get('purge_processor_test.status')->get('a'));

    // Tests the cancel button.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Cancel'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[0]['command']);
    $this->assertEqual(1, count($json));

    // Tests adding processors.
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Add'));
    $this->assertRaw(t('Processor A'));
    $this->assertRaw(t('Processor C'));
    $this->assertRaw(t('Processor D'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'purge_processor_test.a'], ['op' => t('Add')]);
    $this->assertTrue($this->configFactory->get('purge_processor_test.status')->get('a'));
    $this->assertEqual('closeDialog', $json[0]['command']);
    // The redirect command proves that its submit enabled the processor.
    $this->assertEqual('redirect', $json[1]['command']);
    $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'purge_processor_test.c'], ['op' => t('Add')]);
    $this->assertTrue($this->configFactory->get('purge_processor_test.status')->get('c'));
    $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'purge_processor_test.d'], ['op' => t('Add')]);
    $this->assertTrue($this->configFactory->get('purge_processor_test.status')->get('d'));
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(404);
  }

}

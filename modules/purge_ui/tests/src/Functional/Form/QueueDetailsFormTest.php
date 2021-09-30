<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\PluginDetailsForm;

/**
 * Tests \Drupal\purge_ui\Form\PluginDetailsForm (for queue backends).
 *
 * @group purge
 */
class QueueDetailsFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_queue_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = PluginDetailsForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_ui.plugin_detail_form';

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queue_detail_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Memory';

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueueService();
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\QueueDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Memory');
    $this->assertSession()->responseContains('A non-persistent, per-request memory queue (not useful on production systems).');
    $this->assertSession()->responseContains('Close');
    $ajax = $this->postAjaxForm([], 'Close');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

}

<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\PluginDetailsForm;

/**
 * Tests \Drupal\purge_ui\Form\PluginDetailsForm (for queuers).
 *
 * @group purge
 */
class QueuerDetailsFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_queuer_test'];

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
  protected $route = 'purge_ui.queuer_detail_form';

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => 'a'];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist'];

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Queuer A';

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService(['a']);
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\QueuerDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Queuer A');
    $this->assertSession()->responseContains('Test queuer A.');
    $this->assertSession()->responseContains('Close');
    $ajax = $this->postAjaxForm([], 'Close');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

}

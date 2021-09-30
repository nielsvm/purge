<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\PluginDetailsForm;

/**
 * Tests \Drupal\purge_ui\Form\PluginDetailsForm (for purgers).
 *
 * @group purge
 */
class PurgerDetailsFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_purger_test'];

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
  protected $route = 'purge_ui.purger_detail_form';

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => 'id0'];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist'];

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Purger A';

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializePurgersService(['a']);
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\PurgerDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Purger A');
    $this->assertSession()->responseContains('Test purger A.');
    $this->assertSession()->responseContains('Close');
    $ajax = $this->postAjaxForm([], 'Close');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

}

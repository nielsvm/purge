<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\PurgerDeleteForm;

/**
 * Tests \Drupal\purge_ui\Form\PurgerDeleteForm.
 *
 * @group purge
 */
class PurgerDeleteFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = PurgerDeleteForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.purger_delete_form';

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
  protected $routeTitle = 'Are you sure you want to delete Purger A?';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializePurgersService(['a']);
  }

  /**
   * Tests that the "No" cancel button is present.
   */
  public function testNoPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('No');
  }

  /**
   * Tests "No" cancel button form submit.
   */
  public function testNoSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $ajax = $this->postAjaxForm([], 'No');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

  /**
   * Tests that 'Yes, delete..', deletes the purger and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testDeletePurger(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Yes, delete this purger!');
    $this->assertSame(['id0' => 'a'], $this->purgePurgers->getPluginsEnabled());
    $ajax = $this->postAjaxForm([], 'Yes, delete this purger!');
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->purgePurgers->reload();
    $this->assertSame(TRUE, is_array($this->purgePurgers->getPluginsEnabled()));
    $this->assertEmpty($this->purgePurgers->getPluginsEnabled());
  }

  /**
   * Assert that deleting a purger that does not exist, passes silently.
   */
  public function testDeletePurgerWhichDoesNotExist(): void {
    $this->drupalLogin($this->adminUser);
    $ajax = $this->postAjaxForm([], 'Yes, delete this purger!', $this->routeParametersInvalid);
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function testRouteAccess(): void {
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(403);
    // This overloaded test exists because the form is always accessible, even
    // under bad input, to allow the form submit handler to emit its Ajax
    // even directly after the purger got deleted.
    //
    // @see \Drupal\purge_ui\Form\PurgerDeleteForm::deletePurger
    $path = $this->getPath($this->routeParametersInvalid);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
  }

}

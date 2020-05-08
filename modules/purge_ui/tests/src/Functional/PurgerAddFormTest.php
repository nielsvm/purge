<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerAddForm.
 *
 * @group purge_ui
 */
class PurgerAddFormTest extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The route that renders the form.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_add_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_ui', 'purge_purger_test'];

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess(): void {
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->initializePurgersService([]);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->initializePurgersService(['a', 'b', 'c']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->initializePurgersService(['a', 'b', 'c', 'withform', 'good']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(404);
    $this->initializePurgersService(['a', 'b']);
  }

  /**
   * Tests clicking the add button, adds it and closes the screen.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAdd(): void {
    $this->initializePurgersService(['a', 'withform', 'good']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->responseContains('Add');
    $this->assertTrue(count($this->purgePurgers->getPluginsEnabled()) === 3);
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), ['plugin_id' => 'c'], 'Add');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertTrue(in_array('c', $this->purgePurgers->getPluginsEnabled()));
    $this->assertEquals(3, count($json));
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancel(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->responseContains('Cancel');
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), [], 'Cancel');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

  /**
   * Tests the 'plugin_id' form element for listing only available purgers.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   */
  public function testTwoAvailablePurgers(): void {
    $this->initializePurgersService(['c', 'withform']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->fieldExists('plugin_id');
    $this->assertSession()->pageTextContains('Purger A');
    $this->assertSession()->pageTextContains('Purger B');
    $this->assertSession()->pageTextNotContains('Configurable purger');
    $this->assertSession()->fieldValueEquals('op', 'Cancel');
    $this->assertSession()->fieldValueEquals('op', 'Add');
  }

}

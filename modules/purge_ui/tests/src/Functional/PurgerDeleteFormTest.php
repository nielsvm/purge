<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerDeleteForm.
 *
 * @group purge_ui
 */
class PurgerDeleteFormTest extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.purger_delete_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_purger_test', 'purge_ui'];

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
    $this->initializePurgersService(['c']);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertSession()->statusCodeEquals(200);
    // Non-existing ID's also need to get passed through to the form because
    // else the submit would break exactly after the purger was deleted.
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo(): void {
    $this->initializePurgersService(['c']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertSession()->responseContains('No');
    $json = $this->drupalPostForm(Url::fromRoute($this->route, ['id' => 'id0'])->toString(), [], 'No');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

  /**
   * Tests that 'Yes, delete..', deletes the purger and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testDelete(): void {
    $this->initializePurgersService(['c']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertSession()->responseContains('Yes, delete this purger!');
    $this->assertTrue(array_key_exists('id0', $this->purgePurgers->getPluginsEnabled()));
    $json = $this->drupalPostForm(Url::fromRoute($this->route, ['id' => 'id0'])->toString(), [], 'Yes, delete this purger!');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertTrue(is_array($this->purgePurgers->getPluginsEnabled()));
    $this->assertTrue(empty($this->purgePurgers->getPluginsEnabled()));
    $this->assertEquals(3, count($json));
    // Assert that deleting a purger that does not exist, passes silently.
    $json = $this->drupalPostForm(Url::fromRoute($this->route, ['id' => 'doesnotexist'])->toString(), [], 'Yes, delete this purger!');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

}

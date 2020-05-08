<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerMoveForm.
 *
 * @group purge_ui
 */
class PurgerMoveFormTest extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The route that renders the form (moving down).
   *
   * @var string
   */
  protected $routeDown = 'purge_ui.purger_move_down_form';

  /**
   * The route that renders the form (moving up).
   *
   * @var string
   */
  protected $routeUp = 'purge_ui.purger_move_up_form';

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
    $args_down = ['id' => 'id0', 'direction' => 'down'];
    $args_up = ['id' => 'id0', 'direction' => 'up'];
    $this->initializePurgersService(['a']);
    $this->drupalGet(Url::fromRoute($this->routeDown, $args_down));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet(Url::fromRoute($this->routeUp, $args_up));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->routeDown, $args_down));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet(Url::fromRoute($this->routeUp, $args_up));
    $this->assertSession()->statusCodeEquals(200);
    $args_down = ['id' => 'doesnotexist', 'direction' => 'down'];
    $args_up = ['id' => 'doesnotexist', 'direction' => 'up'];
    $this->drupalGet(Url::fromRoute($this->routeDown, $args_down));
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet(Url::fromRoute($this->routeUp, $args_up));
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerMoveForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo(): void {
    $args_down = ['id' => 'id0', 'direction' => 'down'];
    $args_up = ['id' => 'id0', 'direction' => 'up'];
    $this->initializePurgersService(['a']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->routeDown, $args_down));
    $this->assertSession()->responseContains('No');
    $this->drupalGet(Url::fromRoute($this->routeUp, $args_up));
    $this->assertSession()->responseContains('No');
    $json = $this->drupalPostForm(Url::fromRoute($this->routeDown, $args_down)->toString(), [], 'No');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
    $json = $this->drupalPostForm(Url::fromRoute($this->routeUp, $args_up)->toString(), [], 'No');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

  /**
   * Tests that 'Yes!', moves the purger in order and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testMove(): void {
    $down = ['id' => 'id0', 'direction' => 'down'];
    $up = ['id' => 'id2', 'direction' => 'up'];
    $this->initializePurgersService(['a', 'b', 'c']);
    $this->drupalLogin($this->adminUser);
    // Test that the initial order of the purgers is exactly as configured.
    $this->assertEquals(['a', 'b', 'c'], array_values($this->purgePurgers->getPluginsEnabled()));
    // Test the 'down' variant of the move form.
    $this->drupalGet(Url::fromRoute($this->routeDown, $down));
    $this->assertSession()->responseContains('Do you want to move Purger A down in the execution order?');
    $json = $this->drupalPostForm(Url::fromRoute($this->routeDown, $down)->toString(), [], 'Yes!');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertEquals(['b', 'a', 'c'], array_values($this->purgePurgers->getPluginsEnabled()));
    // Test the 'up' variant of the move form.
    $this->drupalGet(Url::fromRoute($this->routeUp, $up));
    $this->assertSession()->responseContains('Do you want to move Purger C up in the execution order?');
    $json = $this->drupalPostForm(Url::fromRoute($this->routeUp, $up)->toString(), [], 'Yes!');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertEquals(['b', 'c', 'a'], array_values($this->purgePurgers->getPluginsEnabled()));
  }

}

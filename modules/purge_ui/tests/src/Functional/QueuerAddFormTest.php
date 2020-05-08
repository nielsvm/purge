<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueuerAddForm.
 *
 * @group purge_ui
 */
class QueuerAddFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.queuer_add_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_ui', 'purge_queuer_test'];

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
    $this->initializeQueuersService([]);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeQueuersService(['a', 'b', 'c']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeQueuersService(
      [
        'a',
        'b',
        'c',
        'withform',
        'purge_ui_block_queuer',
      ]
    );
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(404);
    $this->initializeQueuersService(['a', 'b']);
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
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
   * Tests clicking the add button, adds it and closes the screen.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAdd(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->responseContains('Add');
    $this->assertSession()->responseNotContains('Queuer A');
    $this->assertSession()->responseNotContains('Queuer B');
    $this->assertSession()->responseContains('Queuer C');
    $this->assertSession()->responseContains('Queuer with form');
    $this->assertTrue(count($this->purgeQueuers->getPluginsEnabled()) === 2);
    $this->assertTrue(in_array('a', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertTrue(in_array('b', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertFalse(in_array('c', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertFalse(in_array('withform', $this->purgeQueuers->getPluginsEnabled()));
    // Test that adding the plugin succeeds and results in a redirect command,
    // which only happens when it was able to save the data.
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), ['id' => 'c'], 'Add');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->assertEquals(3, count($json));
  }

}

<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorAddForm.
 *
 * @group purge_ui
 */
class ProcessorAddFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.processor_add_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_ui', 'purge_processor_test'];

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
    $this->initializeProcessorsService([]);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeProcessorsService(['a', 'b', 'c']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeProcessorsService(
      [
        'a',
        'b',
        'c',
        'withform',
        'purge_ui_block_processor',
      ]
    );
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(404);
    $this->initializeProcessorsService(['a', 'b']);
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
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
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAdd(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->responseContains('Add');
    $this->assertSession()->responseNotContains('Processor A');
    $this->assertSession()->responseNotContains('Processor B');
    $this->assertSession()->responseContains('Processor C');
    $this->assertSession()->responseContains('Processor with form');
    $this->assertTrue(count($this->purgeProcessors->getPluginsEnabled()) === 2);
    $this->assertTrue(in_array('a', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertTrue(in_array('b', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertFalse(in_array('c', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertFalse(in_array('withform', $this->purgeProcessors->getPluginsEnabled()));
    // Test that adding the plugin succeeds and results in a redirect command,
    // which only happens when it was able to save the data.
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), ['id' => 'c'], 'Add');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->assertEquals(3, count($json));
  }

}

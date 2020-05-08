<?php

namespace Drupal\Tests\purge_queuer_coretags\Functional;

use Drupal\Tests\purge_ui\Functional\QueuerConfigFormTestBase;

/**
 * Tests \Drupal\purge_queuer_coretags\Form\ConfigurationForm.
 *
 * @group purge
 */
class ConfigurationFormTest extends QueuerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_queuer_coretags', 'purge_coretags_removed_test'];

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'coretags';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\purge_queuer_coretags\Form\ConfigurationForm';

  /**
   * Test the blacklist section.
   */
  public function testFieldExistence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    // Assert its standard fields and values.
    $this->assertSession()->fieldExists('edit-blacklist-0');
    $this->assertSession()->responseContains('config:');
    $this->assertSession()->fieldExists('edit-blacklist-1');
    $this->assertSession()->responseContains('4xx-response');
    $this->assertSession()->fieldExists('edit-blacklist-3');
    $this->assertSession()->fieldNotExists('edit-blacklist-4');
    $this->assertSession()->pageTextContains('Add prefix');
    $this->assertSession()->pageTextContains('if you know what you are doing');
    // Test that direct configuration changes are reflected properly.
    $this->config('purge_queuer_coretags.settings')
      ->set('blacklist', ['a', 'b', 'c', 'd'])
      ->save();
    $this->drupalGet($this->route);
    $this->assertSession()->fieldExists('edit-blacklist-0');
    $this->assertSession()->fieldExists('edit-blacklist-1');
    $this->assertSession()->fieldExists('edit-blacklist-2');
    $this->assertSession()->fieldExists('edit-blacklist-3');
    $this->assertSession()->fieldNotExists('edit-blacklist-4');
    // Submit 1 valid and three empty values, test the re-rendered form.
    $form = $this->getFormInstance();
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValue('blacklist', ['testvalue', '', '', '']);
    $this->formBuilder->submitForm($form, $form_state);
    $this->assertEquals(0, count($form_state->getErrors()));
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('testvalue');
    $this->assertSession()->fieldExists('edit-blacklist-0');
    $this->assertSession()->fieldNotExists('edit-blacklist-1');
  }

}

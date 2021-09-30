<?php

namespace Drupal\Tests\purge_queuer_coretags\Functional;

use Drupal\purge_queuer_coretags\Form\ConfigurationForm;
use Drupal\Tests\purge_ui\Functional\Form\Config\QueuerConfigFormTestBase;

/**
 * Tests \Drupal\purge_queuer_coretags\Form\ConfigurationForm.
 *
 * @group purge
 */
class QueuerConfigFormTest extends QueuerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queuer_coretags', 'purge_coretags_removed_test'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'coretags';

  /**
   * {@inheritdoc}
   */
  protected $formClass = ConfigurationForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_queuer_coretags.configuration_form';

  /**
   * {@inheritdoc}
   */
  public function testDefaultFormState(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    // Assert its standard fields and values.
    $this->assertSession()->fieldExists('edit-blacklist-0');
    $this->assertSession()->fieldExists('edit-blacklist-1');
    $this->assertSession()->fieldExists('edit-blacklist-2');
    $this->assertSession()->fieldExists('edit-blacklist-3');
    $this->assertSession()->fieldExists('edit-blacklist-4');
    $this->assertSession()->fieldExists('edit-blacklist-5');
    $this->assertSession()->fieldExists('edit-blacklist-6');
    $this->assertSession()->fieldExists('edit-blacklist-7');
    $this->assertSession()->responseContains('value="4xx-response"');
    $this->assertSession()->responseContains('value="config:core.extension"');
    $this->assertSession()->responseContains('value="extensions"');
    $this->assertSession()->responseContains('value="config:purge"');
    $this->assertSession()->responseContains('value="theme_registry"');
    $this->assertSession()->responseContains('value="config:field.storage"');
    $this->assertSession()->responseContains('value="route_match"');
    $this->assertSession()->responseContains('value="routes"');
    $this->assertSession()->pageTextContains('Add prefix');
    $this->assertSession()->pageTextContains('if you know what you are doing');
  }

  /**
   * {@inheritdoc}
   */
  public function testSaveConfigurationSubmit(): void {
    // Test that direct configuration changes are reflected properly.
    $this->config('purge_queuer_coretags.settings')
      ->set('blacklist', ['a', 'b', 'c', 'd'])
      ->save();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->fieldExists('edit-blacklist-0');
    $this->assertSession()->fieldExists('edit-blacklist-1');
    $this->assertSession()->fieldExists('edit-blacklist-2');
    $this->assertSession()->fieldExists('edit-blacklist-3');
    $this->assertSession()->fieldNotExists('edit-blacklist-4');
    // Submit 1 valid and three empty values, test the re-rendered form.
    $form = $this->getFormInstance();
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValue('blacklist', ['testvalue', '', '', '']);
    $this->formBuilder()->submitForm($form, $form_state);
    $this->assertSame(0, count($form_state->getErrors()));
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('testvalue');
    $this->assertSession()->fieldExists('edit-blacklist-0');
    $this->assertSession()->fieldNotExists('edit-blacklist-1');
  }

}

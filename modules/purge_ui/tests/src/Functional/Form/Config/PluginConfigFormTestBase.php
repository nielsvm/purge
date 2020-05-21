<?php

namespace Drupal\Tests\purge_ui\Functional\Form\Config;

use Drupal\Tests\purge_ui\Functional\Form\AjaxFormTestBase;

/**
 * Testbase for purge_ui configuration forms (for plugins).
 *
 * @see \Drupal\purge_ui\Form\PluginConfigFormBase
 */
abstract class PluginConfigFormTestBase extends AjaxFormTestBase {

  /**
   * Indicate whether this test tests a dialog route.
   *
   * @var bool
   */
  protected $dialogRouteTest = FALSE;

  /**
   * The ID for the loaded instance of the plugin, usually 'id0'.
   *
   * @var string
   */
  protected $instanceId = 'id0';

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var null|string
   */
  protected $pluginId = NULL;

  /**
   * Form arguments passed to FormStateInterface::addBuildInfo().
   *
   * @var array
   */
  protected $formArgs = [
    0 => [
      'id' => NULL,
      'dialog' => FALSE,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => NULL];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist'];

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->routeParameters['id'] = $this->pluginId;
    $this->formArgs[0]['id'] = $this->pluginId;
    $this->initializePlugin();
  }

  /**
   * Initialize the plugin instance required to render the form.
   */
  protected function initializePlugin(): void {
    throw new \Exception("Derivatives need to implement ::initializePlugin().");
  }

  /**
   * {@inheritdoc}
   */
  protected function shouldReturnAjaxProperties(): bool {
    return $this->dialogRouteTest;
  }

  /**
   * {@inheritdoc}
   */
  protected function assertTestProperties(): void {
    parent::assertTestProperties();
    $this->assertNotNull($this->pluginId, '$pluginId not set');
  }

  /**
   * Tests save button presence/absence.
   */
  public function testSaveConfigurationPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertActionExists('edit-submit', 'Save configuration');
  }

  /**
   * Tests save button presence/absence.
   */
  public function testSaveConfigurationSubmit(): void {
    throw new \LogicException("Implementation of testSaveConfigurationSubmit mandatory!");
  }

  /**
   * Tests cancel button presence/absence.
   */
  public function testCancelPresenceOrAbsence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    if ($this->dialogRouteTest) {
      $this->assertActionExists('edit-cancel', 'Cancel');
    }
    else {
      $this->assertActionNotExists('edit-cancel', 'Cancel');
    }
  }

  /**
   * Tests cancel button form submit.
   */
  public function testCancelSubmit(): void {
    $this->drupalLogin($this->adminUser);
    if ($this->dialogRouteTest) {
      $ajax = $this->postAjaxForm([], 'Cancel');
      $this->assertAjaxCommandCloseModalDialog($ajax);
      $this->assertAjaxCommandsTotal($ajax, 1);
    }
    else {
      $this->assertFalse(FALSE, "Don't mark this test as risky!");
    }
  }

}

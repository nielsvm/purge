<?php

namespace Drupal\Tests\purge_ui\Functional;

/**
 * Testbase for \Drupal\purge_ui\Form\ProcessorConfigFormBase derivatives.
 */
abstract class ProcessorConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.processor_config_form';

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = 'purge_ui.processor_config_dialog_form';

  /**
   * {@inheritdoc}
   */
  protected function assertFormTitle(): void {
    $label = $this->purgeProcessors->getPlugins()[$this->plugin]['label'];
    $this->assertSession()->titleEquals("Configure $label | Drupal");
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin(): void {
    $this->initializeProcessorsService([$this->plugin]);
  }

}

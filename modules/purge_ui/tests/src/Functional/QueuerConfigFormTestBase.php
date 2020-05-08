<?php

namespace Drupal\Tests\purge_ui\Functional;

/**
 * Testbase for \Drupal\purge_ui\Form\QueuerConfigFormBase derivatives.
 */
abstract class QueuerConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.queuer_config_form';

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = 'purge_ui.queuer_config_dialog_form';

  /**
   * {@inheritdoc}
   */
  protected function assertFormTitle(): void {
    $label = $this->purgeQueuers->getPlugins()[$this->plugin]['label'];
    $this->assertSession()->titleEquals("Configure $label | Drupal");
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin(): void {
    $this->initializeQueuersService([$this->plugin]);
  }

}

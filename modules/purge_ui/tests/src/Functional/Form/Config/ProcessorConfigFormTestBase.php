<?php

namespace Drupal\Tests\purge_ui\Functional\Form\Config;

/**
 * Testbase for \Drupal\purge_ui\Form\ProcessorConfigFormBase derivatives.
 */
abstract class ProcessorConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.processor_config_form';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    if ($this->dialogRouteTest) {
      $this->route = 'purge_ui.processor_config_dialog_form';
    }

    // Set the expected route title for the test subject.
    $label = $this->purgeProcessors->getPlugins()[$this->pluginId]['label'];
    $this->routeTitle = sprintf("Configure %s", $label);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin(): void {
    $this->initializeProcessorsService([$this->pluginId]);
  }

}

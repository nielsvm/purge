<?php

namespace Drupal\Tests\purge_ui\Functional\Form\Config;

/**
 * Testbase for \Drupal\purge_ui\Form\PurgerConfigFormBase derivatives.
 */
abstract class PurgerConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.purger_config_form';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    if ($this->dialogRouteTest) {
      $this->route = 'purge_ui.purger_config_dialog_form';
    }

    // Purgers are refered to by instance id.
    $this->routeParameters['id'] = $this->instanceId;
    $this->formArgs[0]['id'] = $this->instanceId;

    // Set the expected route title for the test subject.
    $label = $this->purgePurgers->getLabels()[$this->instanceId];
    $this->routeTitle = sprintf("Configure %s", $label);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin(): void {
    $this->initializePurgersService([$this->pluginId]);
  }

}

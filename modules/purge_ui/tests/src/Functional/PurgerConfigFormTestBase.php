<?php

namespace Drupal\Tests\purge_ui\Functional;

/**
 * Testbase for \Drupal\purge_ui\Form\PurgerConfigFormBase derivatives.
 */
abstract class PurgerConfigFormTestBase extends PluginConfigFormTestBase {

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_config_form';

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = 'purge_ui.purger_config_dialog_form';

  /**
   * {@inheritdoc}
   */
  protected function assertFormTitle(): void {
    $label = $this->purgePurgers->getLabels()['id0'];
    $this->assertSession()->titleEquals("Configure $label | Drupal");
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin(): void {
    $this->initializePurgersService([$this->plugin]);
  }

  /**
   * Return the ID argument given to the form.
   */
  protected function getId(): string {
    // Since initializePurgersService() autogenerates the IDs, ours is known.
    return 'id0';
  }

}

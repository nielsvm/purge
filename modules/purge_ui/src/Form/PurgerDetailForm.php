<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgerDetailForm.
 */

namespace Drupal\purge_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\purge_ui\Form\CloseDialogTrait;

/**
 * Show more information on purger {id}.
 */
class PurgerDetailForm extends FormBase {
  use CloseDialogTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.purger_detail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definition = $form_state->getBuildInfo()['args'][0]['definition'];
    $form['description'] = [
      '#markup' => $definition['description']
    ];

    // Set dialog code and add the close button.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['close'] = [
      '#type' => 'submit',
      '#value' => $this->t('Close'),
      '#weight' => -10,
      '#ajax' => ['callback' => '::closeDialog']
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}

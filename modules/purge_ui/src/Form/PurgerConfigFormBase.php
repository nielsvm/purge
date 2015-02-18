<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgerConfigFormBase.
 */

namespace Drupal\purge_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides a base class for purger configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs from the purge
 * configuration page. For testing purposes, a dialogless variant of the form
 * can be found on /admin/config/development/performance/purge/PLUGINID.
 */
abstract class PurgerConfigFormBase extends ConfigFormBase {

  /**
   * Respond a CloseModalDialogCommand to close the modal dialog.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function closeDialog(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * Determine if this is a AJAX dialog request or not.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   */
  public function isDialog(array &$form, FormStateInterface $form_state) {
    if (isset($form['#attached']['library'])) {
      if (in_array('core/drupal.dialog.ajax', $form['#attached']['library'])) {
        return TRUE;
      }
    }
    if ($this->getRequest()->get('dialog', FALSE)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;

    // If we're being rendered as AJAX modal dialog, change the form.
    if ($this->isDialog($form, $form_state)) {
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $form['#prefix'] = '<div id="purger-config-dialog-form">';
      $form['#suffix'] = '</div>';

      // Adapt the button to send commands and add a cancel button.
      $form['actions']['submit']['#ajax'] = ['callback' => '::submitForm'];
      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#weight' => -10,
        '#ajax' => ['callback' => '::closeDialog']
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->isDialog($form, $form_state)) {
      $response = new AjaxResponse();
      if ($form_state->getErrors()) {
        unset($form['#prefix'], $form['#suffix']);
        $form['status_messages'] = [
          '#theme' => 'status_messages',
          '#weight' => -10,
        ];
        $response->addCommand(new HtmlCommand('#purger-config-dialog-form', $form));
      }
      else {
        $response->addCommand(new CloseModalDialogCommand());
      }
      return $response;
    }
    return parent::submitForm($form, $form_state);
  }

}

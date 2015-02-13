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
 */
abstract class PurgerConfigFormBase extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    $form = parent::buildForm($form, $form_state);

    // @see \Drupal\editor\Form\EditorLinkDialog for source example.
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="purger-config-dialog-form">';
    $form['#suffix'] = '</div>';

    // Override the normal handling of a config form.
    $form['actions']['submit']['#submit'] = [];
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitForm',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

}

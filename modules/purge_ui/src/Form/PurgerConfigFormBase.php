<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgerConfigFormBase.
 */

namespace Drupal\purge_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Provides a base class for purger configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs through links
 * pointing at /admin/config/development/performance/purge/purger/ID/dialog. You
 * can use /admin/config/development/performance/purge/purger/ID as testing
 * variant that works outside of the modal dialog.
 */
abstract class PurgerConfigFormBase extends ConfigFormBase {
  use CloseDialogTrait;

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
    return $form_state->getBuildInfo()['args'][0]['dialog'];
  }

  /**
   * Retrieve the unique instance ID for the purger being configured.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. Plugins with 'multi_instance' set
   * to TRUE in their annotations, are likely to require the use of this method
   * to differentiate their purger instance (e.g. through configuration).
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId()
   *
   * @return string
   *   The unique identifier for this purger instance.
   */
  public function getId(FormStateInterface $form_state) {
    return $form_state->getBuildInfo()['args'][0]['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

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
        $response->addCommand(new ReloadConfigFormCommand('edit-purgers'));
      }
      return $response;
    }
    return parent::submitForm($form, $form_state);
  }

}

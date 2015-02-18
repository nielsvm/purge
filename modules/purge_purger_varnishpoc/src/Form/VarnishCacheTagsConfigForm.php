<?php

/**
 * @file
 * Contains \Drupal\purge_purger_varnishpoc\Form\VarnishCacheTagsConfigForm.
 */

namespace Drupal\purge_purger_varnishpoc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;

/**
 * Configuration elements for the Varnish cache tags purger.
 */
class VarnishCacheTagsConfigForm extends PurgerConfigFormBase {

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
    return 'purge_purger_varnishpoc.configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Varnish URL'),
      '#default_value' => '',
      '#required' => TRUE,
      '#description' => $this->t('The URL of the Varnish instance to send <code>BAN</code> requests to.')
    ];
    $form['timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0,
      '#title' => $this->t('Timeout'),
      '#default_value' => 1.0,
      '#required' => TRUE,
    ];
    $form['connection_timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0,
      '#title' => $this->t('Connection timeout'),
      '#default_value' => 0.5,
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

}

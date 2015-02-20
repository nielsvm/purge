<?php

/**
 * @file
 * Contains \Drupal\purge_purger_varnishpoc\Form\VarnishTagConfigForm.
 */

namespace Drupal\purge_purger_varnishpoc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;

/**
 * Configuration elements for the Varnish cache tags purger.
 */
class VarnishTagConfigForm extends PurgerConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['purge_purger_varnishpoc.settings'];
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
    // @TODO: url needs to be split out into hostname, port, path, etc.
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Varnish URL'),
      '#default_value' => $this->config('purge_purger_varnishpoc.settings')->get('url'),
      '#required' => TRUE,
      '#description' => $this->t('The URL of the Varnish instance to send <code>BAN</code> requests to.')
    ];
    $form['header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#default_value' => $this->config('purge_purger_varnishpoc.settings')->get('header'),
      '#required' => TRUE,
      '#description' => $this->t('The outbound HTTP header that identifies the tag to be purged.')
    ];
    $form['timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0,
      '#title' => $this->t('Timeout'),
      '#default_value' => $this->config('purge_purger_varnishpoc.settings')->get('timeout'),
      '#required' => TRUE,
    ];
    $form['connect_timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0,
      '#title' => $this->t('Connection timeout'),
      '#default_value' => $this->config('purge_purger_varnishpoc.settings')->get('connect_timeout'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $c = $this->config('purge_purger_varnishpoc.settings');
    $c->set('url', $form_state->getValue('url'))->save();
    $c->set('header', $form_state->getValue('header'))->save();
    $c->set('timeout', $form_state->getValue('timeout'))->save();
    $c->set('connect_timeout', $form_state->getValue('connect_timeout'))->save();
    return parent::submitForm($form, $form_state);
  }

}

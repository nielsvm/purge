<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\QueuerEnableForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge\Plugin\Purge\Queuer\ServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Enable a queuer service.
 */
class QueuerEnableForm extends ConfigFormBase {
  use CloseDialogTrait;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\ServiceInterface
   */
  protected $purgeQueuers;

  /**
   * Constructs a QueuerEnableForm object.
   *
   * @param \Drupal\purge\Plugin\Purge\Queuer\ServiceInterface $purge_queuers
   *   The purge queuers registry service.
   *
   * @return void
   */
  public function __construct(ServiceInterface $purge_queuers) {
    $this->purgeQueuers = $purge_queuers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.queuers'));
  }


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
    return 'purge_ui.queuer_enable_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // List all available queuer services.
    $options = [];
    foreach ($this->purgeQueuers->getDisabled() as $id => $queuer) {
      $options[$id] = t("@title: @description", ['@title' => $queuer->getTitle(), '@description' => $queuer->getDescription()]);
    }
    $form['id'] = [
      '#default_value' => count($options) ? key($options) : NULL,
      '#type' => 'radios',
      '#options' => $options
    ];

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#access' => count($options),
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t("Add"),
      '#ajax' => ['callback' => '::enableQueuer']
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#weight' => -10,
      '#ajax' => ['callback' => '::closeDialog']
    ];
    return $form;
  }

  /**
   * Enable the queuer service.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function enableQueuer(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $id = $form_state->getValue('id');
    $response->addCommand(new CloseModalDialogCommand());
    if (isset($this->purgeQueuers->getDisabled()[$id])) {
      $this->purgeQueuers->get($id)->enable();
      $response->addCommand(new ReloadConfigFormCommand('edit-queuers'));
    }
    return $response;
  }

}

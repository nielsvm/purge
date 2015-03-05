<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\ProcessorEnableForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\purge\Processor\ServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;

/**
 * Enable a processor service.
 */
class ProcessorEnableForm extends ConfigFormBase {
  use CloseDialogTrait;

  /**
   * @var \Drupal\purge\Processor\ServiceInterface
   */
  protected $purgeProcessors;

  /**
   * Constructs a ProcessorEnableForm object.
   *
   * @param \Drupal\purge\Processor\ServiceInterface $purge_processors
   *   The purge processors registry service.
   *
   * @return void
   */
  public function __construct(ServiceInterface $purge_processors) {
    $this->purgeProcessors = $purge_processors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.processors'));
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
    return 'purge_ui.processor_enable_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // List all available processor services.
    $options = [];
    foreach ($this->purgeProcessors->getDisabled() as $id => $processor) {
      $options[$id] = t("@title: @description", ['@title' => $processor->getTitle(), '@description' => $processor->getDescription()]);
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
      '#ajax' => ['callback' => '::enableProcessor']
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
   * Enable the processor service.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function enableProcessor(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $id = $form_state->getValue('id');
    $response->addCommand(new CloseModalDialogCommand());
    if (isset($this->purgeProcessors->getDisabled()[$id])) {
      $this->purgeProcessors->get($id)->enable();
      $options = ['fragment' => 'edit-purgers', 'query' => ['processors' => time()]];
      $response->addCommand(new RedirectCommand(Url::fromRoute('purge_ui.config_form', [], $options)->toString()));
    }
    return $response;
  }

}

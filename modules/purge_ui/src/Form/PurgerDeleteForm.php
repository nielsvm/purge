<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgerDeleteForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\purge\Purger\ServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;

/**
 * Delete the {id} purger instance.
 */
class PurgerDeleteForm extends ConfirmFormBase {
  use CloseDialogTrait;

  /**
   * Unique instance ID for the purger being deleted.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin definition for the purger being deleted.
   *
   * @var array
   */
  protected $definition;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a DeletePurgerForm object.
   *
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purgers
   *   The purger service.
   *
   * @return void
   */
  public function __construct(ServiceInterface $purge_purgers) {
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.purgers'));
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
    return 'purge_ui.purger_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->id = $form_state->getBuildInfo()['args'][0]['id'];
    $this->definition = $form_state->getBuildInfo()['args'][0]['definition'];
    $form = parent::buildForm($form, $form_state);

    // This is rendered as a modal dialog, so we need to set some extras.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->getConfirmText(),
      '#ajax' => ['callback' => '::deletePurger']
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No'),
      '#weight' => -10,
      '#ajax' => ['callback' => '::closeDialog']
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, remove this purger!');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove @label?', ['@label' => $this->definition['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Delete the purger.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function deletePurger(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    if (isset($this->purgePurgers->getPluginsEnabled()[$this->id])) {
      $options = ['fragment' => 'edit-purgers', 'query' => ['unique' => time()]];
      $response->addCommand(new RedirectCommand((string) Url::fromRoute('purge_ui.config_form', [], $options)));
      $this->purgePurgers->deletePluginsEnabled([$this->id]);
    }
    return $response;
  }

}

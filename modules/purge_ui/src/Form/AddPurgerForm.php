<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\AddPurgerForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\purge\Purger\ServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;

/**
 * Add a new instance of a purger plugin to purge.
 */
class AddPurgerForm extends ConfigFormBase {
  use CloseDialogTrait;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a AddPurgerForm object.
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
    return 'purge_ui.purger_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Provide all plugins that can be added.
    $available = $this->purgePurgers->getPluginsAvailable();
    $plugins = $this->purgePurgers->getPlugins();
    foreach ($plugins as $plugin_id => $definition) {
      if (!in_array($plugin_id, $available)) {
        unset($plugins[$plugin_id]);
      }
      else {
        $plugins[$plugin_id] = $definition['label'];
      }
    }
    $form['plugin_id'] = [
      '#access' => count($plugins),
      '#default_value' => count($plugins) ? key($plugins) : NULL,
      '#type' => 'radios',
      '#options' => $plugins
    ];

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#access' => count($plugins),
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t("Add"),
      '#ajax' => ['callback' => '::addPurger']
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
   * Add the purger.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function addPurger(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $enabled = $this->purgePurgers->getPluginsEnabled();
    $plugin_id = $form_state->getValue('plugin_id');
    $response->addCommand(new CloseModalDialogCommand());
    if (in_array($plugin_id, $this->purgePurgers->getPluginsAvailable())) {
      $enabled[$this->purgePurgers->createId()] = $plugin_id;
      $this->purgePurgers->setPluginsEnabled($enabled);
      $options = ['fragment' => 'edit-purgers', 'query' => ['unique' => time()]];
      $response->addCommand(new RedirectCommand((string) Url::fromRoute('purge_ui.config_form', [], $options)));
    }
    return $response;
  }

}

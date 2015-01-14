<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgeUiMainConfigForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Purger\ServiceInterface as PurgerServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;

/**
 * Configure settings for the Purge core APIs.
 */
class PurgeUiMainConfigForm extends ConfigFormBase {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurger;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a PurgeUiMainConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purger
   *   The purger service.
   * @param \Drupal\purge\Queue\ServiceInterface $purge_queue
   *   The purge queue service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PurgerServiceInterface $purge_purger, QueueServiceInterface $purge_queue) {
    $this->configFactory = $config_factory;
    $this->purgePurger = $purge_purger;
    $this->purgeQueue = $purge_queue;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('purge.purger'),
      $container->get('purge.queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.core_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#type' => 'item',
      '#description' => $this->t('The purge module provides a generic (external)
        cache invalidation interface, technology agnostic.')
    ];

    // Settings related to the purge.purger service.
    $plugins = $this->configFactory->get('purge.purger')->get('plugins');
    $purgers = $this->purgePurger->getPlugins(TRUE);
    $form['purger'] = [
      '#type' => 'details',
      '#title' => $this->t('Purger'),
      '#description' => $this->t('The purger is the plugin that executes purge instructions on the external cache system.'),
      '#open' => TRUE,
    ];
    if (empty($purgers)) {
      $form['purger']['purger_nothing_available'] = [
        '#type' => 'item',
        '#description' => $this->t("You don't have any third-party modules
          installed that provide purgers. Please install the appropriate module
          that provides a purger for your external cache system.")
      ];
    }
    else {
      $form['purger']['purger_detection'] = [
        '#default_value' => ($plugins == 'automatic_detection') ? $plugins : 'manual',
        '#type' => 'radios',
        '#options' => [
          'automatic_detection' => $this->t('Use all available purgers'),
          'manual' => $this->t('Select plugins:'),
        ],
      ];
      $form['purger']['purger_plugins'] = [
        '#default_value' => $this->purgePurger->getPluginsEnabled(),
        '#options' => $purgers,
        '#type' => 'checkboxes',
        '#description' => $this->t('When multiple purgers are enabled, each purge instruction will be sent to all plugins. If one plugin fails to execute a purge, all purgers are considered to have failed.'),
        '#states' => [
          'disabled' => [
            ':input[name="purger_detection"]' => ['value' => 'automatic_detection'],
          ],
        ],
      ];
    }

    // Settings related to the purge.queue service.
    $form['queue'] = [
      '#type' => 'details',
      '#title' => t('Queue'),
      '#description' => $this->t('The queue is where purge instructions are getting stored in.'),
      '#open' => TRUE,
    ];
    $form['queue']['queue_plugin'] = [
      '#default_value' => $this->configFactory->get('purge.queue')->get('plugin'),
      '#options' => $this->purgeQueue->getPlugins(TRUE),
      '#type' => 'radios',
      '#description' => $this->t('The queue service is backed by a queue plugin
        that stores the purge instructions. On most setups the database provider
        scales best up to a couple of thousand items in the queue, whereas the
        file queue plugin depends much more on the underlying IO performance
        of your server.')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('purger_plugins')) {
      if ($form_state->getValue('purger_detection') == 'automatic_detection') {
        $this->configFactory->get('purge.purger')
          ->set('plugins', 'automatic_detection')
          ->save();
      }
      else {
        $purgers = [];
        foreach ($form_state->getValue('purger_plugins') as $option) {
          if (is_string($option)) {
            $purgers[] = $option;
          }
        }
        $this->configFactory->get('purge.purger')
          ->set('plugins', implode(',', $purgers))
          ->save();
      }
    }
    if ($form_state->hasValue('queue_plugin')) {
      $this->configFactory->get('purge.queue')
        ->set('plugin', $form_state->getValue('queue_plugin'))
        ->save();
    }
    parent::submitForm($form, $form_state);
  }
}

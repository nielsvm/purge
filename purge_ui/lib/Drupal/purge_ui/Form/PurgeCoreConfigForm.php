<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgeCoreConfigForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Queue\QueueServiceInterface;

/**
 * Configure settings for the Purge core APIs.
 */
class PurgeCoreConfigForm extends ConfigFormBase {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a PurgeCoreConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueServiceInterface $purge_queue) {
    $this->configFactory = $config_factory;
    $this->purgeQueue = $purge_queue;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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
  public function buildForm(array $form, array &$form_state) {

    // Settings related to the purge.queue service.
    $form['queue'] = array(
      '#type' => 'details',
      '#title' => t('Queue'),
      '#open' => TRUE,
    );
    $form['queue']['plugin'] = array(
      '#default_value' => $this->configFactory->get('purge.queue')->get('plugin'),
      '#options' => $this->purgeQueue->getPlugins(TRUE),
      '#type' => 'radios',
      '#description' => $this->t('The queue service is backed by a queue plugin
        that stores the purge instructions. On most setups the database provider
        scales best up to a couple of thousand items in the queue, whereas the
        file queue plugin depends much more on the underlying IO performance
        of your server.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('purge.queue')
      ->set('plugin', $form_state['values']['plugin'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
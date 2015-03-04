<?php

/**
 * @file
 * Contains \Drupal\purge_processor_test\ProcessorA.
 */

namespace Drupal\purge_processor_test;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\purge\Processor\ProcessorInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\Invalidation\ServiceInterface as InvalidationServiceInterface;

/**
 * Testing processor A.
 */
class ProcessorA implements ProcessorInterface {
  use StringTranslationTrait;

  /**
   * The container id of this processor.
   *
   * @var string
   */
  protected $id;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config prefix and setting field holding status for this processor.
   *
   * @var array
   */
  protected $config = ['purge_processor_test.status', 'a'];

  /**
   * Whether this processor is enabled.
   *
   * @var bool
   */
  protected $status;

  /**
   * Constructs a new ProcessorA.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    list($prefix, $key) = $this->config;
    $this->status = $this->configFactory->get($prefix)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    list($prefix, $key) = $this->config;
    $this->configFactory->getEditable($prefix)->set($key, FALSE)->save();
    $this->status = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    list($prefix, $key) = $this->config;
    $this->configFactory->getEditable($prefix)->set($key, TRUE)->save();
    $this->status = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Processor A");
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("A processor that doesn't process but still is one!");
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
  }

}

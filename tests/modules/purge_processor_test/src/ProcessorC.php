<?php

/**
 * @file
 * Contains \Drupal\purge_processor_test\QueuerC.
 */

namespace Drupal\purge_processor_test;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge_processor_test\ProcessorA;

/**
 * Testing processor C.
 */
class ProcessorC extends ProcessorA implements ProcessorInterface {

  /**
   * The config prefix and setting field holding status for this processor.
   *
   * @var array
   */
  protected $config = ['purge_processor_test.status', 'c'];

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Processor C");
  }

}

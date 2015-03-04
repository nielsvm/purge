<?php

/**
 * @file
 * Contains \Drupal\purge_processor_test\QueuerB.
 */

namespace Drupal\purge_processor_test;

use Drupal\purge\Processor\ProcessorInterface;
use Drupal\purge_processor_test\ProcessorA;

/**
 * Testing processor B.
 */
class ProcessorB extends ProcessorA implements ProcessorInterface {

  /**
   * The config prefix and setting field holding status for this processor.
   *
   * @var array
   */
  protected $config = ['purge_processor_test.status', 'b'];

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Processor B");
  }

}

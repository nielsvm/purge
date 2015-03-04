<?php

/**
 * @file
 * Contains \Drupal\purge_queuer_test\QueuerC.
 */

namespace Drupal\purge_queuer_test;

use Drupal\purge\Queuer\QueuerInterface;
use Drupal\purge_queuer_test\QueuerA;

/**
 * Testing queuer C.
 */
class QueuerC extends QueuerA implements QueuerInterface {

  /**
   * The config prefix and setting field holding status for this queuer.
   *
   * @var array
   */
  protected $config = ['purge_queuer_test.status', 'c'];

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Queuer C");
  }

}

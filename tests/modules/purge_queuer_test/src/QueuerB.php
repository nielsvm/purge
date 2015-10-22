<?php

/**
 * @file
 * Contains \Drupal\purge_queuer_test\QueuerB.
 */

namespace Drupal\purge_queuer_test;

use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\purge_queuer_test\QueuerA;

/**
 * Testing queuer B.
 */
class QueuerB extends QueuerA implements QueuerInterface {

  /**
   * The config prefix and setting field holding status for this queuer.
   *
   * @var array
   */
  protected $config = ['purge_queuer_test.status', 'b'];

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Queuer B");
  }

}

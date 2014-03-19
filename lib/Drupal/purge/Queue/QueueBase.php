<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueBase.
 */

namespace Drupal\purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Queue\QueueInterface;

/**
 * Provides a ReliableQueueInterface compliant queue that can hold queue items.
 */
abstract class QueueBase implements QueueInterface {

  /**
   * var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  function __construct(ContainerInterface $service_container) {
    $this->container = $service_container;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginName() {
    return basename(str_replace('\\', '/', get_class($this)));
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $ids = array();

    // This implementation emulates multiple creation and is NOT efficient. It
    // exists for API reliability and invites derivatives to override it, for
    // example: by one multi-row database query.
    foreach ($items as $data) {
      if (($item = $this->createItem($data)) === FALSE) {
        return FALSE;
      }
      $ids[] = $item;
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $items = array();

    // This implementation emulates multiple item claiming and is NOT efficient,
    // but exists to provide a reliable API. Derivatives are invited to override
    // it, for example by one multi-row select database query.
    for ($i = 1; $i <= $claims; $i++) {
      if (($item = $this->claimItem($lease_time)) === FALSE) {
        break;
      }
      $items[] = $item;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {

    // This implementation emulates multiple item deletion and is NOT efficient,
    // but exists to provide API reliability. Derivatives are invited to
    // override it, for example by one multi-row delete database query.
    foreach ($items as $item) {
      $this->deleteItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    $failures = array();

    // This implementation emulates multiple item releases and is NOT efficient,
    // but exists to provide API reliability. Derivatives are invited to
    // override it, for example by a multi-row update database query.
    foreach ($items as $item) {
      if ($this->releaseItem($item) === FALSE) {
        $failures[] = $item;
      }
    }
    return $failures;
  }
}

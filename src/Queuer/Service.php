<?php

/**
 * @file
 * Contains \Drupal\purge\Queuer\Service.
 */

namespace Drupal\purge\Queuer;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\purge\Queuer\ServiceInterface;
use Drupal\purge\ServiceBase;

/**
 * Provides the service that keeps a registry of queuers and facilitates access.
 */
class Service extends ServiceBase implements ServiceInterface {
  use ContainerAwareTrait;

  /**
   * Current iterator position.
   *
   * @var int
   * @ingroup iterator
   */
  protected $position = 0;

  /**
   * Mapping of container ids to the iterator indexes.
   *
   * @var string[]
   * @ingroup iterator
   */
  protected $idmap = [];

  /**
   * All registered queuers.
   *
   * @var \Drupal\purge\Queuer\QueuerInterface[]
   */
  protected $queuers = [];

  /**
   * Retrieve all queuer instances as soon as we can.
   *
   * @return void
   */
  protected function retrieveQueuers() {
    if (empty($this->queuers)) {
      $i = 0;
      foreach ($this->container->getParameter('purge_queuers') as $id) {
        $this->queuers[$i] = $this->container->get($id);
        $this->idmap[$id] = $i;
        $i++;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    $this->retrieveQueuers();
    return isset($this->idmap[$id]) ? $this->queuers[$this->idmap[$id]] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailable() {
    $available = [];
    foreach ($this as $id => $queuer) {
      if (!$queuer->isEnabled()) {
        $available[$id] = $queuer;
      }
    }
    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabled() {
    $enabled = [];
    foreach ($this as $id => $queuer) {
      if ($queuer->isEnabled()) {
        $enabled[$id] = $queuer;
      }
    }
    return $enabled;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function key() {
    $this->retrieveQueuers();
    foreach ($this->idmap as $id => $i) {
      if ($this->position === $i) {
        return $id;
      }
    }
    return $this->position;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function next() {
    $this->retrieveQueuers();
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    $this->position = 0;
    $this->queuers = [];
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function current() {
    $this->retrieveQueuers();
    if ($this->valid()) {
      return $this->queuers[$this->position];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function rewind() {
    $this->retrieveQueuers();
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function valid() {
    $this->retrieveQueuers();
    return isset($this->queuers[$this->position]);
  }

}

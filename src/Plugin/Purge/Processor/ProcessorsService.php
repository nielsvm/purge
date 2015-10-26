<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Processor\ProcessorsService.
 */

namespace Drupal\purge\Plugin\Purge\Processor;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\purge\ServiceBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;

/**
 * Provides the service that gives access to registered processing policies.
 */
class ProcessorsService extends ServiceBase implements ProcessorsServiceInterface {
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
   * All registered processing policies.
   *
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface[]
   */
  protected $processors = [];

  /**
   * Retrieve all processing policies.
   *
   * @return void
   */
  protected function retrieveProcessors() {
    if (empty($this->processors)) {
      $i = 0;
      foreach ($this->container->getParameter('purge_processors') as $id) {
        $this->processors[$i] = $this->container->get($id);
        $this->idmap[$id] = $i;
        if (!$this->processors[$i]->getId()) {
          $this->processors[$i]->setId($id);
        }
        $i++;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    $this->retrieveProcessors();
    return isset($this->idmap[$id]) ? $this->processors[$this->idmap[$id]] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisabled() {
    $available = [];
    foreach ($this as $id => $processor) {
      if (!$processor->isEnabled()) {
        $available[$id] = $processor;
      }
    }
    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabled() {
    $enabled = [];
    foreach ($this as $id => $processor) {
      if ($processor->isEnabled()) {
        $enabled[$id] = $processor;
      }
    }
    return $enabled;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function key() {
    $this->retrieveProcessors();
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
    $this->retrieveProcessors();
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    $this->position = 0;
    $this->processors = [];
    $this->idmap = [];
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function current() {
    $this->retrieveProcessors();
    if ($this->valid()) {
      return $this->processors[$this->position];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function rewind() {
    $this->retrieveProcessors();
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function valid() {
    $this->retrieveProcessors();
    return isset($this->processors[$this->position]);
  }

}

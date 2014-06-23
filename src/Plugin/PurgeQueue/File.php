<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeQueue\File.
 */

namespace Drupal\purge\Plugin\PurgeQueue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant file backed queue.
 *
 * @PurgeQueue(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("A file-based queue for small systems."),
 *   service_dependencies = {}
 * )
 */
class File extends QueueBase implements QueueInterface {

  /**
   * The filename where the queue is stored in.
   */
  private $file = 'public://purge-queue-file';

  /**
   * Whether the buffer has been initialized or not.
   */
  private $bufferInitialized;

  /**
   * The internal buffer where all data is copied in.
   */
  private $buffer = array();

  /**
   * Define all the columns as found in the queue file.
   */
  const SEPARATOR = '|';
  const DATA = 0;
  const EXPIRE = 1;
  const CREATED = 2;

  /**
   * Setup a single file based queue.
   */
  function __construct() {
    $this->file = str_replace('public:/', PublicStream::basePath(), $this->file);
  }

  /**
   * Trigger a disk commit when the object is destructed.
   */
  function __destruct() {
    if ($this->bufferInitialized) {
      $this->bufferCommit();
    }
  }

  /**
   * Initialize the file buffer, ensure the queue is loaded.
   */
  private function bufferInitialize() {
    if (!$this->bufferInitialized) {
      $this->bufferInitialized = TRUE;

      // Open and parse the queue file, if it wasn't there during initialization
      // it will automatically become written at some point.
      if (file_exists($this->file)) {
        foreach (file($this->file) as $line) {
          $line = explode(self::SEPARATOR, str_replace("\n", '', $line));
          $item_id = (int)array_shift($line);
          $line[self::EXPIRE] = (int)$line[self::EXPIRE];
          $line[self::CREATED] = (int)$line[self::CREATED];
          $this->buffer[$item_id] = $line;
        }
      }
    }
  }

  /**
   * Commit the buffer to disk.
   */
  public function bufferCommit() {
    $ob = '';
    $fh = fopen($this->file, 'w');
    foreach($this->buffer as $item_id => $line) {
      $ob .= $item_id . SELF::SEPARATOR . $line[SELF::DATA] . SELF::SEPARATOR
        . $line[SELF::EXPIRE] . SELF::SEPARATOR . $line[SELF::CREATED] . "\n";
    }
    fwrite($fh, $ob);
    fclose($fh);
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createItem().
   */
  public function createItem($data) {
    $this->bufferInitialize();
    end($this->buffer);
    $id = key($this->buffer) + 1;
    $this->buffer[$id] = array(
      SELF::DATA => serialize($data),
      SELF::EXPIRE => 0,
      SELF::CREATED => time(),
    );
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $this->bufferInitialize();
    end($this->buffer);
    $id = key($this->buffer) + 1;
    foreach ($items as $data) {
      $this->buffer[$id] = array(
        SELF::DATA => serialize($data),
        SELF::EXPIRE => 0,
        SELF::CREATED => time(),
      );
      $ids[] = $id;
      $id++;
    }
    return $ids;
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::numberOfItems().
   */
  public function numberOfItems() {
    $this->bufferInitialize();
    return count($this->buffer);
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function claimItem($lease_time = 3600, $id = NULL) {
    if ($id == NULL) {
      $this->bufferInitialize();
      reset($this->buffer);
      $id = key($this->buffer);
    }
    if (empty($this->buffer)) {
      return FALSE;
    }
    if (!isset($this->buffer[$id])) {
      return FALSE;
    }
    if (
      ($this->buffer[$id][SELF::EXPIRE] === 0)
      || ( ($this->buffer[$id][SELF::EXPIRE] !== 0)
        && (time() > $this->buffer[$id][SELF::EXPIRE]))
      ) {
      $this->buffer[$id][SELF::EXPIRE] = time() + $lease_time;
      $item = new \stdClass();
      $item->item_id = $id;
      $item->data = unserialize($this->buffer[$id][SELF::DATA]);
      $item->expire = $this->buffer[$id][SELF::EXPIRE];
      $item->created = $this->buffer[$id][SELF::CREATED];
      return $item;
    }
    else {
      $id++;
      return $this->claimItem($lease_time, $id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $items = array();
    for ($i = 1; $i <= $claims; $i++) {
      if (($item = $this->claimItem($lease_time, NULL)) === FALSE) {
        break;
      }
      $items[] = $item;
    }
    return $items;
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::releaseItem().
   */
  public function releaseItem($item) {
    $this->bufferInitialize();
    if (!isset($this->buffer[$item->item_id])) {
      return FALSE;
    }
    $this->buffer[$item->item_id][SELF::EXPIRE] = 0;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    $this->bufferInitialize();
    foreach ($items as $item) {
      $this->releaseItem($item);
    }
    return array();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteItem().
   */
  public function deleteItem($item) {
    $this->bufferInitialize();
    if (!isset($this->buffer[$item->item_id])) {
      return FALSE;
    }
    unset($this->buffer[$item->item_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {
    $this->bufferInitialize();
    foreach ($items as $item) {
      $this->deleteItem($item);
    }
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createQueue().
   */
  public function createQueue() {
    $this->bufferInitialize();
    $this->deleteQueue();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteQueue().
   */
  public function deleteQueue() {
    $this->bufferInitialize();
    $this->buffer = array();
  }
}

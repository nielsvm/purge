<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeQueue\File.
 */

namespace Drupal\purge\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\Memory;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\purge\Queue\PluginInterface as Queue;

/**
 * A \Drupal\purge\Queue\PluginInterface compliant file-based queue.
 *
 * @PurgeQueue(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("a file-based queue for fast I/O systems."),
 *   service_dependencies = {}
 * )
 */
class File extends Memory implements Queue {

  /**
   * The file path to which the queue buffer gets written to.
   */
  protected $file = 'public://purge-queue-file';

  /**
   * The separator string to split columns with.
   */
  const SEPARATOR = '|';

  /**
   * The queue constructor.
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
   * {@inheritdoc}
   */
  private function bufferInitialize() {
    if (!$this->bufferInitialized) {
      $this->bufferInitialized = TRUE;
      $this->buffer = [];

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
}

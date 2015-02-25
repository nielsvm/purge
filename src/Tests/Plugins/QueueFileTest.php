<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\QueueFileTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\Core\DestructableInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests the 'file' queue plugin.
 *
 * @group purge
 * @see \Drupal\purge\Queue\PluginInterface
 */
class QueueFileTest extends PluginTestBase {
  protected $plugin_id = 'file';

  /**
   * The file path to which the queue buffer gets written to.
   *
   * @see \Drupal\purge\Plugin\PurgeQueue\File::$file
   */
  protected $file = 'public://purge-queue-file';

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->file = str_replace('public:/', PublicStream::basePath(), $this->file);
  }

  /**
   * Tests if the buffer gets written to disk properly.
   *
   * @see \Drupal\purge\Plugin\PurgeQueue\File::bufferInitialize
   * @see \Drupal\purge\Plugin\PurgeQueue\File::bufferCommit
   * @see \Drupal\purge\Plugin\PurgeQueue\File::deleteQueue
   * @see \Drupal\purge\Plugin\PurgeQueue\File::destruct
   */
  function testBufferReadingAndWriting() {
    $this->assertTrue($this->queue instanceof DestructableInterface);
    $this->assertFalse(file_exists($this->file));

    // Create one item without claiming it, and test the written output.
    $this->queue->createItem('s1');
    $this->assertFalse(file_exists($this->file));
    $this->queue->destruct();
    $this->assertTrue(file_exists($this->file));
    $this->assertEqual('1|s:2:"s1";|0|' . time() . "\n", file_get_contents($this->file));

    // Delete the queue and assure the file is gone.
    $this->queue->deleteQueue();
    $this->assertFalse(file_exists($this->file));

    // Create one item and claim it, test the output written to disk.
    $this->queue->createItem('s2');
    $i = $this->queue->claimItem();
    $this->queue->destruct();
    $this->assertTrue(file_exists($this->file));
    $this->assertEqual('1|s:2:"s2";|' . $i->expire . '|' . $i->created . "\n", file_get_contents($this->file));

    // Delete the queue file, write our own file to disk and reload the queue.
    $this->queue->deleteQueue();
    $this->queue = NULL;
    file_put_contents($this->file, '1|s:6:"qwerty";|0|12345' . "\n");
    $this->assertTrue(file_exists($this->file));
    $this->setUpQueuePlugin();
    $claim = $this->queue->claimItem(1);
    $this->assertTrue(is_object($claim));
    $this->assertEqual(1, $claim->item_id);
    $this->assertEqual('qwerty', $claim->data);
    $this->assertEqual(time() + 1, $claim->expire);
    $this->assertEqual(12345, $claim->created);

    $this->queue->deleteQueue();
  }

}

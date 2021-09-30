<?php

namespace Drupal\Tests\purge\Kernel\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\TxBuffer;
use Drupal\purge\Plugin\Purge\Queue\TxBufferInterface;
use Drupal\Tests\purge\Kernel\KernelTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer.
 *
 * @group purge
 */
class TxBufferTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->purgeQueueTxbuffer = new TxBuffer();
  }

  /**
   * Test that the state constants are available.
   */
  public function testStates(): void {
    $this->assertEquals(TxBufferInterface::CLAIMED, 0);
    $this->assertEquals(TxBufferInterface::ADDING, 1);
    $this->assertEquals(TxBufferInterface::ADDED, 2);
    $this->assertEquals(TxBufferInterface::RELEASING, 3);
    $this->assertEquals(TxBufferInterface::RELEASED, 4);
    $this->assertEquals(TxBufferInterface::DELETING, 5);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::count.
   */
  public function testCount(): void {
    $this->assertEquals(0, count($this->purgeQueueTxbuffer));
    $this->purgeQueueTxbuffer->set($this->getInvalidations(5), TxBufferInterface::CLAIMED);
    $this->assertEquals(5, count($this->purgeQueueTxbuffer));
    $this->purgeQueueTxbuffer->set($this->getInvalidations(1), TxBufferInterface::CLAIMED);
    $this->assertEquals(6, count($this->purgeQueueTxbuffer));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::current.
   */
  public function testCurrent(): void {
    $objects = $this->getInvalidations(5);
    $this->assertFalse($this->purgeQueueTxbuffer->current());
    $this->purgeQueueTxbuffer->set($objects, TxBufferInterface::CLAIMED);
    $c = $this->purgeQueueTxbuffer->current();
    $this->assertTrue($c instanceof InvalidationInterface);
    $this->assertEquals($objects[0]->getId(), $c->getId());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::delete.
   */
  public function testDelete(): void {
    $objects = $this->getInvalidations(5);
    $this->purgeQueueTxbuffer->set($objects, TxBufferInterface::CLAIMED);

    // Test that deleting foreign objects, doesn't affect the buffer at all.
    $this->purgeQueueTxbuffer->delete($this->getInvalidations(1));
    $this->assertEquals(5, count($this->purgeQueueTxbuffer));
    $this->purgeQueueTxbuffer->delete($this->getInvalidations(2));
    $this->assertEquals(5, count($this->purgeQueueTxbuffer));

    // Now assert that deleting those we added earlier, does affect the buffer.
    $this->purgeQueueTxbuffer->delete(array_pop($objects));
    $this->assertEquals(4, count($this->purgeQueueTxbuffer));
    $this->purgeQueueTxbuffer->delete($objects);
    $this->assertEquals(0, count($this->purgeQueueTxbuffer));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::deleteEverything.
   */
  public function testDeleteEverything(): void {
    $this->purgeQueueTxbuffer->set($this->getInvalidations(5), TxBufferInterface::CLAIMED);
    $this->purgeQueueTxbuffer->deleteEverything();
    $this->assertEquals(0, count($this->purgeQueueTxbuffer));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::getByProperty.
   */
  public function testGetByProperty(): void {
    $i = $this->getInvalidations(1);
    $this->purgeQueueTxbuffer->set($i, TxBufferInterface::CLAIMED);
    $this->purgeQueueTxbuffer->setProperty($i, 'find', 'me');
    $this->assertFalse($this->purgeQueueTxbuffer->getByProperty('find', 'you'));
    $this->assertFalse($this->purgeQueueTxbuffer->getByProperty('find', 0));
    $match = $this->purgeQueueTxbuffer->getByProperty('find', 'me');
    $this->assertTrue($match instanceof InvalidationInterface);
    $this->assertEquals($i->getId(), $match->getId());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::getFiltered.
   */
  public function testGetFiltered(): void {
    $this->assertEquals(0, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::CLAIMED)));
    $this->purgeQueueTxbuffer->set($this->getInvalidations(5), TxBufferInterface::CLAIMED);
    $this->assertEquals(5, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::CLAIMED)));
    $this->purgeQueueTxbuffer->set($this->getInvalidations(3), TxBufferInterface::ADDING);
    $this->assertEquals(3, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::ADDING)));
    $this->purgeQueueTxbuffer->set($this->getInvalidations(7), TxBufferInterface::DELETING);
    $this->assertEquals(7, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::DELETING)));
    $this->assertEquals(10, count($this->purgeQueueTxbuffer->getFiltered(
      [TxBufferInterface::ADDING, TxBufferInterface::DELETING])));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::getState.
   */
  public function testGetState(): void {
    $i = $this->getInvalidations(1);
    $this->assertNull($this->purgeQueueTxbuffer->getState($i));
    $this->purgeQueueTxbuffer->set($i, TxBufferInterface::CLAIMED);
    $this->assertEquals(TxBufferInterface::CLAIMED, $this->purgeQueueTxbuffer->getState($i));
    $this->purgeQueueTxbuffer->set($i, TxBufferInterface::DELETING);
    $this->assertEquals(TxBufferInterface::DELETING, $this->purgeQueueTxbuffer->getState($i));
    $this->purgeQueueTxbuffer->delete($i);
    $this->assertNull($this->purgeQueueTxbuffer->getState($i));
  }

  /**
   * Tests TxBuffer::setProperty and TxBuffer::getProperty.
   */
  public function testSetAndGetProperty(): void {
    $i = $this->getInvalidations(1);

    // Assert that setting/getting properties on unbuffered objects won't work.
    $this->assertNull($this->purgeQueueTxbuffer->getProperty($i, 'prop'));
    $this->assertFalse($this->purgeQueueTxbuffer->getProperty($i, 'prop', FALSE));
    $this->purgeQueueTxbuffer->setProperty($i, 'prop', 'value');
    $this->assertNull($this->purgeQueueTxbuffer->getProperty($i, 'prop'));

    // Assert that once buffered, it all does work.
    $this->purgeQueueTxbuffer->set($i, TxBufferInterface::CLAIMED);
    $this->assertNull($this->purgeQueueTxbuffer->getProperty($i, 'prop'));
    $this->assertFalse($this->purgeQueueTxbuffer->getProperty($i, 'prop', FALSE));
    $this->purgeQueueTxbuffer->setProperty($i, 'prop', 'value');
    $this->assertEquals('value', $this->purgeQueueTxbuffer->getProperty($i, 'prop'));
    $this->purgeQueueTxbuffer->setProperty($i, 'prop', 5.5);
    $this->assertEquals(5.5, $this->purgeQueueTxbuffer->getProperty($i, 'prop'));
    $this->purgeQueueTxbuffer->setProperty($i, 'prop', [1]);
    $this->assertTrue(is_array($this->purgeQueueTxbuffer->getProperty($i, 'prop')));
    $this->assertTrue(current($this->purgeQueueTxbuffer->getProperty($i, 'prop')) === 1);
    $this->purgeQueueTxbuffer->delete($i);
    $this->assertNull($this->purgeQueueTxbuffer->getProperty($i, 'prop'));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::has.
   */
  public function testHas(): void {
    $i = $this->getInvalidations(1);
    $this->assertFalse($this->purgeQueueTxbuffer->has($i));
    $this->purgeQueueTxbuffer->set($i, TxBufferInterface::CLAIMED);
    $this->assertTrue($this->purgeQueueTxbuffer->has($i));
    $this->purgeQueueTxbuffer->delete($i);
    $this->assertFalse($this->purgeQueueTxbuffer->has($i));
  }

  /**
   * Tests TxBuffer::key and TxBuffer::next.
   */
  public function testKeyAndNext(): void {
    $objects = $this->getInvalidations(5);
    $this->assertNull($this->purgeQueueTxbuffer->key());
    $this->purgeQueueTxbuffer->set($objects, TxBufferInterface::CLAIMED);

    // Test that objects got added to the buffer in equal order as offered.
    foreach ($objects as $i) {
      $this->assertEquals($i->getId(), $this->purgeQueueTxbuffer->key());
      $this->purgeQueueTxbuffer->next();
    }

    // Test that iterating the buffer works as expected.
    foreach ($this->purgeQueueTxbuffer as $id => $i) {
      $this->assertTrue($i instanceof InvalidationInterface);
      $found = FALSE;
      foreach ($objects as $i) {
        if ($i->getId() === $id) {
          $found = TRUE;
          break;
        }
      }
      $this->assertTrue($found);
    }
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::rewind.
   */
  public function testRewind(): void {
    $objects = $this->getInvalidations(5);
    $this->assertNull($this->purgeQueueTxbuffer->key());
    $this->assertFalse($this->purgeQueueTxbuffer->rewind());
    $this->assertNull($this->purgeQueueTxbuffer->key());
    $this->purgeQueueTxbuffer->set($objects, TxBufferInterface::CLAIMED);
    $this->assertEquals($objects[0]->getId(), $this->purgeQueueTxbuffer->key());
    // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
    foreach ($this->purgeQueueTxbuffer as $i) {
      // Just iterate, to advance the internal pointer.
    }
    $this->purgeQueueTxbuffer->rewind();
    $this->assertEquals($objects[0]->getId(), $this->purgeQueueTxbuffer->key());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::set.
   */
  public function testSet(): void {
    $objects = $this->getInvalidations(4);

    // Assert that objects get set and become iterable.
    $this->purgeQueueTxbuffer->set($objects, TxBufferInterface::DELETING);
    foreach ($objects as $i) {
      $found = FALSE;
      foreach ($this->purgeQueueTxbuffer as $id => $i) {
        if ($id == $i->getId()) {
          $found = TRUE;
          break;
        }
      }
      $this->assertTrue($found);
    }

    // Assert that object states are correct.
    $this->assertEquals(4, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::DELETING)));
    $this->purgeQueueTxbuffer->set($objects[0], TxBufferInterface::ADDING);
    $this->assertEquals(3, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::DELETING)));
    $this->assertEquals(1, count($this->purgeQueueTxbuffer->getFiltered(TxBufferInterface::ADDING)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\TxBuffer::valid.
   */
  public function testValid(): void {
    $this->assertFalse($this->purgeQueueTxbuffer->valid());
    $this->purgeQueueTxbuffer->set($this->getInvalidations(2), TxBufferInterface::CLAIMED);
    $this->assertTrue($this->purgeQueueTxbuffer->valid());
    $this->purgeQueueTxbuffer->next();
    $this->assertTrue($this->purgeQueueTxbuffer->valid());
    $this->purgeQueueTxbuffer->next();
    $this->assertFalse($this->purgeQueueTxbuffer->valid());
  }

}

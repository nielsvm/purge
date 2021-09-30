<?php

namespace Drupal\Tests\purge\Kernel\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\Exception\InvalidPropertyException;
use Drupal\purge\Plugin\Purge\Queue\ProxyItem;
use Drupal\purge\Plugin\Purge\Queue\TxBuffer;
use Drupal\Tests\purge\Kernel\KernelTestBase;

/**
 * Tests \Drupal\purge\Tests\Queue\ProxyItem.
 *
 * @group purge
 */
class ProxyItemTest extends KernelTestBase {

  /**
   * The TxBuffer object in which state and properties get stored.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\TxBuffer
   */
  protected $buffer;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->buffer = new TxBuffer();
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\ProxyItem::__get.
   */
  public function testGet(): void {
    $i = $this->getInvalidations(1);
    $i->setStateContext('a');
    $i->setState(InvalidationInterface::PROCESSING);
    $i->setProperty('foo', 'bar');
    $i->setStateContext(NULL);
    $p = new ProxyItem($i, $this->buffer);
    $this->buffer->set($i, TxBuffer::CLAIMED);

    // Test the 'item_id' property and changing it directly on the buffer.
    $this->assertNull($p->item_id);
    $this->buffer->setProperty($i, 'item_id', '5');
    $this->assertEquals('5', $p->item_id);
    $this->buffer->delete($i);
    $this->assertNull($p->item_id);
    $this->buffer->set($i, TxBuffer::CLAIMED);
    $this->assertNull($p->item_id);

    // Test the 'data' array property and its peculiar format.
    $this->assertTrue(is_array($p->data));
    $this->assertEquals($i->getPluginId(), $p->data[ProxyItem::DATA_INDEX_TYPE]);
    $this->assertEquals($i->getType(), $p->data[ProxyItem::DATA_INDEX_TYPE]);
    $this->assertTrue(is_array($p->data[ProxyItem::DATA_INDEX_STATES]));
    $this->assertEquals(1, count($p->data[ProxyItem::DATA_INDEX_STATES]));
    $this->assertTrue(isset($p->data[ProxyItem::DATA_INDEX_STATES]['a']));
    $this->assertEquals(InvalidationInterface::PROCESSING, $p->data[ProxyItem::DATA_INDEX_STATES]['a']);
    $this->assertEquals($i->getExpression(), $p->data[ProxyItem::DATA_INDEX_EXPRESSION]);
    $this->assertTrue(isset($p->data[ProxyItem::DATA_INDEX_PROPERTIES]['a']['foo']));
    $this->assertEquals('bar', $p->data[ProxyItem::DATA_INDEX_PROPERTIES]['a']['foo']);

    // Test the 'created' property and changing it directly on the buffer.
    $this->assertNull($p->created);
    $this->buffer->setProperty($i, 'created', 123456789);
    $this->assertEquals(123456789, $p->created);

    // Test that bad properties throw a InvalidPropertyException as expected.
    foreach (['properties', 'buffer', 'test'] as $property) {
      $thrown = FALSE;
      try {
        $p->$property;
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown);
    }
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\ProxyItem::__set.
   */
  public function testSet(): void {
    $i = $this->getInvalidations(1);
    $p = new ProxyItem($i, $this->buffer);
    $this->buffer->set($i, TxBuffer::CLAIMED);

    // Test setting the 'item_id' and 'created' properties.
    $this->assertNull($this->buffer->getProperty($i, 'item_id'));
    $p->item_id = 5;
    $this->assertEquals(5, $this->buffer->getProperty($i, 'item_id'));
    $p->item_id = 'FOOBAR';
    $this->assertEquals('FOOBAR', $this->buffer->getProperty($i, 'item_id'));
    $this->assertNull($this->buffer->getProperty($i, 'created'));
    $p->created = FALSE;
    $this->assertFalse($this->buffer->getProperty($i, 'created'));
    $p->created = 0.7;
    $this->assertEquals(0.7, $this->buffer->getProperty($i, 'created'));

    // Test setting 'data' (RO) and other non-existing properties.
    foreach (['data', 'foo', 'bar'] as $property) {
      $thrown = FALSE;
      try {
        $p->$property = time();
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown);
    }
  }

}

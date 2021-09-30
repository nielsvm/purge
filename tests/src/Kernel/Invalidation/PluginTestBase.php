<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\TypeUnsupportedException;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\Tests\purge\Kernel\KernelTestBase;

/**
 * Provides an abstract test class to thoroughly test invalidation types.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
abstract class PluginTestBase extends KernelTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressions = NULL;

  /**
   * String expressions invalid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressionsInvalid;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test'];

  /**
   * Set up the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializePurgersService(['good']);
    $this->initializeInvalidationFactoryService();
  }

  /**
   * Retrieve a invalidation object provided by the plugin.
   */
  public function getInstance(): InvalidationInterface {
    return $this->getInvalidations(1, $this->pluginId, $this->expressions[0]);
  }

  /**
   * Retrieve a immutable invalidation object, which wraps the plugin.
   */
  public function getImmutableInstance(): ImmutableInvalidationInterface {
    return $this->purgeInvalidationFactory->getImmutable(
      $this->pluginId,
      $this->expressions[0]
    );
  }

  /**
   * Tests the code contract strictly enforced on invalidation type plugins.
   */
  public function testCodeContract(): void {
    $this->assertTrue($this->getInstance() instanceof ImmutableInvalidationInterface);
    $this->assertTrue($this->getInstance() instanceof InvalidationInterface);
    $this->assertTrue($this->getInstance() instanceof ImmutableInvalidationBase);
    $this->assertTrue($this->getInstance() instanceof InvalidationBase);
    $this->assertTrue($this->getImmutableInstance() instanceof ImmutableInvalidationInterface);
    $this->assertFalse($this->getImmutableInstance() instanceof InvalidationInterface);
    $this->assertTrue($this->getImmutableInstance() instanceof ImmutableInvalidationBase);
    $this->assertFalse($this->getImmutableInstance() instanceof InvalidationBase);
  }

  /**
   * Tests TypeUnsupportedException.
   */
  public function testTypeUnsupportedException(): void {
    $this->initializePurgersService([], TRUE);
    $this->expectException(TypeUnsupportedException::class);
    $this->getInvalidations(1, $this->pluginId, $this->expressions[0], FALSE);
    $this->getInstance(FALSE);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidation.
   */
  public function testImmutable(): void {
    $immutable = $this->getImmutableInstance();
    $mutable = $this->getInstance();
    $this->assertEquals($immutable->__toString(), $mutable->__toString());
    $this->assertEquals($immutable->getExpression(), $mutable->getExpression());
    $this->assertEquals($immutable->getState(), $mutable->getState());
    $this->assertEquals($immutable->getStateString(), $mutable->getStateString());
    $this->assertEquals($immutable->getType(), $mutable->getType());
  }

  /**
   * Test that instances initialize with no properties.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperties
   */
  public function testPropertiesInitializeEmpty(): void {
    $i = $this->getInstance();
    $this->assertSame([], $i->getProperties());
  }

  /**
   * Test deleting a property.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::deleteProperty
   */
  public function testDeleteProperty(): void {
    $i = $this->getInstance();
    $i->setStateContext('purger_a');
    $i->setProperty('myprop', 1234);
    $i->deleteProperty('myprop');
    $this->assertSame(NULL, $i->getProperty('myprop'));
  }

  /**
   * Test retrieving a property.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperty
   */
  public function testGetProperty(): void {
    $i = $this->getInstance();
    $i->setStateContext('purger_b');
    $i->setProperty('my_book', 'Nineteen Eighty-Four');
    $this->assertSame('Nineteen Eighty-Four', $i->getProperty('my_book'));
    $this->assertSame(NULL, $i->getProperty('my_film'));

    // Test again within a different context.
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext('purger_b2');
    $this->assertSame(NULL, $i->getProperty('my_book'));
  }

  /**
   * Test setting a property.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setProperty
   */
  public function testSetProperty(): void {
    $i = $this->getInstance();
    $i->setStateContext('purger_d');
    $this->assertSame(NULL, $i->setProperty('my_film', 'Pulp Fiction'));
    $this->assertSame('Pulp Fiction', $i->getProperty('my_film'));
  }

  /**
   * Test that properties are stored by context.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperties
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperty
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::deleteProperty
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setProperty
   */
  public function testPropertyStorageModel(): void {
    $i = $this->getInstance();

    // Verify retrieving and setting properties.
    $i->setStateContext('purger1');
    $this->assertSame(NULL, $i->getProperty('doesntexist'));
    $this->assertSame(NULL, $i->setProperty('key1', 'foobar'));
    $this->assertSame('foobar', $i->getProperty('key1'));
    $this->assertSame(NULL, $i->deleteProperty('key1'));
    $this->assertSame(NULL, $i->getProperty('key1'));
    $this->assertSame(NULL, $i->setProperty('key1', 'foobar2'));
    $this->assertSame('foobar2', $i->getProperty('key1'));

    // Switch state to add some more properties.
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext('purger2');
    $i->setProperty('key2', 'baz');
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext(NULL);

    // Verify that every property is stored by context.
    $p = $i->getProperties();
    $this->assertSame(2, count($p));
    $this->assertSame(TRUE, isset($p['purger1']['key1']));
    $this->assertSame('foobar2', $p['purger1']['key1']);
    $this->assertSame(TRUE, isset($p['purger2']['key2']));
    $this->assertSame('baz', $p['purger2']['key2']);
  }

  /**
   * Test that you can't delete a property without specifying the state context.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::deleteProperty
   */
  public function testStateContextExceptionDeleteProperty(): void {
    $i = $this->getInstance();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Call ::setStateContext() before deleting properties!');
    $i->deleteProperty('my_setting');
  }

  /**
   * Test that you can't fetch a property without specifying the state context.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperty
   */
  public function testStateContextExceptionGetProperty(): void {
    $i = $this->getInstance();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Call ::setStateContext() before retrieving properties!');
    $i->getProperty('my_setting');
  }

  /**
   * Test that you can't set a property without specifying the state context.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setProperty
   */
  public function testStateContextExceptionSetProperty(): void {
    $i = $this->getInstance();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Call ::setStateContext() before setting properties!');
    $i->setProperty('my_setting', FALSE);
  }

  /**
   * Test the initial state of the invalidation object.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getState
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getStateString
   */
  public function testStateInitial(): void {
    $i = $this->getInstance();
    $this->assertEquals($i->getState(), InvalidationInterface::FRESH);
    $this->assertEquals($i->getStateString(), 'FRESH');
  }

  /**
   * Test switching away from the acceptable states.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setStateContext
   */
  public function testStateSwitchGoodBehavior(): void {
    $i = $this->getInstance();
    $i->setStateContext('failingpurger');
    $i->setState(InvalidationInterface::NOT_SUPPORTED);
    $i->setStateContext(NULL);
    $i->setStateContext('failingpurger');
    $i->setState(InvalidationInterface::PROCESSING);
    $i->setStateContext(NULL);
    $i->setStateContext('failingpurger');
    $i->setState(InvalidationInterface::SUCCEEDED);
    $i->setStateContext(NULL);
    $i->setStateContext('failingpurger');
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext(NULL);
    $this->assertSame(['failingpurger'], $i->getStateContexts());
  }

  /**
   * Test exception when switching away from the 'FRESH' state.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setStateContext
   */
  public function testStateSwitchBadBehavior(): void {
    $i = $this->getInstance();
    $i->setStateContext('test');
    $this->expectException(BadPluginBehaviorException::class);
    $this->expectExceptionMessage('Only NOT_SUPPORTED, PROCESSING, SUCCEEDED and FAILED are valid outbound states.');
    $i->setStateContext(NULL);
  }

  /**
   * Test exception when setting state in NULL context.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   */
  public function testStateSetInGeneralContext(): void {
    $i = $this->getInstance();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('State cannot be set in NULL context!');
    $i->setState(InvalidationInterface::FAILED);
  }

  /**
   * Test exceptions when setting invalid states.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   */
  public function testStateSetInvalidStateA(): void {
    $i = $this->getInstance();
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('$state not an integer!');
    $i->setState('2');
  }

  /**
   * Test exceptions when setting invalid states.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   */
  public function testStateSetInvalidStateB(): void {
    $i = $this->getInstance();
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('$state not an integer!');
    $i->setState('FRESH');
  }

  /**
   * Test exceptions when setting invalid states.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   */
  public function testStateSetInvalidStateC(): void {
    $i = $this->getInstance();
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('$state is out of range!');
    $i->setState(-1);
  }

  /**
   * Test exceptions when setting invalid states.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   */
  public function testStateSetInvalidStateD(): void {
    $i = $this->getInstance();
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('$state is out of range!');
    $i->setState(5);
  }

  /**
   * Test exceptions when setting invalid states.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   */
  public function testStateSetInvalidStateE(): void {
    $i = $this->getInstance();
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('$state is out of range!');
    $i->setState(100);
  }

  /**
   * Test overal state storage and retrieval.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setStateContext
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getState
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getStateString
   */
  public function testStateStorageAndRetrieval(): void {
    $i = $this->getInstance();

    // Test setting normal states results in the same return state.
    $test_states = [
      InvalidationInterface::PROCESSING    => 'PROCESSING',
      InvalidationInterface::SUCCEEDED     => 'SUCCEEDED',
      InvalidationInterface::FAILED        => 'FAILED',
      InvalidationInterface::NOT_SUPPORTED => 'NOT_SUPPORTED',
    ];
    $context = 0;
    $i->setStateContext((string) $context);
    foreach ($test_states as $state => $string) {
      $this->assertNull($i->setStateContext((string) ($context++)));
      $this->assertNull($i->setState($state));
      $this->assertEquals($i->getState(), $state);
      $this->assertEquals($i->getStateString(), $string);
    }
    $i->setStateContext(NULL);
  }

  /**
   * Test if typecasting invalidation objects to strings gets us a string.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::__toString
   */
  public function testStringExpression(): void {
    $this->assertEquals(
      (string) $this->getInstance(),
      $this->expressions[0],
      'The __toString method returns $expression.'
    );
  }

  /**
   * Test if all valid string expressions properly instantiate the object.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::__construct
   */
  public function testValidExpressions(): void {
    if (is_null($this->expressions)) {
      $this->assertInstanceOf(
        InvalidationInterface::class,
        $this->purgeInvalidationFactory->get($this->pluginId)
      );
    }
    else {
      foreach ($this->expressions as $e) {
        $this->assertInstanceOf(
          InvalidationInterface::class,
          $this->purgeInvalidationFactory->get($this->pluginId, $e)
        );
      }
    }
  }

  /**
   * Test if all invalid string expressions fail to instantiate the object.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::__construct
   */
  public function testInvalidExpressions(): void {
    foreach ($this->expressionsInvalid as $exp) {
      $thrown = FALSE;
      try {
        $this->purgeInvalidationFactory->get($this->pluginId, $exp);
      }
      catch (\Exception $e) {
        $thrown = $e;
      }
      if (is_null($exp)) {
        $this->assertInstanceOf(MissingExpressionException::class, $thrown);
      }
      else {
        $this->assertInstanceOf(InvalidExpressionException::class, $thrown);
      }
    }
  }

  /**
   * Test retrieving the plugin ID and definition.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getPluginId
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getType
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getPluginDefinition
   */
  public function testPluginIdAndDefinition(): void {
    // Test mutable objects.
    $mutable = $this->getInstance();
    $this->assertEquals($this->pluginId, $mutable->getPluginId());
    $this->assertEquals($this->pluginId, $mutable->getType());
    $d = $mutable->getPluginDefinition();
    $this->assertTrue(is_array($d));
    $this->assertTrue(is_array($d['examples']));
    $this->assertTrue($d['label'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['label']));
    $this->assertTrue($d['description'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['description']));
    $this->assertTrue(isset($d['expression_required']));
    $this->assertTrue(isset($d['expression_can_be_empty']));
    $this->assertTrue(isset($d['expression_must_be_string']));
    if (!$d["expression_required"]) {
      $this->assertFalse($d["expression_can_be_empty"]);
    }
    // Test the immutable objects.
    $immutable = $this->getImmutableInstance();
    $this->assertEquals($this->pluginId, $immutable->getPluginId());
    $this->assertEquals($this->pluginId, $immutable->getType());
    $d = $immutable->getPluginDefinition();
    $this->assertTrue(is_array($d));
    $this->assertTrue(is_array($d['examples']));
    $this->assertTrue($d['label'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['label']));
    $this->assertTrue($d['description'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['description']));
    $this->assertTrue(isset($d['expression_required']));
    $this->assertTrue(isset($d['expression_can_be_empty']));
    $this->assertTrue(isset($d['expression_must_be_string']));
    if (!$d["expression_required"]) {
      $this->assertFalse($d["expression_can_be_empty"]);
    }
  }

}

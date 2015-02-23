<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Invalidation\PluginTestBase.
 */

namespace Drupal\purge\Tests\Invalidation;

use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidPropertyException;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Invalidation\Exception\InvalidStateException;
use Drupal\purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Tests\KernelTestBase;

/**
 * Provides an abstract test class to thoroughly test invalidation types.
 *
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
abstract class PluginTestBase extends KernelTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * List of - read only - allowed data properties on invalidation types.
   *
   * @var array
   */
  protected $properties = ['data', 'item_id', 'created'];

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var string[]|null
   */
  protected $expressions = NULL;

  /**
   * String expressions INvalid to the invalidation type being tested.
   *
   * @var string[]|null
   */
  protected $expressionsInvalid;

  // /**
  //  * String expressions INvalid to all invalidation types being tested.
  //  *
  //  * @var string
  //  */
  // protected $expressionsInvalidGlobal = ['', '   ', []];

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->initializeInvalidationFactoryService();
  }

  /**
   * Retrieve an invalidation object provided by the plugin.
   */
  function getInstance() {
    return $this->purgeInvalidationFactory->get($this->plugin_id, $this->expressions[0]);
  }

  /**
   * Tests the code contract strictly enforced on invalidation type plugins.
   */
  function testCodeContract() {
    $this->assertTrue($this->getInstance() instanceof Invalidation);
    $this->assertTrue($this->getInstance() instanceof PluginBase);
  }

  /**
   * Test if setting and getting the object state goes well.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::setState
   * @see \Drupal\purge\Invalidation\PluginInterface::getState
   * @see \Drupal\purge\Invalidation\PluginInterface::getStateString
   */
  function testState() {
    $i = $this->getInstance();
    $test_states = [
      Invalidation::STATE_NEW           => 'NEW',
      Invalidation::STATE_ADDING        => 'ADDING',
      Invalidation::STATE_ADDED         => 'ADDED',
      Invalidation::STATE_CLAIMED       => 'CLAIMED',
      Invalidation::STATE_PURGING       => 'PURGING',
      Invalidation::STATE_PURGED        => 'PURGED',
      Invalidation::STATE_PURGEFAILED   => 'PURGEFAILED',
      Invalidation::STATE_RELEASING     => 'RELEASING',
      Invalidation::STATE_RELEASED      => 'RELEASED',
      Invalidation::STATE_DELETING      => 'DELETING',
      Invalidation::STATE_DELETED       => 'DELETED',
    ];

    // Test the initial state of the invalidation object.
    $this->assertEqual($i->getState(), Invalidation::STATE_NEW, 'getState: STATE_NEW');
    $this->assertEqual($i->getStateString(), 'NEW', 'getStateString: NEW');

    // Test setting, getting and getting the string version of each state.
    foreach ($test_states as $state => $string) {
      $this->assertNull($i->setState($state), "setState(STATE_$string): NULL");
      $this->assertEqual($i->getState(), $state, "getState(): STATE_$string");
      $this->assertEqual($i->getStateString(), $string, "getStateString(): $string");
    }

    // Test \Drupal\purge\Invalidation\PluginInterface::setState catches bad input.
    foreach(['2', 'NEW', -1, 11, 100] as $badstate) {
      $thrown = FALSE;
      try {
        $i->setState($badstate);
      }
      catch (InvalidStateException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown, 'Bad input '. var_export($badstate, TRUE)
        .' results in InvalidStateException being thrown.');
    }
  }

  /**
   * Test if typecasting invalidation objects to strings gets us a string.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__toString
   */
  function testStringExpression() {
    $this->assertEqual( (string)$this->getInstance(), $this->expressions[0],
      'The __toString method returns $expression.');
  }

  /**
   * Test if all valid string expressions properly instantiate the object.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__construct
   */
  function testValidExpressions() {
    if (is_null($this->expressions)) {
      $invalidation = $this->purgeInvalidationFactory->get($this->plugin_id);
    }
    else {
      foreach ($this->expressions as $e) {
        $invalidation = $this->purgeInvalidationFactory->get($this->plugin_id, $e);
      }
    }
  }

  /**
   * Test if all invalid string expressions fail to instantiate the object.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__construct
   */
  function testInvalidExpressions($expressions = NULL) {
    foreach ($this->expressionsInvalid as $exp) {
      $thrown = FALSE;
      try {
        $invalidation = $this->purgeInvalidationFactory->get($this->plugin_id, $exp);
      }
      catch (\Exception $e) {
        $thrown = $e;
      }
      if (is_null($exp)) {
        $this->assertTrue($thrown instanceof MissingExpressionException, var_export($exp, TRUE));
      }
      else {
        $this->assertTrue($thrown instanceof InvalidExpressionException, var_export($exp, TRUE));
      }
    }
  }

  /**
   * Test retrieving the plugin ID and definition.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::getPluginId
   * @see \Drupal\purge\Invalidation\PluginInterface::getPluginDefinition
   */
  function testPluginIdAndDefinition() {
    $i = $this->getInstance();
    $this->assertEqual($this->plugin_id, $i->getPluginId());
    $d = $i->getPluginDefinition();
    $this->assertTrue(is_array($d));
    $this->assertTrue(is_array($d['examples']));
    $this->assertTrue($d['label'] instanceof TranslationWrapper);
    $this->assertFalse(empty((string) $d['label']));
    $this->assertTrue($d['description'] instanceof TranslationWrapper);
    $this->assertFalse(empty((string) $d['description']));
    $this->assertTrue(isset($d['expression_required']));
    $this->assertTrue(isset($d['expression_can_be_empty']));
    $this->assertTrue(isset($d['expression_must_be_string']));
    if (!$d["expression_required"]) {
      $this->assertFalse($d["expression_can_be_empty"]);
    }
  }

  /**
   * Test whether certain variables can be read.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__get
   */
  function testVariableGettingValidOnes() {
    $i = $this->getInstance();
    foreach($this->properties as $property) {
      $thrown = FALSE;
      try {
        $i->$property;
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertFalse($thrown, "Can read property '$property' from object.");
    }
  }

  /**
   * Test whether random variables cannot be read.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__get
   */
  function testVariableGettingInvalidOnes() {
    $properties = ['a', 'b', 'c', 'd'];
    $i = $this->getInstance();
    foreach($properties as $property) {
      $thrown = FALSE;
      try {
        $i->$property;
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown,
        "Cannot read property '$property' from object.");
    }
  }

  /**
   * Test whether setting variables is dissalowed.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__set
   */
  function testVariableSettingProhibition() {
    $i = $this->getInstance();
    foreach($this->properties as $property) {
      $thrown = FALSE;
      try {
        $i->$property = 0;
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown, "Cannot set property '$property' on object.");
    }
  }

  /**
   * Test the methods dealing with the Queue data properties of invalidations.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::setQueueItemInfo
   * @see \Drupal\purge\Invalidation\PluginInterface::setQueueItemId
   * @see \Drupal\purge\Invalidation\PluginInterface::setQueueItemCreated
   */
  function testQueueItemData() {
    $i = $this->getInstance();
    $this->assertTrue(is_string($i->data), '"data" property is a string.');
    $this->assertTrue(strlen($i->data), '"data" property is not empty.');
    $this->assertNull($i->item_id, '"item_id" property is initially NULL.');
    $this->assertNull($i->created, '"item_id" property is initially NULL.');

    $i->setQueueItemId('foo');
    $this->assertEqual($i->item_id, 'foo', 'setQueueItemId sets "item_id".');
    $i->setQueueItemCreated('123');
    $this->assertEqual($i->created, '123', 'setQueueItemCreated sets "created".');

    $i->setQueueItemInfo('a', 'b');
    $this->assertEqual($i->item_id, 'a', 'setQueueItemInfo sets "item_id".');
    $this->assertEqual($i->created, 'b', 'setQueueItemInfo sets "created".');
  }
}

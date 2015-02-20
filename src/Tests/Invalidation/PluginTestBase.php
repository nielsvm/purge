<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Invalidation\PluginTestBase.
 */

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Invalidation\PluginInterface as Invalidation;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidPropertyException;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Invalidation\Exception\InvalidStateException;
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
   * String representations valid to the invalidation type being tested.
   *
   * @var string
   */
  protected $representations;

  /**
   * String representations INvalid to the invalidation type being tested.
   *
   * @var string
   */
  protected $representationsInvalid;

  /**
   * String representations INvalid to all invalidation types being tested.
   *
   * @var string
   */
  protected $representationsInvalidGlobal = ['', '   ', []];

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
    return $this->purgeInvalidationFactory->get(
      $this->plugin_id,
      $this->representations[0]);
  }

  /**
   * Tests the code contract strictly enforced on invalidation type plugins.
   */
  function testCodeContract() {
    $this->assertTrue($this->getInstance() instanceof Invalidation,
      'Uses \Drupal\purge\Invalidation\PluginInterface');
    $this->assertTrue($this->getInstance() instanceof PluginBase,
      'Uses \Drupal\purge\Invalidation\PluginBase');
  }

  /**
   * Test if setting and getting the object state goes well.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::setState
   * @see \Drupal\purge\Invalidation\PluginInterface::getState
   * @see \Drupal\purge\Invalidation\PluginInterface::getStateString
   */
  function testState() {
    $p = $this->getInstance();
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
    $this->assertEqual($p->getState(), Invalidation::STATE_NEW, 'getState: STATE_NEW');
    $this->assertEqual($p->getStateString(), 'NEW', 'getStateString: NEW');

    // Test setting, getting and getting the string version of each state.
    foreach ($test_states as $state => $string) {
      $this->assertNull($p->setState($state), "setState(STATE_$string): NULL");
      $this->assertEqual($p->getState(), $state, "getState(): STATE_$string");
      $this->assertEqual($p->getStateString(), $string, "getStateString(): $string");
    }

    // Test \Drupal\purge\Invalidation\PluginInterface::setState catches bad input.
    foreach(['2', 'NEW', -1, 11, 100] as $badstate) {
      $thrown = FALSE;
      try {
        $p->setState($badstate);
      }
      catch (InvalidStateException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown, 'Bad input '. var_export($badstate, TRUE)
        .' results in InvalidStateException being thrown.');
    }
  }

  /**
   * Test if typecasting invalidation objects to strings gets us input representation.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__toString
   */
  function testStringRepresentation() {
    $this->assertEqual( (string)$this->getInstance(), $this->representations[0],
      'The __toString method returns $representation.');
  }

  /**
   * Test if all valid string expressions create the desired invalidation.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__construct
   */
  function testStringValidRepresentations() {
    foreach ($this->representations as $r) {
      $invalidation = $this->purgeInvalidationFactory->fromRepresentation($r);
      $this->assertEqual($this->plugin_id, $invalidation->getPluginId(),
        sprintf("fromRepresentation(%s) returned a %s, expected %s invalidation.",
          var_export($r, TRUE), $invalidation->getPluginId(), $this->plugin_id));
    }
  }

  /**
   * Test if all invalid string representations create the desired invalidation.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__construct
   */
  function testStringInvalidRepresentations($representations = NULL) {
    if (is_null($representations)) {
      $this->testStringInvalidRepresentations($this->representationsInvalidGlobal);
      $this->testStringInvalidRepresentations($this->representationsInvalid);
    }
    else {
      foreach ($representations as $r) {

        // Test the expected exception on the invalidation type directly.
        $thrown = FALSE;
        try {
          $invalidation = $this->purgeInvalidationFactory->get($this->plugin_id, $r);
        }
        catch (InvalidExpressionException $e) {
          $thrown = $e;
        }
        $this->assertTrue($thrown,
          sprintf("get(%s) threw a "
            ." InvalidExpressionException.", var_export($r, TRUE)));

        // Assure that fromRepresentation doesn't return our plugin.
        try {
          $invalidation = $this->purgeInvalidationFactory->fromRepresentation($r);
          $this->assertNotEqual($this->plugin_id, $invalidation->getPluginId(),
            sprintf("fromRepresentation(%s) returned not a %s invalidation.",
              var_export($r, TRUE), $this->plugin_id));
        }
        catch (InvalidExpressionException $e) {
          // Its okay if this exception gets thrown, since that's not what we
          // are testing here.
        }
      }
    }
  }

  /**
   * Test setting and getting the plugin ID.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__getPluginId
   * @see \Drupal\purge\Invalidation\PluginInterface::__setPluginId
   */
  function testPluginIdSettingAndGetting() {
    $p = $this->getInstance();
    $this->assertEqual($this->plugin_id, $p->getPluginId());
    $p->setPluginId('foobar');
    $this->assertEqual('foobar', $p->getPluginId());
  }

  /**
   * Test whether certain variables can be read.
   *
   * @see \Drupal\purge\Invalidation\PluginInterface::__get
   */
  function testVariableGettingValidOnes() {
    $p = $this->getInstance();
    foreach($this->properties as $property) {
      $thrown = FALSE;
      try {
        $p->$property;
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
    $p = $this->getInstance();
    foreach($properties as $property) {
      $thrown = FALSE;
      try {
        $p->$property;
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
    $p = $this->getInstance();
    foreach($this->properties as $property) {
      $thrown = FALSE;
      try {
        $p->$property = 0;
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
    $p = $this->getInstance();
    $this->assertTrue(is_string($p->data), '"data" property is a string.');
    $this->assertTrue(strlen($p->data), '"data" property is not empty.');
    $this->assertNull($p->item_id, '"item_id" property is initially NULL.');
    $this->assertNull($p->created, '"item_id" property is initially NULL.');

    $p->setQueueItemId('foo');
    $this->assertEqual($p->item_id, 'foo', 'setQueueItemId sets "item_id".');
    $p->setQueueItemCreated('123');
    $this->assertEqual($p->created, '123', 'setQueueItemCreated sets "created".');

    $p->setQueueItemInfo('a', 'b');
    $this->assertEqual($p->item_id, 'a', 'setQueueItemInfo sets "item_id".');
    $this->assertEqual($p->created, 'b', 'setQueueItemInfo sets "created".');
  }
}

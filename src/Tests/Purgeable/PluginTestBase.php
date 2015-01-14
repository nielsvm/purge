<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Purgeable\PluginTestBase.
 */

namespace Drupal\purge\Tests\Purgeable;

use Drupal\purge\Purgeable\PluginInterface as Purgeable;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidPropertyException;
use Drupal\purge\Purgeable\Exception\InvalidRepresentationException;
use Drupal\purge\Purgeable\Exception\InvalidStateException;
use Drupal\purge\Tests\TestBase;

/**
 * Provides an abstract test class to thoroughly test Purgeable plugins.
 *
 * @group purge
 * @see \Drupal\purge\Purgeable\PluginInterface
 */
abstract class PluginTestBase extends TestBase {

  /**
   * The plugin ID of the purgeable plugin being tested.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * List of - read only - allowed data properties on purgeable objects.
   *
   * @var array
   */
  protected $properties = ['data', 'item_id', 'created'];

  /**
   * The service that generates purgeable objects on-demand.
   *
   * @var \Drupal\purge\Purgeable\ServiceInterface
   */
  protected $purgePurgeables;

  /**
   * String representations valid to the purgeable plugin being tested.
   *
   * @var string
   */
  protected $representations;

  /**
   * String representations INvalid to the purgeable plugin being tested.
   *
   * @var string
   */
  protected $representationsInvalid;

  /**
   * String representations INvalid to all purgeable plugins being tested.
   *
   * @var string
   */
  protected $representationsInvalidGlobal = ['', '   ', []];

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->purgePurgeables = $this->container->get('purge.purgeables');
  }

  /**
   * Retrieve a purgeable object provided by the plugin.
   */
  function getInstance() {
    return $this->purgePurgeables->fromNamedRepresentation(
      $this->plugin_id,
      $this->representations[0]);
  }

  /**
   * Tests the code contract strictly enforced on purgeable plugins.
   */
  function testCodeContract() {
    $this->assertTrue($this->getInstance() instanceof Purgeable,
      'Uses \Drupal\purge\Purgeable\PluginInterface');
    $this->assertTrue($this->getInstance() instanceof PluginBase,
      'Uses \Drupal\purge\Purgeable\PluginBase');
  }

  /**
   * Test if setting and getting the object state goes well.
   *
   * @see \Drupal\purge\Purgeable\PluginInterface::setState
   * @see \Drupal\purge\Purgeable\PluginInterface::getState
   * @see \Drupal\purge\Purgeable\PluginInterface::getStateString
   */
  function testState() {
    $p = $this->getInstance();
    $test_states = [
      Purgeable::STATE_NEW           => 'NEW',
      Purgeable::STATE_ADDING        => 'ADDING',
      Purgeable::STATE_ADDED         => 'ADDED',
      Purgeable::STATE_CLAIMED       => 'CLAIMED',
      Purgeable::STATE_PURGING       => 'PURGING',
      Purgeable::STATE_PURGED        => 'PURGED',
      Purgeable::STATE_PURGEFAILED   => 'PURGEFAILED',
      Purgeable::STATE_RELEASING     => 'RELEASING',
      Purgeable::STATE_RELEASED      => 'RELEASED',
      Purgeable::STATE_DELETING      => 'DELETING',
      Purgeable::STATE_DELETED       => 'DELETED',
    ];

    // Test the initial state of the purgeable object.
    $this->assertEqual($p->getState(), Purgeable::STATE_NEW, 'getState: STATE_NEW');
    $this->assertEqual($p->getStateString(), 'NEW', 'getStateString: NEW');

    // Test setting, getting and getting the string version of each state.
    foreach ($test_states as $state => $string) {
      $this->assertNull($p->setState($state), "setState(STATE_$string): NULL");
      $this->assertEqual($p->getState(), $state, "getState(): STATE_$string");
      $this->assertEqual($p->getStateString(), $string, "getStateString(): $string");
    }

    // Test \Drupal\purge\Purgeable\PluginInterface::setState catches bad input.
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
   * Test if typecasting a purgeable to a string gets us input representation.
   *
   * @see \Drupal\purge\Purgeable\PluginInterface::__toString
   */
  function testStringRepresentation() {
    $this->assertEqual( (string)$this->getInstance(), $this->representations[0],
      'The __toString method returns $representation.');
  }

  /**
   * Test if all valid string representations create the desired purgeable.
   *
   * @see \Drupal\purge\Purgeable\PluginInterface::__construct
   */
  function testStringValidRepresentations() {
    foreach ($this->representations as $r) {
      $purgeable = $this->purgePurgeables->fromRepresentation($r);
      $this->assertEqual($this->plugin_id, $purgeable->getPluginId(),
        sprintf("fromRepresentation(%s) returned a %s, expected %s purgeable.",
          var_export($r, TRUE), $purgeable->getPluginId(), $this->plugin_id));
    }
  }

  /**
   * Test if all invalid string representations create the desired purgeable.
   *
   * @see \Drupal\purge\Purgeable\PluginInterface::__construct
   */
  function testStringInvalidRepresentations($representations = NULL) {
    if (is_null($representations)) {
      $this->testStringInvalidRepresentations($this->representationsInvalidGlobal);
      $this->testStringInvalidRepresentations($this->representationsInvalid);
    }
    else {
      foreach ($representations as $r) {

        // Test the expected exception on the purgeable plugin directly.
        $thrown = FALSE;
        try {
          $purgeable = $this->purgePurgeables->fromNamedRepresentation($this->plugin_id, $r);
        }
        catch (InvalidRepresentationException $e) {
          $thrown = $e;
        }
        $this->assertTrue($thrown,
          sprintf("fromNamedRepresentation(%s) threw a "
            ." InvalidRepresentationException.", var_export($r, TRUE)));

        // Assure that fromRepresentation doesn't return our plugin.
        try {
          $purgeable = $this->purgePurgeables->fromRepresentation($r);
          $this->assertNotEqual($this->plugin_id, $purgeable->getPluginId(),
            sprintf("fromRepresentation(%s) returned not a %s purgeable.",
              var_export($r, TRUE), $this->plugin_id));
        }
        catch (InvalidRepresentationException $e) {
          // Its okay if this exception gets thrown, since that's not what we
          // are testing here.
        }
      }
    }
  }

  /**
   * Test setting and getting the plugin ID.
   *
   * @see \Drupal\purge\Purgeable\PluginInterface::__getPluginId
   * @see \Drupal\purge\Purgeable\PluginInterface::__setPluginId
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
   * @see \Drupal\purge\Purgeable\PluginInterface::__get
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
   * @see \Drupal\purge\Purgeable\PluginInterface::__get
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
   * @see \Drupal\purge\Purgeable\PluginInterface::__set
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
   * Test the methods dealing with the Queue Item data properties of purgeables.
   *
   * @see \Drupal\purge\Purgeable\PluginInterface::setQueueItemInfo
   * @see \Drupal\purge\Purgeable\PluginInterface::setQueueItemId
   * @see \Drupal\purge\Purgeable\PluginInterface::setQueueItemCreated
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

<?php

namespace Drupal\Tests\purge\Unit;

/**
 * Overrides ::getConfigFactoryStub().
 *
 * @see \Drupal\Tests\UnitTestCase
 */
trait FixGetConfigFactoryStubTrait {

  /**
   * Overrides ::getConfigFactoryStub().
   *
   * @see \Drupal\Tests\UnitTestCase::getConfigFactoryStub
   *
   * @todo Fix lines 55-63 which have been added in order to make mutable stub calls
   *   work in unit tests, e.g.: ->getEditable()->set()->save().
   */
  public function getConfigFactoryStub(array $configs = []) {
    $config_get_map = [];
    $config_editable_map = [];
    // Construct the desired configuration object stubs, each with its own
    // desired return map.
    foreach ($configs as $config_name => $config_values) {
      $map = [];
      foreach ($config_values as $key => $value) {
        $map[] = [$key, $value];
      }
      // Also allow to pass in no argument.
      $map[] = ['', $config_values];

      $immutable_config_object = $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
        ->disableOriginalConstructor()
        ->getMock();
      $immutable_config_object->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
      $config_get_map[] = [$config_name, $immutable_config_object];

      $mutable_config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
        ->disableOriginalConstructor()
        ->getMock();
      $mutable_config_object->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
      $mutable_config_object->expects($this->any())
        ->method('set')
        ->will($this->returnValue($mutable_config_object));
      $mutable_config_object->expects($this->any())
        ->method('save')
        ->will($this->returnValue($mutable_config_object));
      $config_editable_map[] = [$config_name, $mutable_config_object];
    }
    // Construct a config factory with the array of configuration object stubs
    // as its return map.
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($config_get_map));
    $config_factory->expects($this->any())
      ->method('getEditable')
      ->will($this->returnValueMap($config_editable_map));
    return $config_factory;
  }

}

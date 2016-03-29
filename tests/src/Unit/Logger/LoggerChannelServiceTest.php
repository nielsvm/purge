<?php

/**
 * @file
 * Contains \Drupal\Tests\purge\Unit\Logger\LoggerServiceTest.
 */

namespace Drupal\Tests\purge\Unit\Logger;

use Drupal\purge\Logger\LoggerService;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\purge\Unit\FixGetConfigFactoryStubTrait;

/**
 * @coversDefaultClass \Drupal\purge\Logger\LoggerService
 * @group purge
 */
class LoggerServiceTest extends UnitTestCase {
  use FixGetConfigFactoryStubTrait;

  /**
   * Default configuration.
   *
   * @var array[]
   */
  const DEFAULT_CONFIG = [
    'purge.logger_channels' => [
      'channels' => [
        ['id' => 'exists', 'grants' => [1,2,3]],
        ['id' => 'foo', 'grants' => [1,2,3]],
        ['id' => 'foobar', 'grants' => [1,2,3]],
        ['id' => 'foobarbaz', 'grants' => [1,2,3]]
      ]
    ]
  ];

  /**
   * The tested LoggerService object.
   *
   * @var \Drupal\purge\Logger\LoggerService
   */
  protected $loggerService;

  /**
   * The mocked channel part factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Logger\LoggerChannelPartFactoryInterface
   */
  protected $purgeLoggerPartsFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->loggerChannelPartFactory = $this->getMock('\Drupal\purge\Logger\LoggerChannelPartFactoryInterface');
    $this->loggerChannelPartFactory->method('create')
      ->willReturn($this->getMock('\Drupal\purge\Logger\LoggerChannelPartInterface'));
  }

  /**
   * @covers ::destruct
   *
   * @dataProvider providerTestDestruct()
   */
  public function testDestruct($expect_write, $call = NULL, $arguments = []) {
    $config_factory = $this->getConfigFactoryStub(SELF::DEFAULT_CONFIG);
    $config_factory
      ->expects($expect_write ? $this->once() : $this->never())
      ->method('getEditable')
      ->with($this->stringContains('purge.logger_channels'));
    $service = new LoggerService($config_factory, $this->loggerChannelPartFactory);
    if (!is_null($call)) {
      call_user_func_array([$service, $call], $arguments);
    }
    $service->destruct();
  }

  /**
   * Provides test data for testDestruct().
   */
  public function providerTestDestruct() {
    return [
      [FALSE],
      [FALSE, 'deleteChannels', ['doesnotexist']],
      [TRUE, 'deleteChannel', ['exists']],
      [TRUE, 'deleteChannels', ['ex']],
      [TRUE, 'setChannel', ['new', [1,2,3]]],
      [TRUE, 'setChannel', ['exists', []]],
    ];
  }

  /**
   * @covers ::deleteChannel
   *
   * @dataProvider providerTestDeleteChannel()
   */
  public function testDeleteChannel($id, $exists) {
    $config_factory = $this->getConfigFactoryStub(SELF::DEFAULT_CONFIG);
    $service = new LoggerService($config_factory, $this->loggerChannelPartFactory);
    $this->assertEquals($exists, $service->hasChannel($id));
    $this->assertEquals(NULL, $service->deleteChannel($id));
    $this->assertEquals(FALSE, $service->hasChannel($id));
  }

  /**
   * Provides test data for testDeleteChannel().
   */
  public function providerTestDeleteChannel() {
    return [
      ['exists', TRUE],
      ['foobarbaz', TRUE],
      ['doesnotexists', FALSE],
    ];
  }

  /**
   * @covers ::deleteChannels
   *
   * @dataProvider providerTestDeleteChannels()
   */
  public function testDeleteChannels($id_starts_with, $has, $hasnot) {
    $config_factory = $this->getConfigFactoryStub(SELF::DEFAULT_CONFIG);
    $service = new LoggerService($config_factory, $this->loggerChannelPartFactory);
    foreach($has as $id) {
      $this->assertEquals(TRUE, $service->hasChannel($id));
    }
    foreach($hasnot as $id) {
      $this->assertEquals(TRUE, $service->hasChannel($id));
    }
    $this->assertEquals(NULL, $service->deleteChannels($id_starts_with));
    foreach($has as $id) {
      $this->assertEquals(TRUE, $service->hasChannel($id));
    }
    foreach($hasnot as $id) {
      $this->assertEquals(FALSE, $service->hasChannel($id));
    }
  }

  /**
   * Provides test data for testDeleteChannels().
   */
  public function providerTestDeleteChannels() {
    return [
      ['E', ['foo', 'foobar', 'foobarbaz', 'exists'], []],
      ['e', ['foo', 'foobar', 'foobarbaz'], ['exists']],
      ['ex', ['foo', 'foobar', 'foobarbaz'], ['exists']],
      ['exi', ['foo', 'foobar', 'foobarbaz'], ['exists']],
      ['exis', ['foo', 'foobar', 'foobarbaz'], ['exists']],
      ['exist', ['foo', 'foobar', 'foobarbaz'], ['exists']],
      ['exists', ['foo', 'foobar', 'foobarbaz'], ['exists']],
      ['foobarbaz', ['exists', 'foo', 'foobar'], ['foobarbaz']],
      ['foobarba', ['exists', 'foo', 'foobar'], ['foobarbaz']],
      ['foobarb', ['exists', 'foo', 'foobar'], ['foobarbaz']],
      ['foobar', ['exists', 'foo'], ['foobar', 'foobarbaz']],
      ['fooba', ['exists', 'foo'], ['foobar', 'foobarbaz']],
      ['foob', ['exists', 'foo'], ['foobar', 'foobarbaz']],
      ['foo', ['exists'], ['foo', 'foobar', 'foobarbaz']],
      ['fo', ['exists'], ['foo', 'foobar', 'foobarbaz']],
      ['f', ['exists'], ['foo', 'foobar', 'foobarbaz']],
    ];
  }

  /**
   * @covers ::get
   *
   * @dataProvider providerTestGet()
   */
  public function testGet($id, $shouldexist) {
    $config_factory = $this->getConfigFactoryStub(SELF::DEFAULT_CONFIG);
    $service = new LoggerService($config_factory, $this->loggerChannelPartFactory);
    if ($shouldexist) {
      $uncached = $service->get($id);
      $this->assertInstanceOf('\Drupal\purge\Logger\LoggerChannelPartInterface', $uncached);
      $cached = $service->get($id);
      $this->assertInstanceOf('\Drupal\purge\Logger\LoggerChannelPartInterface', $cached);
      $this->assertEquals(spl_object_hash($uncached), spl_object_hash($cached));
    }
    else {
      $thrown = FALSE;
      try {
        $service->get($id);
      }
      catch (\LogicException $e) {
        $thrown = TRUE;
      }
      $this->assertEquals(TRUE, $thrown);
    }
  }

  /**
   * Provides test data for testGet().
   */
  public function providerTestGet() {
    return [
      ['exists', TRUE],
      ['doesnotexists', FALSE],
    ];
  }

  /**
   * @covers ::hasChannel
   *
   * @dataProvider providerTestHasChannel()
   */
  public function testHasChannel($id, $shouldexist) {
    $config_factory = $this->getConfigFactoryStub(SELF::DEFAULT_CONFIG);
    $service = new LoggerService($config_factory, $this->loggerChannelPartFactory);
    $this->assertEquals($service->hasChannel($id), $shouldexist);
  }

  /**
   * Provides test data for testHasChannel().
   */
  public function providerTestHasChannel() {
    return [
      ['exists', TRUE],
      ['foo', TRUE],
      ['foobar', TRUE],
      ['foobarbaz', TRUE],
      ['fo', FALSE],
      ['doesnotexists', FALSE],
    ];
  }

  /**
   * @covers ::setChannel
   *
   * @dataProvider providerTestSetChannel()
   */
  public function testSetChannel($id, $preexists) {
    $config_factory = $this->getConfigFactoryStub(SELF::DEFAULT_CONFIG);
    $service = new LoggerService($config_factory, $this->loggerChannelPartFactory);
    $this->assertEquals($preexists, $service->hasChannel($id));
    $this->assertEquals(NULL, $service->setChannel($id, [1,2,3]));
    $this->assertEquals(TRUE, $service->hasChannel($id));
  }

  /**
   * Provides test data for testSetChannel().
   */
  public function providerTestSetChannel() {
    return [
      ['exists', TRUE],
      ['foobarbaz', TRUE],
      ['doesnotexists', FALSE],
      ['alsofake', FALSE],
    ];
  }

}

<?php

namespace Drupal\Tests\purge\Unit\Logger;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\purge\Logger\LoggerChannelPart;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\purge\Logger\LoggerChannelPart
 *
 * @group purge
 */
class LoggerChannelPartTest extends UnitTestCase {

  /**
   * The mocked logger channel.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
   */
  protected $loggerChannelPurge;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->loggerChannelPurge = $this->createMock('\Psr\Log\LoggerInterface');
  }

  /**
   * Helper to all severity methods.
   */
  private function helperForSeverityMethods($id, array $grants, $output, $severity): void {
    $occurrence = is_null($output) ? $this->never() : $this->once();
    $this->loggerChannelPurge
      ->expects($occurrence)
      ->method('log')
      ->with(
        $this->stringContains($severity),
        $this->stringContains('@purge_channel_part: @replaceme'),
        $this->callback(function ($subject) use ($id, $output) {
          return ($subject['@purge_channel_part'] === $id) && ($subject['@replaceme'] === $output);
        })
      );
    $part = new LoggerChannelPart($this->loggerChannelPurge, $id, $grants);
    $part->$severity('@replaceme', ['@replaceme' => $output]);
  }

  /**
   * @covers ::__construct
   */
  public function testInstance(): void {
    $part = new LoggerChannelPart($this->loggerChannelPurge, 'id', []);
    $this->assertInstanceOf('\Psr\Log\LoggerInterface', $part);
  }

  /**
   * @covers ::getGrants
   *
   * @dataProvider providerTestGetGrants()
   */
  public function testGetGrants(array $grants): void {
    $part = new LoggerChannelPart($this->loggerChannelPurge, 'id', $grants);
    $this->assertEquals(count($grants), count($part->getGrants()));
    $this->assertEquals($grants, $part->getGrants());
    foreach ($part->getGrants() as $k => $v) {
      $this->assertTrue(is_int($k));
      $this->assertTrue(is_int($v));
    }
  }

  /**
   * Provides test data for testGetGrants().
   */
  public function providerTestGetGrants(): array {
    return [
      [[]],
      [[RfcLogLevel::EMERGENCY]],
      [[RfcLogLevel::ALERT]],
      [[RfcLogLevel::CRITICAL]],
      [[RfcLogLevel::ERROR]],
      [[RfcLogLevel::WARNING]],
      [[RfcLogLevel::NOTICE]],
      [[RfcLogLevel::INFO]],
      [[RfcLogLevel::INFO, RfcLogLevel::DEBUG]],
      [[RfcLogLevel::DEBUG]],
    ];
  }

  /**
   * @covers ::emergency
   *
   * @dataProvider providerTestEmergency()
   */
  public function testEmergency($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'emergency');
  }

  /**
   * Provides test data for testEmergency().
   */
  public function providerTestEmergency(): array {
    return [
      ['good', [RfcLogLevel::EMERGENCY], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::alert
   *
   * @dataProvider providerTestAlert()
   */
  public function testAlert($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'alert');
  }

  /**
   * Provides test data for testAlert().
   */
  public function providerTestAlert(): array {
    return [
      ['good', [RfcLogLevel::ALERT], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::critical
   *
   * @dataProvider providerTestCritical()
   */
  public function testCritical($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'critical');
  }

  /**
   * Provides test data for testCritical().
   */
  public function providerTestCritical(): array {
    return [
      ['good', [RfcLogLevel::CRITICAL], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::error
   *
   * @dataProvider providerTestError()
   */
  public function testError($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'error');
  }

  /**
   * Provides test data for testError().
   */
  public function providerTestError(): array {
    return [
      ['good', [RfcLogLevel::ERROR], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::warning
   *
   * @dataProvider providerTestWarning()
   */
  public function testWarning($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'warning');
  }

  /**
   * Provides test data for testWarning().
   */
  public function providerTestWarning(): array {
    return [
      ['good', [RfcLogLevel::WARNING], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::notice
   *
   * @dataProvider providerTestNotice()
   */
  public function testNotice($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'notice');
  }

  /**
   * Provides test data for testNotice().
   */
  public function providerTestNotice(): array {
    return [
      ['good', [RfcLogLevel::NOTICE], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::info
   *
   * @dataProvider providerTestInfo()
   */
  public function testInfo($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'info');
  }

  /**
   * Provides test data for testInfo().
   */
  public function providerTestInfo(): array {
    return [
      ['good', [RfcLogLevel::INFO], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::debug
   *
   * @dataProvider providerTestDebug()
   */
  public function testDebug($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'debug');
  }

  /**
   * Provides test data for testDebug().
   */
  public function providerTestDebug(): array {
    return [
      ['good', [RfcLogLevel::DEBUG], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * @covers ::log
   *
   * @dataProvider providerTestLog()
   */
  public function testLog($id, $level, $message, $output): void {
    $this->loggerChannelPurge
      ->expects($this->once())
      ->method('log')
      ->with(
        $this->stringContains($level),
        $this->stringContains('@purge_channel_part: ' . $message),
        $this->callback(function ($subject) use ($id, $output) {
          return ($subject['@purge_channel_part'] === $id) && ($subject['@replaceme'] === $output);
        })
      );
    $part = new LoggerChannelPart($this->loggerChannelPurge, $id);
    $part->log($level, $message, ['@replaceme' => $output]);
  }

  /**
   * Provides test data for testLog().
   */
  public function providerTestLog(): array {
    return [
      ['id1', 'level1', 'message @placeholder', ['@placeholder' => 'foo']],
      ['id2', 'level2', 'message @placeholder', ['@placeholder' => 'bar']],
      ['id3', 'level3', 'message @placeholder', ['@placeholder' => 'baz']],
    ];
  }

}

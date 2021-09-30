<?php

namespace Drupal\Tests\purge\Kernel\TagsHeader;

use Drupal\Tests\purge\Kernel\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService.
 *
 * @group purge
 */
class ServiceTest extends KernelServiceTestBase {

  /**
   * The name of the service as defined in services.yml.
   *
   * @var string
   */
  protected $serviceId = 'purge.tagsheaders';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_tagsheader_test'];

  /**
   * All tagsheader plugins that can be expected.
   *
   * @var string[]
   */
  protected $plugins = [
    'a',
    'b',
    'c',
  ];

  /**
   * Tests TagsHeadersService::count.
   */
  public function testCount(): void {
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEquals(count($this->plugins), count($this->service));
  }

  /**
   * Tests TagsHeadersService::getPluginsEnabled.
   */
  public function testGetPluginsEnabled(): void {
    $plugin_ids = $this->service->getPluginsEnabled();
    foreach ($this->plugins as $plugin_id) {
      $this->assertTrue(in_array($plugin_id, $plugin_ids));
    }
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::current
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::key
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::next
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::rewind
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::valid
   */
  public function testIteration(): void {
    $this->assertIterator(
      $this->plugins,
      '\Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface'
    );
  }

}

<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests purge_ui_form_system_performance_settings_alter().
 *
 * @group purge
 */
class SystemPerformanceFormTest extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Route providing the system configuration form which purge_ui alters.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'system.performance_settings';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui'];

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Verify that our alterations are rendered.
   */
  public function testFormIsAltered(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $id = 'edit-page-cache-maximum-age';
    $option_default = $this->assertSession()->optionExists($id, '0');
    $option_1months = $this->assertSession()->optionExists($id, '2764800')->getText();
    $option_6months = $this->assertSession()->optionExists($id, '16588800')->getText();
    $option_week = $this->assertSession()->optionExists($id, '604800')->getText();
    $option_year = $this->assertSession()->optionExists($id, '31536000')->getText();
    $this->assertTrue($option_default->hasAttribute('selected'));
    $this->assertSame('1 month', $option_1months);
    $this->assertSame('6 months', $option_6months);
    $this->assertSame('1 week', $option_week);
    $this->assertSame('1 year (recommended for external cache invalidation)', $option_year);
  }

}

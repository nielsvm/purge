<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Testbase for tests testing \Drupal\purge_ui\Controller\DashboardController.
 */
abstract class DashboardTestBase extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Route providing the main configuration form of the purge module.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.dashboard';

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
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route);
    }
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess(): void {
    $this->drupalGet($this->route);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('When content on your website changes, your purge setup will take care of refreshing external caching systems and CDNs.');
    $this->assertSession()->statusCodeEquals(200);
  }

}

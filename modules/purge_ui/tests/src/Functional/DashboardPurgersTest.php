<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildPurgers().
 *
 * @group purge
 */
class DashboardPurgersTest extends DashboardTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test'];

  /**
   * Test the purgers section of the configuration form.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildPurgers
   */
  public function testPurgersSection(): void {
    $this->drupalLogin($this->adminUser);
    // Assert that without any enabled purgers, the form stays empty.
    $this->initializePurgersService();
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Each layer of caching on top of your site is cleared by a purger. Purgers are provided by third-party modules and support one or more types of cache invalidation.');
    $this->assertSession()->responseContains('Drupal Origin');
    $this->assertSession()->responseContains('Public Endpoint');
    $this->assertSession()->responseNotContains('Purger A</th>');
    $this->assertSession()->responseNotContains('Purger B</th>');
    $this->assertSession()->responseNotContains('Purger C</th>');
    $this->assertSession()->responseNotContains('Configurable purger</th>');
    // Assert that enabled purgers show up and have the right buttons attached.
    $this->initializePurgersService(['a', 'withform']);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Purger A</a>');
    $this->assertSession()->responseContains('Configurable purger</a>');
    $purger_0_route_params = ['id' => 'id0'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.purger_detail_form', $purger_0_route_params)->toString());
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('purge_ui.purger_config_dialog_form', $purger_0_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.purger_delete_form', $purger_0_route_params)->toString());
    $purger_1_route_params = ['id' => 'id1'];
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.purger_detail_form', $purger_1_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.purger_config_dialog_form', $purger_1_route_params)->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('purge_ui.purger_delete_form', $purger_1_route_params)->toString());
    // Assert that the purger-type supportability matrix shows the checkmarks.
    $expected_checkmark_image_url = file_url_transform_relative(file_create_url('core/misc/icons/73b355/check.svg'));
    $this->assertFalse(empty($this->cssSelect('img[width=18][height=18][alt=Supported][title=Supported][src="' . $expected_checkmark_image_url . '"]')));
    $this->assertSession()->responseNotContains('<img supports="drupal-domain"');
    $this->assertSession()->responseNotContains('<img supports="drupal-path"');
    $this->assertSession()->responseContains('<img supports="drupal-tag"');
    $this->assertSession()->responseNotContains('<img supports="drupal-regex"');
    $this->assertSession()->responseNotContains('<img supports="drupal-wildcardpath"');
    $this->assertSession()->responseNotContains('<img supports="drupal-wildcardurl"');
    $this->assertSession()->responseNotContains('<img supports="drupal-url"');
    $this->assertSession()->responseNotContains('<img supports="drupal-everything"');
    $this->assertSession()->responseNotContains('<img supports="id0-domain"');
    $this->assertSession()->responseNotContains('<img supports="id0-path"');
    $this->assertSession()->responseNotContains('<img supports="id0-tag"');
    $this->assertSession()->responseNotContains('<img supports="id0-regex"');
    $this->assertSession()->responseNotContains('<img supports="id0-wildcardpath"');
    $this->assertSession()->responseNotContains('<img supports="id0-wildcardurl"');
    $this->assertSession()->responseNotContains('<img supports="id0-url"');
    $this->assertSession()->responseContains('<img supports="id0-everything"');
    $this->assertSession()->responseNotContains('<img supports="id1-domain"');
    $this->assertSession()->responseContains('<img supports="id1-path"');
    $this->assertSession()->responseNotContains('<img supports="id1-tag"');
    $this->assertSession()->responseNotContains('<img supports="id1-regex"');
    $this->assertSession()->responseNotContains('<img supports="id1-wildcardpath"');
    $this->assertSession()->responseNotContains('<img supports="id1-wildcardurl"');
    $this->assertSession()->responseNotContains('<img supports="id1-url"');
    $this->assertSession()->responseNotContains('<img supports="id1-everything"');
    // Assert that 'Add purger' only shows up when it actually should.
    $this->assertSession()->responseContains('Add purger');
    $this->initializePurgersService(['a', 'b', 'c', 'withform', 'good']);
    $this->drupalGet($this->route);
    $this->assertSession()->responseNotContains('Add purger');
  }

}

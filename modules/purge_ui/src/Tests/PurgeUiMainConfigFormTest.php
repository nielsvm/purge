<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgeUiMainConfigFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the configuration form that sets the active plugins.
 *
 * @group purge
 * @see \Drupal\purge\Purger\PurgerServiceInterface
 * @see \Drupal\purge\Queue\QueueServiceInterface
 */
class PurgeUiMainConfigFormTest extends WebTestBase {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Purger\PurgerServiceInterface
   */
  protected $purgePurger;

  /**
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * @var string
   */
  protected $path = 'admin/config/development/performance/purge';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('purge_ui', 'purge_test');

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->purgePurger = $this->container->get('purge.purger');
    $this->purgeQueue = $this->container->get('purge.queue');
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));
  }

  /**
   * Asserts local tasks in the page output.
   *
   * @warning
   * This helper is copied from its original, and thus needs maintenance.
   *
   * @see \Drupal\system\Tests\Menu\LocalTasksTest.
   */
  protected function assertLocalTasks(array $hrefs, $level = 0) {
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(
      ':class' => $level == 0 ? 'tabs primary' : 'tabs secondary',
    ));
    $this->assertTrue(count($elements), 'Local tasks found.');
    foreach ($hrefs as $index => $element) {
      $expected = url($hrefs[$index]);
      $method = ($elements[$index]['href'] == $expected ? 'pass' : 'fail');
      $this->{$method}(format_string('Task @number href @value equals @expected.', array(
        '@number' => $index + 1,
        '@value' => (string) $elements[$index]['href'],
        '@expected' => $expected,
      )));
    }
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess() {
    $this->drupalGet($this->path);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->assertResponse(200);
    $this->drupalGet('admin/config/development/performance');
    $this->assertLocalTasks(array(
      'admin/config/development/performance',
      $this->path,
    ));
  }

  /**
   * Test if the form stores the queue and purger settings.
   */
  public function testFormPurpose() {
    $this->drupalLogin($this->admin_user);

    // Test that selecting purgers result in the right setting value.
    $this->configFactory->get('purge.purger')->set('plugins', 'purger_a')->save();
    $this->drupalGet($this->path);
    $this->assertFieldByName('purger_detection', 'manual');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-b');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-c');
    $this->assertFieldChecked('edit-purger-plugins-purger-a');

    // Test that putting purge.purger.plugins to 'automatic_detection' results
    // in the form selecting all purgers and the right mode.
    $this->configFactory->get('purge.purger')->set('plugins', 'automatic_detection')->save();
    $this->drupalGet($this->path);
    $this->assertFieldByName('purger_detection', 'automatic_detection');
    $this->assertFieldChecked('edit-purger-plugins-purger-a');
    $this->assertFieldChecked('edit-purger-plugins-purger-b');
    $this->assertFieldChecked('edit-purger-plugins-purger-c');

    // Test that just submitting the form, results in the exact same config.
    $this->configFactory->get('purge.queue')->set('plugin', 'queue_a')->save();
    $this->drupalPostForm($this->path, array(), t('Save configuration'));
    $this->assertEqual('automatic_detection',
      $this->configFactory->get('purge.purger')->get('plugins'));
    $this->assertEqual('queue_a',
      $this->configFactory->get('purge.queue')->get('plugin'));

    // Test that changing the queue plugin, gets reflected in the config.
    $edit = array('queue_plugin' => 'queue_b');
    $this->drupalPostForm($this->path, $edit, t('Save configuration'));
    $this->assertEqual('queue_b',
      $this->configFactory->get('purge.queue')->get('plugin'));

    // Mislead the form submit by listing purgers but set to automatic detect.
    $edit = array(
      'purger_detection' => 'automatic_detection',
      'purger_plugins[purger_b]' => TRUE,
      'purger_plugins[purger_a]' => TRUE,
      'purger_plugins[purger_c]' => FALSE,
    );
    $this->drupalPostForm($this->path, $edit, t('Save configuration'));
    $this->assertEqual('automatic_detection',
      $this->configFactory->get('purge.purger')->get('plugins'));

    // Select two purgers manually and verify the settings.
    $edit['purger_detection'] = 'manual';
    $this->drupalPostForm($this->path, $edit, t('Save configuration'));
    $plugins = explode(',', $this->configFactory->get('purge.purger')->get('plugins'));
    $enabled = $this->purgePurger->getPluginsEnabled();
    $this->assertTrue(in_array('purger_a', $plugins), 'plugin_a configured');
    $this->assertTrue(in_array('purger_b', $plugins), 'plugin_b configured');
    $this->assertTrue(in_array('purger_a', $enabled), 'plugin_a active');
    $this->assertTrue(in_array('purger_b', $enabled), 'plugin_b active');
  }
}

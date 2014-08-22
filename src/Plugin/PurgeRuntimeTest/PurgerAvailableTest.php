<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeRuntimeTest\PurgerAvailableTest.
 */

namespace Drupal\purge\Plugin\PurgeRuntimeTest;

use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Purger\PurgerServiceInterface;
use Drupal\purge\RuntimeTest\RuntimeTestInterface;
use Drupal\purge\RuntimeTest\RuntimeTestBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "purgeravailable",
 *   title = @Translation("Purger(s) configured"),
 *   description = @Translation("Tests if there is a purger plugin available."),
 *   service_dependencies = {"purge.purger"},
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class PurgerAvailableTest extends RuntimeTestBase implements RuntimeTestInterface {

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\PurgerServiceInterface
   */
  protected $purgePurger;

  /**
   * Constructs a \Drupal\purge\Plugin\PurgeRuntimeTest\PurgerAvailableTest object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\purge\Purger\PurgerServiceInterface $purge_purger
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PurgerServiceInterface $purge_purger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgePurger = $purge_purger;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $purgers = $this->purgePurger->getPluginsLoaded();

    // Test for the 'dummy' purger, which only loads if nothing else exists.
    if (in_array('dummy', $purgers)) {
      $this->value = $this->t("n/a");
      $this->recommendation = $this->t("There is no purger loaded which means ".
        "that you need a module enabled to provide a purger plugin to clear ".
        "your external cache or CDN.");
      return SELF::SEVERITY_ERROR;
    }
    elseif (count($purgers) > 3) {
      $this->value = implode(', ', $purgers);
      $this->recommendation = $this->t("You have more than 3 purgers active ".
        "on one system. This introduces the risk of congesting Drupal as ".
        "multiple purgers are clearing external caches. It is highly ".
        "recommended is to simplify your caching architecture.");
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->value = implode(', ', $purgers);
      $this->recommendation = $this->t("Purger configured.");
      return SELF::SEVERITY_OK;
    }
  }
}

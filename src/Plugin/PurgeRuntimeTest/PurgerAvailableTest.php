<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeRuntimeTest\PurgerAvailableTest.
 */

namespace Drupal\purge\Plugin\PurgeRuntimeTest;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Queue\PluginInterface as Queue;
use Drupal\purge\Purger\ServiceInterface as PurgerService;
use Drupal\purge\RuntimeTest\PluginInterface as RuntimeTest;
use Drupal\purge\RuntimeTest\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeRuntimeTest(
 *   id = "purgeravailable",
 *   title = @Translation("Purger(s) configured"),
 *   description = @Translation("Tests if there is a purger plugin available."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class PurgerAvailableTest extends PluginBase implements RuntimeTest {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Purger\PurgerServiceInterface $purge_purger
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, PurgerService $purge_purger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->purgePurger = $purge_purger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('purge.purger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $purgers = $this->purgePurger->getPluginsEnabled();

    // Test for the 'null' purger, which only loads if nothing else exists.
    if (in_array('null', $purgers)) {
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
        "recommended is to simplify your caching architecture if possible.");
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->value = implode(', ', $purgers);
      $this->recommendation = $this->t("Purger configured.");
      return SELF::SEVERITY_OK;
    }
  }
}

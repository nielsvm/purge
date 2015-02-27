<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeDiagnosticCheck\CapacityCheck.
 */

namespace Drupal\purge\Plugin\PurgeDiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purger\ServiceInterface as PurgersService;
use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Checks if there is purging capacity available.
 *
 * @PurgeDiagnosticCheck(
 *   id = "capacity",
 *   title = @Translation("Capacity"),
 *   description = @Translation("Checks if there is invalidation capacity available."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class CapacityCheck extends PluginBase implements PluginInterface {

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a CapacityCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\purge\Purger\PurgerServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PurgersService $purge_purgers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('purge.purgers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->value = $this->purgePurgers->getCapacityLimit();

    // When the capacity is zero - this would be problematic.
    if ($this->value === 0) {
      $this->recommendation = $this->t("There is no purging capacity available.");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($this->value < 3) {
      $this->recommendation = $this->t("There is not much capacity available, this means that Drupal might be attempting purges at higher rate then your configuration is able to catch up. Expect your queue to build up quickly.");
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->recommendation = $this->t("Your system can invalidate @number items per Drupal request.", ['@number' => $this->value]);
      return SELF::SEVERITY_OK;
    }
  }

}

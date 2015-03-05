<?php
/**
 * @file
 * Contains \Drupal\purge_processor_lateruntime\EventSubscriber\LateRuntimeProcessor.
 */

namespace Drupal\purge_processor_lateruntime\EventSubscriber;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\Processor\ProcessorInterface;

/**
 * Processes queue items at the end of every request.
 */
class LateRuntimeProcessor implements ProcessorInterface, EventSubscriberInterface, ContainerAwareInterface {
  use ContainerAwareTrait;
  use StringTranslationTrait;

  /**
   * The container id of this processor.
   *
   * @var string
   */
  protected $id;

  /**
   * The ImmutableConfig object 'purge_processor_lateruntime.settings'.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The purge logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Diagnostics service that reports any preliminary issues before purging.
   *
   * @var \Drupal\purge\DiagnosticCheck\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * The queue in which to store, claim and release invalidation objects from.
   *
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * Make both the immutable config object and the factory available.
   */
  protected function initializeConfig() {
    if (is_null($this->configFactory)) {
      $this->configFactory = $this->container->get('config.factory');
      $this->config = $this->configFactory->get('purge_processor_lateruntime.settings');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->initializeConfig();
    $this->configFactory->getEditable('purge_processor_lateruntime.settings')->set('status', FALSE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->initializeConfig();
    $this->configFactory->getEditable('purge_processor_lateruntime.settings')->set('status', TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $this->initializeConfig();
    return $this->config->get('status');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Processes purge queue items during the same request, only for high-performance purgers!");
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::FINISH_REQUEST][] = 'onKernelFinishRequest';
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Late runtime processor");
  }

  /**
   * Invoked by the FINISH_REQUEST kernel event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FinishRequestEvent $event
   *   The event object.
   *
   * @return void
   */
  public function onKernelFinishRequest(FinishRequestEvent $event) {
    if (!$this->isEnabled()) {
      return;
    }
    $this->logger = $this->container->get('logger.channel.purge');
    $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    $this->purgePurgers = $this->container->get('purge.purgers');
    $this->purgeQueue = $this->container->get('purge.queue');
    $this->processQueue();
  }

  /**
   * Process a reasonable number of items from the queue when there's any.
   */
  protected function processQueue() {

    // When the system is showing fire, immediately stop attempting to purge.
    if ($fire = $this->purgeDiagnostics->isSystemOnFire()) {
      return $this->logger->error($fire->getRecommendation());
    }

    // If the system shows signs of smoke, warn the user and continue purging.
    if ($this->config->get('log_warnings')) {
      if ($smoke = $this->purgeDiagnostics->isSystemShowingSmoke()) {
        $this->logger->warning($smoke->getRecommendation());
      }
    }

    // Claim as many invalidation objects as we can.
    $claims = $this->purgeQueue->claimMultiple(
      $this->purgePurgers->getCapacityLimit(),
      $this->purgePurgers->getClaimTimeHint()
    );

    // Let the purgers process and then let the queue figure out the results.
    $this->purgePurgers->invalidateMultiple($claims);
    $this->purgeQueue->deleteOrReleaseMultiple($claims);
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
  }

}

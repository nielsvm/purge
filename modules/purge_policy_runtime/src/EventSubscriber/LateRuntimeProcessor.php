<?php
/**
 * @file
 * Contains \Drupal\purge_policy_runtime\EventSubscriber\LateRuntimeProcessor.
 */

namespace Drupal\purge_policy_runtime\EventSubscriber;

use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Processes queue items at the end of every request.
 */
class LateRuntimeProcessor implements EventSubscriberInterface, ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * The ImmutableConfig object 'purge_policy_runtime.settings'.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::FINISH_REQUEST][] = 'onKernelFinishRequest';
    return $events;
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
    $this->config = $this->container->get('config.factory')->get('purge_policy_runtime.settings');
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

}

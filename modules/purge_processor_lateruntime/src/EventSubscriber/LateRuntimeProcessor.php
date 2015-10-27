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

/**
 * Processes queue items at the end of every request.
 */
class LateRuntimeProcessor implements EventSubscriberInterface, ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * The processor plugin or FALSE when disabled.
   *
   * @var false|\Drupal\purge_processor_lateruntime\Plugin\Purge\Processor\LateRuntimeProcessor
   */
  protected $processor;

  /**
   * Diagnostics service that reports any preliminary issues before purging.
   *
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * The queue in which to store, claim and release invalidation objects from.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
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
   * Initialize the services.
   *
   * @return bool
   *   TRUE when everything is available, FALSE when our plugin is disabled.
   */
  protected function initialize() {
    if (is_null($this->processor)) {
      // If the lateruntime processor plugin doesn't load, this object is not
      // allowed to operate and thus loads the least possible dependencies.
      $this->processor = $this->container->get('purge.processors')->get('lateruntime');
      if ($this->processor !== FALSE) {
        $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
        $this->purgePurgers = $this->container->get('purge.purgers');
        $this->purgeQueue = $this->container->get('purge.queue');
      }
    }
    return $this->processor !== FALSE;
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

    // Immediately stop if our plugin is disabled.
    if (!$this->initialize()) {
      return;
    }

    // When the system is showing fire, immediately stop attempting to purge.
    if ($fire = $this->purgeDiagnostics->isSystemOnFire()) {
      return;
    }

    // Claim a chunk of invalidations, process and let the queue handle results.
    $capacity = $this->purgePurgers->capacityTracker();
    if ($limit = $capacity->getLimit()) {
      $claims = $this->purgeQueue->claimMultiple($limit, $capacity->getTimeHint());
      if (count($claims)) {
        $this->purgePurgers->invalidateMultiple($claims);
        $this->purgeQueue->deleteOrReleaseMultiple($claims);
      }
    }
  }

}

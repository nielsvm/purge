<?php

/**
 * @file
 * Contains \Drupal\purge\EventSubscriber\CacheableResponseSubscriber.
 */

namespace Drupal\purge\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Cache\CacheableResponseInterface;

/**
 * Adds the Purge-Cache-Tags response header, to aid external caching systems.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * The name of the cache tags header sent.
   *
   * @var string
   */
  const HEADER = 'Purge-Cache-Tags';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * Sets the Purge-Cache-Tags header on cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // First ensure that ::getCacheableMetadata() exists on the object.
    $response = $event->getResponse();
    if ($response instanceof CacheableResponseInterface) {

      // Only proceed setting the tags when it isn't yet set by other modules.
      $tags = $response->getCacheableMetadata()->getCacheTags();
      $response->headers->set(SELF::HEADER, implode(' ', $tags));
    }
  }

}

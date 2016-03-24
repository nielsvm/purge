<?php

/**
 * @file
 * Contains \Drupal\purge\EventSubscriber\CacheTagsHeaderSubscriber.
 */

namespace Drupal\purge\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the X-Cache-Tags response header, to aid external caching systems.
 */
class CacheTagsHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * Subscribed callback to the KernelEvents::RESPONSE event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   *
   * @return void.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // First ensure that ::getCacheableMetadata() exists on the object.
    $response = $event->getResponse();
    if (method_exists($response, 'getCacheableMetadata')) {

      // Only proceed setting the tags when it isn't yet set by other modules.
      if (is_null($response->headers->get('X-Cache-Tags'))) {
        $tags = $response->getCacheableMetadata()->getCacheTags();
        $response->headers->set('X-Cache-Tags', implode(' ', $tags));
      }
    }
  }

}

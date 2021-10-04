<?php

namespace Drupal\purge\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Add cache tags headers on cacheable responses, for external caching systems.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * The tagsheaders service for iterating the available header plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface
   */
  protected $purgeTagsHeaders;

  /**
   * Construct a CacheableResponseSubscriber object.
   *
   * @param \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface $purge_tagsheaders
   *   The tagsheaders service for iterating the available header plugins.
   */
  public function __construct(TagsHeadersServiceInterface $purge_tagsheaders) {
    $this->purgeTagsHeaders = $purge_tagsheaders;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * Add cache tags headers on cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // Only set any headers when this is a cacheable response.
    $response = $event->getResponse();

    // Cache tags should be injected only when the response is cacheable. It is
    // cacheable when dynamic_page_cache module (if enabled) says so.
    // Alternatively, if dynamic_page_cache module is uninstalled, then we
    // fallback on testing that at least 'no-cache' cache directive is not
    // present in the response headers.
    $cacheTagsNeeded = $response instanceof CacheableResponseInterface;
    if ($response->headers->has('X-Drupal-Dynamic-Cache')) {
      $cacheTagsNeeded = $cacheTagsNeeded && $response->headers->get('X-Drupal-Dynamic-Cache') !== 'UNCACHEABLE';
    }
    $cacheTagsNeeded = $cacheTagsNeeded && !$response->headers->hasCacheControlDirective('no-cache');

    if ($cacheTagsNeeded) {

      // Iterate all tagsheader plugins and add a header for each plugin.
      $tags = $response->getCacheableMetadata()->getCacheTags();
      foreach ($this->purgeTagsHeaders as $header) {
        if ($header->isEnabled()) {

          // Retrieve the header name and perform a few simple sanity checks.
          $name = $header->getHeaderName();
          if ((!is_string($name)) || empty(trim($name))) {
            $pluginId = $header->getPluginId();
            throw new \LogicException("Header plugin '$pluginId' should return a non-empty string on ::getHeaderName()!");
          }

          $response->headers->set($name, $header->getValue($tags));
        }
      }
    }
  }

}

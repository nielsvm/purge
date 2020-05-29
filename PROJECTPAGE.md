**_The modular external cache invalidation framework._**

The ``purge`` module facilitates cleaning **external caching systems**,
**reverse proxies** and **CDNs** as content actually changes. This allows
external caching layers to keep unchanged content cached infinitely, making
content delivery more efficient, resilient and better guarded against traffic
spikes.

## Drupal 9
Version ``8.x-3.0`` has been tested on Drupal 9 and found to work smoothly!

## Drupal 8
The ``8.x-3.x`` versions enable invalidation of content from external systems
leveraging Drupal's brand new cache architecture. The technology-agnostic plugin
architecture allows for different server configurations and use cases. Last but
not least, it enforces a separation of concerns and should be seen as a
**middleware** solution (see
[``README.md``](http://cgit.drupalcode.org/purge/plain/README.md?h=8.x-3.x)).

###### Getting started
For most simple configurations, start with:

* ``drush en purge purge_ui``
* ``drush en purge_drush purge_queuer_coretags purge_processor_cron``
* Head over to ``admin/config/development/performance/purge``.
* Now you need to install - and probably configure -  a third-party module that
  provides a **purger**. If no module supports invalidation of your cache layer
  and doing so works over HTTP, then use the generic
  [``purge_purger_http``](https://www.drupal.org/project/purge_purger_http).

###### Third-party integration
This project aims to get all modules dealing with proxies and CDNs on board and
to integrate with Purge. As known to date, these modules are or are being
integrated:

 * **[``purge_purger_http``](https://www.drupal.org/project/purge_purger_http)**
   for generic HTTP-based invalidation, e.g. ``nginx``, ``squid``, etc.
 * **[``purge_queuer_url``](https://www.drupal.org/project/purge_queuer_url)**
   for legacy platforms not supporting cache tags. This is a **poor** solution
   when you regularly import content, it can lead to unsustainable big queues!
 * **[``acquia_purge``](https://www.drupal.org/project/acquia_purge)**
 * **[``akamai``](https://www.drupal.org/project/akamai)**
 * **[``cloudflare``](https://www.drupal.org/project/cloudflare)**
 * **[``cloudfront_purger``](https://www.drupal.org/project/cloudfront_purger)**
 * **[``fastlypurger``](https://www.drupal.org/project/fastly)**
 * **[``keycdn``](https://www.drupal.org/project/keycdn)**
 * **[``varnish_purge``](https://www.drupal.org/project/varnish_purge)**
 * **[``max_cdn_cache``](https://www.drupal.org/node/2902048)**
 * **[``nginx_cache_clear``](https://www.drupal.org/node/2902052)**

Interested? Reach out any time of day and we'll get you going!

## Drupal 7 and Pressflow 6
The Drupal 7 version is not recommended anymore for general use. Instead, we
recommend users to either consider upgrading Drupal to leverage the power
of **cache tags**, or instead set a short-lived cache TTL.

The purge module clears content from reverse proxy caches like [Varnish](http://varnish-cache.org/), [Squid](http://www.squid-cache.org/) or [Nginx](http://nginx.net/) by issuing an http _PURGE_ request to them. This allows delivering content updates faster to end users while maintaining efficient caching.

### Stable: Purge 1.x for Pressflow 6 and Drupal 7

The current stable 1.x branches work in conjunction with the [Cache Expiration module](http://drupal.org/project/expire) to act on events that are likely to expire URLs from the proxy cache. It will not longer receive new features, just bugfixes.

#### Requirements

*   One or more [reverse proxy caches](http://en.wikipedia.org/wiki/Reverse_proxy) like [Varnish](http://varnish-cache.org/) (recommended), [Squid](http://www.squid-cache.org/) or [this section in the Varnish chapter](http://nginx.net/) that point to your webserver(s).
    Varnish needs a modification to its configuration file. See [this section in the Varnish chapter](http://drupal.org/node/1054886#purge) of the Drupal handbook
*   Squid needs to have purging [enabled in its configuration](http://docstore.mik.ua/squid/FAQ-7.html#ss7.5).
*   Nginx needs [an extra module and configuration](http://labs.frickle.com/nginx_ngx_cache_purge/). See the installation hints below and the included the README.txt Also see this issue [#1048000] for more background info and compiling/installation hints
*   A cachable version for Drupal 6. This can be an official Drupal 6 release with [a patch](http://drupal.org/node/466444) applied or use [Pressflow](http://pressflow.org/), a cachable friendly fork of Drupal. Drupal 7 works out of the box.
*   PHP with [curl](http://php.net/manual/en/book.curl.php) enabled. The 1.x releases of Purge uses curl for issuing the http PURGE requests.

#### Installation

*   Unpack, place and enable just like any other module.
*   Navigate to ``Administration`` -> ``Site configuration`` -> ``Purge settings``.
*   Set your proxy URL(s) like ``http://localhost`` or ``http://192.168.1.23:8080 http://192.168.2.34:8080``
*   If you are using nginx you need to specify the purge path and the get method in your proxy setting like this: ``http://192.168.1.76:8080/purge?purge_method=get``
*   If you are using the Acquia Cloud we recommend you use the platform specific module [Acquia Purge](http://drupal.org/project/acquia_purge) instead.
*   Optional: Install [Rules](http://drupal.org/project/rules) for advanced cache clearing scenarios or [Drush](http://drupal.org/project/drush) for command line purging. Both are supported through the expire module.

### Experimental: Purge 2.x for Drupal 7

The 7.x-2.x branch has been the place where new features and strategies for Purge have been experimented on. It's not likely to have a stable release soon and possibly never at all in favor of a backport of the 8.x-3.x branch. Some of the new 2.x features we've been tinkering with: **Not suitable for production use!** See [#1826926] for more details.

### Drupal 8: Purge 3.x

Work on a Drupal 8.0.x version of Purge is ongoing. More details soon.

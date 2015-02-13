Purge
------------------------------------------------------------------------------

The Purge module for Drupal 8 enables invalidation of content from external
caches, reverse proxies and CDN platforms. The technology agnostic plugin
architecture enables different server configurations, use cases, enforces a
separation of concerns and can be seen as **middleware** solution.

Terminology overview
------------------------------------------------------------------------------

#### Queuer
With Purge, end users can manually invalidate a page with a Drush command or
theoretically even with the convenience of a "clear this page"-button. Caches
are however meant to be transparent to end users and to only invalidate when
something actually changed - and thus requires external caches also to be
transparent.

When editing content of any sort, Drupal will transparently invalidate cached
pages in Drupal's own **anonymous page cache** and does this efficiently. When
Drupal renders a page, it lists all the things on the page in a special HTTP
response header named ``X-Drupal-Cache-Tags``. This allows all cached pages
that show ``node:1`` on them, to be invalidated, when that node changed.

Purge ships with the ``CacheTagsQueuer`` enabled by default. So when Drupal
clears things from its own page cache, Purge will add a purgeable to the queue
causing the external page also to be invalidated.

#### Queue
Queueing is an inevitable and important part of Purge as it makes cache
invalidation resilient, stable and accurate. Some reverse cache systems can
clear thousands of items under a second, yet others - for instance CDN's - can
demand multi-step purges that can easily take up 30 minnutes. Although the
queue can technically be left out of the process entirely, it will be required
in the majority of use cases.

#### Purgeables
Purgeables are little value objects that **decribe and track invalidations**
on one or more external caching systems within the Purge pipeline. These
objects float freely between **queue** and **purgers** but can also be created
on the fly and in third-party code.

##### Purgeable types
To properly allow purgers and external cache systems to invalidate content, it
has to be crystal clear what *thing* needs to be *purged*. Although not every
purger supports every type, the most important one is ``tag`` since Drupal's
own architecture and anonymous page cache is cleared using the same concept.

* ``\Drupal\purge\Plugin\PurgePurgeable\FullDomain``
* ``\Drupal\purge\Plugin\PurgePurgeable\Path``
* ``\Drupal\purge\Plugin\PurgePurgeable\WildcardPath``
* ``\Drupal\purge\Plugin\PurgePurgeable\Tag``

#### Purgers
Purgers do all the hard work of telling external systems what to invalidate
and do this in the technically required way, for instance with external API
calls, through telnet commands or with specially crafted HTTP requests.

Purge **doesn't ship any** purger, as this is context specific. You could for
instance have multiple purgers enabled to both clean a local proxy and a CDN
at the same time.

#### Processing Policies
Although editing content leads to ``tag`` purgeables automatically getting
queued, this doesn't mean they get processed automatically. It is up to you
to select configure a stable situation for your needs.

Policy possibilities:

* **none** tags get queued, but nothing clears automatically.
* **``cron``** claims from the queue & purges during cron.
* **``ajaxui``** after editing content, an AJAX-based progressbar works the queue.
* **``runtime``** purges (just-queued) items during the same request (**SLOW**).

API examples
------------------------------------------------------------------------------

#### Direct invalidation
```
$p = \Drupal::service('purge.purgeables')->fromNamedRepresentation('tag', 'node:1');
\Drupal::service('purge.purger')->purge($p);
```

```
$factory = \Drupal::service('purge.purgeables');
$p = [
  $factory->fromNamedRepresentation('tag', 'node:1'),
  $factory->fromNamedRepresentation('tag', 'node:2'),
  $factory->fromNamedRepresentation('path', 'contact'),
  $factory->fromNamedRepresentation('wildcardpath', 'news/*'),
];
\Drupal::service('purge.purger')->purgeMultiple($p);
```

#### Queuing
```
$p = \Drupal::service('purge.purgeables')->fromNamedRepresentation('path', 'news/');
\Drupal::service('purge.queue')->add($p);
```

```
$factory = \Drupal::service('purge.purgeables');
$p = [
  $factory->fromNamedRepresentation('tag', 'node:1'),
  $factory->fromNamedRepresentation('tag', 'node:2'),
];
\Drupal::service('purge.queue')->addMultiple($p);
```

#### Queue processing
```
// Processing must occur within 10 seconds.
$queue = \Drupal::service('purge.queue');
if ($p = $queue->claim(10)) {
  $success = \Drupal::service('purge.purger')->purge($p);
  if ($success) {
    $queue->delete($p);
  }
  else {
    $queue->release($p);
  }
}
```

Purge
------------------------------------------------------------------------------

The Purge module for Drupal 8 enables invalidation of content from external
caches, reverse proxies and CDN platforms. The technology-agnostic plugin
architecture allows for different server configurations and use cases. Last but
not least, it enforces a separation of concerns and should be seen as a
**middleware** solution.

Terminology overview
------------------------------------------------------------------------------

#### Queuer
With Purge, end users can manually invalidate a page with a Drush command or,
theoretically, via a "clear this page" button in the GUI. Caches
are however meant to be transparent to end users and to only be invalidated
when something actually changed - and thus requires external caches to also be
transparent.

When editing content of any kind, Drupal will transparently and efficiently
invalidate cached pages in Drupal's own **anonymous page cache**. When Drupal
renders a page, it lists all the rendered items on the page in a special HTTP
response header named ``X-Drupal-Cache-Tags``. For example, this allows all
cached pages with the ``node:1`` Cache-Tag in their headers to be invalidated,
when that particular node (node/1) is changed.

Purge ships with the ``CacheTagsQueuer``, a mechanism which puts Drupal's
invalidated Cache-Tags into Purge's queue. So, when Drupal clears rendered
items from its own page cache, Purge will add a _invalidation_ object to its
queue so that it gets cleared remotely as well. When this is undesired behavior,
take a look at ``tests/modules/purge_noqueuer_test/``.

#### Queue
Queueing is an inevitable and important part of Purge as it makes cache
invalidation resilient, stable and accurate. Certain reverse cache systems can
clear thousands of items under a second, yet others - for instance CDNs - can
demand multi-step purges that can easily take up 30 minutes. Although the
queue can technically be left out of the process entirely, it will be required
in the majority of use cases.

#### Invalidations
Invalidations are small value objects that **decribe and track invalidations**
on one or more external caching systems within the Purge pipeline. These
objects float freely between **queue** and **purgers** but can also be created
on the fly and in third-party code.

##### Invalidation types
Purge has to be crystal clear about what needs invalidation towards its purgers,
and therefore has the concept of invalidation types. Individual purgers declare
which types they support and can even declare their own types when that makes
sense. Since Drupal invalidates its own caches using cache tags, the ``tag``
type is the most important one to support in your architecture.

* **``domain``** Invalidates an entire domain name.
* **``everything``** Invalidates everything.
* **``path``** Invalidates by path, e.g. ``news/article-1``.
* **``regex``** Invalidates by regular expression, e.g.: ``\.(jpg|jpeg|css|js)$``.
* **``tag``** Invalidates by Drupal cache tag, e.g.: ``menu:footer``.
* **``url``** Invalidates by URL, e.g. ``http://site.com/node/1``.
* **``wildcardpath``** Invalidates by path, e.g. ``news/*``.
* **``wildcardurl``** Invalidates by URL, e.g. ``http://site.com/node/*``.

#### Purgers
Purgers do all the hard work of telling external systems what to invalidate
and do this in the technically required way, for instance with external API
calls, through telnet commands or with specially crafted HTTP requests.

Purge **doesn't ship any purger**, as this is context specific. You could for
instance have multiple purgers enabled to both clean a local proxy and a CDN
at the same time.

#### Diagnostic checks
External cache invalidation usually depends on many parameters, for instance
configuration settings such as hostname or CDN API keys. In order to prevent
hard crashes during runtime that affect end-user workflow, Purge allows plugins
to write preventive diagnostic checks that can check their configurations and
anything else that affects runtime execution. These checks can block all purging
but also raise warnings and other diagnostic information. End-users can rely on
Drupal's status report page where these checks bubble up.

#### Processing Policies
Although editing content leads to ``tag`` invalidation objects automatically
getting queued, this doesn't mean they get processed automatically. It is up to
you to select a stable configuration for your needs.

Policy possibilities:

* **none** tags get queued, but nothing gets cleared automatically.
* **``cron``** claims items from the queue & purges during cron.
* **``ajaxui``** AJAX-based progress bar working the queue after a piece of
content has been updated.
* **``runtime``** purges (just-queued) items during the same request (**SLOW**).

API examples
------------------------------------------------------------------------------

#### Direct invalidation
```
$i = \Drupal::service('purge.invalidation.factory')->get('tag', 'node:1');
\Drupal::service('purge.purgers')->invalidate($i);
```

```
$i = [
  \Drupal::service('purge.invalidation.factory')->get('tag', 'node:1'),
  \Drupal::service('purge.invalidation.factory')->get('tag', 'node:2'),
  \Drupal::service('purge.invalidation.factory')->get('path', 'contact'),
  \Drupal::service('purge.invalidation.factory')->get('wildcardpath', 'news/*'),
];
\Drupal::service('purge.purgers')->invalidateMultiple($i);
```

#### Queuing
```
$i = \Drupal::service('purge.invalidation.factory')->get('path', 'news/');
\Drupal::service('purge.queue')->add($i);
```

```
$i = [
  \Drupal::service('purge.invalidation.factory')->get('tag', 'node:1'),
  \Drupal::service('purge.invalidation.factory')->get('tag', 'node:2'),
];
\Drupal::service('purge.queue')->addMultiple($i);
```

#### Queue processing
```
// Processing must occur within 10 seconds.
$queue = \Drupal::service('purge.queue');
if ($i = $queue->claim(10)) {
  $success = \Drupal::service('purge.purgers')->invalidate($i);
  if ($success) { // @ TODO - this no longer works
    $queue->delete($i);
  }
  else {
    $queue->release($i);
  }
}
```

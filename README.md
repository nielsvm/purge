# Purge
##### _The modular external cache invalidation framework_
------------------------------------------------------------------------------

The Purge module for Drupal 8 enables invalidation of content from external
caches, reverse proxies and CDN platforms. The technology-agnostic plugin
architecture allows for different server configurations and use cases. Last but
not least, it enforces a separation of concerns and should be seen as a
**middleware** solution.

The framework explained
------------------------------------------------------------------------------
Purge isn't just a single API but made up of several API pillars all driven by
plugins, allowing very flexible end-user setups. All of them are clearly
defined to enforce a sustainable and maintainable framework over the longer
term. This also allows everyone to build, improve and fix bugs in only the
plugins they provide and therefore allows everyone to 'scale up' solving
external cache invalidation in the best way possible.

#### Queuer
With Purge, end users can manually invalidate a page with a Drush command or,
theoretically, via a "clear this page" button in the GUI. Caches
are however meant to be transparent to end users and to only be invalidated
when something actually changed - and thus requires external caches to also be
transparent.

When editing content of any kind, Drupal will transparently and efficiently
invalidate cached pages in Drupal's own **anonymous page cache**. When Drupal
renders a page, it can lists all the rendered items on the page in a special
HTTP response header named ``X-Drupal-Cache-Tags``. For example, this allows all
cached pages with the ``node:1`` Cache-Tag in their headers to be invalidated,
when that particular node (node/1) is changed.

Purge ships with the **Core tags queuer**, which replicates everything Drupal
core invalidated onto Purge's queue. So, when Drupal clears rendered items from
its own page cache, Purge will add a _invalidation_ object to its queue so that
it gets cleared remotely as well.

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
Drupal's status report page where these checks also bubble up.

#### Capacity tracker
The capacity tracker is the central orchestrator between limited - request
lifetime - system resources and an ever growing queue of invalidation objects.

The tracker aggregates capacity hints given by loaded purgers and sets
uniformized purging capacity boundaries. It tracks how much purges are taking
place - counts successes and failures - and actively protects the set
limits. This protects end-users against requests exceeding resource limits
such as maximum execution time and memory exhaustion. At the same time it
aids queue processors by dynamically giving the number of items that can
be processed in one go.

#### Processors
With queuers adding ``tag`` invalidation objects to the queue, this still leaves
the processing of it open. Since different use cases are possible, it is up to
you to configure a stable processing policy that's suitable for your use case.

Possibilities:

* **``cron``** claims items from the queue & purges during cron.
* **``ajaxui``** AJAX-based progress bar working the queue after a piece of
content has been updated.
* **``lateruntime``** purges items from the queue on every request (**SLOW**).

Third-party integration
------------------------------------------------------------------------------
The purge project aims to get as many Drupal modules integrating with reverse
proxies and CDNs on board to integrate with it, this doesn't only offer the
shared benefit of saving work - **it will actually make the end user experience
better**.

As known to date, these modules are being integrated:

* **Cloudflare**: https://github.com/aweingarten/cloudflare
* **Akamai**: https://github.com/cam8001/akamai

Interested? Reach out any time of day and we'll get you going!

API examples
------------------------------------------------------------------------------

#### Invalidation without queue
You can simply invalidate objects by creating them and feeding them to the
purgers service directly. The purgers service then processes your request and
sets processing states accordingly.

```
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
$purgeInvalidationFactory = \Drupal::service('purge.invalidation.factory');
$purgePurgers = \Drupal::service('purge.purgers');

$invalidations = [
  $purgeInvalidationFactory->get('tag', 'node:1'),
  $purgeInvalidationFactory->get('tag', 'node:2'),
  $purgeInvalidationFactory->get('path', 'contact'),
  $purgeInvalidationFactory->get('wildcardpath', 'news/*'),
];

try {
  $purgePurgers->invalidate($invalidations);
}
catch (DiagnosticsException $e) {
  // Diagnostic exceptions happen when the system cannot purge.
}
catch (CapacityException $e) {
  // Capacity exceptions happen when too much was purged during this request.
}
```
When this code finished successfully, the ``$invalidations`` array holds the
objects it had before, but now each object has changed its state. You can now
verify this by iterating over the objects.

```
foreach ($invalidations as $invalidation) {
  var_dump($invalidation->getStateString());
}
```

Which could then look like this:

```
string(6) "FAILED"
string(6) "FAILED"
string(9) "SUCCEEDED"
string(10) "PROCESSING"
```

The results reveal why you should ** normally not invalidate without queue**,
because items can fail or need to run again later to finish entirely.

#### Queueing
Contrary to direct purging without a queue, going through the queue is in fact
much easier. Queuers are the entities creating objects and then add them to
the queue by calling ``add()`` on the queue service.

```
$purgeInvalidationFactory = \Drupal::service('purge.invalidation.factory');
$purgeQueue = \Drupal::service('purge.queue');

$invalidations = [
  $purgeInvalidationFactory->get('tag', 'node:1'),
  $purgeInvalidationFactory->get('tag', 'node:2'),
  $purgeInvalidationFactory->get('path', 'contact'),
  $purgeInvalidationFactory->get('wildcardpath', 'news/*'),
];

$purgeQueue->add(invalidations);
```

What happens now depends on the **processors you configured**, as some might
purge very quickly after adding items to the queue whereas others might need
a time-based delay before this occurs. Items enter the queue in state ``FRESH``
and normally leave the processor in the states ``SUCCEEDED``, ``FAILED``,
``PROCESSING`` or when no single plugins supported it: ``NOT_SUPPORTED``. Items
that don't succeed, cycle back to the queue until it gets manually cleared.

#### Queue processing
Processing items from the queue is handled by processors, which users can add
and configure according to their configuration.

```
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
$purgePurgers = \Drupal::service('purge.purgers');
$purgeQueue = \Drupal::service('purge.queue');

$claim_limit = $this->purgePurgers->capacityTracker()->getLimit();
$lease_time = $this->purgePurgers->capacityTracker()->getTimeHint();
$claims = $this->purgeQueue->claim($claim_limit, $lease_time);
try {
  $purgePurgers->invalidate($invalidations);
}
catch (DiagnosticsException $e) {
  // Diagnostic exceptions happen when the system cannot purge.
}
catch (CapacityException $e) {
  // Capacity exceptions happen when too much was purged during this request.
}

$purgeQueue->handleResults($claims);
```

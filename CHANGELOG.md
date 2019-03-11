[//]: # ( clear&&curl -s -F input_files[]=@CHANGELOG.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )

# Changelog
_Note: newest records always go on top_

* **FIXED #2795131:** `purge_tokens` should not set Max-Age to 0.
* **IMPROVED:** propelled codestyle into 2019 after thousands of fixes (phpcs --standard=Drupal)
* **FIXED #2795131:** codestyle in the tests got overhauled.
* **FIXED #2795131:** Drush commands ported to Drush 9, tested extensively and UX improved.
* **FIXED:** `Invalidation\WildcardPathInvalidation` declaration issue
* **IMPROVED:** updated `PROJECTPAGE.md`, `README.md` and **new** `CHANGELOG.md`.
* **IMPROVED:** Drupal's status report now only shows warning and error diagnostics.
* **IMPROVED:** `Counter\Counter::set()` only writes if the value changes.
* **IMPROVED:** `Queue\DatabaseQueue::numberOfItems()` now builds the `COUNT()` OOP-style.
* **IMPROVED:** `Queue\StatsTracker::updateTotals()` no longer maintains the ``::numberOfItems()`` statistic.
* **IMPROVED:** `Queue\NumberOfItemsStatistic` is now synced from `QueueService::commit` and nowhere else.
* **NEW:** `DiagnosticsServiceInterface::filterInfo`, `::filterOk`, `::filterWarnings`, `::filterWarningsAndErrors`, `::filterErrors`
* **NEW:** `DiagnosticsServiceInterface::toMessageList()`
* **NEW:** `DiagnosticsServiceInterface::toRequirementsArray()` parameter `$prefix_title`.
* **APIBREAK:** `DiagnosticsServiceInterface::getRequirementsArray()` --> `::toRequirementsArray()`
* **APIBREAK:** `DiagnosticsServiceInterface::getHookRequirementsArray()` removed
* **APIBREAK:** `DiagnosticCheckInterface::getHookRequirementsArray()`` removed
* **APIBREAK:** `Queue\numberOfItemsStatistic`     --> `NumberOfItemsStatistic`
* **APIBREAK:** `Queue\totalProcessingStatistic`   --> `TotalProcessingStatistic`
* **APIBREAK:** `Queue\totalSucceededStatistic`    --> `TotalSucceededStatistic`
* **APIBREAK:** `Queue\totalFailedStatistic`       --> `TotalFailedStatistic`
* **APIBREAK:** `Queue\totalNotSupportedStatistic` --> `TotalNotSupportedStatistic`

### 8.x-3.0-beta8
* **IMPROVED:** `purge_ui`'s diagnostic report, its colored and visually pretty now.
* **APIBREAK:** Rewrote `Queue\StatsTrackerInterface` from the ground up.
* **APIBREAK:** Simplified `Counter\PersistentCounterInterface` and `CounterInterface` into one merged `CounterInterface`, and `::setWriteCallback` lost its `$id` parameter.
* **NEW:** `TypeUnsupportedException`, thrown from `InvalidationsService::get()`, this prevents people from adding things to their queue that aren't supported.
* **NEW:** `QueueSizeDiagnosticCheck` which warns after 30000 queue items and does a safety shutdown over 10000.
* **NEW / IMPROVED:** `drush` commands:
  * `cache-rebuild-external (cre)` shorthand for "`drush p-invalidate everything`".
  * `p-processor-add (pradd)`
  * `p-processor-ls (prols -> prls)`
  * `p-processor-lsa (prlsa)`
  * `p-processor-rm (prrm)`
  * `p-queue-stats (pqs)`: now has much richer output and a`--reset-totals` switch.
  * `p-queue-empty (pqe)`: no longer resets statistics (hint: it shouldn't).
  * `p-queuer-add (puadd)`
  * `p-queuer-ls (puls)`
  * `p-queuer-lsa (pulsa)`
  * `p-queuer-rm (purm)`
  * `p-purger-mvu (ppmvu)`: move a purger UP in the execution order.
  * `p-purger-mvd (ppmvd)`: move a purger DOWN in the execution order.
* **FIXED:** `Undefined variable count QueueService:122`
* **FIXED:** `DatabaseQueue::createItemMultiple()` call to `db_insert()` -> `$this->connection->insert()`
* **FIXED:** Endless queue-loop of invalidations after removing a purger.
* **FIXED:** drush commands now no longer require a `drush cache-rebuild` to become visible.

### 8.x-3.0-beta7
* **IMPROVED:** `drush p-queue-add` now allows adding multiple items to the queue using commas.
* **IMPROVED:** `drush p-queue-work` now has a `--finish` argument.
* **IMPROVED:** `drush p-invalidate` and `drush p-queue-add` now ask for confirmation when invalidating `everything`, to prevent users from accidentally harming their website at a moment where this isn’t a good idea.
* **NEW:** `drush p-queue-volume` to view the current queue volume.
* **NEW:** `drush p-debug-en` and `drush p-debug-dis` for quickly enabling and disabing debug logging again. Works great together with `drush p-queue-work -v`!
* **NEW:** `\Drupal\purge\Logger\LoggerChannelPart::isDebuggingEnabled()` to make it easier for downstreams to prevent heavy overhead code when this ain’t needed.
* **FIXED:** bug in `RuntimeMeasurement::stop()` causing fatal errors.
* **IMPROVED:** micro-optimized `\Drupal\purge\Logger\LoggerChannelPart`.
* **IMPROVED:** renamed several `drush` commands to improve consistency:
  * `p-processors` `PPRO` --> `p-procsr-ls` `PROLS`
  * `p-purgers` `PPPU` --> `p-purger-ls` `PPLS`
  * `p-purgers-available` `PPPUA` --> `p-purger-lsa` `PPLSA`
  * `p-purger-add` `PPA` --> `p-p-purger-add` `PPADD`
  * `p-purger-rm` `PPR` --> `p-purger-rm` `PPRM`
  * `p-queuers` `PQRS` --> `p-queuer-ls` `PQULS`

### 8.x-3.0-beta6
* **NEW:** Drush commands `p-purgers`, `p-purgers-available`, `p-purger-add`, `p-purger-rm`.
* **IMPROVED:** 5 min TTLs no longer shut down purging, but its still a very bad idea.

### 8.x-3.0-beta5
* **FIXED #2692523:**: the `Purge-Cache-Tags` header has been dropped, and moves to the [purge\_purger\_http](https://drupal.org/project/purge_purger_http) project (as a submodule). The reasoning here is clear and in line with the strategy of the purge module for the future: HTTP headers are implementation details and the responsibility of purger modules, not a core task (and technology-assumption) for the core project. Users who need customized configurations, already likely use the generic HTTP purger while others will use submodules that already introduce their own headers (and remove them too).
* **NEW:** diagnostic check: `\Drupal\purge\Plugin\Purge\DiagnosticCheck\MaxAgeCheck`.
* **NEW:** diagnostic check: `\Drupal\purge\Plugin\Purge\DiagnosticCheck\PageCacheCheck`.
* **FIXED #2791387:** A new configurable block "`Purge this page`" has been added to allow administrators to quickly purge by URL, path or clear "everything". Alternatively, the block can also be configured to queue the items instead of direct execution.
* **APIBREAK:** `TagsHeaderInterface` now also extends `ContainerFactoryPluginInterface` in order for `create::` injection support.
* **IMPROVED:** test coverage (`4430 passes, 0f, 0e`), [pareview.sh](http://www.pareview.sh) compliance.
* **IMPROVED:** The "`page cache maximum age`" dropdown on the performance tab, contains a lot more serious options now.
* **IMPROVED:** `::testIteration()` tests on `KernelServiceTestBase` derivatives, by introducing `assertIterator()`.
* **FIXED #2706567:** by lahoosascoots, nielsvm, arknoll: `QueuerService` and `ProcessorsService` assumes there are plugins in CMI.
* `Tests\Queue\PluginTestBase` now tests the paging logic of queue plugins.
* **FIXED #2795131:** has rewritten the entire database queue backend, which now automatically deploys a new database table `purge_queue`. Please note that **upgrading users** will see their queues wiped out and are required to run **`drush p-queue-empty`** to also reset queue statistics.
* **FIXED #2744215:** diagnostic errors will now get logged for **new installations**, old installations will need to check "error" for the "diagnostics" log channel in their logging configurations.
* **FIXED #2755019:** Remove @file tag docblock
* **FIXED #2787993:** Allowed memory size of 268435456 bytes exhausted
* **FIXED #2724217:** Incompatibility with Monolog
* **FIXED #2744135:** PurgerCapacityDataInterface::hasRuntimeMeasurement() further clarified.

### 8.x-3.0-beta4
* **APIBREAK:** `http.response.debug_cacheability_headers` has been dropped.
* **IMPROVED:** Tag-Header plugins: now allow submodules to set export headers with cache tags, the default header now is `Purge-Cache-Tags`.
* **IMPROVED:** Invalidation properties: invalidation objects now have `::setProperty()`, `::getProperty()`, this allows purgers to pass through information between processing rounds (Akamai requirement).
* **IMPROVED:** Log management now allows purge and its submodules to log extensively and users have fine-grained control over it.

### 8.x-3.0-beta3
* **FIXED:** `\Drupal\purge\Plugin\Purge\Queue\StatsTracker::destruct()` which was potentially writing the buffer multiple times.
* **FIXED:** `\Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationBase::getState()` - Catch combination states where one or more purgers added NOT\_SUPPROTED but other purgers added states as well.
* **APIBREAK:** All purger implementations now need to implement `::hasRuntimeMeasurement()` and can now drop their already existing `::getTimeHint()` implementations unless they desire keeping that level of control. The end-user benefit is that capacity calculation becomes much more dynamic and now depends upon real-world execution times, which means that capacity can increase or decrease depending on how the purgers perform. The downstream benefit is that less code is required in purgers, reducing the risk of bugs and hassle.

### 8.x-3.0-beta2
* **APIBREAK:** The service `purge.queue.txbuffer` became public.

### 8.x-3.0-beta1
* Purge version 8.x-3.0-beta1 - codename Hello World!
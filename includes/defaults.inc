<?php

/**
 *  @file
 *   Provides default configuration and handlers or the Purge module.
 */

/**
 * Class definition for the Defaults Bundle.
 */
class PurgeBundleDefault extends PurgeBundleFull {

  /**
   * Construct the default bundle.
   */
  public function __construct() {
    // Lets get the types defined.
    // First get dependecies up.
    $this->type['depend'] = new PurgeItemType();
    $this->type['depend']->name = t('Dependency');
    $this->type['depend']->description = t('Declares conditions for the items availability.');

    // Target Type definition.
    $this->type['target'] = new PurgeItemType();
    $this->type['target']->name = t('Target');
    $this->type['target']->description = t('Targets are the URLs where the Purge requests will be sent to.');

    // Domain type definition.
    $this->type['domain'] = new PurgeItemType();
    $this->type['domain']->name = t('Domain');
    $this->type['domain']->description = t('Domains to generate Purge requests with.');

    // Header type definition.
    $this->type['header'] = new PurgeItemType();
    $this->type['header']->name = t('Header');
    $this->type['header']->description = t('HTTP Headers added to the requests.');

    $this->type['queue'] = new PurgeItemType();
    $this->type['queue']->name = t('Queue');
    $this->type['queue']->description = t('Queue Purge requests to handle large numbers of purges.');
    $this->type['queue']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);
    $this->type['queue']->options = array(
      'batch' => array(
        'name' => t('Batch size'),
        'context' => 'item',
        'description' => t('Configure how many purges will be processed in a single queue task.'),
        'values' => array(
          '1' => t('Single'),
          '10' => '10',
          '25' => '25 (Default)',
          '50' => '50',
        ),
        'default_value' => '25',
      ),
      'parallel' => array(
        'name' => t('Parellel Processing'),
        'context' => 'item',
        'description' => t('Process the batches in parallel.'),
        'default_value' => 0,
      ),
    );

    // Handler type definition.
    $this->type['handler'] = new PurgeItemType();
    $this->type['handler']->name = t('Handler');
    $this->type['handler']->description = t('The different http request handlers supported.');
    $this->type['handler']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);
    $this->type['handler']->options = array(
      'method' => array(
        'name' => t('Purge Method'),
        'context' => 'item',
        'description' => t('The method used to perform the purge.'),
        'values' => array(
          'PURGE' => 'HTTP request method PURGE',
          'BAN' => 'HTTP request method BAN.',
          'GET' => 'HTTP request method GET.',
        ),
        'default_value' => 'http_purge',
      ),
      'multi' => array(
        'name' => t('Parallel'),
        'context' => 'item',
        'description' => t('Issue the purge requests in parallel.'),
        'default_value' => 1,
      ),
      'non_blocking' => array(
        'name' => t('Non Blocking'),
        'context' => 'item',
        'description' => t('Issue non-blocking requests. Enhances performance but disables error reporting.'),
        'default_value' => 1,
      ),
    );

    // Purger type definition
    $this->type['purger'] = new PurgeItemType();
    $this->type['purger']->name = t('Purger');
    $this->type['purger']->description = t('Purgers are the main configurable items in Purge and do the actual purging.');
    $this->type['purger']->access = array(PURGE_ACCESS_FULL);
    $this->type['purger']->options = array(
      'watchdog' => array(
        'name' => t('Enable Logging'),
        'context' => 'type',
        'description' => t('Log activities to the Drupal watchdog log.'),
        'default_value' => 1,
      ),
    );

    // Target definitions.
    // The Drupal base url as target
    $this->target['drupal_url'] = new PurgeItemTargetDrupal();
    $this->target['drupal_url']->name = t('Drupal URL');
    $this->target['drupal_url']->description = t('Use the Drupal Base URL as a target.');
    $this->target['drupal_url']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE,PURGE_ACCESS_VIEW);
    $this->target['drupal_url']->urls = array();


    // Domain definition.
    // The domain from the Drupal Basd URL.
    $this->domain['drupal_domain'] = new PurgeItemDomainDrupal();
    $this->domain['drupal_domain']->name = t('Drupal Domain');
    $this->domain['drupal_domain']->description = t('Use the domain in the Drupal Base URL.');
    $this->domain['drupal_domain']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE,PURGE_ACCESS_VIEW);
    $this->domain['drupal_domain']->domains = array();

    $this->domain['purgeable_url_domain'] = new PurgeItemDomainPurgeableUrl();
    $this->domain['purgeable_url_domain']->name = t('Purgeable URL Domain');
    $this->domain['purgeable_url_domain']->description = t('Use the domain name provided by the Purgeable URL.');
    $this->domain['purgeable_url_domain']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);


    // Header definitions.
    // The Gzip header.
    $this->header['gzip'] = new PurgeItemHeaderStatic();
    $this->header['gzip']->name = t('Gzip');
    $this->header['gzip']->description = t(
      'The Gzip header enables compression and is enabled by default on most http clients.'
    );
    $this->header['gzip']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_CLONE,PURGE_ACCESS_VIEW);
    $this->header['gzip']->headers = array('Accept-Encoding' => 'gzip');


    // Queue definitions
    // The Fake Queue.
    $this->queue['fake'] = new PurgeItemQueueFake();
    $this->queue['fake']->name = t('No Queue');
    $this->queue['fake']->description = t('Do not queue the purges but process them right away.');
    $this->queue['fake']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);

    // The QueueRunner Queue
    $this->queue['runner'] = new PurgeItemQueueRunner();
    $this->queue['runner']->name = 'Queue Runner';
    $this->queue['runner']->description = t('Use the Queue Runner Module to offload purges to a continues cli process.');
    $this->queue['runner']->available = 0;
    $this->queue['runner']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);
    $this->queue['runner']->item = array(
      'depend' => array(
        'module' => array(
          'queue_runner' => 0,
        ),
        'options' => array(
          'batch' => '25',
          'parallel' => 1,
        ),
      ),
    );


    // Handler definitions.
    // Drupal http request.
    $this->handler['drupal_http_request'] = new PurgeItemHandlerDrupal();
    $this->handler['drupal_http_request']->name = t('Drupal HTTP Request');
    $this->handler['drupal_http_request']->description = t('The native http request function of Drupal.');
    $this->handler['drupal_http_request']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);
    $this->handler['drupal_http_request']->options = array(
      'method' => 'http_purge',
    );

    // Curl Single Handler
    $this->handler['curl'] = new PurgeItemHandlerCurl();
    $this->handler['curl']->name = t('Curl');
    $this->handler['curl']->description = t('Use the Curl library to send out requests http requests.');
    $this->handler['curl']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);
    $this->handler['curl']->options = array(
      'method' => 'PURGE',
      'multi' => 1,
    );

    // HTTPrl Handler.
    $this->handler['httprl'] = new PurgeItemHandlerHttprl();
    $this->handler['httprl']->name = t('HTTPrl');
    $this->handler['httprl']->description = t(
      'The HTTP Request Library module provides an advanced library that allows non-blocking requests.'
    );
    $this->handler['httprl']->enabled = 1;
    $this->handler['httprl']->access = array(PURGE_ACCESS_LIST,PURGE_ACCESS_ENABLE);
    $this->handler['httprl']->options = array(
      'method' => 'PURGE',
      'multi' => 1,
      'non_blocking' => 1,
    );
  }
}


/**
 * Class definition for the Example Bundle.
 *   Provides a set of examples for various uses.
 */
class PurgeBundleExample extends PurgeBundleBasic {

  /**
   * Constructor.
   */
  public function __construct() {
    // Targets
    // The localhost target
    $this->target['localhost'] = new PurgeItemTargetStatic();
    $this->target['localhost']->name = t('Localhost');
    $this->target['localhost']->description = t('The default localhost ip address.');
    $this->target['localhost']->enabled = 1;
    $this->target['localhost']->access = array(PURGE_ACCESS_FULL);
    $this->target['localhost']->provider = 'purge_example';
    $this->target['localhost']->urls = array('http://127.0.0.1');

    // Domains
    // Get the current domain from the domain object.
    $item_domain_drupal = new PurgeItemDomainDrupal();
    $item_domain_drupal->get_data_cached();

    $this->domain['current_domain'] = new PurgeItemDomainStatic();
    $this->domain['current_domain']->name = t('Current Domain');
    $this->domain['current_domain']->description = t('Use the current domain as an example domain.');
    $this->domain['current_domain']->domains = $item_domain_drupal->domains[0];

    // Headers
    // Purgers

    // Now create the purger objects
    $this->purger['minimal_purger'] = new PurgeItemPurgerBasicUrl();
    $this->purger['minimal_purger']->name = t('Minimal Purger');
    $this->purger['minimal_purger']->description = t('Uses Drupal native methods to issue the requests.');
    $this->purger['minimal_purger']->options = array(
      'handler' => array(
        'method' => 'http_purge',
      ),
      'purger' => array(
        'watchdog' => 1,
      ),
    );
    $this->purger['minimal_purger']->pointers = array(
      'target' => array('drupal_url'),
      'domain' => array('drupal_domain'),
      'header' => array('gzip'),
      'queue' => array('fake'),
      'handler' => array('drupal_http_request'),
    );

    $this->purger['default_purger'] = new PurgeItemPurgerBasicUrl();
    $this->purger['default_purger']->name = t('Default Purger');
    $this->purger['default_purger']->description = t('Uses common settings used for a Varnish setup.');
    $this->purger['default_purger']->options = array(
      'handler' => array(
        'method' => 'http_ban',
        'multi' => 0,
      ),
      'purger' => array(
        'watchdog' => 1
      ),
    );
    $this->purger['default_purger']->pointers = array(
      'target' => array('drupal_url', 'localhost'),
      'domain' => array('drupal_domain'),
      'header' => array('gzip'),
      'queue' => array('fake'),
      'handler' => array('curl'),
    );
  }
}

<?php

/**
 *  @file
 *   Provides default configuration and purgers or the Purge module.
 */

/**
 * Class definition for the Defaults Bundle.
 */
class PurgeBundleDefault extends PurgeBundleBasic {

  /**
   * Construct the default bundle.
   */
  public function __construct() {
    // First lets get the types defined.
    // Target Type definition.
    $this->type['target'] = new PurgeItemType();
    $this->type['target']->name = t('Target');
    $this->type['target']->description = t('Targets are the URLs where the Purge requests will be sent to.');
    $this->type['target']->enabled = 1;
    $this->type['target']->access = PURGE_ACCESS_SYSTEM;
    $this->type['target']->parent_class = 'PurgeItemTarget';

    // Domain type definition.
    $this->type['domain'] = new PurgeItemType();
    $this->type['domain']->name = t('Domain');
    $this->type['domain']->description = t('Domains to generate Purge requests with.');
    $this->type['domain']->enabled = 1;
    $this->type['domain']->access = PURGE_ACCESS_SYSTEM;
    $this->type['domain']->parent_class = 'PurgeItemDomain';
    $this->type['domain']->options = array(
      'purgeable_url_domain' => array(
        'name' => t('Purgeable URL Domain'),
        'context' => 'type',
        'description' => t('This wil use the domain supplies in by the purgeable url.'),
        'default_value' => 1,
      ),
    );

    // Header type definition.
    $this->type['header'] = new PurgeItemType();
    $this->type['header']->name = t('Header');
    $this->type['header']->description = t('HTTP Headers added to the requests.');
    $this->type['header']->enabled = 1;
    $this->type['header']->access = PURGE_ACCESS_SYSTEM;
    $this->type['header']->parent_class = 'PurgeItemHeader';

    // Purger type definition.
    $this->type['purger'] = new PurgeItemType();
    $this->type['purger']->name = t('Purger');
    $this->type['purger']->description = t('The different http request purgers supported.');
    $this->type['purger']->enabled = 0;
    $this->type['purger']->access = PURGE_ACCESS_SYSTEM;
    $this->type['purger']->parent_class = 'PurgeItemPurger';
    $this->type['purger']->options = array(
      'method' => array(
        'name' => t('Purge Method'),
        'context' => 'item',
        'description' => t('The method used to perform the purge.'),
        'values' => array(
          'http_purge' => 'HTTP request method PURGE',
          'http_ban' => 'HTTP request method BAN.',
          'http_get' => 'HTTP request method GET.',
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

    // Set type definition
    $this->type['set'] = new PurgeItemType();
    $this->type['set']->name = t('Set');
    $this->type['set']->description = t('Sets are combination of all item types.');
    $this->type['set']->enabled = 1;
    $this->type['set']->access = PURGE_ACCESS_SYSTEM;
    $this->type['set']->parent_class = 'PurgeItemSet';
    $this->type['set']->options = array(
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
    $this->target['drupal_url']->enabled = 1;
    $this->target['drupal_url']->access = PURGE_ACCESS_ENABLE;
    $this->target['drupal_url']->urls = array();

    // The localhost target
    $this->target['localhost'] = new PurgeItemTargetStatic();
    $this->target['localhost']->name = t('Localhost');
    $this->target['localhost']->description = t('The default localhost ip address.');
    $this->target['localhost']->enabled = 1;
    $this->target['localhost']->access = PURGE_ACCESS_FULL;
    $this->target['localhost']->urls = array('http://127.0.0.1');


    // Domain definition.
    // The domain from the Drupal Basd URL.
    $this->domain['drupal_domain'] = new PurgeItemDomainDrupal();
    $this->domain['drupal_domain']->name = t('Drupal Domain');
    $this->domain['drupal_domain']->description = t('Use the domain in the Drupal Base URL.');
    $this->domain['drupal_domain']->enabled = 1;
    $this->domain['drupal_domain']->access = PURGE_ACCESS_ENABLE;
    $this->domain['drupal_domain']->domains = array();


    // Header definitions.
    // The Gzip header.
    $this->header['gzip'] = new PurgeItemHeaderStatic();
    $this->header['gzip']->name = t('Gzip');
    $this->header['gzip']->description = t(
      'The Gzip header enables compression and is enabled by default on most http clients.'
    );
    $this->header['gzip']->enabled = 1;
    $this->header['gzip']->access = PURGE_ACCESS_ENABLE;
    $this->header['gzip']->headers = array('Accept-Encoding' => 'gzip');


    // Purger definitions.
    // Drupal http request.
    $this->purger['drupal_http_request'] = new PurgeItemPurgerDrupal();
    $this->purger['drupal_http_request']->name = t('Drupal HTTP Request');
    $this->purger['drupal_http_request']->description = t('The native http request function of Drupal.');
    $this->purger['drupal_http_request']->enabled = 1;
    $this->purger['drupal_http_request']->access = PURGE_ACCESS_ENABLE;
    $this->purger['drupal_http_request']->options = array(
      'method' => 'http_purge',
    );

    // Curl Single Purger
    $this->purger['curl'] = new PurgeItemPurgerCurl();
    $this->purger['curl']->name = t('Curl');
    $this->purger['curl']->description = t('Use the Curl library to send out requests http requests.');
    $this->purger['curl']->enabled = 1;
    $this->purger['curl']->access = PURGE_ACCESS_ENABLE;
    $this->purger['curl']->options = array(
      'method' => 'http_purge',
      'multi' => 1,
    );

    // HTTPrl Purger.
    $this->purger['httprl'] = new PurgeItemPurgerHttprl();
    $this->purger['httprl']->name = t('HTTPrl');
    $this->purger['httprl']->description = t(
      'The HTTP Request Library module provides an advanced library that allows non-blocking requests.'
    );
    $this->purger['httprl']->enabled = 1;
    $this->purger['httprl']->access = PURGE_ACCESS_ENABLE;
    $this->purger['httprl']->options = array(
      'method' => 'http_purge',
      'multi' => 1,
      'non_blocking' => 1,
    );


    // Now create the set objects
    $this->set['minimal_set'] = new PurgeItemSet();
    $this->set['minimal_set']->name = t('Minimal Set');
    $this->set['minimal_set']->description = t('Uses Drupal native methods to issue the requests.');
    $this->set['minimal_set']->enabled = 1;
    $this->set['minimal_set']->access = PURGE_ACCESS_CLONE;
    $this->set['minimal_set']->options = array(
      'domain' => array(
        'purgeable_url_domain' => 1,
      ),
      'purger' => array(
        'method' => 'http_purge',
      ),
      'set' => array(
        'watchdog' => 1,
      ),
    );
    $this->set['minimal_set']->pointers = array(
      'target' => array('drupal_url'),
      'domain' => array('drupal_domain'),
      'header' => array('gzip'),
      'purger' => array('drupal_http_request'),
    );
    $this->set['minimal_set']->target = array('drupal_url' => $this->target['drupal_url']);
    $this->set['minimal_set']->domain = array('drupal_domain' => $this->domain['drupal_domain']);
    $this->set['minimal_set']->header = array('gzip' => $this->header['gzip']);
    $this->set['minimal_set']->purger = array('drupal_http_request' => $this->purger['drupal_http_request']);

    // Now create the set objects
    $this->set['default_set'] = new PurgeItemSet();
    $this->set['default_set']->name = t('Default Set');
    $this->set['default_set']->description = t('Uses Drupal native methods to issue the requests.');
    $this->set['default_set']->enabled = 1;
    $this->set['default_set']->access = PURGE_ACCESS_CLONE;
    $this->set['default_set']->options = array(
      'domain' => array(
        'purgeable_url_domain' => 0,
      ),
      'purger' => array(
        'method' => 'http_purge',
        'multi' => 1,
      ),
      'set' => array(
        'watchdog' => 1
      ),
    );
    $this->set['default_set']->pointers = array(
      'target' => array('drupal_url', 'localhost'),
      'domain' => array('drupal_domain'),
      'header' => array('gzip'),
      'purger' => array('curl'),
    );
    $this->set['default_set']->target = array(
      'drupal_url' => $this->target['drupal_url'],
      'localhost' => $this->target['localhost'],
    );
    $this->set['default_set']->domain = array('drupal_domain' => $this->domain['drupal_domain']);
    $this->set['default_set']->header = array('gzip' => $this->header['gzip']);
    $this->set['default_set']->purger = array('curl' => $this->purger['curl']);

  }

  /**
   * Save the Defaults.
   *   Will overwrite all existing configurations!
   *   Use reconfigure to restore the defaults but keep the other items.
   */
  public function save() {
    parent::save();
  }
}

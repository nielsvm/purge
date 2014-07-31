<?php

/**
 * @file
 *
 * Contains the main purging functionality and error handling.
 */

/**
 * Constant definitions.
 */


// Config permissions.
define('PURGE_ACCESS_SYSTEM', 0);
define('PURGE_ACCESS_LIST', 1);
define('PURGE_ACCESS_ENABLE', 2);
define('PURGE_ACCESS_RENAME', 3);
define('PURGE_ACCESS_CLONE', 4);
define('PURGE_ACCESS_VIEW', 5);
define('PURGE_ACCESS_FULL', 6);



/**
 * Interface definition for Validateable items.
 */
interface PurgeValidateable {
  function validate();
}

/**
 * Interface definition for Cacheable.
 *   The data will be fetched once and cached for reuse.
 */
interface PurgeCacheable {
  function get_data_cached();
}

/**
 * Interface definition for item that can process Purgeable urls.
 */
interface PurgeProcessableUrl {
  function process_url($purgeable_url);
}

/**
 * Interface definition for item that can process Purgeable domains.
 */
interface PurgeProcessableDomain {
  function process_domain($purgeable_domain);
}

/**
 * Interface definition for item that can process Purgeable Tags.
 */
interface PurgeProcessableTag {
  function process_tag($purgeable_tag);
}

/**
 * Class for purge configuration management.
 *   Defines a basic configuration object.
 */
abstract class PurgeItemBasic {
  public $name = '';
  public $description = '';
  public $enabled = 1;
  public $access = array(PURGE_ACCESS_FULL);
  public $provider = 'user';
  public $options;
}

/**
 * Class definition for the Types object class.
 */
class PurgeItemType extends PurgeItemBasic {

}


/**
 * Abstract Class definition for Purge Targets.
 */
abstract class PurgeItemTarget extends PurgeItemBasic implements PurgeValidateable {
  public $urls = array();

  /**
   * Validate
   *
   * @return $errors
   */
  public function validate() {
    // Call the parent for name validation
    $errors = array();
    $errors = parent::validate();
    // Validate the URLs
    foreach ($urls as $url) {
      if (!valid_url($url)) {
        $errors[] = array(
          'urls' => t('Invalid Target URL')
        );
      }
    }
    return $errors;
  }
}

/**
 * Class definition for static targets.
 */
class PurgeItemTargetStatic extends PurgeItemTarget implements PurgeValidateable {

}

/**
 * Class definition for the Drupal Target.
 */
class PurgeItemTargetDrupal extends PurgeItemTarget implements PurgeCacheable {

  /**
   * Set api data to url.
   */
  public function get_data_cached() {
    $base_url = $GLOBALS['base_url'];
    $this->urls = array($base_url);
  }
}

/**
 * Class definition for domains.
 */
abstract class PurgeItemDomain extends PurgeItemBasic {
  public $domains = array();

  /**
   * Validate the domains.
   */
  public function validate() {
    foreach ($this->domains as $domain) {
      $pieces = explode(".",$domain);
      foreach($pieces as $piece) {
        if (!preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $piece) || preg_match('/-$/', $piece) ) {
          $errors[] = array(
            'provider' => 'purge_domain',
            'message' => t('Invalid domain'),
          );
        }
        return errors;
      }
    }
  }
}

/**
 * Class definition for static domain items.
 */
class PurgeItemDomainStatic extends PurgeItemDomain {

}

/**
 * Class definition for the Drupal domain item.
 */
class PurgeItemDomainDrupal extends PurgeItemDomain implements PurgeCacheable {

  /**
   * Set the domain to the base urls domain.
   */
  public function get_data_cached() {
    $base_url = $GLOBALS['base_url'];
    // dprint_r($base_url);
    $this->domains = array(parse_url($base_url, PHP_URL_HOST));
  }
}

/**
 * Abstract class definition for Purge Headers.
 */
abstract class PurgeItemHeader extends PurgeItemBasic {
  public $headers = array();
}

/**
 * Class definition for static header items.
 */
class PurgeItemHeaderStatic extends PurgeItemHeader {

}


/**
 * Class definition for Queues.
 */
abstract class PurgeItemQueue extends PurgeItemBasic {

}

/**
 * Class definition for the Fake Queue.
 *   This will issue all requests in the current process.
 */
class PurgeItemQueueFake extends PurgeItemQueue {
  public $queue = array();

  /**
   * Dummy function.
   */
  public function process() {
    // Since the we'll need a full bundle we'll rely on a fallback in the
    // runtime bundle.
  }
}

/**
 * Class definition for Handlers.
 */
abstract class PurgeItemHandler extends PurgeItemBasic {

}

/**
 * Class definition for the Drupal HTTP request handler.
 */
class PurgeItemHandlerDrupal extends PurgeItemHandler {

  /**
   * Function to execute items from a queue.
   *
   * @param array $purges
   *
   * @param array $handler_options
   *
   * @return array $purges
   */
  public function execute($purges, $handler_options) {
    foreach ($purges as $purge_id => $purge) {
      $options = array(
        'headers' => $purge['headers'],
        'method' => $handler_options['method'],
      );
      $results = drupal_http_request($purge['purge_url'],$options);
      $purges[$purge_id]['http_code'] = 200; // $results['code'];
    }
    return $purges;
  }
}

/**
 * Class definition for the Curl HTTP request library.
 */
class PurgeItemHandlerCurl extends PurgeItemHandler {

  /**
   * Funtion will determine what options are set and call the right execute
   * function.
   */
  public function execute($purges, $handler_options) {
    if ($handler_options['multi']) {
      //if ($handler_options['legacy']) {
      //  $purges = $this->execute_legacy($purges, $handler_options);
      //}
      //else {
        $purges = $this->execute_multi($purges, $handler_options);
      // }
    }
    else {
      $purges = $this->execute_single($purges, $handler_options);
    }
    return $purges;
  }

  /**
   * Function to procerss a single purge with curl.
   */
  private function execute_single($purges, $handler_options) {
    foreach ($purges as $purge_id => $purge) {
      $curl_request = $this->get_curl_request($purge, $handler_options);
      // Execute the request
      curl_exec($curl_request);
      // Get http status code.
      $info = curl_getinfo($curl_request);
      $purges[$purge_id]['http_code'] = $info['http_code'];
    }
    return $purges;
  }

  /**
   * Function to process multiple purges with curl.
   */
  private function execute_multi($purges, $handler_options) {
    $curl_purges = curl_multi_init();
    foreach ($purges as $purge_id => $purge) {
      $curl_requests[$purge_id] = $this->get_curl_request($purge, $handler_options);
      curl_multi_add_handle($curl_purges, $curl_requests[$purge_id]);
    }
    // Execute the purge requests
    ob_start();
    $active = 0;
    $multi_result = $this->multi_exec($curl_purges, $active);
    while ($active && $multi_result == CURLM_OK) {
      if (curl_multi_select($curl_purges) == -1) {
        usleep(100000);
      }
      $multi_result = $this->multi_exec($curl_purges, $active);
    }
    ob_end_clean();

    // Result collection. Collects the http code returned for each url purged
    foreach ($purges as $purge_id => $purge) {
      $info = curl_getinfo($curl_requests[$purge_id]);
      $purges[$purge_id]['http_code'] = $info['http_code'];
      curl_multi_remove_handle($curl_purges, $curl_requests[$purge_id]);
    }
    curl_multi_close($curl_purges);
    return $purges;
  }

  /**
    * Wrapper for curl_multi_exec().
    */
  private function multi_exec($curl_purges, &$active) {
    do {
      $multi_result = curl_multi_exec($curl_purges, $active);
    } while ($multi_result == CURLM_CALL_MULTI_PERFORM);
    return $multi_result;
  }



  /**
   * Function to construct a curl request object.
   */
  private function get_curl_request($purge, $handler_options) {
    $headers = array();
    foreach ($purge['headers'] as $header_key => $header_value) {
      $headers[] = $header_key . ": " . $header_value;
    }
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, $handler_options['method']);
    curl_setopt($curl_request, CURLOPT_URL, $purge['purge_url']);
    curl_setopt($curl_request, CURLOPT_HEADER, 1);
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $purge['headers']);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 0);

    return $curl_request;
  }

}

/**
 * Class definition for HTTPrl handler.
 */
class PurgeItemHandlerHttprl extends PurgeItemHandler {

  public function execute($purges, $handler_options) {
    foreach ($purges as $purge_id => $purge) {
      $headers_array = headers2array($purge['headers']);
      // Gather options
      $options = array(
        'method' => $handler_options['method'],
        'blocking' => FALSE,
        'headers' => $purge['headers'],
      );
      // Queue the request
      httprl_request($purge['purge_url'], $options);
      // Since we don't care about the results, let's fake 200
      $purges[$purge_id]['http_code'] = 200;
    }
    // Issue all  requests.
    $request = httprl_send_request();
    return $purges;
  }
}

/**
 * Class definition for Purgers.
 */
abstract class PurgeItemPurger extends PurgeItemBasic {

}

/**
 * Class definition for the Basic Url Purger.
 */
class PurgeItemPurgerBasicUrl extends PurgeItemPurger implements PurgeProcessableUrl {
  public $pointers = array(
    'target' => array(),
    'domain' => array(),
    'header' => array(),
    'queue' => array(),
    'handler' => array(),
  );
  public $target = array();
  public $domain = array();
  public $header = array();
  public $queue = array();
  public $handler = array();

  /**
   * Serialize the Purger data and replace pointers with a string.
   */
  public function __sleep() {
    // Iterate through all pointers objects and replace with names
    $this->get_pointers();

    return array('name', 'description', 'enabled', 'access', 'options', 'provider', 'pointers');
  }

  /**
   * Populate the flat pointers variable with names of the object pointers.
   */
  public function get_pointers() {
    foreach ($this->pointers as $pointer_type => $pointer_names) {
        $this->pointers{$pointer_type} = array();
      foreach ($pointer_names as $pointer_name) {
        $this->pointers{$pointer_type}[] = $pointer_name;
      }
    }
  }

  /**
   * Process purgeables.
   *
   * @param array $purgeablea_url
   *
   * @return array $queue_item
   */
  public function process_url($purgeable_url) {
    $purges = array();
    // First set all processable items with the purgeable data.
    // echo $purgeable_type;
    foreach ($this->pointers as $pointer_type => $pointers) {
      foreach ($pointers as $pointer_name) {
        if ($pointer_type != 'handler') {
          if ($this->{$pointer_type}[$pointer_name] instanceof PurgeProcessableUrl) {
            $this->{$pointer_type}[$pointer_name]->process_url($purgeable_url);
          }
        }
      }
      // gather targets.
      $target_urls = array();
      foreach ($this->target as $target_name => $target) {
        foreach ($target->urls as $url) {
          $target_urls[] = $url;
        }
      }
      // Gather domains.
      $domains = array();
      foreach ($this->domain as $domain_name => $domain) {
        foreach ($domain->domains as $domain_domain) {
          $domains[] = $domain_domain;
        }
      }
      if ($this->options['domain']['purgeable_url_domain']) {
        $domains[] = parse_url($purgeable, PHP_URL_HOST);
      }
      // Gather headers.
      $headers = array();
      foreach ($this->header as $header_name => $header) {
        foreach ($header->headers as $header_key => $header_value) {
          $headers[$header_key] = $header_value;
        }
      }
      // Now lets fill the purges array
      $parsed_purgeable = parse_url($purgeable);
      foreach ($target_urls as $target_url) {
        foreach ($domains as $domain) {
          $purge_headers = $headers;
          // Add the host header.
          $purge_headers['Host'] = $domain;
          $purges[] = array(
            'purge_url' => $target_url . $parsed_purgeable['path'],
            'headers' => $purge_headers,
          );
        }
      }

    }
    // Get the hanlder and its options.
    $handler = $this->pointers['handler'][0];
    $handler_options = array();
    $handler_options = $this->options['handler'];

    $queue_item = array (
      'handler' => $handler,
      'handler_options' => $handler_options,
      'purges' => $purges,
    );
    $queue_name = $this->pointers['queue'][0];
    $this->queue[$queue_name]->queue[] = $queue_item;
    // return $queue_item;
  }
}

/**
 * Class definition for basic bundles.
 */
Class PurgeBundleBasic {
  public $type = array();
  public $purger = array();
  public $target = array();
  public $domain = array();
  public $header = array();
  public $queue = array();
  public $handler = array();

  /**
   * Constructor for the basic bundle.
   */
  public function __construct() {
    // Get the data from the given dataset.
    $select_data = $this->get_data();
    // Then get the datastructure of the types up.
    foreach ($select_data['type'] as $type_name => $type_data) {
      $this->type[$type_name] = unserialize($type_data);
      // And create an object for each item
      foreach ($select_data[$type_name] as $item_name => $item_data) {
        $this->{$type_name}[$item_name] = unserialize($item_data);
      }
    }
    // Purgers need special handling
    foreach ($this->purger as $purger_name => $purger) {
      // Each type name that is a pointer.
      foreach ($purger->pointers as $pointer_type => $item_names ) {
        $this->purger[$purger_name]->{$pointer_type} = array();
      }
    }
  }

  /**
   * Function to select data from the dataset.
   *
   * @return array $data
   */
  public function get_data() {
    // Get the data from the given dataset.
    $data = array();
    $data = variable_get('purge_config');

    return $data;
  }

  /**
   * Function to set the type objects.
   */
  public function set_types($data) {
    // Get the datastructure of the types up.
    foreach ($data['type'] as $type_name => $type_data) {
      // And create an object for each type
      $this->type[$type_name] = unserialize($type_data);
    }
  }

  /**
   * Validate the bundle.
   *
   * @return array $errors
   */
  public function validate() {
    $errors = array();
    foreach ($this->type as $type_name => $type) {
      foreach ($this->{$type_name} as $items) {
        foreach ($items as $item_name => $item) {
          if ($item instanceof PurgeValidateable) {
            $item_errors = $item->validate();
            if (count($item_errors) > 0) {
              foreach ($item_errors as $item_error) {
                $errors[] = $item_error;
              }
            }
          }
        }
      }

    }
    return $errors;
  }

  /**
   * Save the bundle to the variable.
   */
  public function save() {
    $data = array();
    // First let get the item types up
    $data['type'] = array();
    foreach ($this->type as $type_name => $type) {
      $data['type'][$type_name] = serialize($type);
      $data[$type_name] = array();

      // Now create each item object
      foreach ($this->{$type_name} as $item_name => $item) {
        $data[$type_name][$item_name] = serialize($item);
      }
    }
    // Store the data in the variable
    variable_set('purge_config', $data);
  }
}

/**
 * Class definition for the runtime bundle.
 *   This class is used for processing the purge requests during runtine.
 */
class PurgeBundleRuntime extends PurgeBundleBasic {
  public $purgeables;

  /**
   * Constructor for the Configuration bundle.
   */
  public function __construct() {
    $runtime_data = array();
    // First check if the data is in cache.

    // If not, get from parent.
    $all_data = $this->get_data();

    // Create the types and keep the data for caching.
    $this->set_types($all_data);
    $runtime_data['type'] = $all_data['type'];

    // For loop through the set_data and collect active items.
    $data['purger'] = array();
    foreach ($all_data['purger'] as $purger_name => $purger_data) {
      // check if the purger is enabled.
      if (strpos($purger_data, "s:7:\"enabled\";i:1")) {
        $data['purger'][$purger_name] = $purger_data;
        // Create the purger object.
        $this->purger[$purger_name] = unserialize($purger_data);
        // Now loop through the pointers
        foreach ($this->purger[$purger_name]->pointers as $pointer_type => $item_names) {
          foreach ($item_names as $item_name) {
            $item_data = $all_data[$pointer_type][$item_name];
            // Check in the data if the item is enabled.
            if (strpos($item_data, "s:7:\"enabled\";i:1")) {
              // Check if the item object isn't allready there.
              if (isset($this->{$pointer_type}[$item_name])) {
                // If so just link the pointer to it.
                $this->purger[$purger_name]->{$pointer_type}[$item_name] = $this->{$pointer_type}[$item_name];
              }
              else {
                // Create the object from data
                $this->{$pointer_type}[$item_name] = unserialize($item_data);
                // Check if it item has cacheable data
                if ($this->{$pointer_type}[$item_name] instanceof PurgeCacheable) {
                  $this->{$pointer_type}[$item_name]->get_data_cached();
                }
                // And link the pointer
                $this->purger[$purger_name]->{$pointer_type}[$item_name] = $this->{$pointer_type}[$item_name];
                // And keep the item data
                $data[$pointer_type][$item_name] = $item_data;

              }
            }
          }
        }
      }
    }
  }

  /**
   * Receive requests as flat array of requests.
   *
   * @param array $new_requests
   *   Flat array, just urls.
   */
  public function add_purgeable($purge) {
    // Add to the bundle array
  }


  /**
   * Process all requests.
   */
  public function process() {
    // Walk the purgeables.
    foreach ($this->purgeables as $purgeable_type_name => $purgeables) {
      foreach ($purgeables as $purgeable) {
        // Pass all purgers.
        foreach ($this->purger as $purger_name => $purger) {
          // Process the Purgeable.
          $this->purger[$purger_name]->process($purgeable_type_name, $purgeable);
        }
      }
    }
  }

  /**
   * Execute the current queues in the fake queue.
   *   Code will move to queue class once implemented.
   */
  public function execute() {
  // Pass each of the queues.
    foreach ($this->queue as $queue_name => $queue) {
      $handler_name = $queue_item['handler'];
      // Execute the queue.
      $this->handler[$handler_name]->execute($queue_item['purges'], $queue['handler_options']);
    }
  }
}

/**
 * Class definition for the API Bundle class.
 */
class PurgeBundleAPI extends PurgeBundleBasic {
  public $api = array();

  /**
   * Save function.
   *   For API Bundles this merges the API bundle into the current
   *   configuration.
   */
  public function save() {
    // Get the currently configured bundle.
    $bundle = new PurgeBundleBasic();

    // Loop through the types.
    foreach ($bundle->type as $type_name => $type) {
      // Now pass each items of this API bundle.
      foreach ($this->{$type_name} as $item_name => $item) {
        // Overwrite the bundle
        $bundle->{$type_name}[$item_name] = $item;
      }
    }
    // Delete items no longer in the API bundle.
    //    unset($bundle->{$type_name}[$item_name]);

    // Save the bundle
    $bundle->save();
  }
}

/**
 * Purges urls from reverse proxy caches
 *
 * @param $urls
 *   Array of urls to remove from the proxy cache using the http purge method.
 */
function purge_urls($urls) {
  $bundle = new PurgeBundleRuntime();
  $bundle->purgeables['urls'] =  $urls;
  $bundle->process();
  // dprint_r($bundle->queues);
  $bundle->execute();
}


/**
 * (Re)Configure.
 *   Loads all API provided bundles.
 */
function purge_configure($api = NULL) {
  // Start out by fetching the currently configured bundle.
  $bundle = new PurgeBundleBasic();

  // Then get the Depend bundle.
  $depend_bundle = new PurgeBundleDepend();
  $depend_bundle->save();

  $modifed_bundle = new PurgeBundleBasic();
  // Invoke hook_purge_configure to get the api bundle.
  $api_modules = module_implements('purge_configure');
  foreach ($api_modules as $api_module) {
    $api_bundle = module_invoke($api_module, 'purge_configure', $api);

    // Loop through the types.
    foreach ($bundle->type as $type_name => $type) {
      // Now pass each items of this API bundle.
      foreach ($api_bundle->{$type_name} as $item_name => $item) {
        // Overwrite the bundle
        $bundle->{$type_name}[$item_name] = $item;
      }
    }
  }
  // Save the bundle.
  $bundle->save();
}


/**
 * Logs successful purges and errors to the watchdog.
 *
 * @param $purge_request_results
 *   array of url with their http status code
 */
function purge_logging($purge_request_results) {
  $purge_success = 0;
  $purge_blocking = array();
  $purge_log = array();
  foreach ($purge_request_results as $purge_request_result) {
    switch ($purge_request_result['http_code']) {
      // First check if everything went ok.
      case 200:
        $purge_log[] = $purge_request_result['purge_url'] . ' on ' . $purge_request_result['proxy_name'] . ' Ok';
        $purge_success++;
        break;
      // Notice if the request was not found in the proxy cache
      case 404:
        $purge_log[] = $purge_request_result['purge_url'] . ' on ' . $purge_request_result['proxy_name'] . ' Not Found';
        $purge_success++;
        break;
      // Collect all proxy hosts that are blocking the url requests
      case 405:
        $purge_log[] = $purge_request_result['purge_url'] . ' on ' . $purge_request_result['proxy_name'] . ' Forbidden';
        $purge_blocking[] = parse_url($purge_request_result['proxy_url'], PHP_URL_HOST);
        break;
      // Collect all urls and their http error codes
      default:
        $purge_log[] = $purge_request_result['purge_url'] . ' on '
          . $purge_request_result['proxy_name'] . ' ' . $purge_request_result['http_code'];
        break;
    }

  }

  // Watchdog barking
  $purge_errors = count($purge_request_results) - $purge_success;
  // Just a notice when all is ok
  if ($purge_errors == 0) {
    if ($purge_success == 1) {
      watchdog(
        'purge',
        '1 URL has been successfully purged from the reverse proxy caches: !purge_log',
        array('!purge_log' => expire_print_r($purge_log))
      );
    }
    else {
      watchdog(
        'purge',
        '!purge_success_count URLs have been successfully purged from the reverse proxy caches: !purge_log',
        array('!purge_success_count' => $purge_success, '!purge_log' => expire_print_r($purge_log))
      );
    }
  }
  else {
    // Report all urls with errors
    if ($purge_errors == 1) {
      watchdog(
        'purge',
        '1 error has been encountered when purging URLs !purge_log',
        array('!purge_log' => expire_print_r($purge_log)),
        $severity = WATCHDOG_ERROR
      );
    }
    else {
      watchdog(
        'purge',
        '!purge_errors_count errors have been encountered when purging these URLs. !purge_log',
        array('!purge_errors_count' => count($purge_errors), '!purge_log' => expire_print_r($purge_log)),
        $severity = WATCHDOG_ERROR
      );
    }
    // Report on proxy servers that block the purge requests.
    if (!empty($purge_blocking)) {
      foreach ($purge_blocking as $purge_blocking_server) {
        watchdog(
          'purge',
          'The proxy server host %blocking_server is blocking purge requests. Please review the proxy configuration.',
          array('%blocking_server' => $purge_blocking_server),
          $severity = WATCHDOG_ERROR
        );
      }
    }
  }
}

/**
 * Helper function to explode headers to an array
 */
function headers2array($headers) {
  $headers_array = array();
  foreach ($headers as $header) {
    $header_parts = explode(":", $header);
    $headers_array[$header_parts[0]] = $header_parts[1];
  }
  return $headers_array;
}

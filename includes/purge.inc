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
define('PURGE_ACCESS_VIEW', 1);
define('PURGE_ACCESS_ENABLE', 2);
define('PURGE_ACCESS_CLONE', 3);
define('PURGE_ACCESS_FULL', 4);

// API options.
define('PURGE_API_STATIC', 0);
define('PURGE_API_CACHED', 1);
define('PURGE_API_BATCH', 2);
define('PURGE_API_RUNTIME', 3);
define('PURGE_API_CONFIG', 4);

// Purger features
define('PURGE_HANDLER_STATIC_DOMAIN', 0);
define('PURGE_HANDLER_SINGLE', 1);
define('PURGE_HANDLER_MULTI', 2);
define('PURGE_HANDLER_NON_BLOCKING', 3);


/**
 * Interface definitions for Setable items.
 *   These items can be include in a set.
 */
interface PurgeSetable {

}

/**
 * Interface definition for Optionable items.
 */
interface PurgeOptionable {

}

/**
 * Interface definition for static items.
 *   Items imlementing this contain just static data.
 */
interface PurgeStaticable {

}

/**
 * Interface definition for Cacheable.
 *   The data will be fetched once and cached for reuse.
 */
interface PurgeCacheable {
  function get_data_cached();
}

/**
 * Interface definition for processable items.
 *   These items will have their data fetched for each purgeable.
 */
interface PurgeProcessable {
  function get_data_purgeable();
}

/**
 * Interface definition for 



/**
 * Class for purge configuration management.
 *   Defines a basic configuration object.
 */
abstract class PurgeItemBasic {
  public $name = '';
  public $description = '';
  public $enabled = 1;
  public $access = PURGE_ACCESS_FULL;
  public $options;
  public $api;

  /**
   * Validate
   *   Validate the current configuration.
   *
   * @return array $errors
   */
  public function validate() {
    // Validate the name is unique
    return $errors;
  }

  /**
   * Stage
   *   Execute the API callback.
   */
  public function fetch($context, $purgeable_type = NULL, $purgeable = NULL) {
    // Check if the api is configured.
    if (isset($this->api['callback'])) {
      // Check if this is set to run at runtime
      if (isset($this->api['context'])) {
        if ($this->api['context'] == $context) {
          // Callback execution
          $api_data = $this->api['callback']();
          // Now store the data in this object.
          $this->api_set($api_data);
        }
      }
    }
  }
}

/**
 * Class definition for the Types object class.
 */
class PurgeItemType extends PurgeItemBasic implements PurgeOptionable {
  public parentclass;
}


/**
 * Abstract Class definition for Purge Targets.
 */
abstract class PurgeItemTarget extends PurgeItemBasic {
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
          'provider' => 'purge_target',
          'message' => t('Invalid URL')
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
    $this->domain = array(parse_url($base_url, PHP_URL_HOST));
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
 * Class definition for Purge Purger.
 */
abstract class PurgeItemPurger extends PurgeItemBasic implements PurgeOptionable {
  public $features = array();
}


/**
 * Class definition for full Purge Configurations.
 */
class PurgeItemSet extends PurgeItemBasic {
  public $pointers = array(
    'target' => array(),
    'domain' => array(),
    'header' => array(),
    'purger' => array(),
  );
  public $target = array();
  public $domain = array();
  public $header = array();
  public $purger = array();

  /**
   * Serialize the Set data and replace pointers with a string.
   */
  public function __sleep() {
    // Iterate through all pointers objects and replace with names
    $this->get_pointers();

    return array('name', 'description', 'enabled', 'access', 'options', 'api', 'pointers');
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
   * @param string $purgeable_type
   * @param array $purgeable
   *
   * @return array $purges
   */
  public function process($purgeable_type, $purgeable) {
    if ($purgeable_type == 'url') {

    }
  }
}

/**
 * Class definition for basic bundles.
 */
Class PurgeBundleBasic {
  public $type = array();
  public $set = array();
  public $target = array();
  public $domain = array();
  public $header = array();
  public $purger = array();

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
    // Sets need special handling
    // $pointer_types = array('target', 'domain', 'header', 'purger', 'option');
    // Each Set
    foreach ($this->set as $set_name => $set) {
      // Each type name that is a pointer.
      foreach ($set->pointers as $pointer_type => $item_names ) {
        $this->set[$set_name]->{$pointer_type} = array();
        foreach ($item_names as $item_name) {
          // Each item name now only referenced by name
          foreach ($item_names as $item_name) {
            // Link back to the object in the bundle
         //  dprint_r($this->{$pointer_type}[$item_name]);
         //  $this->set[$set_name]->{$pointer_type}[$item_name] = $this->{$type_name}[$item_name];
            // Populate the set options with the type and item options.
          //  $this->set[$set_name]->options[$pointer_type] = $this->type[$pointer_type]->options;
          }
        }
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
  public $purgeable;
  public $queue;

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
    $data['set'] = array();
    foreach ($all_data['set'] as $set_name => $set_data) {
      // check if the set is enabled.
      if (strpos($set_data, "s:7:\"enabled\";i:1")) {
        $data['set'][$set_name] = $set_data;
        // Create the set object.
        $this->set[$set_name] = unserialize($set_data);
        // Now loop through the pointers
        foreach ($this->set[$set_name]->pointers as $pointer_type => $item_names) {
          foreach ($item_names as $item_name) {
            $item_data = $all_data[$pointer_type][$item_name];
            // Check in the data if the item is enabled.
            if (strpos($item_data, "s:7:\"enabled\";i:1")) {
              // Check if the item object isn't allready there.
              if (isset($this->{$pointer_type}[$item_name])) {
                // If so just link the pointer to it.
                $this->set[$set_name]->{$pointer_type}[$item_name] = $this->{$pointer_type}[$item_name];
              }
              else {
                // Create the object from data
                $this->{$pointer_type}[$item_name] = unserialize($item_data);
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
   * Prepare the bundle for processing.
   */
  public function fetch($context) {
    // Call api callbacks for these item types
    $type_names = array('target', 'domain', 'header');
    foreach ($type_names as $type_name) {
      foreach ($this->{$type_name} as $item_name => $item) {
        $this->{$type_name}[$item_name]->fetch($context);
      }
    }
  }

  /**
   * Process all requests.
   */
  public function process() {
    // Walk the purgeables.
    foreach ($this->purgeable as $purgeable_type => $purgeables) {
      foreach ($purgeables as $purgeable_key => $purgeable) {
        // Pass all sets.
        foreach ($this-set as $set_name => $set) {
          $set_purges = $this->set[$set_name]->process($purgeable_type, $purgeable);
        }
      }
    }
  }
}


/**
 * Purges urls from reverse proxy caches
 *
 * @param $purge_urls
 *   Array of urls to remove from the proxy cache using the http purge method.
 */
function purge_urls($urls) {
  $bundle = new PurgeBundleRuntime();
  $bundle->purgeables['urls'] =  $urls;
  $bundle->fetch(PURGE_CONTEXT_RUNTIME);
  $bundle->process();
}


/**
 * Issue purge requests using drupal_http_request.
 */
function purge_drupal($purges) {
  // Go through each purge url one at a time
  foreach ($purges as $purge_id => $purge) {
    // Get the method
    $method = purge_get_method($purge['options']);
    $headers = headers2array ($purge['headers']);
    $options = array(
      'headers' => $headers,
      'method' => $method,
    );
    $results = drupal_http_request($purge['purge_url'],$options);
    $purges[$purge_id]['http_code'] = 200; // $results['code'];
  }
  return $purges;
}

/**
 * Issue purge requests using single curl requests.
 */
function purge_curl_single($purges) {
  // Go through all purge requests one by one.
  foreach ($purges as $purge_id => $purge) {
    // Create a curl request
    $current_curl_request = curl_init();
    curl_setopt($current_curl_request, CURLOPT_CUSTOMREQUEST, purge_get_method($purge['options']));
    curl_setopt($current_curl_request, CURLOPT_URL, $purge['purge_url']);
    curl_setopt($current_curl_request, CURLOPT_HEADER, 1);
    curl_setopt($current_curl_request, CURLOPT_HTTPHEADER, $purge['headers']);
    curl_setopt($current_curl_request, CURLOPT_RETURNTRANSFER, 0);
    // Execute the request
    curl_exec($current_curl_request);
    // Get http status code.
    $info = curl_getinfo($current_curl_request);
    $purges[$purge_id]['http_code'] = $info['http_code'];
    // Close the curl request.
    curl_close($current_curl_request);
  }
  return $purges;
}



/**
 * Issue purge request using curl_multi in a way compliant with legacy PHP/Curl implementations
 *
 */
function purge_curl_multi_legacy($purges) {
  // Initialise a curl_multi object
  $curl_purges = curl_multi_init();
  $current_curl_purge = 0;
  foreach ($purges as $purge) {
    $curl_purge[$current_curl_purge] = curl_init();
    curl_setopt($curl_purge[$current_curl_purge], CURLOPT_CUSTOMREQUEST, purge_get_method($purge['options']));
    curl_setopt($curl_purge[$current_curl_purge], CURLOPT_URL, $purge['purge_url']);
    curl_setopt($curl_purge[$current_curl_purge], CURLOPT_HEADER, 1);
    curl_setopt($curl_purge[$current_curl_purge], CURLOPT_HTTPHEADER, $purge['headers']);
    curl_setopt($curl_purge[$current_curl_purge], CURLOPT_RETURNTRANSFER, 0);
    curl_multi_add_handle($curl_purges, $curl_purge[$current_curl_purge]);
    $current_curl_purge++;
  }
  // Execute the purge requests
  ob_start();
  do {
    // This loop is only necessary for libcurl earlier than 7.20.0, however
    // RHEL 5 has just that. Otherwise a simple call would do.
    do {
      $multi_result = curl_multi_exec($curl_purges, $active);
    } while ($multi_result == CURLM_CALL_MULTI_PERFORM);
    // Block until there is activity on any of the purgers. Avoids
    // busywaiting.
    if ($multi_result == CURLM_OK) {
      $select_result = curl_multi_select($curl_purges);
    }
    if ($multi_result != CURLM_OK || $select_result == -1) {
    // @TODO: error handling. Something truly awkward happened.
      ob_end_clean();
    return FALSE;
    }
  } while ($select_result != -1 && $active && $multi_result == CURLM_OK);
  ob_end_clean();

  // Result collection. Collects the http code returned for each url purged
  $current_curl_purge = 0;
  foreach ($purges as $purge) {
    $info = curl_getinfo($curl_purge[$current_curl_purge]);
    $purges[$current_curl_purge]['http_code'] = $info['http_code'];
    curl_multi_remove_handle($curl_purges, $curl_purge[$current_curl_purge]);
    $current_curl_purge++;
  }
  curl_multi_close($curl_purges);
  return $purges;
}

/**
 * Issue purges using the httprl module.
 */
function purge_httprl($purges) {
  foreach ($purges as $purge_id => $purge) {
    // Get headers
    $headers_array = headers2array($purge['headers']);
    // Gather options
    $options = array(
      'method' => purge_get_method($purge['options']),
      'blocking' => FALSE,
      'headers' => $headers_array,
    );
    // Queue the request
    httprl_request($purge['purge_url'], $options);
    // Since we don't care about the results, let's fake 200
    $purges[$purge_id]['http_code'] = 200;
  }
  // Issue all requests.
  $request = httprl_send_request();
  return $purges;
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

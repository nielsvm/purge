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
define('PURGE_ACCESS_ClONE', 3);
define('PURGE_ACCESS_FULL', 4);

/**
 * Class for purge configuration management.
 *   Defines a basic configuration object.
 */
abstract class PurgeConfBasic {
  public $id = 0;
  public $enabled = true;
  public $access = PURGE_ACCESS_SYSTEM;
  public $name = '';
  public $description = '';
  public $callback = '';

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
}

/**
 * Class definition for Purge Targets.
 */
class PurgeConfTarget extends PurgeConfBasic {
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

    return $errors;
  }
}

/**
 * Class definition for domains.
 */
class PurgeConfDomain extends PurgeConfBasic {
  public $domains = array();
}

/**
 * Class definition for Purge Headers.
 */
class PurgeConfHeader extends PurgeConfBasic {
  public $headers = array();
}

/**
 * Class definition for Purge Options.
 */
class PurgeConfOption extends PurgeConfBasic {
  public $options = array();
}


/**
 * Class definition for full Purge Configurations.
 */
class PurgeConfSet extends PurgeConfBasic {
  public $target = array();
  public $domain = array();
  public $header = array();
  public $option = array();

  /**
   * Constructor for sets.
   */
  public function __construct($id, $data, $objects) {
    // Call the parent constructor
    parent::__construct($id, $data);
    // Now point point all variables to the object in the bundle
    $types = array('targets', 'domains', 'headers', 'options');
    foreach ($types as $type) {
      foreach($objects[$type] as $obj_id => $object ) {
        $target[$obj_id] = $object;
      }
    }
  }
}

/**
 * Class definition for basic bundles.
 */
Class PurgeBundleBasic {
  public $types = array('set','target','domain','header','option');
  public $set = array();
  public $target = array();
  public $domain = array();
  public $header = array();
  public $option = array();

  /**
   * Constructor for the basic bundle.
   */
  public function __construct() {
    // Get the data from the given dataset.
    $select_data = $this->select_data();
    // And create the object from it's data
    foreach ($this->types as $type) {
      foreach ($select_data[$type] as $id => $data) {
        $this->$type[$id] = unserialize($data);
      }
    }
  }

  /**
   * Function to select data from the dataset.
   *
   * @return array $data
   */
  public function select_data() {
    // Get the data from the given dataset.
    $data = array();
    $data = variable_get('purge_config');

    return $data;
  }

  /**
   * Save the bundle to the variable.
   */
  public function save() {
    $data = array();
    foreach ($types as $type) {
      $data[$type] = array();
      foreach ($this->$type as $item_name => $item) {
        $data[$type][$item_name] = serialize($item);
      }
    }
    // Store the data in the variable
    variable_set('purge_config', $data);
  }
}

/**
 * Class definition for the runtime bundle.
 */
class PurgeBundleRuntime extends PurgeBundleBasic {

  /**
   * Constructor for the Configuration bundle.
   *
   * @param int $query
   *   Can be PURGE_ALL, PURGE_ENABLED, PURGE_SINGLE.
   * @param int $id
   *   Conf ID For use with PURGE_SINGLE.
   */
  public function __construct($bundle = PURGE_BUNDLE_ACTIVE, $id = NULL) {
    $types = array('target', 'domain', 'header', 'option');
    // First get all enabled sets.
    $this->get_data($all_data, 'set');
    foreach ($this->data['set'] as $set_id => $set_data) {
      // Now get all the items types.
      foreach ($types as $type) {
        $type_data = array();
        // Inspect each of the ids the set refers too.
        foreach ($set_data[$type] as $ref_id) {
          $type_data[$ref_id] = $all_data[$type][$ref_id];
        }
        // Keep the data from this item
        $this->get_data($type_data, $type);
      }
    }
    // Now lets contruct all objects
    foreach ($types as $type) {
      foreach ($this->data[$type] as $id => $data) {
        switch ($type) {
          case 'target':
            $this->target[$id] = new PurgeConfTarget($id, $data);
            break;
          case 'domain':
            $this->domain[$id] = new PurgeConfDomain($id, $data);
            break;
          case 'header':
            $this->header[$id] = new PurgeConfHeader($id, $data);
            break;
          case 'option':
            $this->option[$id] = new PurgeConfOption($id, $data);
            break;
        }
      }
    }
    // Now construct the sets.
    foreach ($this->data['set'] as $id => $data) {
      $this->set[$id] = new PurgeConfSet($id, $data);
    }
  }

  /**
   * Private function to get configuration item from a single item.
   *
   * @param array $data
   *   Item data as an array.
   * @param string type
   *   Can be set, target, domain, header, option.
   */
  private function get_data($all_data, $type) {
    // Inspect all data.
    foreach ($all_data[$type] as $id => $data) {
      // Get the enabled ones.
      if ($data['en'] == 0) {
        $this->data[$type][$id] = $data;
      }
    }
  }
}



/**
 * Purges urls from reverse proxy caches
 *
 * @param $purge_urls
 *   Array of urls to remove from the proxy cache using the http purge method.
 *
 * @return
 *   Array of urls and their http status codes after purging.
 */
function purge_urls($purge_urls) {
  // Get the Drupal base url and parse it.
  global $base_url;
  $base_url_parts = parse_url($base_url);
  // Get all enabled and active proxy configurations
  $proxies = array();
  $proxies = purge_config_get_enabled();
  // This array will be filled with requests.
  $purge_requests = array();
  $current_purge_request = 0;

  // Find out the url parts we need from the urls to be purged
  foreach ($purge_urls as $purge_url) {
    $purge_url_parts = parse_url($purge_url);

    // Process all urls for each proxy
    foreach ($proxies as $proxy) {
      // Set the base url of the proxy request (scheme + domain)
      // First check if the use base url option is set
      if ($proxy->url == '_USE_BASEURL_') {
        $purge_base_url = $base_url_parts['scheme'] . $base_url_parts['host'];
        // Add port to the host if set
        if (array_key_exists('port', $base_url_parts)) {
          $purge_base_url = $purge_base_url . ":" . $base_url_parts['port'];
        }
      }
      else {
        // Just take the configured URL
        $purge_base_url = $proxy->url;
      }

      // Set the path
      $purge_path = '';
      $purge_path .= $purge_url_parts['path'];
      if (array_key_exists('query', $purge_url_parts)) {
        $purge_path .= "?" . $purge_url_parts['query'];
      }

      // Determine all host headers to set.
      $purge_url_domains = array();
      $domains = $proxy->get_domains(True);
      foreach ($domains as $domain) {
        if ($domain[0] == '_') {
          if ($domain == '_DOMAINS_EXPIRE_') {
            if (array_key_exists('host', $purge_url_parts)) {
              $purge_url_domain = $purge_url_parts['host'];
              // Add port to the host if set
              if (array_key_exists('port', $purge_url_parts)) {
                $purge_url_domain = $purge_url_domain . ":" . $purge_url_parts['port'];
              }
              $purge_url_domains[] = $purge_url_domain;
            }
          }
        }
        else {
          $purge_url_domains[] = $domain;
        }
      }
      // Then add the Drupal base url if needed.
      if (((array_key_exists) OR (count($purge_url_domains) == 0))) {
        $purge_url_domain = $base_url_parts['host'];
        // Add port to the host if set
        if (array_key_exists('port', $base_url_parts)) {
          $purge_url_domain = $purge_url_domain . ":" . $base_url_parts['port'];
        }
        $purge_url_domains[] = $purge_url_domain;
      }
      // Remove double entries
      $purge_url_domains = array_unique($purge_url_domains);

      // Set Headers
      $purge_headers = array();
      $headers = $proxy->headers;
      foreach ($headers as $header) {
        if ($header[0] != '_') {
          $purge_headers[] = $header;
        }
      }

      // Now add a purge request for each domain
      foreach ($purge_url_domains as $purge_url_domain) {
        // Add purge url, proxy config and domain to the array for later processing
        $purge_requests[$current_purge_request]['purge_url'] = $purge_url;
        $purge_requests[$current_purge_request]['proxy_name'] = $proxy->name;
        $purge_requests[$current_purge_request]['domain'] = $purge_url_domain;
        // Set the purge url
        $purge_requests[$current_purge_request]['purge_url'] = $purge_base_url . $purge_path;
        // Set the options
        $purge_requests[$current_purge_request]['options'] = $proxy->options;
        // Set the headers
        $all_headers = array();
        if (count($purge_headers > 0)) {
          $all_headers = $purge_headers;
        }
        $all_headers[] = "Host: " . $purge_url_domain;
        $purge_requests[$current_purge_request]['headers'] = $all_headers;
        $current_purge_request++;
      }
    }
  }
  return $purge_requests;
}

/**
 * Get the request method from a set options.
 */
function purge_get_method($options) {
  if (in_array('_METHOD_GET_', $options)) {
    $method = 'GET';
  }
  elseif (in_array('_METHOD_BAN_', $options)) {
    $method = 'BAN';
  }
  else {
    $method = 'PURGE';
  }
  return $method;
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
    // Block until there is activity on any of the handlers. Avoids
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

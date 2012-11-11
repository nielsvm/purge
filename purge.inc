<?php

/**
 * @file
 * Contains the main purging functionality and error handling
 */

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
  $proxy_configs = array();
  $proxy_configs = purge_proxy_get_config(0, 'FULL', 'ACTIVE');

  // This array will be filled with requests.
  $purge_requests = array();
  $current_purge_request = 0;

  // Find out the url parts we need from the urls to be purged
  foreach ($purge_urls as $purge_url) {
    $purge_url_parts = parse_url($purge_url);

    // Process all urls for each proxy
    foreach ($proxy_configs as $proxy_config) {
      // Get the pid of the current proxy config
      $pid = $proxy_config['pid'];

      // Set the base url of the proxy request (scheme + domain)
      // First check if the use base url option is set
      if ($proxy_config[$pid]['proxy_use_baseurl']) {
        $purge_base_url = $base_url_parts['scheme'] . $base_url_parts['host'];
        // Add port to the host if set
        if (array_key_exists('port', $base_url_parts)) {
          $purge_base_url = $purge_base_url . ":" . $base_url_parts['port'];
        }
      }
      else {
        // Check for ssl/https
        if ($proxy_config[$pid]['proxy_ssl']) {
          $purge_base_url = "https://";
        }
        else {
          $purge_base_url = "http://";
        }
        // Add hostname
        $purge_base_url = $purge_base_url . $proxy_config[$pid]['proxy_host'];
        // Add port to the host if set
        if ($proxy_config[$pid]['proxy_port'] != '') {
          $purge_base_url = $purge_base_url . ":" . $proxy_config[$pid]['proxy_port'];
        }
      }

      // Set the path
      $purge_path = '';
      if ($proxy_config[$pid]['url_prefix'] != '') {
        $purge_path .= $proxy_config[$pid]['url_prefix'];
      }
      $purge_path .= $purge_url_parts['path'];
      if (array_key_exists('query', $purge_url_parts)) {
        $purge_path .= "?" . $purge_url_parts['query'];
      }

      // Determine all host headers to set.
      $purge_url_domains = array();
      // First check for a domain from the expire hook
      if ($proxy_config[$pid]['domains_expire'] == TRUE) {
        if (array_key_exists('host', $purge_url_parts)) {
          $purge_url_domain = $purge_url_parts['host'];
          // Add port to the host if set
          if (array_key_exists('port', $purge_url_parts)) {
            $purge_url_domain = $purge_url_domain . ":" . $purge_url_parts['port'];
          }
          $purge_url_domains[] = $purge_url_domain;
        }
      }
      // Then add custom domains
      if (isset($proxy_config[$pid]['domains_custom'])) {
        $custom_domains = $proxy_config[$pid]['domains_custom'];
        foreach ($custom_domains as $custom_domain) {
          $purge_url_domains[] = $custom_domain;
        }
      }
      // Then add the Drupal base url if needed.
      if ((($proxy_config[$pid]['domains_drupal']) 
            OR (count($purge_url_domains) == 0)) 
          AND ($proxy_config[$pid]['proxy_use_baseurl'] == FALSE)
        ) {
        $purge_url_domain = $base_url_parts['host'];
        // Add port to the host if set
        if (array_key_exists('port', $base_url_parts)) {
          $purge_url_domain = $purge_url_domain . ":" . $base_url_parts['port'];
        }
        $purge_url_domains[] = $purge_url_domain;
      }
      // Remove double entries
      array_unique($purge_url_domains);

      // Set Headers
      $purge_headers = array();
      if (isset($proxy_config[$pid]['headers_custom'])) {
        $purge_headers = $proxy_config[$pid]['headers_custom'];
      }
      if ($proxy_config[$pid]['headers_acquia'] == TRUE) {
        $purge_headers[] = 'X-Acquia-Purge:' . $_SERVER['AH_SITE_NAME'];
      }

      // Now add a purge request for each domain
      foreach ($purge_url_domains as $purge_url_domain) {
        // Add purge url, proxy config and domain to the array for later processing
        $purge_requests[$current_purge_request]['purge_url'] = $purge_url;
        $purge_requests[$current_purge_request]['proxy_name'] = $proxy_config[$pid]['name'];
        $purge_requests[$current_purge_request]['domain'] = $proxy_url_domain;
        // Set the purge url
        $purge_requests[$current_purge_request]['purge_url'] = $purge_base_url . $purge_path;
        // Set the options
        $purge_requests[$current_purge_request]['request_method'] = $proxy_config[$pid]['options_type'];
        $purge_requests[$current_purge_request]['request_parallel'] = $proxy_config[$pid]['options_parallel'];
        $purge_requests[$current_purge_request]['request_nonblocking'] = $proxy_config[$pid]['options_nonblocking'];
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
  // Issue the requests using curl (for now)
  $purge_request_results = purge_issue_requests_curl($purge_requests);
  return $purge_request_results;
}

/**
 * Issue purge request using curl
 *
 */
function purge_issue_requests_curl($purges) {
  // Initialise a curl_multi object
  $curl_purges = curl_multi_init();
  $current_curl_purge = 0;
  foreach ($purges as $purge) {
    $curl_purge[$current_curl_purge] = curl_init();
    curl_setopt($curl_purge[$current_curl_purge], CURLOPT_CUSTOMREQUEST, $purge['request_method']);
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
        $purge_log[] = $purge_request_result['purge_url'] . ' on ' . $purge_request_result['proxy_url'] . ' Ok';
        $purge_success++;
        break;
      // Notice if the request was not found in the proxy cache
      case 404:
        $purge_log[] = $purge_request_result['purge_url'] . ' on ' . $purge_request_result['proxy_url'] . ' Not Found';
        $purge_success++;
        break;
      // Collect all proxy hosts that are blocking the url requests
      case 405:
        $purge_log[] = $purge_request_result['purge_url'] . ' on ' . $purge_request_result['proxy_url'] . ' Forbidden';
        $purge_blocking[] = parse_url($purge_request_result['url'], PHP_URL_HOST);
        break;
      // Collect all urls and their http error codes
      default:
        $purge_log[] = $purge_request_result['purge_url'] . ' on '
          . $purge_request_result['proxy_url'] . ' ' . $purge_request_result['http_code'];
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

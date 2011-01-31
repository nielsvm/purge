<?php
// $Id$

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
  // Get settings
  $proxy_urls = explode(' ', variable_get('purge_proxy_urls', 'http://localhost:80'));
  // Initialise a curl_multi object
  $purge_requests = array();
  $curl_multi_obj = curl_multi_init();
  $current_purge_request = 0;

  // Find out the url parts we need from the urls to be purged
  foreach ($purge_urls as $purge_url) {
    $purge_url_parts = parse_url($purge_url);
    // Determine the host
    $purge_url_host = $purge_url_parts['host'];
    // Add portnames to the host if any are set
    if ($purge_url_parts['port']) {
      $purge_url_host = $purge_url_host . ":" . $purge_url_parts['port'];
    }

    foreach ($proxy_urls as $proxy_url) {
      // Initialize a curl object for each request
      $purge_requests[$current_purge_request] = curl_init();
      // Make it a PURGE request (not GET or POST)
      curl_setopt($purge_requests[$current_purge_request], CURLOPT_CUSTOMREQUEST, "PURGE");
      // Replace the sites host with the proxy host
      $proxy_purge_url = str_replace($purge_url_parts['scheme'] . $purge_url_host , $proxy_url , $purge_url);
      curl_setopt($purge_requests[$current_purge_request], CURLOPT_URL, $proxy_purge_url);
      // Set the host header to the sites hostname
      curl_setopt($purge_requests[$current_purge_request], CURLOPT_HEADER, 1);
      curl_setopt($purge_requests[$current_purge_request], CURLOPT_HTTP_HEADER, array("Host: " . $purge_url_host));
      curl_setopt($purge_requests[$current_purge_request], CURLOPT_RETURNTRANSFER, 0);
      curl_multi_add_handle($curl_multi_obj, $purge_requests[$current_purge_request]);
      $current_purge_request++;
    }
  }
  // Execute the purge requests
  do {
    curl_multi_exec($curl_multi_obj, $running);
  } while ($running > 0);

  // Result collection. Collects the http code returned for each url purged
  $current_purge_request = 0;
  $purge_request_results = array();
  foreach ($purge_requests as $purge_request) {
    $info = curl_getinfo($purge_request);
    $purge_request_results[$current_purge_request]['url'] = $info['url'];
    $purge_request_results[$current_purge_request]['http_code'] = $info['http_code'];
    curl_multi_remove_handle($curl_multi_obj, $purge_request);
    $current_purge_request++;
  }
  curl_multi_close($curl_multi_obj);
  return $purge_request_results;
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
  $purge_errors = array();
  foreach ($purge_request_results as $purge_request_result) {
    switch ($purge_request_result['http_code']) {
      // First check if everything went ok.
      case 200;
        $purge_success++;
        break;
      // Collect all proxy hosts that are blocking the url requests
      case 405;
        $purge_blocking[] = parse_url($purge_request_result['url'], PHP_URL_HOST);
      // Collect all urls and their http error codes
      default;
      $purge_errors[] = $purge_request_result['url'] . '(' . $purge_request_result['http_code'] . ')';
      break;
    }
  }

  // Watchdog barking
  // Just a notice when all is ok
  if (count($purge_errors) == 0) {
    if ($purge_success == 1) {
      watchdog(
        'purge',
        '1 URL has been successfully purged from the reverse proxy caches.'
      );
    }
    else {
      watchdog(
        'purge',
        '!purge_success_count URLs have been successfully purged from the reverse proxy caches.',
        array('!purge_success_count' => $purge_success)
      );
    }
  }
  else {
    // Report all urls with errors
    if (count($purge_errors) == 1) {
      watchdog(
        'purge',
        '1 error has been encountered when purging URL @purge_errors',
        array('@purge_errors' => $purge_errors),
        $severity = WATCHDOG_ERROR
      );
    }
    else {
      watchdog(
        'purge',
        '!purge_errors_count errors have been encountered when purging these URLs. @purge_errors',
        array('!purge_errors_count' => count($purge_errors), '@purge_errors' => implode(',', $purge_errors)),
        $severity = WATCHDOG_ERROR
      );
    }
    // Report on proxy servers that block the purge requests.
    if (!empty($purge_blocking)) {
      foreach ($purge_blocking as $purge_blocking_server) {
        watchdog(
          'purge',
          'The proxy server host %blocking_server is blocking purge requests. Please review the proxy configuration.',
          array('%blocking_server' => $purge_blocking),
          $severity = WATCHDOG_ERROR
        );
      }
    }
  }
}

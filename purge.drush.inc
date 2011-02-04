<?php
// $id$

/**
 * @file
 * This is the drush integration for the purge module
 */

/**
 * Implementation of hook_drush_command().
 */
function purge_drush_command() {
  $items['purge-url'] = array(
    'description' => "Purge a list of URLs from the reverse proxy cache.",
    'arguments' => array(
      'urls' => 'A list of URLs to purge separated by spaces.',
    ),
    'aliases' => array('purl'),
    'drupal dependencies' => array('purge'),
    'callback' => 'drush_purge_url'
  );
  return $items;
}

/**
 * Callback for purge-url drush command.
 * @param string $urls a space separated list of paths.
 */
function drush_purge_url($urls) {
  $urls = explode(' ', $urls);
  module_load_include('inc', 'purge');
 // $results = 
  purge_urls($urls);
  // purge_logging($results);

}


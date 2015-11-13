<?php

/**
 * @file
 * Administrative Drush commands for Purge.
 */

/**
 * Implements hook_drush_help().
 */
function purge_drush_drush_help($command) {
  switch ($command) {
    case 'meta:purge_drush:title':
      return dt('Purge');
    case 'meta:purge_drush:summary':
      return dt('Administrative Drush commands for Purge.');
  }
}

/**
 * Implementation of hook_drush_command().
 */
function purge_drush_drush_command() {
  $items = [];
  $items['p-diagnostics'] = [
    'description' => 'Generate a diagnostic self-service report.',
    'arguments' => [],
    'required-arguments' => 0,
    'options' => [],
    'examples' => [
      'drush purge-diagnostics' => 'Build the diagnostic report as a table.',
      'drush purge-diagnostics --format=json' => 'Export as JSON.',
      'drush purge-diagnostics --format=yaml' => 'Export as YAML.',
    ],
    'outputformat' => [
      'default' => 'table',
      'json' => 'json',
      'yaml' => 'yaml',
    ],
    'aliases' => ['pdia'],
    'core' => ['8+'],
  ];
  $items['p-invalidate'] = [
    'description' => 'Directly invalidate an item without going through the queue.',
    'arguments' => [
      'type' => 'The type of invalidation to perform, e.g.: tag, path, url.',
      'expression' => 'The string expression of what needs to be invalidated.',
    ],
    'required-arguments' => 2,
    'options' => [],
    'examples' => [
      'drush purge-invalidate tag node:1' => 'Clears URLs tagged with "node:1" from external caching platforms.',
      'drush purge-invalidate url http://www.drupal.org/' => 'Clears "http://www.drupal.org/" from external caching platforms.',
    ],
    'outputformat' => [],
    'aliases' => ['pinv'],
    'core' => ['8+'],
  ];
  $items['p-processors'] = [
    'description' => 'List all enabled processors.',
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush purge-processor-list' => 'List all processors in a table.',
      'drush purge-processor-list --table=json' => 'Export all processors in JSON.',
      'drush purge-processor-list --table=yaml' => 'Export all processors in YAML.',
    ],
    'outputformat' => [
      'default' => 'table',
      'json' => 'json',
      'yaml' => 'yaml',
    ],
    'aliases' => ['ppro'],
    'core' => ['8+'],
  ];
  $items['p-queue-add'] = [
    'description' => 'Schedule an item for later processing.',
    'arguments' => [
      'type' => 'The type of invalidation to queue, e.g.: tag, path, url.',
      'expression' => 'The string expression of what needs to be invalidated.',
    ],
    'required-arguments' => TRUE,
    'options' => [],
    'examples' => [
      'drush purge-queue-add tag node:1' => 'Clears URLs tagged with "node:1" from external caching platforms.',
      'drush purge-queue-add url http://www.drupal.org/' => 'Clears "http://www.drupal.org/" from external caching platforms.',
    ],
    'outputformat' => [],
    'aliases' => ['pqa'],
    'core' => ['8+'],
  ];
  $items['p-queue-browse'] = [
    'description' => 'Inspect what is in the queue by paging through it.',
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [
      'limit' => [
        'description' => 'The number of items to show on a single page.',
        'example-value' => '20',
        'value' => 'optional',
      ],
      'page' => [
        'description' => 'The page to show data for, pages start at 1.',
        'example-value' => '1',
        'value' => 'optional',
      ],
    ],
    'examples' => [
      'drush purge-queue-browse' => 'Browse queue content and press space to load more.',
      'drush purge-queue-browse --limit=30' => 'Browse the queue content and show 30 items at a time.',
      'drush purge-queue-browse --page=3' => 'Show page 3 of the queue.',
      'drush purge-queue-browse --format=json' => 'Fetch a page from the queue exported as JSON.',
      'drush purge-queue-browse --format=yaml' => 'Fetch a page from the queue exported as JSON.',
    ],
    'outputformat' => [
      'default' => 'table',
      'json' => 'json',
      'yaml' => 'yaml',
    ],
    'aliases' => ['pqb'],
    'core' => ['8+'],
  ];
  $items['p-queue-empty'] = [
    'description' => 'Clear the queue and reset all statistics.',
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush purge-queue-empty' => 'Clear the queue and reset all statistics.',
    ],
    'outputformat' => [],
    'aliases' => ['pqe'],
    'core' => ['8+'],
  ];
  $items['p-queue-work'] = [
    'description' => 'Claim a chunk of items from the queue and process them.',
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush purge-queuer-workchunk' => 'Claim a chunk of items from the queue and process them.',
    ],
    'outputformat' => [
      'default' => 'message',
      'json' => 'json',
      'yaml' => 'yaml',
    ],
    'aliases' => ['pqw'],
    'core' => ['8+'],
  ];
  $items['p-queuers'] = [
    'description' => 'List all enabled queuers.',
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush purge-queuer-list' => 'List all queuers in a table.',
      'drush purge-queuer-list --table=json' => 'Export all queuers in JSON.',
      'drush purge-queuer-list --table=yaml' => 'Export all queuers in YAML.',
    ],
    'outputformat' => [
      'default' => 'table',
      'json' => 'json',
      'yaml' => 'yaml',
    ],
    'aliases' => ['pqrs'],
    'core' => ['8+'],
  ];
  return $items;
}

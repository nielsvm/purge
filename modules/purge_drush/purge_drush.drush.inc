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
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.diagnostics',],
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
      'pipe-format' => 'yaml',
      'require-engine-capability' => ['format-table'],
    ],
    'aliases' => ['pdia'],
    'core' => ['8+'],
  ];
  $items['p-invalidate'] = [
    'description' => 'Directly invalidate an item without going through the queue.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.invalidation.factory', 'purge.processors', 'purge.purgers'],
    'arguments' => [
      'type' => 'The type of invalidation to perform, e.g.: tag, path, url.',
      'expression' => 'The string expression of what needs to be invalidated.',
    ],
    'required-arguments' => 1,
    'options' => [],
    'examples' => [
      'drush purge-invalidate tag node:1' => 'Clears URLs tagged with "node:1" from external caching platforms.',
      'drush purge-invalidate url http://www.drupal.org/' => 'Clears "http://www.drupal.org/" from external caching platforms.',
      'drush purge-invalidate everything' => 'Clears everything on external caching platforms.',
    ],
    'outputformat' => [
      'default' => 'message',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pinv'],
    'core' => ['8+'],
  ];
  $items['p-processors'] = [
    'description' => 'List all enabled processors.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.processors',],
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
      'pipe-format' => 'list',
      'field-labels' => [
        'id' => 'Id',
        'label' => 'Label',
        'description' => 'Description',
      ],
    ],
    'aliases' => ['ppro'],
    'core' => ['8+'],
  ];
  $items['p-queue-add'] = [
    'description' => 'Schedule an item for later processing.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue', 'purge.queuers', 'purge.invalidation.factory'],
    'arguments' => [
      'type' => 'The type of invalidation to queue, e.g.: tag, path, url.',
      'expression' => 'The string expression of what needs to be invalidated.',
    ],
    'required-arguments' => 1,
    'options' => [],
    'examples' => [
      'drush purge-queue-add tag node:1' => 'Clears URLs tagged with "node:1" from external caching platforms.',
      'drush purge-queue-add url http://www.drupal.org/' => 'Clears "http://www.drupal.org/" from external caching platforms.',
    ],
    'outputformat' => [
      'default' => 'message',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pqa'],
    'core' => ['8+'],
  ];
  $items['p-queue-browse'] = [
    'description' => 'Inspect what is in the queue by paging through it.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [
      'limit' => [
        'description' => 'The number of items to show on a single page.',
        'example-value' => '20',
        'value' => 'optional',
        '_default' => 20,
      ],
      'page' => [
        'description' => 'The page to show data for, pages start at 1.',
        'example-value' => '1',
        'value' => 'optional',
        '_default' => 1,
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
      'pipe-format' => 'list',
      'require-engine-capability' => ['format-table'],
    ],
    'aliases' => ['pqb'],
    'core' => ['8+'],
  ];
  $items['p-queue-empty'] = [
    'description' => 'Clear the queue and reset all statistics.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush purge-queue-empty' => 'Clear the queue and reset all statistics.',
    ],
    'outputformat' => [
      'default' => 'message',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pqe'],
    'core' => ['8+'],
  ];
  $items['p-queue-work'] = [
    'description' => 'Claim a chunk of items from the queue and process them.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue', 'purge.processors', 'purge.purgers'],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush purge-queuer-workchunk' => 'Claim a chunk of items from the queue and process them.',
    ],
    'outputformat' => [
      'default' => 'message',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pqw'],
    'core' => ['8+'],
  ];
  $items['p-queuers'] = [
    'description' => 'List all enabled queuers.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queuers',],
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
      'pipe-format' => 'list',
    ],
    'aliases' => ['pqrs'],
    'core' => ['8+'],
  ];
  return $items;
}

/**
 * Route command execution to the right function and load its file.
 *
 * @param mixed $...
 *   One or more parameters passed depending on the routed command definition.
 *
 * @return mixed
 *   The command response.
 */

function purge_drush_callback_router() {
  $command_context = drush_get_command();
  $command = str_replace('-', '_', $command_context['command']);

  // Load requested container services in our own 'callback services' field.
  $services = [];
  foreach ($command_context['callback services'] as $service) {
    $services[] = \Drupal::service($service);
  }

  // Append options with '_default' as arguments to keep callbacks simple.
  $args = func_get_args();
  foreach ($command_context['options'] as $option => $definition) {
    if (isset($definition['_default'])) {
      $args[] = drush_get_option($option, $definition['_default']);
    }
  }

  // Require the actual file in which the callback resides.
  $file = 'purge_drush.drush_' . $command . '.inc';
  require_once dirname(__FILE__) . '/' .  $file;

  // Build up the final structure of the arguments and dispatch code execution.
  $final_args = array_merge(
    [drush_get_option('format', $command_context['outputformat']['default'])],
    $services, $args);
  return call_user_func_array('drush_purge_drush_' . $command, $final_args);
}

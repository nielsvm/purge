<?php

/**
 * @file
 * Provides administrative interface for the Purge module.
 */

// Load the purge.inc file
module_load_include('inc', 'purge');

/**
 * Menu callback for purge admin settings.
 */
function purge_admin_settings_form() {
  // Get the http request handlers
  $handlers = purge_get_request_handlers();
  $options = array();
  foreach ($handlers as $handler) {
    $options[($handler['mname'])] = $handler['hname'];
  }

  $form = array();
  $form['purge_handler'] = array(
    '#type' => 'select',
    '#title' => 'HTTP request handler',
    '#options' => $options,
    '#default_value' => variable_get('purge_handler', 'drupal'),
    '#description' => t('Select the HTTP request handler to use.'),
  );
  $form['purge_watchdog'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable logging'),
    '#default_value' => variable_get('purge_watchdog', 0),
    '#description' => t('Enable logging of Purge activity to the log.'),
  );
  return system_settings_form($form);
}

/**
 * Function to determine available request issuing methods.
 */
function purge_get_request_handlers() {
  $handlers = array();
  // Start with  the builtin Drupal HTTP Requester.
  $handlers[0]['mname'] = 'purge_drupal';
  $handlers[0]['hname'] = 'Drupal Native';
  $handlers[0]['options'] = '';
  // Now Check for curl
  if (extension_loaded('curl')) {
    // Add the Curl Single method
    $handlers[1]['mname'] = 'purge_curl_single';
    $handlers[1]['hname'] = 'Curl Single';
    $handlers[1]['options'] = '';
    // add the legacy Curl method
    $handlers[2]['mname'] = 'purge_curl_multi_legacy';
    $handlers[2]['hname'] = 'Curl Multi (Legacy)';
    $handlers[2]['options'] = '_PARALLEL_';
    // Check for versions newer then 7.20.x
    $cversion = curl_version();
    if ($cversion['version_number'] >= 463872) {
      // Add the normal curl multi handler
      $handlers[3]['mname'] = 'purge_curl_multi';
      $handlers[3]['hname'] = 'Curl Multi';
      $handlers[3]['options'] = '_PARALLEL_';
    }
    // Check for httprl module
    if (module_exists('httprl')) {
      $handlers[8]['mname'] = 'purge_httprl';
      $handlers[8]['hname'] = 'HTTPrl Module';
      $handlers[8]['options'] = '_PARALLEL_,_NONBLOCKING_';
    }
  }
  return $handlers;
}

/**
 * Menu callback for purge proxy configuration.
 */
function purge_proxy_form($form, $form_state, $pid = 1) {
  $proxy = array();
  // Load the proxy configuration for editing.
  $proxy = purge_proxy_get_config($pid, 'FULL', 'SYSTEM');
  //dprint_r($proxy);
  $form = array();
  // Hidden fields for internal use
  $form['purge_pid'] = array(
    '#type' => 'hidden',
    '#default_value' => $proxy[$pid]['pid'],
  );
  $form['purge_proxy_active'] = array(
    '#type' => 'hidden',
    '#default_value' => '1',
  );
  $form['purge_proxy_general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General Proxy Configuration'),
  );
  $form['purge_proxy_general']['name'] = array(
    '#type' => 'machine_name',
    '#title' => t('Name'),
    '#default_value' => check_plain($proxy[$pid]['name']),
    '#description' => t('The unique machine readable name of this proxy configuration.'),
    '#required' => TRUE,
    '#maxlength' => 21,
    '#machine_name' => array(
      'exists' => 'purge_proxy_name_exists',
    ),
  );
  $form['purge_proxy_general']['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => check_plain($proxy[$pid]['description']),
    '#description' => t('A human readable description of this proxy configuration.'),
  );
  $form['purge_proxy_general']['use_base_url'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use the base URL provided by Drupal'),
    '#default_value' => $proxy[$pid]['proxy_use_baseurl'],
  );

  $form['purge_proxy_general']['protocol'] = array(
    '#type' => 'select',
    '#title' => t('Protocol'),
    '#description' => t('The request protocol the proxy server accepts purge requests in.'),
    '#options' => array(
      '0' => 'http',
      '1' => 'https',
    ),
    '#default_value' => $proxy[$pid]['proxy_ssl'],
  );
  $form['purge_proxy_general']['host'] = array(
    '#type' => 'textfield',
    '#title' => t('Host'),
    '#description' => t('The hostname or ip adress of the proxy server.'),
    '#default_value' => $proxy[$pid]['proxy_host'],
  );
  $form['purge_proxy_general']['port'] = array(
    '#type' => 'textfield',
    '#title' => t('Port'),
    '#description' => t('The tcp port where the proxy is listening.'),
     '#default_value' => $proxy[$pid]['proxy_port'],
  );
  $form['purge_proxy_advanced'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Advanced Proxy Configuration'),
  );
  $form['purge_proxy_advanced']['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Options'),
  );
  $form['purge_proxy_advanced']['options']['type'] = array(
    '#type' => 'select',
    '#title' => t('Request type'),
    '#description' => t('The type of http request issued. Either PURGE or GET.'),
    '#options' => array(
      'PURGE' => t('Purge'),
      'GET' => t('Get'),
    ),
    '#default_value' => $proxy[$pid]['options_type'],
  );
  $form['purge_proxy_advanced']['options']['parallel'] = array(
    '#type' => 'checkbox',
    '#title' => t('Parallel requests'),
    '#description' => t('Purge requests are send in parallel. Disable to send requests on at a time.'),
    '#default_value' => $proxy[$pid]['options_parallel'],
  );
  $form['purge_proxy_advanced']['options']['nonblocking'] = array(
    '#type' => 'checkbox',
    '#title' => t('Non blocking'),
    '#description' => t('Send non blocking purge requests.'),
    '#default_value' => $proxy[$pid]['options_nonblocking'],
  );
  $form['purge_proxy_advanced']['domains'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domains'),
  );

  // Parse the domains
  $custom_domains = array();
  $domains = array();
  $domains = $proxy[$pid]['domains'];
  $domain_set = array();
  $domain_set['expire'] = False;
  $domain_set['drupal'] = False;
  $domain_set['current'] = False;
  $domain_modules = module_implements('purge_domains');
  foreach ($domain_modules as $domain_module) {
    $domain_set[$domain_module] = False;
  }
  foreach ($domains as $domain) {
    if ($domain_set['current'] == False) {
      // Check the Expire option
      if ($domain == '_DOMAINS_EXPIRE_') {
        $domain_set['expire'] = True;
      }
      Elseif ($domain == '_DOMAINS_DRUPAL_') {
        $domain_set['drupal'] = True;
      }
      // Check is this is the start of a set
      Elseif (!strncmp($domain, '_begin_set:', 11)) {
        $domain_module = substr($domain, 11);
        $domain_set[$domain_module] = True;
        $domain_set['current'] = True;
      }
      // Add all other headers as custom headers
      else {
        $custom_domains[] = $domain;
      }
    }
    Else {
      if (!strncmp($domain, '_end_set:', 9)) {
        $domain_set['current'] = False;
      }
    }
  }
  $form['purge_proxy_advanced']['domains']['drupal'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domain from Drupal'),
    '#description' => t('Get domain names from Drupal. Used as default when no other hostnames are provided.'),
    '#default_value' => $domain_set['drupal'],
  );
  $form['purge_proxy_advanced']['domains']['expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domains from Expire'),
    '#description' => t('Get domain names from the Expire module or other modules implementing its hooks.'),
    '#default_value' => $domain_set['expire'],
  );

  // Generate a form checkbox for each module providing domains
  foreach ($domain_modules as $domain_module) {
    $form['purge_proxy_advanced']['domains'][$domain_module] = array(
      '#type' => 'checkbox',
      '#title' => t($domain_module),
      '#description' => t('Set domains prodived by the @module module.', array('@module' => $domain_module)),
      '#default_value' => $domain_set[$domain_module],
    );
  }
  $form['purge_proxy_advanced']['domains']['custom_domains'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom domains'),
    '#description' => t('Provide custom domains to be purged with each request. One line each'),
    '#default_value' => implode("\n", $custom_domains),
  );
  $form['purge_proxy_advanced']['urls'] = array(
    '#type' => 'fieldset',
    '#title' => t('URLs'),
  );
  $form['purge_proxy_advanced']['urls']['prefix'] = array(
    '#type' => 'textfield',
    '#title' => t('URL prefix'),
    '#description' => t('This prefix will be inserted before the purge URL.'),
    '#default_value' => $proxy[$pid]['url_prefix'],
  );
  $form['purge_proxy_advanced']['urls']['wildcards'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow wildcards'),
    '#description' => t('Setting to indicate the proxy accepts wildcards. (Experimental)'),
    '#default_value' => $proxy[$pid]['url_wildcards'],
  );
  $form['purge_proxy_advanced']['headers'] = array(
    '#type' => 'fieldset',
    '#title' => t('Headers'),
  );

  // Parse all headers
  $custom_headers = array();
  $headers = array();
  $headers = $proxy[$pid]['headers'];
  $headers_set = array();
  $headers_set['gzip'] = False;
  $headers_set['current'] = False;
  $header_modules = module_implements('purge_headers');
  foreach ($header_modules as $header_module) {
    $headers_set[$header_module] = False;
  }
  foreach ($headers as $header) {
    if ($headers_set['current'] == False) {
      // Check the gzip option
      if ($header == 'Accept-Encoding: gzip') {
        $headers_set['gzip'] = True;
      }
      // Check is this is the start of a set
      Elseif (!strncmp($header, '_begin_set:', 11)) {
        $header_module = substr($header, 11);
        $headers_set[$header_module] = True;
        $headers_set['current'] = True;
      }
      // Add all other headers as custom headers
      else {
        $custom_headers[] = $header;
      }
    } 
    Else {
      if (!strncmp($header, '_end_set:', 9)) {
        $headers_set['current'] = False;
      }
    }
  }

  $form['purge_proxy_advanced']['headers']['gzip'] = array(
    '#type' => 'checkbox',
    '#title' => t('Gzip'),
    '#description' => t('Set headers to accept gzip compression.'),
    '#default_value' => $headers_set['gzip'],
  );
  // Generate a form checkbox for each module providing headers
  foreach ($header_modules as $header_module) {
    $form['purge_proxy_advanced']['headers'][$header_module] = array(
      '#type' => 'checkbox',
      '#title' => t($header_module),
      '#description' => t('Set headers prodived by the @module module.', array('@module' => $header_module)),
      '#default_value' => $headers_set[$header_module],
    );
  }
  $form['purge_proxy_advanced']['headers']['custom_headers'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom headers'),
    '#description' => t('Provide custom headers to be added to each request. One line each'),
    '#default_value' => implode("\n", $custom_headers),
  );

  // Action buttons
  $form['actions'] = array(
    '#type' => 'actions'
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#submit' => array('purge_proxy_form_submit'),
  );
  // Render an option to submit and enable the configuration 
  if ($proxy[$pid]['enabled'] == False) {
    $form['actions']['submit_enable'] = array(
      '#type' => 'submit',
      '#value' => t('Save and Enable'),
      '#submit' => array('purge_proxy_form_submit', 'purge_proxy_form_enable'),
    );
    $form['actions']['enable'] = array(
      '#type' => 'submit',
      '#value' => t('Enable'),
      '#submit' => array('purge_proxy_enable_form'),
    );
  }
  else {
    $form['actions']['disable'] = array(
      '#type' => 'submit',
      '#value' => t('Disable'),
      '#submit' => array('purge_proxy_disable_form'),
    );
  }
  $form['actions']['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
    '#submit' => array('purge_proxy_delete_form'),
  );
  return $form;
}

/**
 * Function to determine if a proxy configuration name is allready taken
 */
function purge_proxy_name_exists($value) {
  $exists = FALSE;
  $proxies = purge_proxy_get_config(0, 'NAME', 'SYSTEM');
  foreach ($proxies as $proxy) {
    if ($proxy['name'] == $value) {
      $exists = TRUE;
    }
  }
  return $exists;
}

function purge_proxy_form_validate($form, &$form_state) {
  // Validate the name. should be a real machine_name field
  if ($form_state['values']['name'] == '') {
    form_set_error('', t('You must select a name for this proxy configuration.'));
  }
  // Check for use base url
  if ($form_state['values']['use_base_url'] == False) {
    // Check if hostname is unset
    if ($form_state['values']['host'] == '') {
      form_set_error('', t('You should provide a host name or ipaddress when not using the Drupal option'));
    }
    else {
      // Check the hostname
      if ((purge_validate_hostname($form_state['values']['host'])) == False) {
        form_set_error('', t('This is not a valid hostname or ipaddress.'));
      }
    }
  }
  // Check the portname
  if (!($form_state['values']['port'] == '')) {
    if ((is_numeric($form_state['values']['port'])) == False) {
      form_set_error('', t('This is not a valid port number.'));
    }
  }
  // Check all domains
  // dprint_r($form_state['values']['custom_domains']);
  if ((trim($form_state['values']['custom_domains'])) != '') {
    $domains = preg_split('/$\R?^/m', $form_state['values']['custom_domains']);
    $line = 1;
    foreach($domains as $domain) {
      if ((purge_validate_hostname(trim($domain))) == False) {
        form_set_error('', t('There is an invalid custom domain %domain on line number @line', array('%domain' => check_plain($domain), '@line' => $line)));
      }
      $line = ($line + 1);
    }
  }
  // Check headers
}

/**
 * Submit the proxy configuration
 */
function purge_proxy_form_submit($form, &$form_state) {
  // Render the proxy_host field
  if ($form_state['values']['use_base_url'] == 1) {
    $proxy_host = '_USE_BASEURL_';
  }
  else {
    $proxy_host = trim($form_state['values']['host']);
  }
  // Generate the storage field for the domains
  $domains = array();
  // Check for the Drupal and Expire options
  if ($form_state['values']['drupal'] == 1) {
    $domains[] = '_DOMAINS_DRUPAL_';
  }
  if ($form_state['values']['expire'] == 1) {
    $domains[] = '_DOMAINS_EXPIRE_';
  }

  // Add domains provided by modules
  $domain_modules = module_implements('purge_domains');
  foreach ($domain_modules as $domain_module) {
    if ($form_state['values'][$domain_module] == True) {
      $domains[] = '_begin_set:' . $domain_module;
      $module_domains = module_invoke($domain_module, 'purge_domains');
        foreach ($module_domains as $module_domain) {
          $domains[] = $module_domain;
        }
      $domains[] = '_end_set:' . $domain_module;
    }
  }

  // Add the custom headers from the formfield
  $custom_domains = preg_split('/$\R?^/m', $form_state['values']['custom_domains']);
  foreach ($custom_domains as $domain) {
    if ((trim($domain)) != '') {
      $domains[] = trim($domain);
    }
  }

  // Generate the headers field
  $headers = array();
  $custom_headers = preg_split('/$\R?^/m', $form_state['values']['custom_headers']);
  foreach ($custom_headers as $header) {
    if ((trim($header)) != '') {
      $headers[] = trim($header);
    }
  }
  // Add the gzip header if set.
  if ($form_state['values']['gzip'] == 1) {
    $headers[] = 'Accept-Encoding: gzip';
  }
  // Add headers provided by modules
  $header_modules = module_implements('purge_headers');
  foreach ($header_modules as $header_module) {
    if ($form_state['values'][$header_module] == True) {
      $headers[] = '_begin_set:' . $header_module;
      $module_headers = module_invoke($header_module, 'purge_headers');
        foreach ($module_headers as $module_header) {
          $headers[] = $module_header;
        }
      $headers[] = '_end_set:' . $header_module;
    }
  }

  // See if the current form is filled from the empty template.
  if ($form_state['values']['purge_pid'] == 1) {
    // Insert a new record into the proxy configuration database table
    $new_proxy_config = db_insert('purge_proxy')
      ->fields(array('type', 'name', 'description', 'enabled', 'active', 'proxy_ssl', 'proxy_host', 'proxy_port', 'options_type',
        'options_parallel', 'options_nonblocking', 'domains', 'url_prefix', 'url_wildcards', 'headers'))
      ->values(array(
      'type' => 4, //Type 4 indicates this is a user controled configuration
      'name' => $form_state['values']['name'],
      'description' => check_plain($form_state['values']['description']),
      'enabled' => 1, // Configurations are enabled by default
      'active' => $form_state['values']['purge_proxy_active'],
      'proxy_ssl' => $form_state['values']['protocol'],
      'proxy_host' => $proxy_host,
      'proxy_port' => check_plain($form_state['values']['port']),
      'options_type' => $form_state['values']['type'],
      'options_parallel' => $form_state['values']['parallel'],
      'options_nonblocking' => $form_state['values']['nonblocking'],
      'domains' => implode(",", $domains),
      'url_prefix' => check_plain($form_state['values']['prefix']),
      'url_wildcards' => $form_state['values']['wildcards'],
      'headers' => implode(",", $headers),
    ))
    ->execute();
  }
  else {
    // Update the existing record
    $proxy_config = db_update('purge_proxy')
      ->fields(array(
        'name' => $form_state['values']['name'],
        'description' => check_plain($form_state['values']['description']),
        'proxy_ssl' => $form_state['values']['protocol'],
        'proxy_host' => $proxy_host,
        'proxy_port' => check_plain($form_state['values']['port']),
        'options_type' => $form_state['values']['type'],
        'options_parallel' => $form_state['values']['parallel'],
        'options_nonblocking' => $form_state['values']['nonblocking'],
        'domains' => implode(",", $domains),
        'url_prefix' => check_plain($form_state['values']['prefix']),
        'url_wildcards' => $form_state['values']['wildcards'],
        'headers' => implode(",", $headers),
      ))
      ->condition('pid', $form_state['values']['purge_pid'])
      ->execute();
  }
  drupal_set_message(t('The proxy configuration %proxy_name has been saved', array('%proxy_name' => $form_state['values']['name'])));
}

/**
 * Callback to enable a proxy configuration
 */
function purge_proxy_enable_form($form, &$form_state) {
  purge_proxy_enable($form_state['values']['purge_pid'], 1);
}

/**
 * Callback to disable a proxy configuration
 */
function purge_proxy_disable_form($form, &$form_state) {
  purge_proxy_enable($form_state['values']['purge_pid'], 0);
}

/**
 * Callback to delete a proxy configuration
 */
function purge_proxy_delete_form($form, &$form_state, $pid = NULL) {
//  if (isset($form_state['values']['process'])) {
  if (is_null($pid)) {
    if (isset($form_state['values']['purge_pid'])) {
      if ($form_state['values']['purge_pid'] > 1) {
        $pid = $form_state['values']['purge_pid'];
      }
    }
  }
  purge_proxy_delete($pid);
 //   return drupal_goto('admin/config/system/purge/proxy');
  //}
  //else {
  //  return purge_proxy_delete_form_confirm($form, &$form_state);
  //}
}

/**
 * Confirm deletion of a proxy configuration
 */
function purge_proxy_delete_form_confirm($form, &$form_state) {
  $form['process'] = array('#type' => 'hidden', '#value' => 'true');
  return confirm_form($form,
    t('Are you sure you want to delete %proxy_name?'), array('%proxy_name' => $form_state['values']['name']),
    t('admin/config/system/purge/proxy'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Menu callback for purge proxy configuration list
 */
function purge_proxy_list() {
  $rows = array();
  $header = array(t('Name'), t('Description'), t('Status'), t('Actions'));
  $proxies = purge_proxy_get_config(0, 'LIST', 'ALL');
  foreach ($proxies as $proxy) {
    // Get the proxy configuration name and description
    $row = array();
    $row[] = $proxy['name'];
    $row[] = $proxy['description'];
    // Generate the status and action fields 
    $status = '';
    $actions = array();
    // See if this is a template
    if ($proxy['type'] == 2) {
      $status = t('Template');
    }
    else {
      if ($proxy['enabled']) {
        $status = $status . t('Enabled');
        $actions[] = l(t('Disable'), "admin/config/development/performance/purge/disable/{$proxy['pid']}");
      }
      else {
        $status = $status . t('Disabled');
        $actions[] = l(t('Enable'), "admin/config/development/performance/purge/enable/{$proxy['pid']}");
      }
      // Add actions for edit and delete
      $actions[] = l(t('Edit'), "admin/config/development/performance/purge/edit/{$proxy['pid']}");
      $actions[] = l(t('Delete'), "admin/config/development/performance/purge/delete/{$proxy['pid']}");
    }

    $row[] = $status;
    $row[] = implode(' | ', $actions);
    $rows[] = $row;
  }
  // Output themed table
  $proxy_list = theme('table', array('header' => $header, 'rows' => $rows));
  //print($output);
  return $proxy_list;
}

/**
 * Helper function to validate hostnames
 */
function purge_validate_hostname($hostname) {
  $pieces = explode(".",$hostname);
  foreach($pieces as $piece) {
    if (!preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $piece) || preg_match('/-$/', $piece) ) {
      return false;
    }
  }
  return true;
}

/**
 * Helper function to enable or disable proxy configurations
 */
function purge_proxy_enable($pid, $value = 1) {
  // Update the database field
  $query = db_update('purge_proxy')
    ->fields(array('enabled' => $value))
    ->condition('pid', $pid)
    ->execute();
  // Get the name of the proxy configuration
  $proxy_config = purge_proxy_get_config($pid, 'NAME', 'ALL');
  $proxy_name = $proxy_config[$pid]['name'];
  // Set the action label
  if ($value == 1) {
    $action = 'enabled';
  }
  else {
    $action = 'disabled';
  }
  drupal_set_message(t('The proxy configuration %proxy_name has been @action.', array('%proxy_name' => $proxy_name, '@action' => $action)));
  $ouput = drupal_goto('admin/config/development/performance/purge');
  return $output;
}

/**
 * Helper function to delete proxy configurations
 */
function purge_proxy_delete($pid) {
  // Get the name of the proxy configuration
  $proxy_config = purge_proxy_get_config($pid, 'NAME', 'ALL');
  $proxy_name = $proxy_config[$pid]['name'];
  // Delete the configuration from the database
  $query = db_delete('purge_proxy')
    ->condition('pid', $pid)
    ->execute();
  drupal_set_message(t('The proxy configuration %proxy_name has been deleted.', array('%proxy_name' => $proxy_name)));
  $ouput = drupal_goto('admin/config/development/performance/purge');
  return $output;
}

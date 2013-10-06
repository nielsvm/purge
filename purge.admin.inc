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
  $form = array();
  // Form and overview of al proxy configurations
  $header = array(
    'name' => t('Name'),
    'type' => t('Type'),
    'description' => t('Description'),
    'actions' => t('Actions'),
  );
  $proxy_options = array();
  $proxies = purge_config_db_get();
  foreach ($proxies as $proxy) {
    $proxy_options[$proxy->pid] = array(
      'name' => $proxy->name,
      'type' => $proxy->type,
      'description' => $proxy->description,
      'actions' => t('nothing'),
    );
  }

  $form['purge_proxies'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $proxy_options,
  );

  // Advanced Settings
  $form['purge_advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced Settings'),
  );
  // Get the http request handlers
  $handlers = purge_get_request_handlers();
  $options = array();
  foreach ($handlers as $handler) {
    $options[($handler['mname'])] = $handler['hname'];
  }

  $form['purge_advanced']['purge_handler'] = array(
    '#type' => 'select',
    '#title' => 'HTTP request handler',
    '#options' => $options,
    '#default_value' => variable_get('purge_handler', 'drupal'),
    '#description' => t('Select the HTTP request handler to use.'),
  );
  $form['purge_advanced']['purge_watchdog'] = array(
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
  }
  // Check for httprl module
  if (module_exists('httprl')) {
    $handlers[8]['mname'] = 'purge_httprl';
    $handlers[8]['hname'] = 'HTTPrl Module';
    $handlers[8]['options'] = '_PARALLEL_,_NONBLOCKING_';
  }
  // Check for modules implementing handlers
  $handler_modules = module_implements('purge_handlers');
  // Invoke the hook for each module
  foreach ($handler_modules as $handler_module) {
    $module_handlers = module_invoke($handler_module, 'purge_handlers');
    // Add the returned array of handlers to the list
    $handlers = array_merge($handlers, $module_handlers);
  }
  return $handlers;
}

/**
 * Menu callback for purge proxy configuration.
 */
function purge_proxy_form($form, $form_state, $pid = 0) {
  // Load the proxy configuration for editing.
  $proxies = purge_config_db_get(array($pid));
  $proxy = $proxies[$pid];
  //dprint_r($proxy);
  $form = array();
  // Hidden fields for internal use
  $form['purge_pid'] = array(
    '#type' => 'hidden',
    '#default_value' => $proxy->pid,
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
    '#default_value' => check_plain($proxy->name),
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
    '#default_value' => check_plain($proxy->description),
    '#description' => t('A human readable description of this proxy configuration.'),
  );
  // Construct selection form for proxy url configuration.
  $form['purge_proxy_url'] = array(
    '#type' => 'fieldset',
    '#title' => 'Proxy URL',
  );
  $proxy_url_config_options = array(
    0 => t('Use the base URL provided by Drupal'),
    1 => t('Compose the URL by components'),
    2 => t('Manual configuration (Advanced)'),
  );
  // Check to use the base URL or not.
  if ($proxy->url == '_USE_BASEURL_') {
    $proxy_url_config_default = 0;
  }
  else {
    $proxy_url_config_default = 1;
  }
  $form['purge_proxy_url']['purge_proxy_url_options'] = array(
    '#type' => 'radios',
    '#default_value' => $proxy_url_config_default,
    '#description' => 'Select how to configure the proxy URL.',
    '#options' => $proxy_url_config_options,
  );
  // The compose option
  $form['purge_proxy_url']['compose'] = array(
    '#type' => 'fieldset',
  );
  // Parse the proxy url
  $proxy_url_parts = parse_url($proxy->url);

  // Protocol selection
  $proxy_url_protocol = 'http';
  if ($proxy_url_parts['scheme'] == 'https') {
    $proxy_url_protocol = 'https';
  }
  $form['purge_proxy_url']['compose']['protocol'] = array(
    '#type' => 'select',
    '#title' => t('Protocol'),
    '#description' => t('The request protocol the proxy server accepts purge requests in.'),
    '#options' => array(
      '0' => 'http',
      '1' => 'https',
    ),
    '#default_value' => $proxy_url_protocol,
  );
  $form['purge_proxy_url']['compose']['host'] = array(
    '#type' => 'textfield',
    '#title' => t('Host'),
    '#description' => t('The hostname or ip adress of the proxy server.'),
    '#default_value' => $proxy_url_parts['host'],
  );
  // Optional proxy port
  if (isset($proxy_url_parts['port'])) {
    $proxy_url_port = strval($proxy_url_parts['port']);
  }
  else {
    $proxy_url_port = '';
  }
  $form['purge_proxy_url']['compose']['port'] = array(
    '#type' => 'textfield',
    '#title' => t('Port'),
    '#description' => t('The tcp port where the proxy is listening.'),
    '#default_value' => $proxy_url_port,
  );
  // Optional url prefix
  if (isset($proxy_url_parts['path'])) {
    $proxy_url_prefix = $proxy_url_parts['path'];
  }
  else {
    $proxy_url_prefix = '';
  }
  $form['purge_proxy_url']['compose']['prefix'] = array(
    '#type' => 'textfield',
    '#title' => t('URL Prefix'),
    '#description' => t('This prefix will be added before the URL that will be purged.'),
    '#default_value' => $proxy_url_prefix,
  );
  $form['purge_proxy_url']['manual'] = array(
    '#type' => 'fieldset',
  );
  $form['purge_proxy_url']['manual']['url'] = array(
    '#type' => 'textfield',
    '#title' => t('Proxy URL'),
    '#description' => t('The URL that will be used to send out purge requests.'),
    '#default_value' => $proxy->url,
  );
  $form['purge_proxy_advanced'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Advanced Proxy Configuration'),
  );
  // Options, options... Sooo many options...
  $form['purge_proxy_advanced']['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Options'),
  );
  // Get the http method
  if (in_array('_METHOD_GET_', $proxy->options)) {
    $proxy_method = 'GET';
  }
  elseif (in_array('_METHOD_BAN_', $proxy->options)) {
    $proxy_method = 'BAN';
  }
  else {
    $proxy_method = 'PURGE';
  }
  $form['purge_proxy_advanced']['options']['type'] = array(
    '#type' => 'select',
    '#title' => t('Request type'),
    '#description' => t('The type of http request issued. Either PURGE, GET or BAN.'),
    '#options' => array(
      'PURGE' => 'Purge',
      'GET' => 'Get',
      'BAN' => 'Ban',
    ),
    '#default_value' => $proxy_method,
  );
  $form['purge_proxy_advanced']['options']['parallel'] = array(
    '#type' => 'checkbox',
    '#title' => t('Parallel requests'),
    '#description' => t('Purge requests are send in parallel. Disable to send requests on at a time.'),
    '#default_value' => in_array('_PARALLEL_', $proxy->options),
  );
  $form['purge_proxy_advanced']['options']['nonblocking'] = array(
    '#type' => 'checkbox',
    '#title' => t('Non blocking'),
    '#description' => t('Send non blocking purge requests.'),
    '#default_value' => in_array('_NON_BLOCKING_', $proxy->options),
  );
  $form['purge_proxy_advanced']['domains'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domains'),
  );

  // Parse the domains
  $custom_domains = array();
  $domains = array();
  $domains = $proxy->domains;
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
  $form['purge_proxy_advanced']['headers'] = array(
    '#type' => 'fieldset',
    '#title' => t('Headers'),
  );

  // Parse all headers
  $custom_headers = array();
  $headers = array();
  $headers = $proxy->headers;
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
  if (TRUE) {
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
  $proxies = purge_config_db_get();
  foreach ($proxies as $proxy) {
    if ($proxy->name == $value) {
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
  if ($form_state['values']['purge_proxy_url_options'] != 0) {
    // Check the hostname
    if ($form_state['values']['purge_proxy_url_options'] == 1) {
      $proxy_host = $form_state['values']['host'];
      $proxy_port = $form_state['values']['port'];
    }
    elseif ($form_state['values']['purge_proxy_url_options'] == 2) {
      $proxy_url_parts = parse_url($form_state['values']['url']);
      $proxy_host = $proxy_url_parts['host'];
      $proxy_port = strval($proxy_url_parts['port']);
    }
    // Check if hostname is unset
    if ($proxy_host == '') {
      form_set_error('', t('You should provide a host name or ipaddress when not using the Drupal option'));
    }
    else {
      // Check the hostname
      if ((purge_validate_hostname($proxy_host)) == False) {
        form_set_error('', t('This is not a valid hostname or ipaddress.'));
      }
    }
    // Check the portname
    if (!($proxy_port == '')) {
      if ((is_numeric($proxy_port)) == False) {
        form_set_error('', t('This is not a valid port number.'));
      }
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
  // Render proxy url field
  if ($form_state['values']['purge_proxy_url_options'] == 0) {
    $proxy_url = '_USE_BASEURL_';
  }
  // Check for compose option and render URL
  elseif ($form_state['values']['purge_proxy_url_options'] == 1) {
    $proxy_url = $form_state['values']['protocol'] . '://';
    $proxy_url .= $form_state['values']['host'];
    // Add the port when set
    if ($form_state['values']['port'] != '') {
      $proxy_url .= ':' . $form_state['values']['port'];
    }
  }
  // Check for manual option and get URL
  elseif ($form_state['values']['purge_proxy_url_options'] == 2) {
    $proxy_url = trim($form_state['values']['url']);
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

  // Gather options
  $proxy_options = array();
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

  // Create a PurgeProxy object
  $proxy = new PurgeProxy;
  // Check if we need to specify pid
  if ($form_state['values']['purge_pid'] > 0) {
    $proxy->pid = $form_state['values']['purge_pid'];
  }
  // Set the type of configuration
  $proxy->type = $form_state['values']['type'];
  $proxy->name = $form_state['values']['name'];
  $proxy->description = $form_state['values']['description'];
  $proxy->url = $proxy_url;
  $proxy->options = $proxy_options;
  $proxy->domains = $domains;
  $proxy->headers = $headers;
  // Save the proxy object
  $save = $proxy->db_save();
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

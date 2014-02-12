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
    // Filter out only the user managed configurations
    if (strpos($proxy->type, '_user')) {
      // Render the action links
      $actions = array();
      $actions[] = l(t('Edit'), "admin/config/development/performance/purge/edit/{$proxy->pid}");
      $actions[] = l(t('Delete'), "admin/config/development/performance/purge/delete/{$proxy->pid}");
      $proxy_options[$proxy->pid] = array(
        'name' => $proxy->name,
        'type' => $proxy->type,
        'description' => $proxy->description,
        'actions' => implode(' | ', $actions),
      );
    }
  }
  $form['purge_proxies'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $proxy_options,
    '#default_value' => variable_get('purge_proxies'),
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
 * Menu callback for template selection form.
 */
function purge_template_form($form, $form_state) {
  // Start the form with a header.
  $form = array();
  $header = array(
    'name' => t('Name'),
    'type' => t('Type'),
    'description' => t('Description'),
  );
  // Gather options
  $options = array();
  // Add an option for an blank configuration
  $options[0] = array(
    'name' => t('Empty Template'),
    'type' => 'purge_template',
    'description' => t('Start with a completely blank configuration.')
  );
  $proxies = purge_config_db_get();
  foreach ($proxies as $proxy) {
    // Filter out only the user managed configurations
    if (strpos($proxy->type, '_template')) {
      $options[$proxy->pid] = array(
        'name' => $proxy->name,
        'type' => $proxy->type,
        'description' => $proxy->description,
      );
    }
  }

  $form['purge_template'] = array(
    '#type' => 'tableselect',
    '#title' => t('Select a template to start with.'),
    '#header' => $header,
    '#options' => $options,
    '#multiple' => FALSE,
    '#default_value' => 1,
  );
  // Add the actions
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Select'),
    '#submit' => array('purge_template_form_submit'),
  );
  return $form;
}

/**
 * Callback for the select button on the template form.
 */
function purge_template_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/development/performance/purge/add/' . $form_state['values']['purge_template'];
}

/**
 * Menu callback for purge proxy configuration.
 */
function purge_proxy_form($form, $form_state, $pid = 0, $form_action = 'add') {
  // dprint_r($form_action);
  // Load the proxy configuration for editing.
  if ($pid == 0) {
    $proxy = new PurgeProxy();
  }
  else {
    $proxies = purge_config_db_get(array($pid));
    $proxy = $proxies[0];
  }
  if ($form_action == 'add') {
    $proxy->name = '';
    $proxy->description = '';
  }
  $form = array();
  // Hidden fields for internal use
  $form['form_action'] = array(
    '#type' => 'hidden',
    '#default_value' => $form_action,
  );
  $form['purge_pid'] = array(
    '#type' => 'hidden',
    '#default_value' => $proxy->pid,
  );
  $form['purge_type'] = array(
    '#type' => 'hidden',
    '#value' => $proxy->type,
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
    '#states' => array(
      'visible' => array(
        ':input[name="purge_proxy_url_options"]' => array('value' => 1),
      ),
    ),
  );
  // Parse the proxy url
  $proxy_url_parts = parse_url($proxy->url);

  // Protocol selection
  $proxy_url_protocol = 0;
  if ($proxy_url_parts['scheme'] == 'https') {
    $proxy_url_protocol = 0;
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
    '#states' => array(
      'visible' => array(
        ':input[name="purge_proxy_url_options"]' => array('value' => 2),
      ),
    ),
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
  $form['purge_proxy_advanced']['options']['method'] = array(
    '#type' => 'select',
    '#title' => t('Request method'),
    '#description' => t('The method of http request used. Either PURGE, GET or BAN.'),
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
  // The domains section
  $form['purge_proxy_advanced']['domains'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domains'),
  );

  $form['purge_proxy_advanced']['domains']['drupal'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domain from Drupal'),
    '#description' => t('Get domain names from Drupal. Used as default when no other hostnames are provided.'),
    '#default_value' => array_key_exists('drupal', $proxy->domain_sets),
  );
  $form['purge_proxy_advanced']['domains']['expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domains from Expire'),
    '#description' => t('Get domain names from the Expire module or other modules implementing its hooks.'),
    '#default_value' => array_key_exists('expire', $proxy->domain_sets),
  );

  // Generate a form checkbox for each module providing domains
  $domain_modules = module_implements('purge_domains');
  dprint_r($domain_modules);
  foreach ($domain_modules as $domain_module) {
    $domain_module_enabled = FALSE;
    foreach ($proxy->domain_sets as $domain_set_key => $domain_set) {
      // Check if there is a domain set that starts with the name if the module
      if (strncmp($domain_set_key, $domain_module, strlen($domain_module))) {
        // set it as enabled
       // $domain_module_enabled = TRUE;
      }
    }
    // Now generate a checkbox for this module
    $form['purge_proxy_advanced']['domains'][$domain_module] = array(
      '#type' => 'checkbox',
      '#title' => t($domain_module),
      '#description' => t('Set domains prodived by the @module module.', array('@module' => $domain_module)),
      '#default_value' => $domain_module_enabled,
    );
  }
  $domains_custom = '';
  dprint_r($proxy->domain_sets->purge_custom->domains);
  if (array_key_exists('purge_custom', $proxy->domain_sets)) {
    if ($proxy->domain_sets->purge_custom->domains != '') {
      $domains_custom = implode("\n", $proxy->domain_sets->purge_custom->domains);
    }
  }
  $form['purge_proxy_advanced']['domains']['custom_domains'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom domains'),
    '#description' => t('Provide custom domains to be purged with each request. One line each'),
    '#default_value' => $domains_custom,
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
  $proxy_enabled = FALSE;
  if ($form_action == 'edit') {
    $proxies_enabled = variable_get('purge_proxies', array(0 => 0));
    if (in_array($proxy->pid, $proxies_enabled)) {
      if ($proxies_enabled[$proxy->pid] == $proxy->pid) {
        $proxy_enabled = TRUE;
      }
    }
  }
  if ($proxy_enabled == 0) {
    $form['actions']['submit_enable'] = array(
      '#type' => 'submit',
      '#value' => t('Save and Enable'),
      '#submit' => array('purge_proxy_form_submit', 'purge_proxy_enable_form'),
    );
  }
  if ($form_action == 'edit') {
    if ($proxy_enabled == 0) {
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
  }
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
 * Submit the proxy configuration.
 */
function purge_proxy_form_submit($form, &$form_state) {
  // Render proxy url field
  if ($form_state['values']['purge_proxy_url_options'] == 0) {
    $proxy_url = '_USE_BASEURL_';
  }
  // Check for compose option and render URL
  elseif ($form_state['values']['purge_proxy_url_options'] == 1) {
    if ($form_state['values']['protocol'] == 1) {
      $proxy_protocol = 'https';
    }
    else {
      $proxy_protocol = 'http';
    }
    $proxy_url = $proxy_protocol . '://';
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
  // Gather options
  $proxy_options = array();
  if ($form_state['values']['method'] == 'GET') {
    $proxy_options[] = '_METHOD_GET_';
  }
  elseif ($form_state['values']['method'] == 'BAN') {
    $proxy_options[] = '_METHOD_BAN_';
  }

  // Generate the storage field for the domains
  $domain_sets = array();
  // Check for the Drupal and Expire options
  if ($form_state['values']['drupal'] == 1) {
    $domain_sets['drupal'] = array(
      'dynamic' => TRUE,
      'callback' => 'purge_domains_drupal',
    );
  }
  if ($form_state['values']['expire'] == 1) {
    $domain_sets['expire'] = array(
      'dynamic' => TRUE,
      'callback' => 'purge_domains_expire',
    );
  }

  // Add domains provided by modules
  $domain_modules = module_implements('purge_domains');
  foreach ($domain_modules as $domain_module) {
    if ($form_state['values'][$domain_module] == True) {
      $module_domain_sets = module_invoke($domain_module, 'purge_domains');
      $domain_sets = array_merge($domain_sets, $module_domain_sets);
    }
  }

  // Add the custom domains from the formfield
  $custom_domains = preg_split('/$\R?^/m', $form_state['values']['custom_domains']);
  if (count($custom_domains > 0)) {
    foreach ($custom_domains as $domain) {
      if ((trim($domain)) != '') {
        $domains[] = trim($domain);
      }
    }
    $domain_sets['purge_custom'] = array(
      'domains' => $domains,
    );
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
  if ($form_state['values']['form_action'] == 'edit') {
    $proxy->pid = $form_state['values']['purge_pid'];
  }
  // Set the type of configuration
  $proxy->type = str_replace('_template', '_user', $form_state['values']['purge_type']);
  $proxy->name = $form_state['values']['name'];
  $proxy->description = $form_state['values']['description'];
  $proxy->url = $proxy_url;
  $proxy->options = $proxy_options;
  $proxy->domain_sets = $domain_sets;
  $proxy->headers = $headers;
  // Save the proxy object
  $save = $proxy->db_save();
  drupal_set_message(t('The proxy configuration %proxy_name has been saved', array('%proxy_name' => $form_state['values']['name'])));
}

/**
 * Callback to enable a proxy configuration.
 */
function purge_proxy_enable_form($form, &$form_state) {
  purge_proxy_enable($form_state['values']['purge_pid'], 1);
}

/**
 * Callback to disable a proxy configuration.
 */
function purge_proxy_disable_form($form, &$form_state) {
  purge_proxy_enable($form_state['values']['purge_pid'], 0);
}

/**
 * Callback to delete a proxy configuration
 */
function purge_proxy_delete_form($form, &$form_state, $pid = NULL) {
  if (!isset($form_state['values']['purge_pid'])) {
    // Hidden fields for internal use
    $form['purge_pid'] = array(
      '#type' => 'hidden',
      '#default_value' => $pid,
    );
    $form_state['values']['purge_pid'] = $pid;
  }
  $form['purge_proxy_delete'] = array(
    '#title' => t('Confirm Proxy Deletion'),
  );
  return confirm_form($form, t('Deleting node'), t('Are you sure?'), t('Delete'));
}

/**
 * Confirm deletion of a proxy configuration
 */
function purge_proxy_delete_form_submit($form, &$form_state) {
  purge_proxy_delete($form_state['values']['purge_pid']);
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
  // Get the current set
  $proxies_enabled = variable_get('purge_proxies');
  if ($value == 0) {
    $proxies_enabled[$pid] = 0;
  }
  elseif ($value == 1) {
    $proxies_enabled[$pid] = $pid;
  }
  $save = variable_set('purge_proxies', $proxies_enabled);
  // Get the name of the proxy configuration
  $proxies = purge_config_db_get(array($pid));
  $proxy = $proxies[0];
  $proxy_name = $proxy->name;
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
  // Delete the configuration from the database
  $proxy_name = ' that was a goner anyway ';
  $query = db_delete('purge_proxy')
    ->condition('pid', $pid)
    ->execute();
  drupal_set_message(t('The proxy configuration %proxy_name has been deleted.', array('%proxy_name' => $proxy_name)));
  $ouput = drupal_goto('admin/config/development/performance/purge');
  return $output;
}

/**
 * Menu Callback for the Targets overview page
 */
function purge_targets_form($form, &$form_state) {
  // Get all the target sets
  $target_sets = array();
  $target_sets = purge_targets_getall();
  dprint_r($target_sets);
  foreach ($target_sets as $tid => $target_set) {
    // Don't show templates
    if (!strpos($target_set['type'], '_template')) {
      // Determine actions available for this target set.
      $actions = array();
    // Check if this is a user configurable type
      if (strpos($target_set['type'],  '_user:0')) {
        $actions[] = l(t('Edit'), "admin/config/development/performance/purge/target/edit/{$tid}");
        $actions[] = l(t('Clone'), "admin/config/development/performance/purge/target/clone/{$tid}");
        $actions[] = l(t('Delete'), "admin/config/development/performance/purge/target/delete/{$tid}");
      }
      // Check if this is a static set of another type.
      elseif (strpos($target_set['type'], ':0')) {
        $actions[] = l(t('Reset'), "admin/config/development/performance/purge/target/reset/{$tid}");
      }
      $form_rows[$tid] = array(
        'name' => $target_set['name'],
        'desc' => $target_set['desc'],
        'actions' => implode(' | ', $actions),
      );
    }
  }
  // Get state
  $state = purge_targets_state($target_sets);
  dprint_r($state);
  // Get the form table up there.
  $form['purge_targets'] = array(
    '#type' => 'tableselect',
    '#title' => t('Purge Targets'),
    '#header' => array(
      'name' => t('Name'),
      'desc' => t('Description'),
      'actions' => t('Actions'),
    ),
    '#options' => $form_rows,
    '#default_value' => purge_targets_state($target_sets),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Changes'),
  );
  return $form;
}

/**
 * Menu callback for the form to view, edit or clone a target.
 */
function purge_target_form($form, $form_state, $tid, $form_action = 'view') {
  // Get the current target configuration.
  $targets = purge_targets_getall();
  $target = $targets[$tid];
  dprint_r($target);
  // Display the form.
  $form = array();
  $form['target_action'] = array(
    '#type' => 'hidden',
    '#default_value' => $form_action,
  );
  $form['target_id'] = array(
    '#type' => 'hidden',
    '#default_value' => $tid,
  );
  $form['target_type'] = array(
    '#type' => 'hidden',
    '#default_value' => $target['type'],
  );
  $form['target_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#required' => TRUE,
    '#default_value' => $target['name'],
  );
  $form['target_desc'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => $target['desc'],
  );
  // Render the URLs array as a list.
  $target_urls = implode("\n", $target['urls']);
  $form['target_urls'] = array(
    '#type' => 'textarea',
    '#title' => t('URLs'),
    '#description' => t('The URLs where the Purge requests will be sent to. One URL per line.'),
    '#required' => TRUE,
    '#default_value' => $target_urls,
  );
  $form['target_en'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => $target['en'],
  );
  // Render submit buttons
  if ($form_action != 'view') {
    $form['save_enabled'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
  }

  return $form;
}


/**
 * Form submission for the target sets form.
 */
function purge_targets_form_submit($form, $form_state) {
  // First get the current configuration
  $target_sets = purge_targets_getall();
  $current_values = purge_targets_state($target_sets);
  $form_values = $form_state['values']['purge_targets'];
  // Check each submitted value for changes
  foreach ($form_values as $form_tid => $form_value) {
    if ($current_values[$form_tid] != $form_value) {
      // Disable
      if ($form_value == 0) {
        $target_sets[$form_tid]['en'] = '0';
      }
      elseif ($form_value == $form_tid) {
        $target_sets[$form_tid]['en'] = '1';
      }
    }
  }
  variable_set('purge_targets', drupal_json_encode($target_sets));
  // drupal_set_message(print_r($form_values, 1));
}

/**
 * Helper function to get all target sets.
 */
function purge_targets_getall() {
  $target_sets = array();
  $target_sets = drupal_json_decode(variable_get('purge_targets'));
  return $target_sets;
}

/**
 * Helper function to get an array of all enabled target sets.
 */
function purge_targets_state($target_sets) {
  $values = array();
  foreach ($target_sets as $tid => $target_set) {
    if ($target_set['en'] == '1') {
      $values[$tid] = $tid;
    }
    else {
      $values[$tid] = 0;
    }
  }
  return $values;
}



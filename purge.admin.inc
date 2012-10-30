<?php

/**
 * @file
 * Provides administrative interface for the Purge module.
 */


/**
 * Menu callback for purge admin settings.
 */
function purge_admin_settings_form() {
  $form = array();
  $form['purge_expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expire Integration'),
    '#default_value' => variable_get('purge_expire', 1),
    '#description' => t('Uncheck this box to disable integration with the Cache Expiration module.'),
  );
  return system_settings_form($form);
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
  $form['purge_proxy_general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General Proxy Configuration'),
  );
  // TODO: Make this a true machine field
  $form['purge_proxy_general']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => check_plain($proxy[$pid]['name']),
    '#description' => t('The unique machine readable name of this proxy configuration.'),
    '#required' => TRUE,
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
  $form['purge_proxy_advanced']['domains']['expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domains from Expire'),
    '#description' => t('Get domain names from the Expire module, if available.'),
    '#default_value' => $proxy[$pid]['domains_expire'],
  );
  $form['purge_proxy_advanced']['domains']['drupal'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domain from Drupal'),
    '#description' => t('Get domain names from Drupal. Used as default when no other hostnames are provided.'),
    '#default_value' => $proxy[$pid]['domains_drupal'],
  );
  $form['purge_proxy_advanced']['domains']['custom_domains'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom domains'),
    '#description' => t('Provide custom domains to be purged with each request. One line each'),
    '#default_value' => implode("\n", $proxy[$pid]['domains_custom']),
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
  $form['purge_proxy_advanced']['headers']['acquia'] = array(
    '#type' => 'checkbox',
    '#title' => t('Acquia'),
    '#description' => t('Set headers for the Acquia hosting platforms'),
    '#default_value' => $proxy[$pid]['headers_acquia'],
  );
  // TODO: Add Fastly support
  //$form['purge_proxy_advanced']['headers']['fastly'] = array(
    //'#type' => 'textfield',
    //'#title' => t('Fastly API Key'),
    //'#description' => t('Insert a header with your Fastly API key.'),
    //'#default_value' => $proxy[$pid]['headers_fastly'],
  //);
  $form['purge_proxy_advanced']['headers']['custom_headers'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom headers'),
    '#description' => t('Provide custom headers to be added to each request. One line each'),
    '#default_value' => implode("\n", $proxy[$pid]['headers_custom']),
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
  $custom_domains = preg_split('/$\R?^/m', $form_state['values']['custom_domains']);
  foreach ($custom_domains as $domain) {
    if ((trim($domain)) != '') {
      $domains[] = trim($domain);
    }
  }
  if ($form_state['values']['expire'] == 1) {
    $domains[] = '_DOMAINS_EXPIRE_';
  }
  if ($form_state['values']['drupal'] == 1) {
    $domains[] = '_DOMAINS_DRUPAL_';
  }
  // Generate the headers field
  $headers = array();
  $headers = preg_split('/$\R?^/m', $form_state['values']['custom_headers']);
  // See if the current form is filled from the empty template.
  if ($form_state['values']['purge_pid'] == 1) {
    // Insert a new record into the proxy configuration database table
    $new_proxy_config = db_insert('purge_proxy')
      ->fields(array('type', 'name', 'description', 'enabled', 'isdefault', 'proxy_ssl', 'proxy_host', 'proxy_port', 'options_type',
        'options_parallel', 'options_nonblocking', 'domains', 'url_prefix', 'url_wildcards', 'headers'))
      ->values(array(
      'type' => 4, //Type 4 indicates this is a user controled configuration
      'name' => $form_state['values']['name'],
      'description' => check_plain($form_state['values']['description']),
      'enabled' => 0, // Configurations are disabled by default
      'isdefault' => 0,
      'proxy_ssl' => $form_state['values']['protocol'],
      'proxy_host' => $proxy_host,
      'proxy_port' => check_plain($form_state['values']['port']),
      'options_type' => $form_state['values']['type'],
      'options_parallel' => $form_state['values']['parallel'],
      'options_nonblocking' => $form_state['values']['nonblocking'],
      'domains' => implode("\n", $domains),
      'url_prefix' => check_plain($form_state['values']['prefix']),
      'url_wildcards' => $form_state['values']['wildcards'],
      'headers' => implode("\n", $headers),
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
        'domains' => implode("\n", $domains),
        'url_prefix' => check_plain($form_state['values']['prefix']),
        'url_wildcards' => $form_state['values']['wildcards'],
        'headers' => implode("\n", $headers),
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

    if ($proxy['isdefault']) {
      $status = $status . t('Default, ');
    }
    else {
      $actions[] = l(t('Set Default'), "admin/config/system/purge/proxy/setdefault/{$proxy['pid']}");
    }

    if ($proxy['enabled']) {
      $status = $status . t('Enabled, ');
      $actions[] = l(t('Disable'), "admin/config/system/purge/proxy/disable/{$proxy['pid']}");
    }
    else {
      $status = $status . t('Disabled, ');
      $actions[] = l(t('Enable'), "admin/config/system/purge/proxy/enable/{$proxy['pid']}");
    }

    if ($proxy['active']) {
      $status = $status . t('and Active');
      $actions[] = l(t('Deactivate'), "admin/config/system/purge/proxy/deactivate/{$proxy['pid']}");
    }
    else {
      $status = $status . t('and Deactivated');
      $actions[] = l(t('Activate'), "admin/config/system/purge/proxy/activate/{$proxy['pid']}");
    }

    // Add actions for edit and delete
    $actions[] = l(t('Edit'), "admin/config/system/purge/proxy/edit/{$proxy['pid']}");
    $actions[] = l(t('Delete'), "admin/config/system/purge/proxy/delete/{$proxy['pid']}");
    $row[] = $status;
    $row[] = implode(' | ', $actions);
    $rows[] = $row;
  }
  // Output themed table
  $proxy_list = theme('table', array('header' => $header, 'rows' => $rows));
  //print($output);
  $form = array();
  $form['purge_expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expire Integration'),
    '#default_value' => variable_get('purge_expire', 1),
    '#description' => t('Uncheck this box to disable integration with the Cache Expiration module.'),
  );
  // return system_settings_form($form);
  return $proxy_list . drupal_render($form);
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
  $ouput = drupal_goto('admin/config/system/purge/proxy');
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
  $ouput = drupal_goto('admin/config/system/purge/proxy');
  return $output;
}

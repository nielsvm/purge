<?php

/**
 * Define this Export UI plugin.
 */
$plugin = array(
  'schema' => 'purge_proxy',
  'access' => 'administer site configuration',
  'has menu' => 'False',

  // Define the menu item.
//  'menu' => array(
//    'menu prefix' => 'admin/config/system/purge/proxy',
//    'menu item' => 'config',
//    'menu title' => 'Import/Export',
//    'menu description' => 'Import/Export configurations',
//  ),

  // Define user interface texts.
  'title singular' => t('proxy'),
  'title plural' => t('proxies'),
  'title singular proper' => t('Proxy'),
  'title plural proper' => t('Proxies'),

  // Define the names of the functions that provide the add/edit forms.
  'form' => array(
    'settings' => 'purge_proxy_form',
    // 'submit' and 'validate' are also valid callbacks.
  ),
);
// $plugin['menu']['items']['import']['title']= 'blablabla';
module_load_include('inc', 'purge', 'purge.admin');
?>

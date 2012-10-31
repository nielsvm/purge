<?php

/**
 * Define this Export UI plugin.
 */
$plugin = array(
  'schema' => 'purge_proxy',
  'access' => 'administer site configuration',

  // Define the menu item.
  'menu' => array(
    'menu item' => 'purge',
    'menu title' => 'Purge',
    'menu description' => 'Administer Purge Proxy Configurations',
  ),

  // Define user interface texts.
  'title singular' => t('proxy'),
  'title plural' => t('proxies'),
  'title singular proper' => t('Purge proxy'),
  'title plural proper' => t('Purge proxy'),

  // Define the names of the functions that provide the add/edit forms.
  'form' => array(
    'settings' => 'purge_proxy_form',
    // 'submit' and 'validate' are also valid callbacks.
  ),
);

?>

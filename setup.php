<?php

define('PLUGIN_WEBRESOURCES_VERSION', '1.0.0');
define('PLUGIN_WEBRESOURCES_MIN_GLPI', '9.5.0');
define('PLUGIN_WEBRESOURCES_MAX_GLPI', '9.6.0');

function plugin_init_webresources()
{
	global $PLUGIN_HOOKS;
	$PLUGIN_HOOKS['csrf_compliant']['webresources'] = true;
   if (Session::haveRight(PluginWebresourcesResource::$rightname, READ)) {
      $PLUGIN_HOOKS['menu_toadd']['webresources'] = ['plugins' => 'PluginWebresourcesResource'];
   }
   Plugin::registerClass('PluginWebresourcesProfile', ['addtabon' => ['Profile']]);
   $PLUGIN_HOOKS['add_css']['webresources'][] = 'css/webresources.css';
}

function plugin_version_webresources()
{
	return [
	      'name'         => __('Web Resources', 'webresources'),
	      'version'      => PLUGIN_WEBRESOURCES_VERSION,
	      'author'       => 'Curtis Conard',
	      'license'      => 'GPLv2',
	      'homepage'     =>'https://github.com/cconard96/glpi-webresources-plugin',
	      'requirements' => [
	         'glpi'   => [
	            'min' => PLUGIN_WEBRESOURCES_MIN_GLPI,
	            'max' => PLUGIN_WEBRESOURCES_MAX_GLPI
	         ]
	      ]
	   ];
}

function plugin_webresources_check_prerequisites()
{
	if (!method_exists('Plugin', 'checkGlpiVersion')) {
	      $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
	      $matchMinGlpiReq = version_compare($version, PLUGIN_WEBRESOURCES_MIN_GLPI, '>=');
	      $matchMaxGlpiReq = version_compare($version, PLUGIN_WEBRESOURCES_MAX_GLPI, '<');
	      if (!$matchMinGlpiReq || !$matchMaxGlpiReq) {
	         echo vsprintf(
	            'This plugin requires GLPI >= %1$s and < %2$s.',
	            [
	               PLUGIN_WEBRESOURCES_MIN_GLPI,
	               PLUGIN_WEBRESOURCES_MAX_GLPI,
	            ]
	         );
	         return false;
	      }
	   }
	   return true;
}

function plugin_webresources_check_config()
{
	return true;
}


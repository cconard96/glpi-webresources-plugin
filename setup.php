<?php

/*
 -------------------------------------------------------------------------
 Web Resources Plugin for GLPI
 Copyright (C) 2019-2021 by Curtis Conard
 https://github.com/cconard96/glpi-webresources-plugin
 -------------------------------------------------------------------------
 LICENSE
 This file is part of Web Resources Plugin for GLPI.
 Web Resources Plugin for GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Web Resources Plugin for GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Web Resources Plugin for GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_WEBRESOURCES_VERSION', '2.0.4');
define('PLUGIN_WEBRESOURCES_MIN_GLPI', '10.0.0');
define('PLUGIN_WEBRESOURCES_MAX_GLPI', '10.1.0');

function plugin_init_webresources()
{
	global $PLUGIN_HOOKS;
	$PLUGIN_HOOKS['csrf_compliant']['webresources'] = true;

   $plugin = new Plugin();
   if ($plugin->isInstalled('webresources') && $plugin->isActivated('webresources')) {
       Profile::$helpdesk_rights[] = PluginWebresourcesResource::$rightname;
      $config = Config::getConfigurationValues('plugin:Webresources', ['menu']);
      if (Session::haveRight(PluginWebresourcesResource::$rightname, READ)) {
         if (Session::getCurrentInterface() === 'central') {
             $PLUGIN_HOOKS['menu_toadd']['webresources'] = [$config['menu'] ?? 'plugins' => 'PluginWebresourcesDashboard'];
         } else {
             $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::REDEFINE_MENUS]['webresources'] = 'plugin_webresources_redefine_menus';
         }
      }
      Plugin::registerClass('PluginWebresourcesProfile', ['addtabon' => ['Profile']]);
      Plugin::registerClass('PluginWebresourcesConfig', ['addtabon' => 'Config']);
      Plugin::registerClass(PluginWebresourcesDashboard::class, ['addtabon' => ['Central']]);
      $PLUGIN_HOOKS['post_item_form']['webresources'] = 'plugin_webresources_showPostItemForm';
      $PLUGIN_HOOKS['pre_item_update']['webresources'] = [
         'Supplier' => 'plugin_webresources_preupdateitem',
         'Entity' => 'plugin_webresources_preupdateitem',
      ];
      $PLUGIN_HOOKS['pre_item_purge']['webresources'] = 'plugin_webresources_preItemPurge';

       if (!isCommandLine()) {
           // Do not load CSS or JS in CLI mode
           $PLUGIN_HOOKS['add_css']['webresources'][] = 'css/webresources.scss';
           // If current URL contains 'front/central.php' or 'webresources/front/dashboard.php', include the dashboard.js script
           if (strpos($_SERVER['REQUEST_URI'], 'front/central.php') !== false || strpos($_SERVER['REQUEST_URI'], 'webresources/front/dashboard.php') !== false) {
               $PLUGIN_HOOKS['add_javascript']['webresources'][] = 'js/dashboard.js';
           }
       }
   }
}

function plugin_version_webresources()
{
	return [
	      'name'         => __('Web Resources', 'webresources'),
	      'version'      => PLUGIN_WEBRESOURCES_VERSION,
	      'author'       => 'Curtis Conard',
	      'license'      => 'GPLv2+',
	      'homepage'     =>'https://github.com/cconard96/glpi-webresources-plugin',
	      'requirements' => [
	         'glpi'   => [
	            'min' => PLUGIN_WEBRESOURCES_MIN_GLPI,
	            'max' => PLUGIN_WEBRESOURCES_MAX_GLPI
	         ]
	      ]
	   ];
}

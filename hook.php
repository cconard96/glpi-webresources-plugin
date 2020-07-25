<?php

/*
 -------------------------------------------------------------------------
 Web Resources Plugin for GLPI
 Copyright (C) 2019-2020 by Curtis Conard
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

function plugin_webresources_install()
{
   global $DB;

   $res_table = PluginWebresourcesResource::getTable();
   $res_entity_table = PluginWebresourcesResource_Entity::getTable();
   $res_profile_table = PluginWebresourcesResource_Profile::getTable();
   $res_group_table = PluginWebresourcesResource_Group::getTable();
   $res_user_table = PluginWebresourcesResource_User::getTable();
   $clean_install = false;

   if (!$DB->tableExists($res_table)) {
      $query = "CREATE TABLE `{$res_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `users_id` int(11) NOT NULL,
                  `name` varchar(255) NOT NULL,
                  `link` varchar(255) NOT NULL,
                  `icon` varchar(255) DEFAULT NULL,
                  `color` varchar(16) NOT NULL DEFAULT '#000000',
                  `plugin_webresources_categories_id` int(11) DEFAULT 0,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource table' . $DB->error());
      $clean_install = true;
   }
   if (!$DB->tableExists($res_entity_table)) {
      $query = "CREATE TABLE `{$res_entity_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int(11) NOT NULL,
                  `entities_id` int(11) NOT NULL,
                  `is_recursive` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource Entity table' . $DB->error());
   }
   if (!$DB->tableExists($res_profile_table)) {
      $query = "CREATE TABLE `{$res_profile_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int(11) NOT NULL,
                  `profiles_id` int(11) NOT NULL,
                  `entities_id` int(11) NOT NULL,
                  `is_recursive` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource Profile table' . $DB->error());
   }
   if (!$DB->tableExists($res_group_table)) {
      $query = "CREATE TABLE `{$res_group_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int(11) NOT NULL,
                  `groups_id` int(11) NOT NULL,
                  `entities_id` int(11) NOT NULL,
                  `is_recursive` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource Group table' . $DB->error());
   }
   if (!$DB->tableExists($res_user_table)) {
      $query = "CREATE TABLE `{$res_user_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int(11) NOT NULL,
                  `users_id` int(11) NOT NULL,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource User table' . $DB->error());
   }

   $cat_table = PluginWebresourcesCategory::getTable();
   if (!$DB->tableExists($cat_table)) {
      $query = "CREATE TABLE `{$cat_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL,
                  `comment` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource Category table' . $DB->error());
   }

   $migration = new Migration(PLUGIN_WEBRESOURCES_VERSION);
   if ($clean_install) {
      $migration->addRight(PluginWebresourcesResource::$rightname);
   }
   $migration->executeMigration();
	return true;
}

function plugin_webresources_uninstall()
{
   global $DB;

   $res_table = PluginWebresourcesResource::getTable();
   $res_entity_table = PluginWebresourcesResource_Entity::getTable();
   $res_profile_table = PluginWebresourcesResource_Profile::getTable();
   $res_group_table = PluginWebresourcesResource_Group::getTable();
   $res_user_table = PluginWebresourcesResource_User::getTable();

   if ($DB->tableExists($res_table)) {
      $DB->queryOrDie('DROP TABLE'.$DB::quoteName($res_table));
   }
   if ($DB->tableExists($res_entity_table)) {
      $DB->queryOrDie('DROP TABLE'.$DB::quoteName($res_entity_table));
   }
   if ($DB->tableExists($res_profile_table)) {
      $DB->queryOrDie('DROP TABLE'.$DB::quoteName($res_profile_table));
   }
   if ($DB->tableExists($res_group_table)) {
      $DB->queryOrDie('DROP TABLE'.$DB::quoteName($res_group_table));
   }
   if ($DB->tableExists($res_user_table)) {
      $DB->queryOrDie('DROP TABLE'.$DB::quoteName($res_user_table));
   }
	return true;
}

function plugin_webresources_getDropdown() {
   return ['PluginWebresourcesCategory' => PluginWebresourcesCategory::getTypeName(2)];
}


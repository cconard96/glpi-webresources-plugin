<?php

function plugin_webresources_install()
{
   global $DB;

   $res_table = PluginWebresourcesResource::getTable();
   $res_entity_table = PluginWebresourcesResource_Entity::getTable();
   $res_profile_table = PluginWebresourcesResource_Profile::getTable();
   $res_group_table = PluginWebresourcesResource_Group::getTable();
   $res_user_table = PluginWebresourcesResource_User::getTable();
   if (!$DB->tableExists($res_table)) {
      $query = "CREATE TABLE `{$res_table}` (
                  `id` int(11) NOT NULL auto_increment,
                  `users_id` int(11) NOT NULL,
                  `name` varchar(255) NOT NULL,
                  `link` varchar(255) NOT NULL,
                  `icon` varchar(255) DEFAULT NULL,
                  `plugin_webresources_categories_id` int(11) DEFAULT 0,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error creating Web Resource table' . $DB->error());
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


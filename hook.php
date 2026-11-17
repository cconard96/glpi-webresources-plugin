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

function plugin_webresources_install()
{
    global $DB;

    $charset   = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $sign      = DBConnection::getDefaultPrimaryKeySignOption();
    $table_options = "ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC";

    $res_table = PluginWebresourcesResource::getTable();
    $res_entity_table = PluginWebresourcesResource_Entity::getTable();
    $res_profile_table = PluginWebresourcesResource_Profile::getTable();
    $res_group_table = PluginWebresourcesResource_Group::getTable();
    $res_user_table = PluginWebresourcesResource_User::getTable();
    $cat_table = PluginWebresourcesCategory::getTable();
    $autoicon_table = 'glpi_plugin_webresources_autoicons';
    $clean_install = false;

    if (!$DB->tableExists($res_table)) {
        $DB->doQuery("CREATE TABLE `{$res_table}` (
                  `id` int {$sign} NOT NULL auto_increment,
                  `users_id` int {$sign} NOT NULL DEFAULT '0',
                  `name` varchar(255) NOT NULL,
                  `link` varchar(255) NOT NULL,
                  `icon` varchar(255) DEFAULT NULL,
                  `color` varchar(16) NOT NULL DEFAULT '#000000',
                  `plugin_webresources_categories_id` int {$sign} NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `users_id` (`users_id`),
                KEY `plugin_webresources_categories_id` (`plugin_webresources_categories_id`)
               ) {$table_options}");
        $clean_install = true;
    }
    if (!$DB->tableExists($res_entity_table)) {
        $DB->doQuery("CREATE TABLE `{$res_entity_table}` (
                  `id` int {$sign} NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int {$sign} NOT NULL DEFAULT '0',
                  `entities_id` int {$sign} NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `plugin_webresources_resources_id` (`plugin_webresources_resources_id`),
                KEY `entities_id` (`entities_id`)
               ) {$table_options}");
    }
    if (!$DB->tableExists($res_profile_table)) {
        $DB->doQuery("CREATE TABLE `{$res_profile_table}` (
                  `id` int {$sign} NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int {$sign} NOT NULL DEFAULT '0',
                  `profiles_id` int {$sign} NOT NULL DEFAULT '0',
                  `entities_id` int {$sign} DEFAULT NULL,
                  `no_entity_restriction` tinyint NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `plugin_webresources_resources_id` (`plugin_webresources_resources_id`),
                KEY `profiles_id` (`profiles_id`),
                KEY `entities_id` (`entities_id`)
               ) {$table_options}");
    }
    if (!$DB->tableExists($res_group_table)) {
        $DB->doQuery("CREATE TABLE `{$res_group_table}` (
                  `id` int {$sign} NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int {$sign} NOT NULL DEFAULT '0',
                  `groups_id` int {$sign} NOT NULL DEFAULT '0',
                  `entities_id` int {$sign} DEFAULT NULL,
                  `no_entity_restriction` tinyint NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `plugin_webresources_resources_id` (`plugin_webresources_resources_id`),
                KEY `groups_id` (`groups_id`),
                KEY `entities_id` (`entities_id`)
               ) {$table_options}");
    }
    if (!$DB->tableExists($res_user_table)) {
        $DB->doQuery("CREATE TABLE `{$res_user_table}` (
                  `id` int {$sign} NOT NULL auto_increment,
                  `plugin_webresources_resources_id` int {$sign} NOT NULL DEFAULT '0',
                  `users_id` int {$sign} NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `plugin_webresources_resources_id` (`plugin_webresources_resources_id`),
                KEY `users_id` (`users_id`)
               ) {$table_options}");
    }
    if (!$DB->tableExists($cat_table)) {
        $DB->doQuery("CREATE TABLE `{$cat_table}` (
                  `id` int {$sign} NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL,
                  `comment` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `name` (`name`)
               ) {$table_options}");
    }
    if (!$DB->tableExists($autoicon_table)) {
        $DB->doQuery("CREATE TABLE `{$autoicon_table}` (
                  `itemtype` varchar(100) NOT NULL,
                  `items_id` int {$sign} NOT NULL DEFAULT '0',
                  `icon` varchar(255) DEFAULT NULL,
                  `color` varchar(16) NOT NULL DEFAULT '#000000',
                PRIMARY KEY (`itemtype`, `items_id`)
               ) {$table_options}");
    }

    // ---------------------------------------------------------------------
    // Upgrade of tables created by the 1.x / 2.x versions (GLPI 9.5 / 10)
    // ---------------------------------------------------------------------

    // 1. "No entity restriction" used to be stored as a negative entity id.
    //    It is now a dedicated flag, as in GLPI core tables, so that keys can be unsigned.
    foreach ([$res_group_table, $res_profile_table] as $table) {
        if (!$DB->fieldExists($table, 'no_entity_restriction')) {
            $DB->doQuery("ALTER TABLE `{$table}` ADD `no_entity_restriction` tinyint NOT NULL DEFAULT '0' AFTER `entities_id`");
        }
        $DB->update($table, ['no_entity_restriction' => 1, 'entities_id' => 0], ['entities_id' => ['<', 0]]);
        $DB->doQuery("ALTER TABLE `{$table}` MODIFY `entities_id` int {$sign} DEFAULT NULL");
        $DB->update($table, ['entities_id' => null], ['no_entity_restriction' => 1]);
    }

    // 2. NULL values that the new column definitions do not accept anymore
    $DB->update($res_table, ['plugin_webresources_categories_id' => 0], ['plugin_webresources_categories_id' => null]);
    foreach ([$res_entity_table, $res_profile_table, $res_group_table] as $table) {
        $DB->update($table, ['is_recursive' => 0], ['is_recursive' => null]);
    }

    // 3. Column types (unsigned keys when the GLPI database itself uses them)
    $columns = [
        $res_table => [
            'id'                                => "int {$sign} NOT NULL AUTO_INCREMENT",
            'users_id'                          => "int {$sign} NOT NULL DEFAULT '0'",
            'plugin_webresources_categories_id' => "int {$sign} NOT NULL DEFAULT '0'",
        ],
        $res_entity_table => [
            'id'                               => "int {$sign} NOT NULL AUTO_INCREMENT",
            'plugin_webresources_resources_id' => "int {$sign} NOT NULL DEFAULT '0'",
            'entities_id'                      => "int {$sign} NOT NULL DEFAULT '0'",
            'is_recursive'                     => "tinyint NOT NULL DEFAULT '0'",
        ],
        $res_profile_table => [
            'id'                               => "int {$sign} NOT NULL AUTO_INCREMENT",
            'plugin_webresources_resources_id' => "int {$sign} NOT NULL DEFAULT '0'",
            'profiles_id'                      => "int {$sign} NOT NULL DEFAULT '0'",
            'entities_id'                      => "int {$sign} DEFAULT NULL",
            'is_recursive'                     => "tinyint NOT NULL DEFAULT '0'",
        ],
        $res_group_table => [
            'id'                               => "int {$sign} NOT NULL AUTO_INCREMENT",
            'plugin_webresources_resources_id' => "int {$sign} NOT NULL DEFAULT '0'",
            'groups_id'                        => "int {$sign} NOT NULL DEFAULT '0'",
            'entities_id'                      => "int {$sign} DEFAULT NULL",
            'is_recursive'                     => "tinyint NOT NULL DEFAULT '0'",
        ],
        $res_user_table => [
            'id'                               => "int {$sign} NOT NULL AUTO_INCREMENT",
            'plugin_webresources_resources_id' => "int {$sign} NOT NULL DEFAULT '0'",
            'users_id'                         => "int {$sign} NOT NULL DEFAULT '0'",
        ],
        $cat_table => [
            'id' => "int {$sign} NOT NULL AUTO_INCREMENT",
        ],
        $autoicon_table => [
            'items_id' => "int {$sign} NOT NULL DEFAULT '0'",
        ],
    ];
    foreach ($columns as $table => $fields) {
        $changes = [];
        foreach ($fields as $field => $definition) {
            $changes[] = "MODIFY `{$field}` {$definition}";
        }
        $DB->doQuery("ALTER TABLE `{$table}` " . implode(', ', $changes));
    }

    // 4. Charset / collation
    foreach (array_keys($columns) as $table) {
        $DB->doQuery("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collation}");
    }

    if (!count(Config::getConfigurationValues('plugin:Webresources'))) {
        Config::setConfigurationValues('plugin:Webresources', [
            'config_class'    => PluginWebresourcesConfig::class,
            'menu'            => 'plugins',
            'use_duckduckgo'  => 0,
            'use_google'      => 0,
        ]);
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

    $tables = [PluginWebresourcesResource::getTable(), PluginWebresourcesResource_Entity::getTable(),
        PluginWebresourcesResource_Profile::getTable(), PluginWebresourcesResource_Group::getTable(),
        PluginWebresourcesResource_User::getTable(), PluginWebresourcesCategory::getTable(), 'glpi_plugin_webresources_autoicons'];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->dropTable($table);
        }
    }
    Config::deleteConfigurationValues('plugin:Webresources', [
        'config_class',
        'menu',
        'use_duckduckgo',
        'use_google'
    ]);
    $DB->delete('glpi_profilerights', ['name' => PluginWebresourcesResource::$rightname]);
    return true;
}

function plugin_webresources_getDropdown() {
    return ['PluginWebresourcesCategory' => PluginWebresourcesCategory::getTypeName(2)];
}

function plugin_webresources_showPostItemForm(array $params)
{
    global $DB;

    static $supported_types = [Entity::class, Supplier::class];
    $item = $params['item'];
    if (in_array($item::getType(), $supported_types, true)) {
        if ($item::getType() === 'Entity' && ($_REQUEST['_glpi_tab'] ?? 'Entity$main') !== 'Entity$main') {
            return $params;
        }
        $iterator = $DB->request([
            'SELECT' => ['icon', 'color'],
            'FROM'   => 'glpi_plugin_webresources_autoicons',
            'WHERE'  => [
                'itemtype'  => $item::getType(),
                'items_id'  => $item->getID()
            ]
        ]);
        $ico = '';
        $color = '#808080';
        if (count($iterator)) {
            $data = $iterator->current();
            $ico = $data['icon'];
            $color = $data['color'];
        }
        $out = '<tr><td>'.__s('Icon', 'webresources').'</td><td>';
        $out .= Html::input('webresources_icon', [
            'value'  => $ico
        ]);
        $out .= '</td><td>'.__s('Icon color', 'webresources').'</td><td>';
        $out .= Html::showColorField('webresources_color', [
            'value'  => $color,
            'display'   => false
        ]);
        $out .= '</td></tr>';
        echo $out;
    }
    return $params;
}

function plugin_webresources_preupdateitem(CommonDBTM $item)
{
    global $DB;

    static $supported_types = [Entity::class, Supplier::class, Appliance::class];
    if (isset($item->input['webresources_icon']) && in_array($item::getType(), $supported_types, true)) {
        $DB->updateOrInsert('glpi_plugin_webresources_autoicons', [
            'itemtype'  => $item::getType(),
            'items_id'  => $item->getID(),
            'icon'      => $item->input['webresources_icon'],
            'color'     => $item->input['webresources_color'] ?? '#000000'
        ], [
            'itemtype'  => $item::getType(),
            'items_id'  => $item->getID(),
        ]);
        unset($item->input['webresources_icon']);
        unset($item->input['webresources_color']);
    }
}

function plugin_webresources_preItemPurge(CommonDBTM $item)
{
    global $DB;

    static $supported_types = [Entity::class, Supplier::class, Appliance::class];
    if (in_array($item::getType(), $supported_types, true)) {
        $DB->delete('glpi_plugin_webresources_autoicons', [
            'itemtype'  => $item::getType(),
            'items_id'  => $item->getID(),
        ]);
    }
}

function plugin_webresources_redefine_menus($menu)
{
    $menu['webresources'] = [
        'title' => PluginWebresourcesResource::getTypeName(Session::getPluralNumber()),
        'default'  => PluginWebresourcesDashboard::getSearchURL(false),
        'icon' => PluginWebresourcesDashboard::getIcon(),
        'links' => [
            'search' => PluginWebresourcesDashboard::getSearchURL(false),
        ],
    ];
    return $menu;
}

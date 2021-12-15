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

class PluginWebresourcesResource_User extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginWebresourcesResource';
   static public $items_id_1          = 'plugin_webresources_resources_id';
   static public $itemtype_2          = 'User';
   static public $items_id_2          = 'users_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get users for a web resource
    *
    * @param integer $plugin_webresources_resources_id  ID of the web resource
    *
    * @return array Array of users linked to a web resource
    **/
   static function getUsers($plugin_webresources_resources_id) {
      global $DB;

      $users  = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_webresources_resources_id' => $plugin_webresources_resources_id
         ]
      ]);

      foreach ($iterator as $data) {
         $users[$data['users_id']][] = $data;
      }
      return $users;
   }
}

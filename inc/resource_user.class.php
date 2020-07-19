<?php

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

      while ($data = $iterator->next()) {
         $users[$data['users_id']][] = $data;
      }
      return $users;
   }
}
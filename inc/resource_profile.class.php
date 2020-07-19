<?php

class PluginWebresourcesResource_Profile extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginWebresourcesResource';
   static public $items_id_1          = 'plugin_webresources_resources_id';
   static public $itemtype_2          = 'Profile';
   static public $items_id_2          = 'profiles_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get profiles for a web resource
    *
    * @param integer $plugin_webresources_resources_id  ID of the web resource
    *
    * @return array Array of profiles linked to a web resource
    **/
   static function getProfiles($plugin_webresources_resources_id) {
      global $DB;

      $profiles  = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_webresources_resources_id' => $plugin_webresources_resources_id
         ]
      ]);

      while ($data = $iterator->next()) {
         $profiles[$data['profiles_id']][] = $data;
      }
      return $profiles;
   }
}
<?php

class PluginWebresourcesResource_Group extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginWebresourcesResource';
   static public $items_id_1          = 'plugin_webresources_resources_id';
   static public $itemtype_2          = 'Group';
   static public $items_id_2          = 'groups_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get groups for a web resource
    *
    * @param integer $plugin_webresources_resources_id  ID of the web resource
    *
    * @return array Array of groups linked to a web resource
    **/
   static function getGroups($plugin_webresources_resources_id) {
      global $DB;

      $groups  = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_webresources_resources_id' => $plugin_webresources_resources_id
         ]
      ]);

      while ($data = $iterator->next()) {
         $groups[$data['groups_id']][] = $data;
      }
      return $groups;
   }
}
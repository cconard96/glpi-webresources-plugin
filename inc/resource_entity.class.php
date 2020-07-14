<?php

class PluginWebresourcesResource_Entity extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginWebresourcesResource';
   static public $items_id_1          = 'plugin_webresource_resources_id';
   static public $itemtype_2          = 'Entity';
   static public $items_id_2          = 'entities_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get entities for a web resource
    *
    * @param integer $plugin_webresources_resources_id  ID of the knowbaseitem
    *
    * @return array Array of entities linked to a web resource
    **/
   static function getEntities($plugin_webresources_resources_id) {
      global $DB;

      $entities  = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_webresources_resources_id' => $plugin_webresources_resources_id
         ]
      ]);

      while ($data = $iterator->next()) {
         $entities[$data['entities_id']][] = $data;
      }
      return $entities;
   }
}
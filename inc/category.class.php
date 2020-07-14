<?php

class PluginWebresourcesCategory extends CommonDropdown {

   public static $rightname = 'plugin_webresources_resource';

   public static function getTypeName($nb = 0)
   {
      return _n('Category', 'Categories', 'webresources');
   }
}
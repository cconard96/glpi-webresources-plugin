<?php

class PluginWebresourcesDashboard extends CommonGLPI {

   public static $rightname = 'plugin_webresources_resource';

   public static function getTypeName($nb = 0)
   {
      return PluginWebresourcesResource::getTypeName(Session::getPluralNumber());
   }

   public static function getIcon()
   {
      return 'fab fa-chrome';
   }

   static function getAdditionalMenuLinks() {

      $links = [];
      if (static::canView()) {
         $links['summary_kanban'] = PluginWebresourcesDashboard::getSearchURL(false);
      }
      if (count($links)) {
         return $links;
      }
      return false;
   }

   public static function getMenuContent()
   {
      $menu = parent::getMenuContent();
      $menu['links']['search'] = PluginWebresourcesResource::getSearchURL(false);
      if (PluginWebresourcesResource::canCreate()) {
         $menu['links']['add'] = PluginWebresourcesResource::getFormURL(false);
      }
      return $menu;
   }

   public static function showDashboard()
   {
      global $DB;

      $cat_iterator = $DB->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => PluginWebresourcesCategory::getTable(),
      ]);
      $categories = [];
      while ($cat = $cat_iterator->next()) {
         $categories[$cat['id']] = $cat['name'];
      }

      $iterator = $DB->request([
         'FROM'   => PluginWebresourcesResource::getTable()
      ] + PluginWebresourcesResource::getVisibilityCriteria(true));
      $resources = [];
      while($data = $iterator->next()) {
         $resources[$data['plugin_webresources_categories_id']][] = $data;
      }

      echo '<div><div class="webresources-header">'.PluginWebresourcesResource::getTypeName(Session::getPluralNumber()).'</div>';
      echo '<div class="webresources-categories">';
      foreach ($resources as $cat_id => $cat_resources) {
         echo '<div id="webresources-category-'.$cat_id.'" class="webresources-category">';
         $cat_name = $cat_id === 0 ? 'Uncategorized' : $categories[$cat_id];
         echo '<div class="webresources-category-header">'.$cat_name.'</div>';
         echo '<div class="webresources-items">';
         foreach ($cat_resources as $resource) {
            echo '<div class="webresources-item">';
            echo '<a href="'.$resource['link'].'" target="_blank">';
            echo '<div class="webresources-item-icon">';
            $icon_type = Toolbox::isValidWebUrl($resource['icon']) ? 'image' : 'icon';
            if ($icon_type === 'image') {
               echo '<img src="' . $resource['icon'] . '" title="' . $resource['name'] . '" alt="' . $resource['name'] . '"/>';
            } else {
               if (empty($resource['icon'])) {
                  $resource['icon'] = 'fab fa-chrome';
               }
               echo '<i style="color: '.$resource['color'].';" class="' . $resource['icon'] . '" title="' . $resource['name'] . '" alt="' . $resource['name'] . '"></i>';
            }
            echo '</div>';
            echo '<div class="settings-item-title">'.$resource['name'].'</div>';
            echo '</a>';
            echo '</div>';
         }
         echo '</div></div>';
      }
      echo '</div></div>';
   }
}
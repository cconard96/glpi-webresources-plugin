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
            echo '<img src="' . $resource['icon'] . '" title="' . $resource['name'] . '" alt="' . $resource['name'] . '" style="'.($icon_type === 'image' ? 'display: block' : 'display: none').'" onerror="onWRImageLoadError(this);"/>';

            if ($icon_type === 'icon' || empty($resource['icon'])) {
               $resource['icon'] = 'fab fa-chrome';
            }
            echo '<i style="color: '.$resource['color'].';" class="' . $resource['icon'] . '" title="' . $resource['name'] . '"  style="'.($icon_type === 'icon' ? 'display: block' : 'display: none').'" alt="' . $resource['name'] . '"></i>';

            echo '</div>';
            echo '<div class="webresources-item-title">'.$resource['name'].'</div>';
            echo '</a>';
            echo '</div>';
         }
         echo '</div></div>';
      }
      echo '</div></div>';

      $js = <<<JS
function onWRImageLoadError(img) {
   const img_obj = $(img);
   img_obj.hide();
   const i = img_obj.parent().find('i');
   i.show();
   i.attr('class', 'fab fa-chrome');
}
JS;
      echo Html::scriptBlock($js);

   }
}
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

/**
 * Web Resources Dashboard
 */
class PluginWebresourcesDashboard extends CommonGLPI {

   public static string $rightname = 'plugin_webresources_resource';

   public static function getTypeName($nb = 0)
   {
      return PluginWebresourcesResource::getTypeName(Session::getPluralNumber());
   }

   public static function getIcon()
   {
      return 'fab fa-chrome';
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return self::createTabEntry(self::getTypeName(), 0, null, self::getIcon());
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showDashboard();
      return true;
   }

   public static function getMenuContent()
   {
      $menu = parent::getMenuContent();
      $menu['links']['<i class="ti ti-search"></i><span>' . __s('Search') . '</span>'] = PluginWebresourcesResource::getSearchURL(false);
      if (PluginWebresourcesResource::canCreate()) {
         $menu['links']['add'] = PluginWebresourcesResource::getFormURL(false);
      }
      return $menu;
   }

   /**
    * @param bool $skip_rights
    * @return array
    * @since 1.3.0
    */
   public static function getDashboardContexts(bool $skip_rights = false)
   {
      $contexts = [
         'personal'  => __('My resources', 'webresources')
      ];

      if ($skip_rights || Supplier::canView()) {
         $contexts['suppliers'] = Supplier::getTypeName(Session::getPluralNumber());
      }

      if ($skip_rights || Entity::canView()) {
         $contexts['entities'] = Entity::getTypeName(Session::getPluralNumber());
      }

      if (Plugin::isPluginActive('webapplications')) {
         // Need the plugin because the migration to the core did not transfer the url or management url :(
         $contexts['appliances'] = Appliance::getTypeName(Session::getPluralNumber());
      }
      return $contexts;
   }

   private static function getPersonalResources()
   {
      global $DB;

      $res_table = PluginWebresourcesResource::getTable();
      // DISTINCT: a resource matching several targets (entity + profile...) must be listed only once
      $iterator = $DB->request([
            'SELECT'   => ["{$res_table}.*"],
            'DISTINCT' => true,
            'FROM'     => $res_table,
            'ORDER'    => ["{$res_table}.name"],
         ] + PluginWebresourcesResource::getVisibilityCriteria(true));
      $resources = [];
      foreach ($iterator as $data) {
         $resources[(int) $data['plugin_webresources_categories_id']][] = $data;
      }
      ksort($resources);

      return $resources;
   }

   private static function getSupplierResources(bool $skip_rights = false)
   {
      global $DB;

      $types_iterator = $DB->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => SupplierType::getTable()
      ]);
      $types = [
         0  => __('Uncategorized', 'webresources')
      ];
      foreach ($types_iterator as $data) {
         $types[$data['id']] = $data['name'];
      }
      $iterator = $DB->request([
         'SELECT' => [Supplier::getTable().'.id AS id', 'name', 'website', 'suppliertypes_id', Supplier::getTable().'.comment AS comment'],
         'FROM'   => Supplier::getTable()
      ] + getEntitiesRestrictCriteria());
      $resources = [];
      if (!$skip_rights && !Supplier::canView()) {
         return $resources;
      }

      foreach ($iterator as $data) {
         if (!empty($data['website'])) {
            $ico_iterator = $DB->request([
               'SELECT' => ['icon', 'color'],
               'FROM'   => 'glpi_plugin_webresources_autoicons',
               'WHERE'  => [
                  'itemtype'  => Supplier::class,
                  'items_id'  => $data['id']
               ]
            ]);
            if (count($ico_iterator)) {
               $icodata = $ico_iterator->current();
               $ico = empty($icodata['icon']) ? Supplier::getIcon() : $icodata['icon']; // '@auto'
               $color = $icodata['color'];
            } else {
               $ico = Supplier::getIcon(); // '@auto'
               $color = 'inherit';
            }
            $suppliertype_name = $types[$data['suppliertypes_id']] ?? $types[0];
            $resources[$suppliertype_name][] = [
               'name' => $data['name'],
               'link' => $data['website'],
               'color' => $color,
               'icon' => $ico,
               'comment' => $data['comment'] ?? ''
            ];
         }
      }

      return $resources;
   }

   private static function getApplianceResources(bool $skip_rights = false)
   {
      global $DB;

      $types_iterator = $DB->request([
         'SELECT' => ['id', 'name', 'comment'],
         'FROM'   => ApplianceType::getTable()
      ]);
      $types = [
         0  => __('Uncategorized', 'webresources')
      ];
      foreach ($types_iterator as $data) {
         $types[$data['id']] = $data['name'];
      }
      $resources = [];
      if (!$skip_rights && !Appliance::canView()) {
         return $resources;
      }

      if (!Plugin::isPluginActive('webapplications')) {
         return $resources;
      }
      $iterator = $DB->request([
         'SELECT' => ['name', 'address', 'backoffice', 'appliancetypes_id'],
         'FROM'   => PluginWebapplicationsAppliance::getTable(),
         'JOIN'   => [
            Appliance::getTable() => [
               'ON'  => [
                  Appliance::getTable()                        => 'id',
                  PluginWebapplicationsAppliance::getTable()   => 'appliances_id'
               ]
            ]
         ]
      ] + getEntitiesRestrictCriteria());
      $resources = [];
      foreach ($iterator as $data) {
         $appliancetype_name = $types[$data['appliancetypes_id']] ?? $types[0];
         if (!empty($data['address'])) {
            $resources[$appliancetype_name][] = [
               'name' => $data['name'],
               'link' => $data['address'],
               'color' => 'inherit',
               'icon' => Appliance::getIcon(),
               'comment' => $data['comment'] ?? ''
            ];
         }
         if (!empty($data['backoffice'])) {
            $resources[$appliancetype_name][] = [
               'name' => $data['name'] . ' (Management)',
               'link' => $data['backoffice'],
               'color' => 'inherit',
               'icon' => Appliance::getIcon(),
               'comment' => $data['comment'] ?? ''
            ];
         }
      }

      return $resources;
   }

   private static function getEntityResources(bool $skip_rights = false)
   {
      global $DB;

      $category = Entity::getTypeName(Session::getPluralNumber());
      $iterator = $DB->request([
            'SELECT' => [Entity::getTable().'.id AS id', 'completename', 'website', 'comment'],
            'FROM'   => Entity::getTable()
         ] + getEntitiesRestrictCriteria());
      $resources = [
         $category => []
      ];
      if (!$skip_rights && !Entity::canView()) {
         return $resources;
      }

      foreach ($iterator as $data) {
         if (!empty($data['website'])) {
            $ico_iterator = $DB->request([
               'SELECT' => ['icon', 'color'],
               'FROM'   => 'glpi_plugin_webresources_autoicons',
               'WHERE'  => [
                  'itemtype'  => Entity::class,
                  'items_id'  => $data['id']
               ]
            ]);
            if (count($ico_iterator)) {
               $icodata = $ico_iterator->current();
               $ico = empty($icodata['icon']) ? Entity::getIcon() : $icodata['icon'];
               $color = $icodata['color'];
            } else {
               $ico = Entity::getIcon();
               $color = 'inherit';
            }
            $resources[$category][] = [
               'name'   => $data['completename'],
               'link'   => $data['website'],
               'color'  => $color,
               'icon'   => $ico,
               'comment' => $data['comment'] ?? ''
            ];
         }
      }

      return $resources;
   }

   private static function populateAutoIcons(&$resources, bool $regen_icons = false): void
   {
      global $GLPI_CACHE;

      // Fetch and Cache auto-generated icons
      $cache = null;
      try {
         $cache = $regen_icons ? null : $GLPI_CACHE->get('webresources.autoico');
      } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
         // Cache load failed or was empty. Expect lowered performance but it should still function.
      }
      $cache = $cache === null ? [] : json_decode($cache, true);

      $to_fetch = [];
      foreach ($resources as $cat_id => $cat_resources) {
         foreach ($cat_resources as $res_k => $resource) {
            if ($resource['icon'] !== '@auto') {
               continue;
            }

            $key = md5($resource['link']);
            if ($regen_icons || !isset($cache[$key])) {
               $to_fetch[] = $resource['link'];
            }
         }
      }

      // Experimental Feature for baking generated icons to a json file. Not present in release unless manually created.
      if (file_exists(__DIR__ . '/../resources/favicon_map.json')) {
         $baked_favicon_map = json_decode(file_get_contents(__DIR__ . '/../resources/favicon_map.json'), true);
         foreach ($to_fetch as $i => $url) {
            $key = md5($url);
            $host = parse_url($url, PHP_URL_HOST);
            foreach ($baked_favicon_map as $h => $ico) {
               $pattern = "/$h/iu";
               if (preg_match($pattern, $host)) {
                  $cache[$key] = $ico;
                  unset($to_fetch[$i]);
                  continue;
               }
            }
         }
      }
      $fetched = PluginWebresourcesScraper::getMultiple($to_fetch);
      foreach ($fetched as $url => $ico) {
         $key = md5($url);
         $fetched_ico = reset($ico);
         if (!is_array($fetched_ico)) {
            continue;
         }
         $cache[$key] = $fetched_ico['href'];
      }
      foreach ($resources as $cat_id => $cat_resources) {
         foreach ($cat_resources as $res_k => $resource) {
            if ($resource['icon'] !== '@auto') {
               continue;
            }
            $key = md5($resource['link']);
            $resources[$cat_id][$res_k]['icon'] = $cache[$key] ?? null;
         }
      }

      // Save Cache
      try {
         $GLPI_CACHE->set('webresources.autoico', json_encode($cache));
      } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
         // Cache save failed. Expect lowered performance but it should still function.
      }
   }

    /**
     * Get the resources for a specific dashboard context
     * @param string $context
     * @param bool $skip_rights
     * @return array
     */
   private static function getDashboardResources(string $context, bool $skip_rights = false): array
   {
       switch ($context)
       {
           case 'suppliers':
               $default_icon = Supplier::getIcon();
               $resources = self::getSupplierResources($skip_rights);
               $dashboard_header = Supplier::getTypeName(Session::getPluralNumber());
               break;
           case 'appliances':
               $default_icon = Appliance::getIcon();
               $resources = self::getApplianceResources($skip_rights);
               $dashboard_header = Appliance::getTypeName(Session::getPluralNumber());
               break;
           case 'entities':
               $default_icon = Entity::getIcon();
               $resources = self::getEntityResources($skip_rights);
               $dashboard_header = Entity::getTypeName(Session::getPluralNumber());
               break;
           case 'personal':
           default:
               $default_icon = 'fab fa-chrome';
               $resources = self::getPersonalResources();
               $dashboard_header = PluginWebresourcesResource::getTypeName(Session::getPluralNumber());
       }

       return [
           'default_icon'   => $default_icon,
           'resources'      => $resources,
           'dashboard_header' => $dashboard_header,
       ];
   }

   private static function getCategories(): array
   {
       global $DB;
       $categories = [];
       $cat_iterator = $DB->request([
           'SELECT' => ['id', 'name'],
           'FROM' => PluginWebresourcesCategory::getTable(),
       ]);
       foreach ($cat_iterator as $cat) {
           $categories[$cat['id']] = $cat['name'];
       }
       return $categories;
   }

   public static function getDashboardContentGrid(string $context = 'personal', bool $regen_icons = false, bool $skip_rights = false): string
   {
       [
           'default_icon'   => $default_icon,
           'resources'      => $resources,
           'dashboard_header' => $dashboard_header,
       ] = self::getDashboardResources($context, $skip_rights);
       $categories = [];
       if ($context === 'personal') {
           $categories = self::getCategories();
       }
       self::populateAutoIcons($resources, $regen_icons);

       ob_start();
       echo '<div class="webresources-dashboard" data-view-mode="grid" data-context="'.htmlescape($context).'"><div class="webresources-header">'.htmlescape($dashboard_header).'</div>';
       echo '<div class="webresources-categories">';
       foreach ($resources as $cat_id => $cat_resources) {
           echo '<div id="webresources-category-'.htmlescape(is_numeric($cat_id) ? $cat_id : md5((string) $cat_id)).'" class="webresources-category">';
           if (is_numeric($cat_id)) {
               $cat_name = $categories[$cat_id] ?? __('Uncategorized', 'webresources');
           } else {
               $cat_name = $cat_id;
           }
           echo '<div class="webresources-category-header">'.htmlescape($cat_name).'</div>';
           echo '<div class="webresources-items">';
           foreach ($cat_resources as $resource) {
               echo self::getResourceAsIcon($resource, $default_icon);
           }
           echo '</div></div>';
       }
       echo '</div></div>';
       return ob_get_clean();
   }

    public static function getDashboardContentList(string $context = 'personal', bool $regen_icons = false, bool $skip_rights = false): string
    {
        [
            'default_icon'   => $default_icon,
            'resources'      => $resources,
            'dashboard_header' => $dashboard_header,
        ] = self::getDashboardResources($context, $skip_rights);
        $categories = [];
        if ($context === 'personal') {
            $categories = self::getCategories();
        }
        self::populateAutoIcons($resources, $regen_icons);

        ob_start();
        echo '<div class="webresources-dashboard" data-view-mode="list" data-context="'.htmlescape($context).'"><div class="webresources-header">'.htmlescape($dashboard_header).'</div>';
        echo '<div class="webresources-categories">';
        foreach ($resources as $cat_id => $cat_resources) {
            echo '<div id="webresources-category-'.htmlescape(is_numeric($cat_id) ? $cat_id : md5((string) $cat_id)).'" class="webresources-category">';
            if (is_numeric($cat_id)) {
                $cat_name = $categories[$cat_id] ?? __('Uncategorized', 'webresources');
            } else {
                $cat_name = $cat_id;
            }
            echo '<div class="webresources-category-header">'.htmlescape($cat_name).'</div>';
            echo '<table class="table table-sm webresources-items">';
            foreach ($cat_resources as $resource) {
                echo self::getResourceAsTableRow($resource, $default_icon);
            }
            echo '</table></div>';
        }
        echo '</div></div>';
        return ob_get_clean();
    }

   /**
    * @param string $context
    * @param bool $regen_icons If true, automatic icons (Dynamic resources based on other items like Suppliers) are regenerated
    * @param bool $skip_rights
    * @return string
    * @since 1.3.0
    * @deprecated 2.0.0 Use getDashboardContentGrid() or getDashboardContentList() instead.
    *   This method wraps getDashboardContentGrid() and will be removed in 2.1.0.
    */
   public static function getDashboardContent(string $context = 'personal', bool $regen_icons = false, bool $skip_rights = false): string
   {
      return self::getDashboardContentList($context, $regen_icons, $skip_rights);
   }

   /**
    * Normalize and HTML-escape the values of a resource before it is written in the page.
    * Since GLPI 11, values are stored raw in the database, so they must be escaped on output.
    * Links using a script-executing scheme (javascript:, data:, vbscript:) are neutralized.
    */
   private static function prepareResourceForDisplay(array $resource, string $default_icon): array
   {
      $name  = (string) ($resource['name'] ?? '');
      $link  = trim((string) ($resource['link'] ?? ''));
      $icon  = trim((string) ($resource['icon'] ?? ''));
      $color = trim((string) ($resource['color'] ?? ''));

      // Any URI scheme is allowed (market://, softwarecenter://...) except the ones that execute script.
      // Browsers ignore control chars and whitespace inside the scheme, so strip them before checking.
      $compact_link = preg_replace('/[\x00-\x20]+/', '', $link);
      if ($compact_link === '' || preg_match('/^(javascript|data|vbscript):/i', $compact_link)) {
         $link = '#';
      }
      if ($color !== 'inherit' && !preg_match('/^#[0-9a-f]{3,8}$/i', $color)) {
         $color = 'inherit';
      }
      $is_image = PluginWebresourcesToolbox::isValidWebUrl($icon);

      return [
         'name'       => htmlescape($name),
         'link'       => htmlescape($link),
         'is_image'   => $is_image,
         'image'      => $is_image ? htmlescape($icon) : '',
         'icon_class' => htmlescape(($is_image || $icon === '') ? $default_icon : $icon),
         'fallback'   => htmlescape($default_icon),
         'color'      => $color,
         'comment'    => (string) ($resource['comment'] ?? ''),
      ];
   }

   private static function getResourceIconHtml(array $r): string
   {
      $html = '';
      if ($r['is_image']) {
         $html .= '<img src="' . $r['image'] . '" title="' . $r['name'] . '" alt="' . $r['name'] . '" style="display: block" onerror="onWRImageLoadError(this);" data-fallback="' . $r['fallback'] . '"/>';
      }
      $html .= '<i style="color: ' . $r['color'] . ';' . ($r['is_image'] ? 'display: none' : 'display: block') . '" class="' . $r['icon_class'] . '" title="' . $r['name'] . '"></i>';
      return $html;
   }

   private static function getResourceAsIcon(array $resource, string $default_icon = 'fab fa-chrome')
   {
      $r = self::prepareResourceForDisplay($resource, $default_icon);

      $html = '<div class="webresources-item">';
      $html .= '<a href="' . $r['link'] . '" target="_blank" rel="noopener noreferrer">';
      $html .= '<div class="webresources-item-icon">';
      $html .= self::getResourceIconHtml($r);
      $html .= '</div>';
      $html .= '<div class="webresources-item-title">' . $r['name'] . '</div>';
      $html .= '</a>';
      $html .= '</div>';
      return $html;
   }

   private static function getResourceAsTableRow(array $resource, string $default_icon = 'fab fa-chrome')
   {
      $r = self::prepareResourceForDisplay($resource, $default_icon);

      // Plain text comment, truncated to 100 chars
      $comments_full = trim(strip_tags($r['comment']));
      $comments = mb_strlen($comments_full) > 100 ? mb_substr($comments_full, 0, 100) . '...' : $comments_full;

      $html = '<tr style="vertical-align: middle" class="webresources-item">';
      $html .= '<td>';
      $html .= '<a href="' . $r['link'] . '" target="_blank" rel="noopener noreferrer">';
      $html .= '<div class="webresources-item-icon d-table-cell">';
      $html .= self::getResourceIconHtml($r);
      $html .= '</div>';
      $html .= '</a>';
      $html .= '</td>';
      $html .= '<td>';
      $html .= '<a href="' . $r['link'] . '" target="_blank" rel="noopener noreferrer">' . $r['name'] . '</a>';
      $html .= '</td>';
      $html .= '<td title="' . htmlescape($comments_full) . '">';
      $html .= htmlescape($comments);
      $html .= '</td>';
      $html .= '</tr>';
      return $html;
   }

   /**
    * @param string $context
    * @since 1.0.0
    * @since 1.3.0 Accept context param. Moved content to getDashboardContent to allow easy loading over AJAX calls
    * @since 2.0.0 Added $view_mode param to change the view mode of the dashboard
    */
   public static function showDashboard(string $context = 'personal', string $view_mode = 'grid')
   {
      $available_contexts = self::getDashboardContexts();
      if (!array_key_exists($context, $available_contexts)) {
         $context = 'personal';
      }
      echo '<div class="webresources-toolbar">';
      Dropdown::showFromArray('context', $available_contexts, [
         'value'  => $context
      ]);
      echo "<div class='input-group'>";
      echo Html::input('search', [
         'placeholder'  => __('Search'),
         'type'         => 'search',
      ]);
      // Show mode changer icon toggle button (grid and list views)
      $titles = sprintf(
         "data-title-list='%s' data-title-grid='%s'",
         __s('List view', 'webresources'),
         __s('Grid view', 'webresources')
      );
      if ($view_mode === 'grid') {
          echo "<button type='button' class='webresources-view-mode btn btn-outline-secondary' {$titles}><i class='fas fa-list view-switcher' title='".__s('List view', 'webresources')."'></i></button>";
      } else {
          echo "<button type='button' class='webresources-view-mode btn btn-outline-secondary' {$titles}><i class='fas fa-th view-switcher' title='".__s('Grid view', 'webresources')."'></i></button>";
      }
      echo '</div>';
      echo '</div>';
      echo '<div id="webresources-content">';
      // Show loading indicator
       echo '<div class="spinner-border" role="status"></div>';
//      switch ($view_mode) {
//          case 'list':
//              echo self::getDashboardContentList($context);
//              break;
//          case 'grid':
//          default:
//              echo self::getDashboardContentGrid($context);
//              break;
//      }
      echo '</div>';

      $js = <<<JS
function onWRImageLoadError(img) {
   const img_obj = $(img);
   if (img_obj.is(":visible")) {
      img_obj.hide();
      const i = img_obj.parent().find('i');
      i.show();
      const fallback = img_obj.data('fallback') ?? 'fab fa-chrome';
      i.attr('class', fallback);
   }
}
JS;
      echo Html::scriptBlock($js);

   }
}

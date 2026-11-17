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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;

class PluginWebresourcesResource extends CommonDBVisible implements ExtraVisibilityCriteria {

   const WEBRESOURCEADMIN = 1024;

   public static string $rightname = 'plugin_webresources_resource';



   public static function getTypeName($nb = 0)
   {
      return _n('Web Resource', 'Web Resources', (int) $nb, 'webresources');
   }

   public static function getIcon()
   {
      return 'fab fa-chrome';
   }

   public static function getAdditionalMenuLinks() {

      $links = [];
      if (static::canView()) {
         $links['summary_kanban'] = PluginWebresourcesDashboard::getSearchURL(false);
      }
      if (count($links)) {
         return $links;
      }
      return false;
   }

   public function post_getFromDB() {

      // Users
      $this->users    = PluginWebresourcesResource_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = PluginWebresourcesResource_Entity::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = PluginWebresourcesResource_Group::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = PluginWebresourcesResource_Profile::getProfiles($this->fields['id']);
   }

   public function cleanDBonPurge()
   {
      // Remove the targets of the purged resource
      $this->deleteChildrenAndRelationsFromDb([
         PluginWebresourcesResource_User::class,
         PluginWebresourcesResource_Group::class,
         PluginWebresourcesResource_Profile::class,
         PluginWebresourcesResource_Entity::class,
      ]);
   }

   public function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case __CLASS__ :
               $ong[1] = self::getTypeName(1);
               $ong[2] = self::createTabEntry(_n('Target', 'Targets', Session::getPluralNumber()), $nb);
               return $ong;
         }
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() === __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showForm($item->getID());
               break;

            case 2 :
               $item->showVisibility();
               break;
         }
      }
      return true;
   }

   public function showVisibility(): bool
   {
      $ID      = (int) $this->fields['id'];
      $canedit = $this->canEdit($ID);
      $rand    = mt_rand();

      if ($canedit) {
         TemplateRenderer::getInstance()->display('components/add_visibility_target.html.twig', [
            'type' => static::class,
            'rand' => $rand,
            'id'   => $ID,
            'add_target_msg' => __('Add a target'),
            'visiblity_dropdown_params' => $this->getShowVisibilityDropdownParams(),
         ]);
      }

      $recursive_flag = "<span class='fw-bold'>(" . __s('R') . ")</span>";
      $with_entity = static function (string $recipient, array $data) use ($recursive_flag): string {
         if ($data['entities_id'] !== null && !$data['no_entity_restriction']) {
            $recipient = sprintf(
               __s('%1$s / %2$s'),
               $recipient,
               htmlescape(Dropdown::getDropdownName('glpi_entities', $data['entities_id']))
            );
            if ($data['is_recursive']) {
               $recipient = sprintf(__s('%1$s %2$s'), $recipient, $recursive_flag);
            }
         }
         return $recipient;
      };

      $entries = [];
      foreach ($this->users as $val) {
         foreach ($val as $data) {
            $entries[] = [
               'itemtype'  => PluginWebresourcesResource_User::class,
               'id'        => $data['id'],
               'type'      => User::getTypeName(1),
               'recipient' => htmlescape(getUserName($data['users_id'])),
            ];
         }
      }
      foreach ($this->groups as $val) {
         foreach ($val as $data) {
            $entries[] = [
               'itemtype'  => PluginWebresourcesResource_Group::class,
               'id'        => $data['id'],
               'type'      => Group::getTypeName(1),
               'recipient' => $with_entity(htmlescape(Dropdown::getDropdownName('glpi_groups', $data['groups_id'])), $data),
            ];
         }
      }
      foreach ($this->entities as $val) {
         foreach ($val as $data) {
            $recipient = htmlescape(Dropdown::getDropdownName('glpi_entities', $data['entities_id']));
            if ($data['is_recursive']) {
               $recipient = sprintf(__s('%1$s %2$s'), $recipient, $recursive_flag);
            }
            $entries[] = [
               'itemtype'  => PluginWebresourcesResource_Entity::class,
               'id'        => $data['id'],
               'type'      => Entity::getTypeName(1),
               'recipient' => $recipient,
            ];
         }
      }
      foreach ($this->profiles as $val) {
         foreach ($val as $data) {
            $entries[] = [
               'itemtype'  => PluginWebresourcesResource_Profile::class,
               'id'        => $data['id'],
               'type'      => Profile::getTypeName(1),
               'recipient' => $with_entity(htmlescape(Dropdown::getDropdownName('glpi_profiles', $data['profiles_id'])), $data),
            ];
         }
      }

      $massiveactionparams = [
         'num_displayed'    => count($entries),
         'container'        => 'mass' . static::class . $rand,
         'specific_actions' => ['delete' => _x('button', 'Delete permanently')],
      ];
      if ((int) $this->fields['users_id'] !== (int) Session::getLoginUserID()) {
         $massiveactionparams['confirm'] = __('Caution! You are not the author of this item. Deleting targets can result in loss of access.');
      }

      TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
         'is_tab'    => true,
         'nofilter'  => true,
         'nosort'    => true,
         'columns'   => [
            'type'      => _n('Type', 'Types', 1),
            'recipient' => _n('Recipient', 'Recipients', 1),
         ],
         'formatters' => [
            'recipient' => 'raw_html',
         ],
         'entries'             => $entries,
         'total_number'        => count($entries),
         'showmassiveactions'  => $canedit,
         'massiveactionparams' => $massiveactionparams,
      ]);

      return true;
   }

   public static function getVisibilityCriteria(bool $forceall = false): array
   {
      $res_table = self::getTable();
      $res_entity_table = PluginWebresourcesResource_Entity::getTable();
      $res_profile_table = PluginWebresourcesResource_Profile::getTable();
      $res_group_table = PluginWebresourcesResource_Group::getTable();
      $res_user_table = PluginWebresourcesResource_User::getTable();
      $fk = self::getForeignKeyField();

      $has_session_groups = isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]);
      $has_active_profile = isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id']);
      $has_active_entity = isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]);

      $where = [];
      $join = [
         $res_user_table => [
            'ON' => [
               $res_user_table      => $fk,
               $res_table           => 'id'
            ]
         ]
      ];
      if ($forceall || $has_session_groups) {
         $join[$res_group_table] = [
            'ON' => [
               $res_group_table     => $fk,
               $res_table           => 'id'
            ]
         ];
      }
      if ($forceall || $has_active_profile) {
         $join[$res_profile_table] = [
            'ON' => [
               $res_profile_table   => $fk,
               $res_table           => 'id'
            ]
         ];
      }
      if ($forceall || $has_active_entity) {
         $join[$res_entity_table] = [
            'ON' => [
               $res_entity_table    => $fk,
               $res_table           => 'id'
            ]
         ];
      }

      if (Session::haveRight(self::$rightname, self::WEBRESOURCEADMIN)) {
         return [
            'LEFT JOIN' => $join,
            'WHERE' => [],
         ];
      }

      // Users
      if (Session::getLoginUserID()) {
         $where['OR'] = [
            "{$res_user_table}.users_id" => Session::getLoginUserID(),
         ];
      }
      // Groups
      if ($forceall || $has_session_groups) {
         if (Session::getLoginUserID()) {
            $restrict = getEntitiesRestrictCriteria($res_group_table, '', '', true, true);
            $where['OR'][] = [
               "{$res_group_table}.groups_id" => count($_SESSION["glpigroups"])
                  ? $_SESSION["glpigroups"]
                  : [-1],
               'OR' => [
                     "{$res_group_table}.no_entity_restriction" => 1,
                  ] + $restrict
            ];
         }
      }

      // Profiles
      if ($forceall || $has_active_profile) {
         if (Session::getLoginUserID()) {
            $where['OR'][] = [
               "{$res_profile_table}.profiles_id" => $_SESSION["glpiactiveprofile"]['id'],
               'OR' => [
                     "{$res_profile_table}.no_entity_restriction" => 1,
                  ] + getEntitiesRestrictCriteria($res_profile_table, '', '', true, true)
            ];
         }
      }

      // Entities
      if ($forceall || $has_active_entity) {
         if (Session::getLoginUserID()) {
            $restrict = getEntitiesRestrictCriteria($res_entity_table, '', '', true, true);
            if (count($restrict)) {
               $where['OR'] += $restrict;
            } else {
               $where["{$res_entity_table}.entities_id"] = null;
            }
         }
      }

      $criteria = ['LEFT JOIN' => $join];
      if (count($where)) {
         $criteria['WHERE'] = $where;
      }

      return $criteria;
   }

   public function showForm($ID, array $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options + ['formoptions' => 'class="webresources-form"']);

      $name  = (string) ($this->fields['name'] ?? '');
      $icon  = (string) ($this->fields['icon'] ?? '');
      $color = (string) ($this->fields['color'] ?? '#000000');
      if ($color === '') {
         $color = '#000000';
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__s('Name')."</td>";
      echo "<td>";
      $users_id = !empty($this->fields['users_id']) ? $this->fields['users_id'] : Session::getLoginUserID();
      echo Html::hidden('users_id', ['value' => $users_id]);
      echo Html::input('name', ['value' => $name]);
      echo "</td><td>".__s('Link', 'webresources')."</td><td>";
      echo Html::input('link', [
         'value'  => $this->fields['link'] ?? ''
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__s('Icon', 'webresources')."</td><td>";
      echo Html::input('icon', [
         'value'  => $icon
      ]);
      echo '&nbsp;';
      Html::showToolTip(__s('Leave blank to automatically use a favicon', 'webresources'));
      echo "</td>";
      echo "<td>".htmlescape(PluginWebresourcesCategory::getTypeName(1))."</td>";
      echo "<td>";
      Dropdown::show(PluginWebresourcesCategory::class, [
         'value'  => $this->fields['plugin_webresources_categories_id'] ?? 0
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__s('Icon Color', 'webresources')."</td><td>";
      Html::showColorField('color', [
         'value'  => $color
      ]);
      echo '&nbsp;';
      Html::showToolTip(__s('Only applies to FontAwesome icons and not images', 'webresources'));
      echo "</td>";
      echo "<td></td><td></td></tr>";

      // Preview
      echo '<tr><th colspan="4">'.__s('Preview', 'webresources').'</th></tr>';
      echo '<tr class="webresources-categories"><td colspan="4" class="webresources-category">';
      echo '<div id="refresh-preview" style="display: none">';
      echo '<button type="button" class="btn btn-sm btn-outline-secondary"><i class="ti ti-refresh"></i></button>';
      echo '<span class="ms-2">'.__s('Refresh icon image', 'webresources').'</span></div>';
      echo '<div class="webresources-preview" data-view-mode="grid"><div class="webresources-item">';
      echo '<div class="webresources-item-icon">';
      $icon_type = PluginWebresourcesToolbox::isValidWebUrl($icon) ? 'image' : 'icon';
      echo '<img style="display:'.($icon_type === 'image' ? 'block' : 'none').'" src="' . htmlescape($icon_type === 'image' ? $icon : '') . '" title="' . htmlescape($name) . '" alt="' . htmlescape($name) . '"/>';
      $icon_class = ($icon_type === 'image' || $icon === '') ? self::getIcon() : $icon;
      echo '<i style="color: '.htmlescape($color).';display:'.($icon_type === 'icon' ? 'block' : 'none').'" class="' . htmlescape($icon_class) . '" title="' . htmlescape($name) . '"></i>';
      echo '</div>';
      echo '<div class="webresources-item-title">'.htmlescape($name).'</div>';
      echo '</div></div></td></tr>';

      $this->showFormButtons($options);

      $scraper_url = jsescape($CFG_GLPI['root_doc'] . '/plugins/webresources/ajax/scraper.php');
      $default_icon = jsescape(self::getIcon());

      $script = <<<JS
$(document).ready(function() {
   function isWebURL(url) {
      const pattern = /^(?:http[s]?:\/\/(?:[^\s`!()\[\]{};'",<>?«»“”‘’+]+|[^\s`!()\[\]{};:'".,<>«»“”‘’+]))$/iu;
      return pattern.test(url);
   }

   function getIcons(url) {
      let result = {};
      $.ajax({
         method: 'GET',
         async: false,
         url: "{$scraper_url}",
         data: {
            url: url
         },
         success: function(icons) {
            result = icons;
         }
      });
      return result;
   }

   const form = $('.webresources-form');
   form.on('input', 'input[name="name"]', function() {
      $('.webresources-form .webresources-item-title').text($('input[name="name"]').val());
   });
   form.on('input', 'input[name="link"]', function() {
      let link_val = $('input[name="link"]').val();
      let icon_val = $('input[name="icon"]').val();
      if (icon_val === "") {
         const is_url = isWebURL(link_val);
         if (is_url) {
            $('.webresources-form .webresources-item-icon i').hide();
            $('.webresources-form .webresources-item-icon img').show();
            $('.webresources-form #refresh-preview').show();
         }
      }
   });
   form.on('input', 'input[name="icon"]', function() {
      let icon_val = $('input[name="icon"]').val();
      if (icon_val === "") {
         icon_val = "{$default_icon}";
      }
      const is_url = isWebURL(icon_val);
      if (is_url) {
         $('.webresources-form .webresources-item-icon i').hide();
         $('.webresources-form .webresources-item-icon img').show();
         $('.webresources-form #refresh-preview').show();
      } else {
         $('.webresources-form .webresources-item-icon img').hide();
         $('.webresources-form .webresources-item-icon i').show();
         $('.webresources-form .webresources-item-icon i').attr('class', icon_val);
         $('.webresources-form #refresh-preview').hide();
      }
   });
   form.on('input', 'input[name="color"]', function() {
      $('.webresources-form .webresources-item-icon').css({color: $('input[name="color"]').val()})
   });
   $('.webresources-form #refresh-preview button').on('click', function(e) {
      e.preventDefault();
      let icon_val = $('input[name="icon"]').val();
      if (icon_val === '') {
         const found_icons = getIcons($('input[name="link"]').val());
         if (Array.isArray(found_icons)) {
            let last_icon = found_icons[found_icons.length - 1];
            icon_val = last_icon['href'];
            $('input[name="icon"]').val(icon_val);
         }
      }
      $('.webresources-form .webresources-item-icon img').attr('src', icon_val);
      $('.webresources-form #refresh-preview').hide();
   })
});
JS;
      echo Html::scriptBlock($script);

      return true;
   }

   protected function getShowVisibilityDropdownParams() {
      return [
         'type'  => '__VALUE__',
         'right' => self::$rightname
      ];
   }

   public function getSpecificMassiveActions($checkitem = null)
   {
      $actions = [];

      if (Session::getCurrentInterface() === 'central') {
         if (self::canUpdate()) {
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_target']
               = __('Add target', 'webresources');
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'remove_target']
               = __('Remove target', 'webresources');
         }
      }

      $actions = array_merge($actions, parent::getSpecificMassiveActions($checkitem));

      return $actions;
   }

   public static function showMassiveActionsSubForm(MassiveAction $ma)
   {
      global $CFG_GLPI;

      $rand    = mt_rand();

      switch ($ma->getAction()) {
         case 'add_target':
         case 'remove_target':
            echo "<div class='firstbloc'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><td class='tab_bg_2' width='100px'>";

            $types   = ['Entity', 'Group', 'Profile', 'User'];

            $addrand = Dropdown::showItemTypes('_type', $types);
            $params = [
               'type'  => '__VALUE__',
               'right' => self::$rightname
            ];

            Ajax::updateItemOnSelectEvent("dropdown__type".$addrand, "visibility$rand",
               $CFG_GLPI["root_doc"]."/ajax/visibility.php", $params);

            echo "</td>";
            echo "<td><span id='visibility$rand'></span>";
            echo "</td></tr>";
            echo "</table>";
            echo "</div>";
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   protected static function getRelationItemtypeForItemtype(string $itemtype): ?string
   {
      static $map = [
         User::class    => PluginWebresourcesResource_User::class,
         Group::class   => PluginWebresourcesResource_Group::class,
         Profile::class => PluginWebresourcesResource_Profile::class,
         Entity::class  => PluginWebresourcesResource_Entity::class
      ];
      return $map[$itemtype] ?? null;
   }

   public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
   {
      global $DB;

      switch ($ma->getAction()) {
         case 'add_target' :
            $input = $ma->getInput();

            foreach ($ids as $id) {
               $input['plugin_webresources_resources_id'] = $id;
               try {
                  $DB->beginTransaction();
                  $rel_itemtype = self::getRelationItemtypeForItemtype($input["_type"]);
                  $rel_item = new $rel_itemtype();
                  $fk = getForeignKeyFieldForItemType($input["_type"]);
                  if (!isset($input[$fk])) {
                     continue;
                  }

                  if (!is_null($rel_item)) {
                     if (array_key_exists('entities_id', $input) && (int) $input['entities_id'] === -1) {
                        // "No restriction" value selected
                        $input['entities_id'] = 'NULL';
                        $input['no_entity_restriction'] = 1;
                     }
                     $rel_item->add($input);
                     Event::log($input["plugin_webresources_resources_id"], __CLASS__, 4, "plugin",
                        sprintf(__('%s adds a target'), $_SESSION["glpiname"]));
                  }
                  $DB->commit();
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } catch (RuntimeException $e) {
                  $DB->rollBack();
                  $ma->itemDone($item->getType(), $id, $e->getCode());
                  $ma->addMessage($item->getErrorMessage($e->getMessage()));
               }
            }
            return;
         case 'remove_target':
            $input = $ma->getInput();

            foreach ($ids as $id) {
               $input['plugin_webresources_resources_id'] = $id;
               try {
                  $DB->beginTransaction();
                  $rel_itemtype = self::getRelationItemtypeForItemtype($input["_type"]);
                  $fk = getForeignKeyFieldForItemType($input["_type"]);
                  if (!isset($input[$fk])) {
                     continue;
                  }

                  /** @var CommonDBTM $rel_itemtype */
                  if (!is_null($rel_itemtype)) {
                     $input = array_intersect_key($input, array_flip([
                        'plugin_webresources_resources_id', 'users_id', 'groups_id', 'profiles_id', 'entities_id', 'is_recursive',
                     ]));
                     if (array_key_exists('entities_id', $input) && (int) $input['entities_id'] === -1) {
                        // "No restriction" value selected
                        unset($input['entities_id'], $input['is_recursive']);
                        $input['no_entity_restriction'] = 1;
                     }
                     $DB->delete($rel_itemtype::getTable(), $input);
                     Event::log($input["plugin_webresources_resources_id"], __CLASS__, 4, "plugin",
                        sprintf(__('%s deletes a target'), $_SESSION["glpiname"]));
                  }
                  $DB->commit();
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } catch (RuntimeException $e) {
                  $DB->rollBack();
                  $ma->itemDone($item->getType(), $id, $e->getCode());
                  $ma->addMessage($item->getErrorMessage($e->getMessage()));
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}

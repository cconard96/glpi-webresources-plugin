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

class PluginWebresourcesResource extends CommonDBVisible implements ExtraVisibilityCriteria {

   const WEBRESOURCEADMIN = 1024;

   public static $rightname = 'plugin_webresources_resource';

   // For visibility checks
   protected $users     = [];
   protected $groups    = [];
   protected $profiles  = [];
   protected $entities  = [];


   public static function getTypeName($nb = 0)
   {
      return _n('Web Resource', 'Web Resources', 'webresources');
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

   function post_getFromDB() {

      // Users
      $this->users    = PluginWebresourcesResource_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = PluginWebresourcesResource_Entity::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = PluginWebresourcesResource_Group::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = PluginWebresourcesResource_Profile::getProfiles($this->fields['id']);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

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

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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

   public function showVisibility() {
      global $CFG_GLPI;

      $ID      = $this->fields['id'];
      $canedit = $this->canEdit($ID);
      $rand    = mt_rand();
      $nb      = $this->countVisibilities();
      $str_type = strtolower($this::getType());
      $fk = static::getForeignKeyField();

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='{$str_type}visibility_form$rand' id='{$str_type}visibility_form$rand' ";
         echo " method='post' action='".static::getFormURL()."'>";
         echo "<input type='hidden' name='{$fk}' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Add a target')."</tr>";
         echo "<tr class='tab_bg_1'><td class='tab_bg_2' width='100px'>";

         $types   = ['Entity', 'Group', 'Profile', 'User'];

         $addrand = Dropdown::showItemTypes('_type', $types);
         $params = $this->getShowVisibilityDropdownParams();

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand, "visibility$rand",
            $CFG_GLPI["root_doc"]."/ajax/visibility.php", $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'
         => min($_SESSION['glpilist_limit'], $nb),
            'container'
            => 'mass'.__CLASS__.$rand,
            'specific_actions'
            => ['delete' => _x('button', 'Delete permanently')]];

         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $massiveactionparams['confirm']
               = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
         }
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>"._n('Recipient', 'Recipients', Session::getPluralNumber())."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      // Users
      if (count($this->users)) {
         foreach ($this->users as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(PluginWebresourcesResource_User::class, $data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('User')."</td>";
               echo "<td>".getUserName($data['users_id'])."</td>";
               echo "</tr>";
            }
         }
      }

      // Groups
      if (count($this->groups)) {
         foreach ($this->groups as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(PluginWebresourcesResource_Group::class, $data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Group')."</td>";

               $names   = Dropdown::getDropdownName('glpi_groups', $data['groups_id'], 1);
               $entname = sprintf(__('%1$s %2$s'), $names["name"],
                  Html::showToolTip($names["comment"], ['display' => false]));
               if ($data['entities_id'] >= 0) {
                  $entname = sprintf(__('%1$s / %2$s'), $entname,
                     Dropdown::getDropdownName('glpi_entities',
                        $data['entities_id']));
                  if ($data['is_recursive']) {
                     //TRANS: R for Recursive
                     $entname = sprintf(__('%1$s %2$s'),
                        $entname, "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      // Entity
      if (count($this->entities)) {
         foreach ($this->entities as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(PluginWebresourcesResource_Entity::class, $data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Entity')."</td>";
               $names   = Dropdown::getDropdownName('glpi_entities', $data['entities_id'], 1);
               $tooltip = Html::showToolTip($names["comment"], ['display' => false]);
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['is_recursive']) {
                  $entname = sprintf(__('%1$s %2$s'), $entname,
                     "<span class='b'>(".__('R').")</span>");
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      // Profiles
      if (count($this->profiles)) {
         foreach ($this->profiles as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(PluginWebresourcesResource_Profile::class, $data["id"]);
                  echo "</td>";
               }
               echo "<td>"._n('Profile', 'Profiles', 1)."</td>";

               $names   = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id'], 1);
               $tooltip = Html::showToolTip($names["comment"], ['display' => false]);
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['entities_id'] >= 0) {
                  $entname = sprintf(__('%1$s / %2$s'), $entname,
                     Dropdown::getDropdownName('glpi_entities',
                        $data['entities_id']));
                  if ($data['is_recursive']) {
                     $entname = sprintf(__('%1$s %2$s'), $entname,
                        "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

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
                     "{$res_group_table}.entities_id" => ['<', '0'],
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
                  "{$res_profile_table}.entities_id" => ['<', '0'],
                  getEntitiesRestrictCriteria($res_profile_table, '', '', true, true)
               ]
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

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $canedit = $this->can($ID, UPDATE);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td><td>";
      Html::autocompletionTextField($this, "name", ['size' => 34]);
      echo "</td><td>".__('Link', 'webresources')."</td><td>";
      echo Html::input('link', [
         'value'  => $this->fields['link'] ?? ''
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Icon', 'webresourcecs')."</td><td>";
      echo Html::input('icon', [
         'value'  => $this->fields['icon'] ?? ''
      ]);
      echo "</td>";

      echo "<td>".PluginWebresourcesCategory::getTypeName(1)."</td>";
      echo "<td>";
      Dropdown::show(PluginWebresourcesCategory::class, [
         'value'  => $this->fields['plugin_webresources_categories_id'] ?? 0
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Icon Color', 'webresourcecs')."</td><td>";
      Html::showColorField('color', [
         'value'  => $this->fields['color'] ?? '#000000'
      ]);
      echo '&nbsp;';
      Html::showToolTip(__('Only applies to FontAwesome icons and not images', 'webresources'));
      echo "</td>";

      echo "<td></td><td></td></tr>";

      $this->showFormButtons($options);

      return true;
   }

   protected function getShowVisibilityDropdownParams() {
      return [
         'type'  => '__VALUE__',
         'right' => self::$rightname
      ];
   }
}
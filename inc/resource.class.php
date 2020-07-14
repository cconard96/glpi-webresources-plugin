<?php

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
         $where = [
            "{$res_user_table}.users_id" => Session::getLoginUserID(),
         ];
      } else {
         $where = [
            0
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
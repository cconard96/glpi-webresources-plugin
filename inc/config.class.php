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
 * PluginWebresourcesConfig class
 */
class PluginWebresourcesConfig extends CommonDBTM
{

   static protected $notable = true;

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if (!$withtemplate && $item->getType() === 'Config') {
         return __('Web Resources', 'webresources');
      }
      return '';
   }

   public function showForm($ID, array $options = []): bool
   {
      if (!Session::haveRight('config', UPDATE)) {
         return false;
      }
      $config = self::getConfig();

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";

      echo "<table class='tab_cadre_fixe'><thead>";
      echo "<th colspan='4'>" . __('General Settings', 'webresources') . '</th></thead>';
      echo '<td>' . __('Plugin Menu', 'webresources') . '</td>';
      echo '<td>';
      Dropdown::showFromArray('menu', [
         'management'   => __('Management'),
         'plugins'      => _n('Plugin', 'Plugins', Session::getPluralNumber()),
         'tools'        => __('Tools'),
      ], ['value' => $config['menu'] ?? 'plugins']);
      echo '</td><td></td><td>';
      echo '</td></tr></table>';

      echo "<table class='tab_cadre_fixe'><thead>";
      echo "<th colspan='4'>" . __('Favicon Settings', 'webresources') . '</th></thead>';
      echo '<td>' . __('Use DuckDuckGo Favicon Service', 'webresources') . '</td>';
      echo '<td>';
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      echo "<input type='hidden' name='config_context' value='plugin:Webresources'>";
      Dropdown::showYesNo('use_duckduckgo', $config['use_duckduckgo'] ?? 0);
      echo '</td><td>' .__('Use Google Favicon Service', 'webresources'). '</td><td>';
      Dropdown::showYesNo('use_google', $config['use_google'] ?? 0);
      echo '</td></tr></table>';

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save'). '">';
      echo '</td></tr>';
      echo '</table>';
      echo '</div>';
      Html::closeForm();
      return true;
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() === 'Config') {
         $config = new self();
         $config->showForm(-1);
      }
   }

   public static function getConfig() : array
   {
      static $config = null;
      if ($config === null) {
         $config = Config::getConfigurationValues('plugin:Webresources');
      }

      return $config;
   }
}

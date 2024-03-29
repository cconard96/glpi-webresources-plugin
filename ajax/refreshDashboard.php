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

include ('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated('webresources')) {
   Html::displayNotFoundError();
}

Html::header_nocache();

Session::checkLoginUser();

if (!isset($_REQUEST['context'])) {
   throw new RuntimeException('Required argument missing!');
}

if (empty($_REQUEST['context'])) {
    $_REQUEST['context'] = 'personal';
}
if (!isset($_REQUEST['view_mode'])) {
    $_REQUEST['view_mode'] = 'grid';
}

header('Content-Type: text/html', true);
switch ($_REQUEST['view_mode']) {
   case 'grid':
      echo PluginWebresourcesDashboard::getDashboardContentGrid($_REQUEST['context']);
      break;
   case 'list':
      echo PluginWebresourcesDashboard::getDashboardContentList($_REQUEST['context']);
      break;
   default:
      throw new RuntimeException('Invalid view mode!');
}

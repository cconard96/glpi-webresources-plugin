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

Session::checkLoginUser();

$config = Config::getConfigurationValues('plugin:Webresources', ['menu']);
Html::header(PluginWebresourcesDashboard::getTypeName(Session::getPluralNumber()), '', $config['menu'] ?? 'plugins', 'PluginWebresourcesDashboard');

$context = $_GET['context'] ?? 'personal';
PluginWebresourcesDashboard::showDashboard($context);

Html::footer();

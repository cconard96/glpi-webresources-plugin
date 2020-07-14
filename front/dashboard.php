<?php

include ('../../../inc/includes.php');

Session::checkLoginUser();

Html::header(PluginWebresourcesDashboard::getTypeName(Session::getPluralNumber()), '', 'plugins', 'PluginWebresourcesResource');

PluginWebresourcesDashboard::showDashboard();

Html::footer();
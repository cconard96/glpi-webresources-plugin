<?php

include ('../../../inc/includes.php');

Session::checkLoginUser();

Html::header(PluginWebresourcesDashboard::getTypeName(Session::getPluralNumber()), '', 'plugins', 'PluginWebresourcesDashboard');

PluginWebresourcesDashboard::showDashboard();

Html::footer();
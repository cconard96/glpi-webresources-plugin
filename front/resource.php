<?php

include ('../../../inc/includes.php');

Session::checkLoginUser();

Html::header(PluginWebresourcesResource::getTypeName(Session::getPluralNumber()), '', 'plugins', 'PluginWebresourcesDashboard');

Search::show(PluginWebresourcesResource::class);

Html::footer();
<?php

include ('../../../inc/includes.php');

Session::checkLoginUser();

Html::header(PluginWebresourcesResource::getTypeName(Session::getPluralNumber()), '', 'plugins', 'PluginWebresourcesResource');

Search::show(PluginWebresourcesResource::class);

Html::footer();
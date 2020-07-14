<?php

global $CFG_GLPI;
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
include GLPI_ROOT . "/inc/includes.php";
include_once GLPI_ROOT . '/tests/GLPITestCase.php';
include_once GLPI_ROOT . '/tests/DbTestCase.php';
$plugin = new \Plugin();
$plugin->checkStates(true);
$plugin->getFromDBbyDir('webresources');
if (!plugin_webresources_check_prerequisites()) {
  echo "\nPrerequisites are not met!";
  die(1);
}
if (!$plugin->isInstalled('webresources')) {
  $plugin->install($plugin->getID());
}
if (!$plugin->isActivated('webresources')) {
  $plugin->activate($plugin->getID());
}
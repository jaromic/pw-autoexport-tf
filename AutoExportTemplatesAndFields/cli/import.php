#!/usr/bin/php
<?php

// bootstrap ProcessWire:
require_once(__DIR__ . "/../../../../index.php");

function importAll() {
	$log = Wire::getFuel('log');
    $modules = Wire::getFuel('modules');

    $moduleName = 'AutoExportTemplatesAndFields';
    $module = $modules->get($moduleName);
    if(!$module) {
        echo "module '$moduleName' is not installed\n";
        exit(1);
    }

    $persistDirectory = $module->data['persistDirectory'] or $module->getDefaultConfig()['persistDirectory'];
    $persistDirectory =  $module->getDefaultConfig()['persistDirectory']; // TODO

    $msg="starting manual import from '$persistDirectory'...";
	$log->message($msg); echo "$msg\n";

	$msg="manual import from '$persistDirectory' complete...";
	$log->message($msg); echo "$msg\n";
}

backupDB();
importAll();

?>

#!/usr/bin/php
<?php

namespace ProcessWire;

/**
 * entry point
 */
function main() {

    // bootstrap ProcessWire:
    require_once(__DIR__ . "/../../../../index.php");

    // initialize logging:
    global $log;
	$log = Wire::getFuel('log');

    // handle command line and invoke functionality accordingly:
    
    $options=getopt('r:i',array('restore:','import'));
    
    try {
        if(array_key_exists('i', $options) or array_key_exists('import', $options)) {
            // backup DB and import field/template data:
            importAll();
        } elseif( (array_key_exists('r', $options) and $restorePath = $options['r']) or 
                  (array_key_exists('restore', $options) and $restorePath = $options['restore'])
                  ) {
            // restore specified DB backup:
            out("Restoring from '$restorePath'...");
            restoreDB($restorePath);
        } else {
            usage();
        }
    } catch (Exception $e) {
        err("An error occurred - aborting: " . $e->getMessage());
        exit(1);
    }
}

/**
 * outputs usage message
 */
function usage() {
    global $argv;
    echo "usage: " . $argv[0] . " [ -i | --import ] [ -r <path> | --restore <path> ]" ;
}

/**
 * prints message to stdout and logs it via PWs $log
 */
function out($msg) {
    global $log;

	$log->message($msg); 
    echo "$msg\n";
}

/**
 * prints message to stdout and logs it as an error via PWs $log
 */
function err($msg) {
    global $log;

    $log->error($msg);
    echo "$msg\n";
}

/**
 * backs up database to the specified path using WireDatabaseBackup
 * @param $backupPath
 */
function backupDB($backupPath) {
    $db = Wire::getFuel('database');
    $backup = $db->backups();
    $file = $backup->backup();
    if($file) {
        return $file; 
    } else {
        throw new WireException("Backup failed: " . print_r($backup->errors(), true));
    } 
}

/**
 * restores database from the specified path using WireDatabaseBackup
 * @param $restorePath
 */
function restoreDB($restorePath) {
    $db = Wire::getFuel('database');
    $backup = $db->backups();
    $success = $backup->restore($restorePath); 
    if(!$success) {
        throw new WireException("Restore failed: " . print_r($backup->errors(), true));
    }
}

/**
 * @param $directory directory to look for $file
 * @param $file file to import data from
 * @return array data for import that has been read and JSON-decoded from the specified file
 */
function readAndDecodeData($directory, $filename) {
    $data = file_get_contents($directory . DIRECTORY_SEPARATOR . "$filename");
    
    if(FALSE === $data) {
        throw new Exception("Could not read data from '$filename' in '$directory'");
    } else {
        return wireDecodeJSON($data);
    }
}

/**
 * imports all supported data found in the $persistDirectory configured via the
 * AutoExportTemplatesAndFields module 
 */
function importAll() {
    global $log;
    $modules = Wire::getFuel('modules');

    // get AutoExportTemplatesAndFields module:
    $moduleName = 'AutoExportTemplatesAndFields';
    $module = $modules->get($moduleName);
    if(!$module) {
        out("module '$moduleName' is not installed");
        exit(1);
    }

    // get persistDirectory configuration setting
    $persistDirectory = $module->persistDirectory or $module->getDefaultConfig()['persistDirectory'];
    if($persistDirectory==null or $persistDirectory=='') {
        throw new WireException ("Invalid or empty persist directory setting");
    }

    // create backup of DB: 
    out("backing up DB...");
    $file=backupDB($persistDirectory);
    out("backup to '$file' complete.");

    out("starting manual import from '$persistDirectory'...");

    // disable export for the duration of the import:
    $module->setExportDisabled(true);

    // define which collection object and object class to use for import of each file:
    $importFunctionality = array (
            'fields.json' => array(
                'collectionObject' => Wire::getFuel('fields'), 
                'exportableClassName' => 'Field', 
            ),
            'fieldgroups.json' => array(
                'collectionObject' => Wire::getFuel('fieldgroups'), 
                'exportableClassName' => 'FieldGroup', 
            ),
            'templates.json' => array(
                'collectionObject' => Wire::getFuel('templates'), 
                'exportableClassName' => 'Template', 
            ),
    );

    // using above settings, read, decode, and import each file:
    try {
        foreach($importFunctionality as $file=>$importParameters) {
            out("importing data from '$file'...");

            // read and JSON decode data from file:
            $data=readAndDecodeData($persistDirectory, $file);
            if(!$data) throw new WireException("file '$file': Invalid import data");

            // make ProcessWire import the data:
            importGeneralData($importParameters['collectionObject'], $importParameters['exportableClassName'], $data);
        }
        out("manual import from '$persistDirectory' complete...");
    } catch (Exception $e) {
        err($e->getMessage());
    } finally {
        // re-enable export:
        $module->setExportDisabled(false);
    }
}

/**
 * @param $collectionObject instance of the PW API class capable of returning a list of objects of the desired type (e.g. Fields)
 * @param $exportableClassName string, class name of singular objects of the desired type (e.g. Field)
 * @param $collectionData array containing data for all objects of the desired type (e.g. multiple fields) for import
 * @throws WireException
 */
function importGeneralData($collectionObject, $exportableClassName, array $collectionData) {
    global $log;

    $sanitizer = Wire::getFuel('sanitizer');

    // loop through each item of the collection:
    foreach($collectionData as $name => $data) {

        // sanitize the item's name:
        $name = $sanitizer->name($name);
        out("importing $exportableClassName '$name'...");

        // get or create the item object:
        if($collectionObject->get($name)) { // use existing object
            $object=$collectionObject->get($name);
        } else { // create new object
            $object= new $exportableClassName($name);
        }

        // configure the object according to import data:
        $object->setImportData($data);

        // persist the object so it survives this invocation:
        if($exportableClassName=='Template') {
            // special handling for templates: fieldgroups have to be saved
            // first (code borrowed from
            // ProcessTemplateExportImport::saveItem()):
            $fieldgroup = $object->fieldgroup;
            $fieldgroup->save();
            $fieldgroup->saveContext();
            $object->save();
            if(!$object->fieldgroup_id) {
                $object->setFieldgroup($fieldgroup);
                $object->save();
            }
        } else {
            // normal handling for fields, field groups:
            $object->save();
        }
    }
}

main();
?>

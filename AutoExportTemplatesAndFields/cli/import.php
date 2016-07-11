#!/usr/bin/php
<?php

function main($argc, $argv) {
    
    // bootstrap ProcessWire:
    require_once(__DIR__ . "/../../../../index.php");

    // do the import:
    importAll();
}

function out($msg) {
    global $log;

	$log->message($msg); 
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
    if($file) print_r($backup->notes()); 
      else print_r($backup->errors()); 
     return $file; 
}

/**
 * @param $directory directory to look for $file
 * @param $file file to import data from
 * @return array data for import that has been read and JSON-decoded from the specified file
 */
function importData($directory, $filename) {
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

    out("backing up DB...");
    $file=backupDB($persistDirectory);
    out("backup to '$file' complete.");

    out("starting manual import from '$persistDirectory'...");

    $module->setExportDisabled(true);

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

    try {
        foreach($importFunctionality as $file=>$importParameters) {
            out("importing data from '$file'...");
            $data=importData($persistDirectory, $file);
            if(!$data) throw new WireException("file '$file': Invalid import data");

            importGeneralData($importParameters['collectionObject'], $importParameters['exportableClassName'], $data);
        }
        out("manual import from '$persistDirectory' complete...");
    } catch (Exception $e) {
        $log->error($e->getMessage()); 
        echo $e->getMessage()."\n";
    }
    
    $module->setExportDisabled(false);
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

    foreach($collectionData as $name => $data) {

        $name = $sanitizer->name($name);
        echo "importing $exportableClassName '$name'...\n"; // TODO

        if($collectionObject->get($name)) { // existing object
            $object=$collectionObject->get($name);
        } else { // new object
            $object= new $exportableClassName($name);
        }

        $object->setImportData($data);
        if($exportableClassName=='Template') {
            $fieldgroup = $object->fieldgroup;
            $fieldgroup->save();
            $fieldgroup->saveContext();
            $object->save();
            if(!$object->fieldgroup_id) {
                $object->setFieldgroup($fieldgroup);
                $object->save();
            }
        } else {
            $object->save();
        }
    }
}

main($argc, $argv);
?>

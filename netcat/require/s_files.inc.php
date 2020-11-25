<?php

/* $Id: s_files.inc.php 7933 2012-08-09 14:20:11Z alive $ */

function getFileCount($classID, $systemTableID) {
    global $db;

    return $db->get_var("SELECT COUNT(*) FROM `Field`
    WHERE ".($systemTableID ? "`System_Table_ID` = '".intval($systemTableID)."'" : "`Class_ID` = '".intval($classID)."'")."
    AND `TypeOfData_ID` = 6");
}

function unhtmlentities($string) {
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);

    return strtr($string, $trans_tbl);
}

# удаление конкретного файла

function DeleteFile($field_id, $field_name, $classID, $systemTableName, $message, $trashxml = NULL) {
    global $nc_core, $db;

    $field_id = intval($field_id);
    $classID = intval($classID);
    $message = intval($message);
    $systemTableName = $db->escape($systemTableName);
    if (!nc_preg_match("/^[a-z0-9_]+$/i", $field_name)) return 0;

    eval("global \$f_".$field_name."_old;");

    $field = $db->get_var("SELECT `".$field_name."`
    FROM `".($systemTableName ? $systemTableName : "Message".$classID."")."` 
    WHERE `".($systemTableName ? $systemTableName : "Message")."_ID` = '".$message."'");

    if (is_object($trashxml)) {
        $field = $trashxml->query("/netcatml/messages/message[@message_id='".$message."']/".$field_name);
        $field = $field->item(0)->textContent;
    }

    if (!$field) return;

    $field_array = explode(':', $field);
    $name = $field_array[0];
    // имя на диске при использовании ФС Original
    $name_on_disk = $field_array[3] ? $field_array[3] : '';
    // Расширение файла
    $ext = substr($name, strrpos($name, "."));
    // Полный пусть к файлу
    $fullPathToFile = $nc_core->FILES_FOLDER;
    $fullPathToFile.= $name_on_disk ? $name_on_disk : $field_id."_".$message.$ext;
    //путь в случае использовании Filetable
    if ($systemTableName == 'User') {
        $File_Path = "u/";
    } elseif ($systemTableName == 'Template') {
        $File_Path = "t/";
    } elseif ($systemTableName == 'Catalogue') {
        $File_Path = "c/";
    } elseif ($systemTableName) {
        $File_Path = $message."/";
    } else {
        list($subdivid, $subclassid) = $db->get_row("SELECT `Subdivision_ID`, `Sub_Class_ID` FROM `Message".$classID."` WHERE `Message_ID` = '".$message."'", ARRAY_N);
        $File_Path = $subdivid."/".$subclassid."/";
    }

    $q = $db->get_row("SELECT `ID`, `Virt_Name` FROM `Filetable` WHERE `Message_ID` = '".$message."' AND `Field_ID` = '".$field_id."'", ARRAY_N);

    if ($db->num_rows) {
        list($fs_id, $fs_virt_name) = $q;
        //delte file
        if (is_writable($nc_core->FILES_FOLDER.$File_Path.$fs_virt_name)) {
            unlink($nc_core->FILES_FOLDER.$File_Path.$fs_virt_name);
        }
        $db->query("DELETE FROM `Filetable` WHERE `ID` = '".$fs_id."' LIMIT 1");
    }

    eval("\$f_".$field_name."_old = \"\";");

    $res = $db->query("UPDATE `".($systemTableName ? $systemTableName : "Message".$classID)."` SET `LastUpdated` = `LastUpdated`, ".$field_name." = '' WHERE `".($systemTableName ? $systemTableName : "Message")."_ID` = '".$message."'");

    if ($res && is_writable($fullPathToFile)) {
        unlink($fullPathToFile);
    }
    if (is_object($trashxml) && is_writable($fullPathToFile)) {
        unlink($fullPathToFile);
    }
}

# выборка файлов из объекта при его удалении, чтобы их также удалить

function DeleteMessageFiles($classID, $message, $trashfile = '') {
    global $db, $FILES_FOLDER;
    static $storage = array();

    $classID = intval($classID);
    $message = intval($message);

    if (empty($storage[$classID])) {
        $storage[$classID] = $db->get_results("SELECT a.`Field_ID`, a.`Field_Name`, b.`System_Table_Name` FROM `Field` AS a
      LEFT JOIN `System_Table` AS b ON a.`System_Table_ID` = b.`System_Table_ID`
      WHERE a.`Class_ID` = '".$classID."' AND a.`TypeOfData_ID` = 6", ARRAY_N);
    }

    if (empty($storage[$classID])) return false;

    if ($trashfile) {
        $ncCore = nc_Core::get_object();
        /* @var $ncCore nc_Core */
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->load($ncCore->TRASH_FOLDER.$trashfile);
        $xpath = new DOMXPath($doc);
    }

    foreach ($storage[$classID] as $field) {
        list($field_id, $field_name, $systenTableName) = $field;
        DeleteFile($field_id, $field_name, $classID, $systemTableName, $message, $xpath);
    }
}

function DeleteSystemTableFiles($table, $message_id) {
    global $db;

    $systables = array('Catalogue' => 1, 'Subdivision' => 2, 'User' => 3, 'Template' => 4);

    if (!in_array($table, array_keys($systables))) {
        trigger_error("Wrong parameter \$table for DeleteSystemTableMessageFiles() [".$table."]", E_USER_WARNING);
        return;
    }

    $res = $db->get_results("SELECT `Field_ID`, `Field_Name`
    FROM `Field`
    WHERE `Class_ID` = 0
    AND `System_Table_ID` = '".$systables[$table]."'
    AND `TypeOfData_ID` = 6", ARRAY_N);

    if (empty($res)) return false;

    foreach ($res as $field) {
        list($field_id, $field_name) = $field;
        DeleteFile($field_id, $field_name, 0, $table, $message_id);
    }
}

/**
 * Удаление директории файлов шаблона в разделе $cc
 * @param int $cc идентификатор шаблона в разделе
 * @return bool
 */
function DeleteSubClassDir($cc) {
    global $nc_core, $db, $FILES_FOLDER;

    $dir_path = $nc_core->db->get_row("SELECT * FROM `Sub_Class` WHERE `Sub_Class_ID` = '".intval($cc)."' ", ARRAY_A);

    $path = $FILES_FOLDER.$dir_path['Subdivision_ID']."/".$dir_path['Sub_Class_ID'];

    if (is_dir($path) && count(glob($path."/*")) === 0 && is_writable($path)) {
        return @rmdir($path);
    }

    return false;
}

/**
 * Удаление директории раздела с идентификатором $sub
 * @param int $sub идентификатор раздела
 * @return bool
 */
function DeleteSubdivisionDir($sub) {
    global $FILES_FOLDER, $nc_core;

    $path = $FILES_FOLDER.$sub;
    try {
        $nc_core->files->delete_dir($path);
    } catch (nc_Exception_Files_Not_Rights $e) {
        ; //nc_print_status(sprintf(NETCAT_ERROR_UNABLE_TO_DELETE_FILES, $path), 'error');
    }

    return false;
}

/**
 * Удаление директории компонента $cc в  разделе $sub
 *
 * @param int $sub идентификатор раздела
 * @param int $cc идентификатор компонента в разделе
 *
 * @return bool
 */
function DeleteSubClassDirAlways($sub, $cc) {
    global $FILES_FOLDER;

    $path = $FILES_FOLDER.$sub."/".$cc;

    if (!is_dir($path)) return false;

    $files = array();
    $dh = opendir($path);

    if (!is_resource($dh)) return false;

    while (false !== ($filename = readdir($dh))) {
        if ($filename == "." || $filename == "..") continue;
        if (is_writable($path."/".$filename)) {
            unlink($path."/".$filename);
        }
    }
    closedir($dh);

    if (count(glob($path."/*")) === 0 && is_writable($path)) {
        return rmdir($path);
    }
}

/**
 * Удаление файлов шаблона в разделе $cc с идентификатором шаблона $classID
 * @param int $cc идентификатор шаблона в разделе
 * @param int $classID идентификатор шаблона
 * @return bool
 */
function DeleteSubClassFiles($cc, $classID) {
    global $db;

    $cc+= 0;
    $classID+= 0;

    $res = $db->get_results("SELECT `Field_ID`, `Field_Name`
    FROM `Field`
    WHERE `Class_ID` = '".$classID."'
    AND `System_Table_ID` = 0
    AND `TypeOfData_ID` = 6", ARRAY_A);

    if ($res) {
        foreach ($res as $field) {
            $messages = $db->get_col("SELECT `Message_ID`, `".$field['Field_Name']."` FROM `Message".$classID."` WHERE `Sub_Class_ID` = '".$cc."'");
            if ($messages) {
                foreach ($messages as $message_id) {
                    DeleteFile($field['Field_ID'], $field['Field_Name'], $classID, "", $message_id);
                }
            }
        }
    }

    // delete dir
    DeleteSubClassDir($cc);

    return true;
}

function nc_multifield_sql_exec($message, array $SQL_multifield) {
    $SQL_multifield = str_replace('%msgID%', $message, join(', ', array_reverse($SQL_multifield)));
    if ($SQL_multifield) {
        $SQL = "INSERT INTO Multifield(`Field_ID`, `Message_ID`, `Name`, `Size`, `Path`, `Preview`) 
                        VALUES $SQL_multifield";
        nc_Core::get_object()->db->query($SQL);
    }
}

function nc_check_availability_candidates_for_delete_in_multifile_and_delete() {
    if (isset($_POST['del_multifile'][0])) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        
        $del_multifile = array_map('intval', $_POST['del_multifile']);
        $SQL = "SELECT Path,
                       Preview
                    FROM Multifield
                        WHERE ID IN (" . join(', ', $del_multifile) . ")";
        $file_for_del = $db->get_results($SQL);
        
        if ($file_for_del) {
            $array_file_for_del = array();
            foreach ($file_for_del as $file) {
                $array_file_for_del[] = $file->Path;
                if ($file->Preview) {
                    $array_file_for_del[] = $file->Preview;
                }
            }
        }
        $SQL = "DELETE 
                FROM Multifield
                    WHERE ID IN(" . join(', ', $del_multifile) . ")";
        $db->query($SQL);
        
        foreach ($array_file_for_del as $file) {
            $file = nc_standardize_path_to_file(join('/', array($nc_core->DOCUMENT_ROOT, $nc_core->SUB_FOLDER, $file)));
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
?>
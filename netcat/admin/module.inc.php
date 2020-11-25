<?

/* $Id: module.inc.php 8323 2012-11-01 14:14:25Z vadim $ */

function InsertSystemMessage($SysMessage, $Title) {
    global $db;

    $insert = "insert into SystemMessage ( Date, Description, Checked, Message) values ( NOW(),";
    $insert .= "\"".TOOLS_MODULES_INSTALLEDMODULE." \\\"".$Title."\\\"\", ";
    $insert .= "0 ,";
    $insert .= "\"".str_replace("\r", "", str_replace("\n", "", addslashes($SysMessage)))."\")";

    $db->query($insert);
    return $db->insert_id;
}

function UpdateParameters($parameters, $NAME, $VAL) {

    $parameters = str_replace(array("\r\n", "\n", "\r"), "&", $parameters);
    parse_str($parameters, $VARS);

    $VARS[$NAME] = $VAL;

    $result = "";
    while (list($var1, $val1) = each($VARS)) {
        $VARS[$var1] = trim($val1);
        $result .= $var1."=".$val1."\r\n";
    }
    return $result;
}

function SelectParentSub($phase_from=2, $phase_to=3) {
    global $db, $CatalogueID;

    echo "<font color=gray>".TOOLS_MODULES_MSG_CHOISESECTION."<br><br>";


    $Result = $db->get_results("SELECT Catalogue_ID,Catalogue_Name FROM Catalogue ORDER BY Catalogue_ID", ARRAY_N);
    if ($db->num_rows == 1) {
        list($CatalogueID, $CatalogueName) = $Result[0];
    }

    if (!$CatalogueID) {
        if ($db->num_rows) {
            echo "<form method=get>";
            $formtoget = true;
            echo "<br><font color=gray>".CONTROL_USER_SELECTSITE.": <select name=CatalogueID>";

            foreach ($Result as $cat) {
                echo "<option value=${cat[0]}>${cat[0]}: ${cat[1]}";
            }
            echo "</select><input type=hidden name=phase value=$phase_from>\n<input type=submit ></form><br>";
        } else {
            echo CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_NONE."<br>.";
        }
    } else {
        $CatalogueName = $db->get_var("SELECT Catalogue_Name FROM Catalogue WHERE Catalogue_ID='".$CatalogueID."'");
        echo CONTROL_CONTENT_CATALOUGE_ONESITE.": <b>".$CatalogueName."</b><br>";
    }

    if ($CatalogueID) {
        $Result = $db->get_results("SELECT Subdivision_ID,Subdivision_Name FROM Subdivision WHERE Catalogue_ID='".$CatalogueID."' ORDER BY Subdivision_ID", ARRAY_N);
        if ($db->num_rows == 1) {
            list($SubdivisionID, $SubdivisionName) = $Result[0];
        }

        if (!$SubdivisionID) {
            if ($db->num_rows) {
                if (!$formtoget) {
                    echo "<form method=get>";
                    $formtoget = true;
                }
                echo "<font color=gray>".CONTROL_USER_SELECTSECTION.": <select name=SubdivisionID>";
                echo "<option value=0>0: ".CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ROOT;

                foreach ($Result as $sub) {
                    echo "<option value=${sub[0]}>${sub[0]}: ${sub[1]}";
                }
                echo "</select><input type=hidden name=phase value=$phase_to>\n<input type=hidden name=CatalogueID value=".$CatalogueID."><br><br><input type=submit value='".CONTROL_CLASS_CONTINUE."'></form><br>";
            } else {
                echo "<br>".CONTROL_USER_NOONESECSINSITE;
            }
        } else {
            $SubdivisionName = $db->get_var("SELECT Subdivision_Name FROM Subdivision WHERE Subdivision_ID='".$SubdivisionID."'");

            echo "<form method=get>\n" . CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION . ": <b>".$SubdivisionName."</b><br>";
            echo "<input type=hidden name=phase value=$phase_to>\n<input type=hidden name=CatalogueID value=".$CatalogueID.">\n<input type=hidden name=SubdivisionID value=".$SubdivisionID.">\n<input type=submit>\n</form>";
        }
    }
}

function InsertSub($SubdivisionName, $EnglishName, $ExternalURL, $ReadAccessID, $WriteAccessID, $EditAccessID, $SubscribeAccessID, $ModerationID, $ClassID, $ParentSubID, $CatalogueID, $DefaultAction, $Checked, $UseEditDesignTemplate = 0) {
    global $db;

    $Priority = $db->get_var("SELECT MAX(Priority) from Subdivision where Parent_Sub_ID='".$ParentSubID."' AND Catalogue_ID='".$CatalogueID."'");

    $Priority += 1;

    $insert = "insert into Subdivision (";
    $insert .= "Catalogue_ID, Parent_Sub_ID, Subdivision_Name, Checked, ExternalURL, EnglishName, Created, Priority, UseEditDesignTemplate";
    $insert .= ") values (";
    $insert .= intval($CatalogueID).",";
    $insert .= intval($ParentSubID).",";
    $insert .= "\"".$db->escape($SubdivisionName)."\",";
    $insert .= intval($Checked).",";
    $insert .= "\"".$db->escape($ExternalURL)."\",";
    $insert .= "\"".$db->escape($EnglishName)."\",";
    $insert .= "NOW(),";
    $insert .= intval($Priority).",";
    $insert .= intval($UseEditDesignTemplate).")";

    $res = $db->query($insert);
    $SubdivisionID = $db->insert_id;

    if (!$SubdivisionID) return;

    $insert = "insert into Sub_Class (";
    $insert .= "Subdivision_ID, Catalogue_ID, Class_ID, Sub_Class_Name, EnglishName, DefaultAction, Created, Read_Access_ID, Write_Access_ID, Edit_Access_ID, Subscribe_Access_ID, Moderation_ID, Checked";
    $insert .= ") values(";
    $insert .= intval($SubdivisionID).", ";
    $insert .= intval($CatalogueID).", ";
    $insert .= intval($ClassID).", ";
    $insert .= "\"".$SubdivisionName."\", ";
    $insert .= "\"".$EnglishName."\", ";
    $insert .= "\"".$DefaultAction."\", ";
    $insert .= "NOW(), ";
    $insert .= "\"".intval($ReadAccessID)."\", ";
    $insert .= "\"".intval($WriteAccessID)."\", ";
    $insert .= "\"".intval($EditAccessID)."\", ";
    $insert .= "\"".intval($SubscribeAccessID)."\", ";
    $insert .= "\"".intval($ModerationID)."\", ";
    $insert .= "1)";

    $db->query($insert);

    printf(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SECTIONWASCREATED, $SubdivisionName);
    return $SubdivisionID;
}

function GetModuleKeyword($ModuleID) {
    global $db;
    return $db->get_var("select Keyword from Module where Module_ID='".intval($ModuleID)."'");
}

function GetModuleName($ModuleID) {
    global $MODULE_FOLDER, $nc_core;

    if (!$ModuleID) return(constant("NETCAT_MODULE_DEFAULT"));
    $ModuleID = intval($ModuleID);

    $rs = $nc_core->db->get_row("SELECT Module_Name, Keyword FROM Module WHERE Module_ID='".$ModuleID."' LIMIT 1", ARRAY_A);

    if ($rs['Keyword'] != 'default' and $ModuleID >= 1) {
        if (file_exists($MODULE_FOLDER.$rs['Keyword']."/".MAIN_LANG.".lang.php")) {
            require_once($MODULE_FOLDER.$rs['Keyword']."/".MAIN_LANG.".lang.php");
        } else {
            require_once($MODULE_FOLDER.$rs['Keyword']."/en.lang.php");
        }
    }
    return constant($rs['Module_Name']);
}

function GetHelpURL($ModuleID) {
    global $db;
    return $db->get_var("select Help_URL from Module where Module_ID='".intval($ModuleID)."'");
}

function DeleteModule($ModuleID) {
    global $db;

    $delete = "delete from Module where Module_ID='".intval($ModuleID)."'";
    $Result = $db->query($delete);
}

############################################################

function SureRemoveDir($dir, $first=1) {
    if (!$dh = @opendir($dir)) return;
    while (($obj = readdir($dh))) {
        if ($obj == '.' || $obj == '..') continue;
        if (!@unlink($dir.'/'.$obj)) {
            SureRemoveDir($dir.'/'.$obj, 2);
        } else {
            $file_deleted++;
        }
    }
    if ($first == 2) if (@rmdir($dir)) $dir_deleted++;
}

# Удаление содержимого директории, НЕ САМОЙ директории

function DeleteFilesInDirectory($Directory) {
    $win_Directory = str_replace("/", "\\", $Directory);

    if (substr(php_uname(), 0, 7) == "Windows") {
        chdir($win_Directory);

        $dir_list = recursive_listdir($win_Directory);

        for ($i = 0; $i < count($dir_list[files]); $i++)
            unlink($dir_list[files][$i]);
        for ($i = (count($dir_list[dirs]) - 1); $i > -1; $i--)
            rmdir($dir_list[dirs][$i]);
    } else {

        SureRemoveDir($Directory);
    }
}

function recursive_listdir($base) {
    static $filelist = array();
    static $dirlist = array();
    if (is_dir($base)) {
        $dh = opendir($base);
        while (false !== ($dir = readdir($dh))) {
            if (is_dir($base."\\".$dir) && $dir !== '.' && $dir !== '..') {
                $subbase = $base."\\".$dir;
                $dirlist[] = $subbase;
                $subdirlist = recursive_listdir($subbase);
            } elseif (is_file($base."\\".$dir) && $dir !== '.' && $dir !== '..') {
                $filelist[] = $base."\\".$dir;
            }
        }
        closedir($dh);
    }
    $array[dirs] = $dirlist;
    $array[files] = $filelist;
    return $array;
}

###############################################################

function ExecSQL($FileWithSQL) {
    global $db, $nc_core;

    # сколько запросов вернули результат, сколько всего запросов
    $result = array("sqls" => 0, "total" => 0);

    $fp = fopen($FileWithSQL, "r");
    while (!feof($fp)) {
        $statement = chop(fgets($fp, 10240));
        if (strlen($statement) == 0) break;
        $statement = str_replace('%%MYSQL_CHARSET%%', $nc_core->MYSQL_CHARSET, $statement);
        if (!$nc_core->NC_UNICODE)
                $statement = $nc_core->utf8->utf2win($statement);
        $db->query($statement);
        # если запрос выполнился и нет ошибок
        if (!$db->last_error) $result["sqls"]++;

        $result["total"]++;
    }
    fclose($fp);

    return $result;
}

###############################################################
?>
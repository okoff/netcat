<?php

/* $Id: SubClass.php 7232 2012-06-18 05:42:06Z alive $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."subdivision/function.inc.php");
require ($ADMIN_FOLDER."subdivision/subclass.inc.php");
require ($INCLUDE_FOLDER."s_files.inc.php");

$Delimeter = " &gt ";
$CatalogueURL = "".$ADMIN_PATH."catalogue/?phase=6&CatalogueID=";
$SubdivisionURL = "".$ADMIN_PATH."subdivision/?phase=4&SubdivisionID=";

$loc = new SubdivisionLocation($CatalogueID, $ParentSubID, $SubdivisionID);
$sh = new SubdivisionHierarchy($Delimeter, $CatalogueURL, $SubdivisionURL);

$main_section = "control";
$item_id = 1;
$Title1 = "<a href=".$ADMIN_PATH."catalogue/>".SECTION_CONTROL_CONTENT_CATALOGUE."</a>";
$Title1 .= $Delimeter.$sh->Link;

$Title2 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SECTION;
$Title2_1 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SITE;
$Title3 = "<a href=\"".$ADMIN_PATH."subdivision/SubClass.php?CatalogueID=".$CatalogueID."&SubdivisionID=".$SubdivisionID."\">".CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SECTION."</a>";
$Title4 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASS;
$Title5 = GetSubClassName($SubClassID);
$Title6 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASS;
$Title7 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_OPTIONSCLASS;
$Title8 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASSSITE;
$Title9 = "<a href=".$ADMIN_PATH."subdivision/SubClass.php?CatalogueID=".$CatalogueID.">".CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SITE."</a>";

if (in_array($phase, array(2, 4, 5))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

try {
    if (isset($phase)) {
        if ($SubClassID || $SubdivisionID) {
            $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'add');
        }
        $error = "";
        // сохранение формы
        if ($phase == 2 || $phase == 4) {
            // получение значения дополнительных настроек (CustomSettings)
            if (!$ClassID) {
                $SQL = "SELECT `Class_ID`
                            FROM `Sub_Class`
                                WHERE `Sub_Class_ID` = ".+$SubClassID;
                $ClassID = $db->get_var($SQL);
            }

            $SQL = "SELECT `CustomSettingsTemplate`
                        FROM `Class`
                            WHERE `Class_ID` = ".+($Class_Template_ID ? $Class_Template_ID : $ClassID);
            $settings_array = $db->get_var($SQL);

            if ($settings_array) {
                require_once($ADMIN_FOLDER."array_to_form.inc.php");
                $a2f = new nc_a2f($settings_array, 'CustomSettings');
                if (!$a2f->validate($_POST['CustomSettings'])) {
                    $error = $a2f->get_validation_errors();
                } else {
                    $a2f->save($_POST['CustomSettings']);
                    $CustomSettings = $a2f->get_values_as_string();
                }
            } else {
                $CustomSettings = "";
            }

            // проверка значений
            if ($SubClassName == "") {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_NAME;
            } elseif (!$nc_core->sub_class->validate_english_name($EnglishName)) {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID;
            } elseif (preg_match("/^[0-9]+$/", $EnglishName)) {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID;
            } elseif ((!IsAllowedSubClassEnglishName($EnglishName, $SubdivisionID, (int) $SubClassID) ) || ( $EnglishName == "" )) {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD;
            } elseif (is_object($a2f) && $a2f->has_errors()) {
                $error = CONTROL_CLASS_CUSTOM_SETTINGS_HAS_ERROR."<br>".$a2f->get_validation_errors();
            }
        } // of сохранение формы


        switch ($phase) {
            case 1:
                # форма добавления подраздела
                if (!$SubdivisionID)
                        BeginHtml($Title8, $Title1.$Delimeter.$Title9.$Delimeter.$Title8, "http://".$DOC_DOMAIN."/management/sites/sections/class/add/");
                else
                        BeginHtml($Title6, $Title1.$Delimeter.$Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/sites/sections/class/add/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSADD, $SubdivisionID, 0, 0);
                ActionForm(0, 2, 1);

                break;

            case 2:
                # собственно добавление подраздела
                BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSADD, $SubdivisionID, 0, 1);

                if ($error) {
                    nc_print_status($error, 'error');
                    ActionForm(0, 2, 1);
                    break;
                }

                $SubClassID = ActionSubClassCompleted(1);

                $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);

                if ($SubClassID) {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_ADD, 'ok');
                } else {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_ADD, 'error');
                }
                ActionForm($SubClassID, 4, 2);
                $UI_CONFIG->treeChanges['updateNode'][] = array("nodeId" => "sub-$SubdivisionID",
                        "href" => "#object.list(".$SubClassID.")"
                );
                break;

            case 3:
                # форма обновления подраздела
                BeginHtml($Title7, $Title1.$Delimeter.$Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/sites/sections/class/settings/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, NC_PERM_ACTION_EDIT, array($SubdivisionID, $SubClassID), 0, 0);
                $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);
                ActionForm($SubClassID, 4, 2);
                break;

            case 4:
                # собственно обновление подраздела
                BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, NC_PERM_ACTION_EDIT, array($SubdivisionID, $SubClassID), 0, 1);

                $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);

                if ($error) {
                    nc_print_status($error, 'error');
                    ActionForm($SubClassID, 4, 2);
                    break;
                }

                if (ActionSubClassCompleted(2) !== false) {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_EDIT, 'ok');
                } else {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_EDIT, 'error');
                }

                if (+$_REQUEST['isNaked']) {
                    ob_clean();
                    echo 'OK';
                    exit;
                }

                ActionForm($SubClassID, 4, 2);
                break;

            case 5:
                # операции с инфоблоками в разделе
                BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSDEL, $SubdivisionID, 0, 1);

                UpdateSubClassPriority();

                reset($_POST);
                foreach ($_POST AS $key => $val) {
                    if (strcmp(substr($key, 0, strlen("Delete")), "Delete") == 0) {
                        DeleteSubClass($val);
                    }
                }

                reset($_GET);
                foreach ($_GET AS $key => $val) {
                    if (strcmp(substr($key, 0, strlen("Delete")), "Delete") == 0) {
                        DeleteSubClass($val);
                    }
                }

                $update = "UPDATE `Subdivision` SET ";
                $update.= "`UseMultiSubClass`= '".$UseMultiSubClass."'";
                $update.= " WHERE `Subdivision_ID` = ".$SubdivisionID;
                $Result = $db->query($update);

                if ($db->get_var("SELECT COUNT(Sub_Class_ID) FROM Sub_Class WHERE Subdivision_ID = '".$SubdivisionID."'")) {
                    $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'list');
                    ShowList();
                } else {
                    $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'add');
                    ActionForm(0, 2, 1);
                    $UI_CONFIG->treeChanges['updateNode'][] = array("nodeId" => "sub-$SubdivisionID",
                            "href" => "#subclass.add(".$SubdivisionID.")",
                            "align" => "left"
                    );
                }

                if (+$_REQUEST['isNaked']) {
                    ob_clean();
                    echo 'OK';
                    exit;
                }

                break;

            case 6:

                # покажем меню операций для шаблона в разделе
                BeginHtml($Title5, $Title1.(($loc->SubdivisionID) ? $Delimeter.$Title3.$Delimeter.$Title5 : $Delimeter.$Title9.$Delimeter.$Title5), "http://".$DOC_DOMAIN."/management/sites/sections/class/info/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, NC_PERM_ACTION_INFO, array($SubdivisionID, $SubClassID), 0, 0);
                ShowSubClassMenu($SubClassID, 9, "index.php", 3, "SubClass.php", 12, "index.php");

                break;
        }
    } else {
        if (!$SubdivisionID) {
            BeginHtml($Title2_1, $Title1.$Delimeter.$Title2_1, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
        } else {
            BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
        }

        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSLIST, $SubdivisionID, 0, 0);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            $SubClassID_list = ShowList_for_modal();
            if ($_REQUEST['sub_class_id']) {
                ob_clean();
                $sub_class_id = +$_REQUEST['sub_class_id'];
                foreach ($SubClassID_list as $SubClass) {
                    if ($SubClass['ID'] == $sub_class_id) {
                        $SubClassID_list[0] = array ('ID' => $SubClass['ID'], 'name' => $SubClass['name']);
                    }
                }
            }

            ActionForm_for_modal_prefix($SubClassID_list, +$_REQUEST['sub_class_id']);
            ActionForm_for_modal_suffix();
            foreach ($SubClassID_list as $SubClass) {
                ActionForm_for_modal($SubClass['ID']);
            }
            exit;
        } else {
            $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'list', $SubClassID);
            ShowList ();
        }
    }
} catch (nc_Exception_DB_Error $e) {
    nc_print_status(sprintf(NETCAT_ERROR_SQL, $e->query(), $e->error()), 'error');
} catch (Exception $e) {
    nc_print_status($e->getMessage(), 'error');
}

EndHtml ();
?>
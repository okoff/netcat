<?php

/* $Id: catalog.inc.php 7691 2012-07-17 05:46:42Z alive $ */



/*
 * Delete Catalogue and all data within
 *
 * @param int Catalogue_ID
 */

function CascadeDeleteCatalogue($CatalogueID) {
    global $nc_core, $db, $MODULE_FOLDER;

    $CatalogueID = intval($CatalogueID);

    $SubClassResult = $db->get_results("SELECT `Subdivision_ID`, `Sub_Class_ID`, `Class_ID` FROM `Sub_Class` WHERE `Catalogue_ID` = '".$CatalogueID."'", ARRAY_N);

    $CatClassID = array();
    $_subdivision_arr = array();
    $_sub_class_arr = array();
    $_class_arr = array();
    $AddLockTables = "";
    if ($SubClassResult) {
        foreach ($SubClassResult AS $SubClassArray) {
            $AddLockTables.= ", `Message".$SubClassArray[2]."` WRITE";
            $CatClassID[] = $SubClassArray[0];
            $CatClassID[] = $SubClassArray[1];
            $CatClassID[] = $SubClassArray[2];
            // collect subdivisions
            if (!in_array($SubClassArray[0], $_subdivision_arr))
                    $_subdivision_arr[] = $SubClassArray[0];
            // collect subclasses
            if (!in_array($SubClassArray[1], $_sub_class_arr))
                    $_sub_class_arr[] = $SubClassArray[1];
            // collect classes
            if (!in_array($SubClassArray[2], $_class_arr))
                    $_class_arr[] = $SubClassArray[2];
        }
    }

    for ($i = 0; $i < count($CatClassID); $i+=3) {
        // get messages to delete
        if ($db->query("SELECT * FROM `Message".$CatClassID[$i + 2]."` WHERE `Sub_Class_ID` = '".$CatClassID[$i + 1]."'")) {
            $messagesToDelete = $db->get_col(NULL, 0);
            $messages_data = array_combine($db->get_col(NULL, 0), $db->get_results(NULL));
        }

        if (!empty($messagesToDelete)) {
            // delete messages
            $db->query("DELETE FROM `Message".$CatClassID[$i + 2]."` WHERE `Sub_Class_ID` = '".$CatClassID[$i + 1]."'");
            // execute core action
            $nc_core->event->execute("dropMessage", $CatalogueID, $CatClassID[$i], $CatClassID[$i + 1], $CatClassID[$i + 2], $messagesToDelete);
        }
    }

    if (nc_module_check_by_keyword("comments")) {
        include_once ($MODULE_FOLDER."comments/function.inc.php");
        // delete comment rules
        nc_comments::dropRule($db, array($CatalogueID));
        // delete comments
        nc_comments::dropComments($db, $CatalogueID, "Catalogue");
    }
    // delete related subclasses
    $db->query("DELETE FROM `Sub_Class` WHERE `Catalogue_ID` = '".$CatalogueID."'");
    for ($i = 0; $i < count($CatClassID); $i+=3) {
        // delete related dirs
        DeleteSubClassDirAlways($CatClassID[$i], $CatClassID[$i + 1]);
        // execute core action
        $nc_core->event->execute("dropSubClass", $CatalogueID, $CatClassID[$i], $CatClassID[$i + 1]);
    }
    // delete related subdivisions
    $db->query("DELETE FROM `Subdivision` WHERE `Catalogue_ID` = '".$CatalogueID."'");
    // execute core action
    $nc_core->event->execute("dropSubdivision", $CatalogueID, $_subdivision_arr);
    // delete related dirs
    foreach ($_subdivision_arr AS $sub_to_delete) {
        DeleteSubdivisionDir($sub_to_delete);
    }
    // delete catalogue
    $db->query("DELETE FROM `Catalogue` WHERE `Catalogue_ID` = '".$CatalogueID."'");
    // execute core action
    $nc_core->event->execute("dropCatalogue", $CatalogueID);

    return;
}

function HighLevelChildrenNumber($CatalogueID, $available = "") {
    global $db, $perm;

    // часть sql-запроса, ограничивающая выборку только объектами, которые пользователь может видеть
    $security_limit = "";

    // id разделов, которые пользователь может администрировать
    $sub_admin = $perm->listItems('subdivision');

    // id шаблонов в разделе, которые пользователь может администрировать
    $cc_admin = $perm->listItems('subclass');

    // id сайтов, которые пользователь видит (на основе $site_admin, $sub_admin, $cc_admin)
    $allowed_sites = array();

    // id разделов, которые администрирует пользователь, на основе $sub_admin + $cc_admin
    $sub_and_cc_admin = $sub_admin;
    if (is_array($cc_admin) && !empty($cc_admin)) {
        $in_str = join(", ", $cc_admin);
        if ($in_str) {
            $res = $db->get_results("SELECT `Subdivision_ID`
        FROM `Sub_Class`
        WHERE `Sub_Class_ID` IN ($in_str)", ARRAY_A);
            if (!empty($res)) {
                foreach ($res as $row) {
                    $sub_and_cc_admin[] = $row['Subdivision_ID'];
                }
            }
        }
    }

    if (is_array($sub_and_cc_admin) && !empty($sub_and_cc_admin)) {
        // получить родительские разделы для разделов, которые пользователь может
        // модерировать или администрировать
        $res = $db->get_results("SELECT parent.`Subdivision_ID`
      FROM `Subdivision` as parent, `Subdivision` as allowed
      WHERE allowed.`Subdivision_ID` IN (".join(",", array_unique($sub_and_cc_admin)).")
      AND allowed.`Hidden_URL` LIKE CONCAT(parent.`Hidden_URL`, '%')", ARRAY_A);

        // разделы, которые пользователь может видеть
        $allowed_subs = array();
        if (!empty($res)) {
            foreach ($res as $row) {
                // flatten array
                $allowed_subs[] = $row['Subdivision_ID'];
            }
        }

        // id разделов, которые являются дочерними для тех разделов, на которые
        // явно указаны права на администрирование -- эти права наследуются (as of 3.0)
        $sub_child_administrator = array();
        // права наследуются для дочерних узлов
        if (is_array($sub_admin) && !empty($sub_admin)) {
            $res = $db->get_results("SELECT child.`Subdivision_ID`, allowed.`Subdivision_ID` as Allowed_Subdivision_ID
          FROM `Subdivision` as child, `Subdivision` as allowed
          WHERE allowed.`Subdivision_ID` IN (".join(",", array_unique($sub_admin)).")
          AND child.`Hidden_URL` LIKE CONCAT(allowed.`Hidden_URL`, '_%')", ARRAY_A);
        }

        if (!empty($res)) {
            foreach ($res as $row) {
                $allowed_subs[] = $row['Subdivision_ID'];
                $sub_child_administrator[$row['Subdivision_ID']] = $row['Allowed_Subdivision_ID'];
            }
        }

        if ($allowed_subs) {
            $qry_where = " AND a.Subdivision_ID IN (".join(',', $allowed_subs).") ";
        }
    }

    $select = "SELECT COUNT(`Subdivision_ID`) FROM `Subdivision` AS a WHERE `Parent_Sub_ID` = 0
    AND `Catalogue_ID` = '".intval($CatalogueID)."'";

    return $db->get_var("SELECT COUNT(`Subdivision_ID`) FROM `Subdivision` AS a WHERE `Parent_Sub_ID` = 0
    AND `Catalogue_ID` = '".intval($CatalogueID)."'".($available && $qry_where ? $qry_where : ""));
}

function IsAllowedDomain($Domain, $CatalogueID) {
    global $db;

    return!$db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Domain` = '".$db->escape($Domain)."'
    AND `Catalogue_ID` <> '".intval($CatalogueID)."'");
}
?>
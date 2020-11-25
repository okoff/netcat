<?php

/* $Id: user_json.php 8329 2012-11-02 11:31:02Z vadim $ */

ob_start("ob_gzhandler");

define("NC_ADMIN_ASK_PASSWORD", false);
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

if (!$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, 0, 0, 0)) {
    die("/* NO RIGHTS */");
}

list($node_type, $node_id) = explode("-", $node);
$node_id = (int) $node_id;

$ret = array();

// получение пути (action==get_path) - не требуется
// (все дерево загружается одновременно)

/* * *************************************************************************
 * *  вывод узлов
 */


// Пользователи
// [Группы пользователей] -> группы
// Рассылка по базе
$i = 0;
if ($node == "root") {

    /** ГРУППЫ ПОЛЬЗОВАТЕЛЕЙ * */
    if ( $perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_LIST, 0, 0, 0) ) {
        $ret[$i] = array("nodeId" => "usergroup",
                "name" => SECTION_CONTROL_USER_GROUP,
                "href" => "#usergroup.list()",
                "image" => "icon_usergroups",
                "hasChildren" => true,
                "buttons" => array(
                        array("image" => "i_usergroup_add.gif",
                                "label" => CONTROL_USER_ADDNEWGROUP,
                                "href" => "#usergroup.add()")
                )
        );

        // output user groups at once
        $groups = $db->get_results("SELECT PermissionGroup_ID, PermissionGroup_Name
                                  FROM PermissionGroup", ARRAY_A);
        $i++;
        foreach ($groups as $grp) {
            $ret[$i++] = array("nodeId" => "usergroup-$grp[PermissionGroup_ID]",
                    "nameId" => "$grp[PermissionGroup_ID]. $grp[PermissionGroup_ID]",
                    "name" => $grp["PermissionGroup_Name"],
                    "href" => "#usergroup.edit($grp[PermissionGroup_ID])",
                    "image" => "icon_usergroups",
                    "hasChildren" => false,
                    "parentNodeId" => "usergroup",
            );
        }
    }


    /** ПОЛЬЗОВАТЕЛИ * */
    $ret[$i] = array("nodeId" => "users",
            "name" => SECTION_CONTROL_USER,
            "href" => "#user.list()",
            "image" => "icon_user",
            "hasChildren" => false);
    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_ADD, 0, 0, 0))
            $ret[$i]['buttons'] = array(array("image" => "i_user_add.gif",
                        "label" => CONTROL_USER_FUNCS_ADDUSER,
                        "href" => "#user.add()"));


    $i++;
    /** РАССЫЛКА ПО БАЗЕ * */
    if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 0)) {
        $ret[$i] = array("nodeId" => "usermail",
                "name" => SECTION_CONTROL_USER_MAIL,
                "href" => "#user.mail()",
                "image" => "icon_sendmail",
                "hasChildren" => false
        );
    }
}


print "while(1);".nc_array_json($ret);
?>
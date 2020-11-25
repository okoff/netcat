<?php

/* $Id: drag_manager.php 5946 2012-01-17 10:44:36Z denis $ */

ob_start("ob_gzhandler");

define("NC_ADMIN_ASK_PASSWORD", false);

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

$dragged_id = (int) $dragged_id;
if (!$dragged_id) die("0 /* Wrong parameters */");
$target_id = $db->escape($target_id);
if (!$target_id) die("0 /* Wrong parameters */");

// INPUT: $dragged_type, $dragged_id, $target_type, $target_id, $position [inside|below]

if ($dragged_type == 'dataclass' && $target_type == 'group') {

    // dragged site info
    $dragged = $db->get_row("SELECT Class_Group FROM Class WHERE Class_ID=$dragged_id", ARRAY_A);

    // target site info
    $target = $db->get_row("SELECT Class_Group FROM Class WHERE md5(Class_Group)='".$target_id."'", ARRAY_A);

    if ($perm->isAccess(NC_PERM_CLASS, 0, 0, 1)) {
        $db->query("UPDATE Class
                   SET Class_Group = '".$target['Class_Group']."'
                 WHERE Class_ID = $dragged_id");
    }

    die("1 /* OK */");
} else {
    die("0 /* Wrong request ['$dragged_type $dragged_id' $position '$target_type $target_id'] */");
}
?>
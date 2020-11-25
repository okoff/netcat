<?php

ob_start("ob_gzhandler");

define("NC_ADMIN_ASK_PASSWORD", false);

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

/*
 * Показываем дерево разработчика, если у пользователя есть на это права
 */

if (!$perm->isAccess(NC_PERM_MODULE, 0, 0, 0)) {
    exit(NETCAT_MODERATION_ERROR_NORIGHT);
}

list($node_type, $node_id) = explode("-", $node);

if ($node_type == 'root') {
    $module_manager = nc_Core::get_object()->modules;
    $module_list = $module_manager->get_data();
    // widgets
    $ret_modules[] = array("nodeId" => "widgets",
            "name" => WIDGETS,
            "href" => "#widgets",
            "image" => "icon_widget",
            "hasChildren" => false,
            "dragEnabled" => false
    );

    foreach ($module_list as $module) {


        $kw = $module['Keyword'];

        if (file_exists($MODULE_FOLDER.$kw."/".MAIN_LANG.".lang.php")) {
            require_once($MODULE_FOLDER.$kw."/".MAIN_LANG.".lang.php");
        } else {
            require_once($MODULE_FOLDER.$kw."/en.lang.php");
        }

        $custom_location = $module_manager->get_vars($kw, 'ADMIN_SETTINGS_LOCATION');

        $ret_modules[] = array("nodeId" => "module-$module[Module_ID]",
                "name" => constant($module["Module_Name"]),
                "href" => file_exists(nc_Core::get_object()->MODULE_FOLDER . $kw.'/admin.php') && $module['Checked'] ? "#module.$kw" : "#module.settings($kw)",
                "image" => "icon_module_$kw",
                "hasChildren" => false,
                "dragEnabled" => false,
                "buttons" => array(
                        array(
                                "image" => "i_settings.gif",
                                "label" => TOOLS_MODULES_MOD_PREFS,
                                "href" => $custom_location ? $custom_location : "module.settings($kw)")
                )
        );
    }

    $ret = array_reverse($ret_modules);
    print "while(1);".nc_array_json($ret_modules);
    exit;
}
?>
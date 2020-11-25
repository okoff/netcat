<?php

/* $Id: admin.php 7302 2012-06-25 21:12:35Z alive $ */

$main_section = "settings";
# нужное подключаем
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($MODULE_FOLDER."blog/admin.inc.php");
require ($MODULE_FOLDER."blog/function.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

# UI
require ($ADMIN_FOLDER."modules/ui.php");
$UI_CONFIG = new ui_config_module('blog');

# языковые настройки
if (is_file($MODULE_FOLDER.'blog/'.MAIN_LANG.'.lang.php'))
        require_once($MODULE_FOLDER.'blog/'.MAIN_LANG.'.lang.php');
else require_once($MODULE_FOLDER.'blog/en.lang.php');

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

# начальное оформление
$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_BLOG;

# права на запуск этого файла
$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);

if (!isset($phase)) $phase = 1;

switch ($phase) {

    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/blog/");
        nc_admin_blog_begin(1);

        break;

    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/blog/");
        nc_admin_blog_begin(2, (int) $_POST['BlogCatalogue']);

        break;

    case 3:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/blog/");

        if ($_POST['BlogKeyword'] && $_POST['BlogName']) {

            $blogKeyword = $db->escape($_POST['BlogKeyword']);
            if (preg_match("/^[a-z0-9_-]+$/", $blogKeyword)) {

                $blogExist = $db->get_row("SELECT Subdivision_ID FROM Subdivision WHERE EnglishName='".$blogKeyword."' OR Hidden_URL='/".$blogKeyword."/'");

                if (!$blogExist) {
                    # добиваем массив
                    $blogArray["Catalogue"] = (int) $_POST['BlogCatalogue'];
                    $blogArray["Subdivision"] = (int) $_POST['BlogSubdivision'];
                    $blogArray["BlogType"] = $_POST['BlogType'];
                    $blogArray["Keyword"] = $blogKeyword;
                    $blogArray["Name"] = $db->escape($_POST['BlogName']);
                    $blogArray["Checked"] = ($_POST['BlogChecked'] ? 1 : 0);
                    $blogArray["Comments"] = ($_POST['BlogComments'] ? 1 : 0);

                    $result = nc_admin_blog_create($blogArray);
                    # сообщение
                    nc_print_status($result ? NETCAT_MODULE_BLOG_ADMIN_NEW_BLOG_CREATED : NETCAT_MODULE_BLOG_ADMIN_BLOG_CREATE_ERROR, $result ? "ok" : "error");
                    echo "<br><br><a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/blog/admin.php'>".NETCAT_MODULE_BLOG_ADMIN_BLOG_RETURN_TO_SETTINGS."</a>";
                } else {
                    nc_print_status(NETCAT_MODULE_BLOG_ADMIN_BLOG_NAME_ALREADY_EXIST, "error");
                    echo "<br><br><a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/blog/admin.php'>".NETCAT_MODULE_BLOG_ADMIN_BLOG_RETURN_TO_SETTINGS."</a>";
                }
            } else {
                nc_print_status(NETCAT_MODERATION_MSG_TWENTYONE, "error");
                echo "<br><br><a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/blog/admin.php'>".NETCAT_MODULE_BLOG_ADMIN_BLOG_RETURN_TO_SETTINGS."</a>";
            }
        } else {
            nc_print_status(NETCAT_MODULE_BLOG_ADMIN_BLOG_REQUIRED_FIELD, "error");
            echo "<br><br><a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/blog/admin.php'>".NETCAT_MODULE_BLOG_ADMIN_BLOG_RETURN_TO_SETTINGS."</a>";
        }

        break;
}

EndHtml();
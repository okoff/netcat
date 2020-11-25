<?php

/* $Id: add.php 7935 2012-08-09 14:50:10Z ewind $ */

ob_start();

do {

    $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
    include_once ($NETCAT_FOLDER."vars.inc.php");
    require ($INCLUDE_FOLDER."index.php");

    global $db, $perm, $current_user, $perm, $MODULE_VARS, $AUTH_USER_ID;

    $sub = (int) $sub;
    $cc = (int) $cc;

// $sub может быть как самим блогом, так и родительским разделом
// если блог
    $is_blog = $db->get_var("SELECT `ID` FROM `Blog_Subdivision` WHERE `Subdivision_ID` = '".$sub."'");
// если родитель
    if (!$is_blog)
        $is_blog_main = $db->get_var("SELECT `ID` FROM `Blog_Parent` WHERE `Subdivision_ID` = '".$sub."'");

    $sub_data = $db->get_row("SELECT `Catalogue_ID`, `Parent_Sub_ID`, `Hidden_URL`
	FROM `Subdivision`
	WHERE `Subdivision_ID` = '".$sub."'", ARRAY_A);

    $parentSub = $is_blog ? $sub_data['Parent_Sub_ID'] : ($is_blog_main ? $sub : 0);
    $parent_array = $db->get_row("SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = '".intval($parentSub)."'", ARRAY_A);

// есть ли у пользователя свой personal блог
    $current_user_blog = $db->get_row("SELECT bs.* FROM `Blog_Subdivision` AS bs
  LEFT JOIN `Blog_Parent` AS bp ON bs.`Parent_Sub_ID` = bp.`Subdivision_ID`
  WHERE bs.`Creator_ID` = '".intval($current_user['User_ID'])."'
  AND bp.`Type` = 'personal'", ARRAY_A);

    $catalogue = $sub_data['Catalogue_ID'];
    $Hidden_URL = $sub_data['Hidden_URL'];

// обратная ссылка в админке
    if ($inside_admin)
        $goBackLink = $admin_url_prefix."?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc.($curPos ? "&amp;curPos=".$curPos."" : "");

// проверяем права, пользователь должен быть авторизирован
// если корневой раздел блогов помечен на просмотр "уполномоченным", у пользователя должны быть такие права
    if (!$AUTH_USER_ID || ( $parent_array['Read_Access_ID'] == 3 && !( is_object($perm) && $perm->isSubdivision($parentSub, MASK_READ) ) )) {
        if (!$inside_admin)
            $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
        echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_NOPERMISSIONS, $goBackLink, "error"));
        break;
    }

    switch ($nc_blog_operation) {
        // создание блога
        case "create_blog":
            // если корневой раздел блогов помечен на добавление "уполномоченным", у пользователя должны быть такие права
            if ($parent_array['Write_Access_ID'] == 3 && !( is_object($perm) && $perm->isSubdivision($parentSub, MASK_ADD) )) {
                if (!$inside_admin)
                    $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_NOPERMISSIONS, $goBackLink, "error"));
                // выводим Footer и завершаем скрипт
                break;
            }

            // смотрим тип блога для этой субы и заголовок блога Subdivision_Name
            if ($blogType = nc_blog_get_type($parentSub)) {
                $blogTitle = $db->escape($blogTitle);
            } else {
                $blogTitle = "";
            }

            // есть ли такой блог
            if ($blogType == "collective" || $blogType == "corporative") {
                $blogName = strtolower($db->escape($blogName));
            } elseif ($blogType == "personal" && !$ruName) {
                $blogName = strtolower($db->escape($current_user['Login']));
            }

            // проверка на допустимые символы
            $rusName = preg_match("/^.*?[а-я]+.*?$/i", $blogName);
            if ($rusName) {
                $blogName = nc_transliterate($blogName);
                $blogName = strtr($blogName, array("'" => "", " " => "-"));
            }

            // далее
            $validName = preg_match("/^[a-z0-9-]+$/", $blogName);
            if ($validName && $blogName) {
                // проверим свободно ли имя
                $userExist = nc_blog_check_name($sub, $blogName);

                // далее...
                if (( $userExist && !empty($current_user_blog) ) || ($userExist && $blogType == "collective")) {
                    echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_BLOGNAME_EXIST, "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>", "error"));
                } elseif ($userExist && empty($current_user_blog) && $blogType == "personal") {
                    echo NETCAT_MODULE_BLOG_INFO_BLOGNAME_EXIST."<br/>".NETCAT_MODULE_BLOG_INFO_BLOGNAME_TRY_OTHER."<br/><br/>
          <form enctype='multipart/form-data' method='post' action='".$MODULE_FOLDER."blog/add.php'>
            <input name='sub' value='".$sub."' type='hidden'>
            <input name='cc' value='".$cc."' type='hidden'>
            <input name='nc_blog_operation' value='create_blog' type='hidden'>
            <input name='ruName' value='1' type='hidden'>
            <input type='text' name='blogName' value='".$blogName."'>
            <input title='".NETCAT_MODULE_BLOG_CREATE."' value='".NETCAT_MODULE_BLOG_CREATE."' type='submit'>
          </form>";
                } else {
                    // создаем блог и все что для этого нужно
                    $error = nc_blog_create_blog($sub, $catalogue, $Hidden_URL, $subName, $blogName, $blogType, $blogTitle);

                    if (!$error) {
                        // формируем обратную ссылку
                        if ($admin_mode)
                            $goBackLink = $admin_url_prefix."?catalogue=".$catalogue."&sub=".$sub."&cc=".$cc."&curPos=".$curPos."";
                        else
                            $goBackLink = $Hidden_URL.$blogName."/settings.html";

                        echo nc_blog_redirect($goBackLink, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_BLOG_CREATED, NETCAT_MODULE_BLOG_INFO_GOTO_SETTINGS, "ok"));
                    }
                    else {
                        echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_ERROR, "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>", "error"));
                    }
                }
            } else {
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_BLOG_NAME_NOT_CORRECT, "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>", "info"));
            }
            break;

        // присоединение к блогу
        case "join_blog":
        case "unjoin_blog":

            # проверяем права, пользователь должен быть участником блога
            if ($nc_blog_operation == "unjoin_blog") {
                $access = (bool) $perm->isSubClass($cc, 4);
                if (!$access) {
                    if (!$inside_admin)
                        $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
                    echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_NOPERMISSIONS, $goBackLink, "error"));
                }
            }

            $blogType = nc_blog_get_type($sub, 1);
            $userPerm = nc_blog_user_permission($sub, $cc);
            $userscc = $db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '".intval($sub)."' AND `EnglishName` = 'users'");
            $userID = $current_user['User_ID'];

            if ($nc_blog_operation == "join_blog") {
                if (!$userPerm && $blogType == "collective") {
                    $join_result = nc_blog_addrop_user($sub, $userscc, $userID, "add", 2);
                }
            } elseif ($nc_blog_operation == "unjoin_blog") {
                if ($userPerm == "u") {
                    $join_result = nc_blog_addrop_user($sub, $userscc, $userID, "drop", 2);
                }
            }

            if ($join_result) {
                if (!$inside_admin)
                    $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_COMPLETE, $goBackLink, "info"));
            }
            else {
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_ERROR, "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>", "error"));
            }

            # join_blog || unjoin_blog
            break;

        # добавить в друзья (только для частных блогов)
        case "add_user":
        case "drop_user":

            # смотрим тип блога для субы на уровень выше
            $blogType = nc_blog_get_type($sub, 1);

            # только для частных блогов
            if ($blogType == "personal") {
                # кого делаем другом
                if ($ignore_user) {
                    $userID = (int) $ignore_user;
                } else {
                    # ID пользователя владельца блога, так потому что логин может быть русский
                    $userID = $db->get_var("SELECT User_ID FROM Permission
          WHERE AdminType=3
          AND PermissionSet='".$MODULE_VARS['blog']['BLOG_ADMIN_PERMISSION']."'
          AND Catalogue_ID='".$sub."'");
                }

                // my personal blog id
                $mysub = $current_user_blog['Subdivision_ID'];

                if ($mysub) {
                    $myusercc = $db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` = '".intval($MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID'])."' AND `Subdivision_ID` = '".intval($mysub)."' AND `EnglishName` = 'users'");
                }
                if ($ignore_user)
                    $permission = 4; else
                    $permission = 1;# user status
            }
            elseif ($blogType == "collective") {
                # U, M, A
                $userPerm = nc_blog_user_permission($sub, $cc);
                if ($userPerm == "a") {
                    # кого игнорируем
                    $userID = $ignore_user;
                    # мой блог
                    $mysub = $sub;
                    $myusercc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Class_ID=".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']." AND Subdivision_ID=".$sub." AND EnglishName='users'");
                    $permission = 4; # ignore status
                }
            }
            # добавить или удалить
            if ($mysub && $myusercc && $userID) {
                if ($nc_blog_operation == "add_user")
                    $result = nc_blog_addrop_user($mysub, $myusercc, $userID, "add", $permission);
                else
                    $result = nc_blog_addrop_user($mysub, $myusercc, $userID, "drop", $permission);
            }
            # вывод footer
            if ($result) {
                if (!$inside_admin)
                    $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice($blogType == "collective" ? NETCAT_MODULE_BLOG_INFO_USERS_LIST_SAVED : NETCAT_MODULE_BLOG_INFO_FRIEND_LIST_SAVED, $goBackLink, "info"));
            }
            else {
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_ERROR, "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>", "error"));
            }

            # add_user || drop_user
            break;

        # установки прав для пользователей блога в разделе пользователи
        case "save_users":

            # проверяем права, пользователь должен быть владельцем блога
            $access = ( (bool) $perm->isSubdivisionAdmin($sub) || (bool) $perm->isDirector() || (bool) $perm->isSupervisor() );
            if (!$access) {
                if (!$inside_admin)
                    $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_NOPERMISSIONS, $goBackLink, "error"));
            }

            $result = nc_blog_users_settings($sub, $f_Permission, $f_Delete);

            if (!$inside_admin)
                $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
            echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(($result ? NETCAT_MODULE_BLOG_INFO_SETTINGS_SAVED : NETCAT_MODULE_BLOG_INFO_NOSETTINGS_TO_SAVE), $goBackLink, "ok"));

            # save_users
            break;

        case "visual_settings":

            # проверяем права, пользователь должен быть владельцем блога
            $access = ( (bool) $perm->isSubdivisionAdmin($sub) || (bool) $perm->isDirector() || (bool) $perm->isSupervisor() );
            if (!$access) {
                if (!$inside_admin)
                    $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
                echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_NOPERMISSIONS, $goBackLink, "error"));
            }

            nc_blog_visual_settings($sub, "", "", "", $CustomSettings);

            if (!$inside_admin)
                $goBackLink = "<a href='%URL'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
            echo nc_blog_redirect($Hidden_URL, nc_blog_make_notice(NETCAT_MODULE_BLOG_INFO_SETTINGS_SAVED, $goBackLink, "ok"));

            # visual_settings
            break;
    }

} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';

    echo $template_header;
    echo $nc_result_msg;
    echo $template_footer;
} else {
    eval("echo \"".$template_header."\";");
    echo $nc_result_msg;
    eval("echo \"".$template_footer."\";");
}
?>
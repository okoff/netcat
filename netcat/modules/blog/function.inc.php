<?php

/* $Id: function.inc.php 7904 2012-07-31 18:21:00Z lemonade $ */

# функция вывода блогов

function nc_blog_show_blogs($sub, $template="", $ignore_access="") {
    global $db, $current_sub, $MODULE_VARS, $inside_admin, $SUB_FOLDER, $HTTP_ROOT_PATH;

    # блок переменных
    $sub = (int) $sub;
    if (!$sub) return false;
    if ($ignore_access) {
        $ignore_access = (array) $ignore_access;
        $ignore_access = array_map("mysql_real_escape_string", $ignore_access);
    }
    $path = $db->get_var("SELECT `Hidden_URL` FROM `Subdivision` WHERE `Subdivision_ID` = '".$sub."'");

    $SQLres = $db->get_results("SELECT `Subdivision_ID`, `Subdivision_Name`, `EnglishName` FROM `Subdivision`
		WHERE `Parent_Sub_ID` = '".$sub."' AND `Checked` = 1", ARRAY_A);
    if ($SQLres) {
        # пробегаем по массиву
        foreach ($SQLres AS $key => $value) {
            # накладываем на макет
            if ($template) {
                unset($blog);
                # считаем сообщения если указана псевдо переменная
                if (strpos($template, "%BLOG_COUNT_MESSAGES"))
                        $messCount = $db->get_var("SELECT COUNT(*) FROM `Message".$MODULE_VARS['blog']['BLOG_MESSAGES_CLASS_ID']."`
					WHERE `Subdivision_ID` = '".$value['Subdivision_ID']."'".($ignore_access ? " AND `Access` NOT IN (".join(",", $ignore_access).")" : ""));
                # формируем ответ
                if (!$inside_admin) {
                    $blog = str_replace("%BLOG_LINK", $SUB_FOLDER.$path.$value['EnglishName']."/", $template);
                } else {
                    $blog = str_replace("%BLOG_LINK", $SUB_FOLDER.$HTTP_ROOT_PATH."?catalogue=".$value['Catalogue_ID']."&amp;sub=".$value['Subdivision_ID']."", $template);
                }
                $blog = str_replace("%BLOG_NAME", $value['Subdivision_Name'], $blog);
                $blog = str_replace("%BLOG_COUNT_MESSAGES", $messCount, $blog);
                $result[] = $blog;
            }
            # формируем массив для manual programming
            $result_array[] = array("Subdivision_ID" => $value['Subdivision_ID'],
                    "Subdivision_Name" => $value['Subdivision_Name'],
                    "EnglishName" => $value['EnglishName'],
                    "Count" => $messCount);
        }
    }
# возвращаем готовый макет и массив с данными, для энтузиастов
    return array($result, $result_array);
}

/**
 * Функция возвращает массив с полными данными о разделе с блогами,
 * сколько блогов, сами блоги, ссылки на них, сколько сообщений и юзеров
 *
 * @param int - идентификатор директории с блогами
 * @param string - порядок сортировки списка, например "Users DESC, Subdivision_Name ASC", поля сортировки - ключи результирующего массива
 * @param mixed - что игнорировать
 *
 * @return array - массив, каждый элемент которого - блог в разделе $sub
 */
function nc_blog_get_blogs($sub, $orderBy = "", $ignore_access="") {
    global $db, $MODULE_VARS, $admin_mode, $inside_admin, $SUB_FOLDER, $HTTP_ROOT_PATH;

    # проверим основной параметр
    $subExists = $db->get_var("SELECT `ID` FROM `Blog_Parent` WHERE `Subdivision_ID` = '".(int) $sub."'");
    if (!$subExists) return false;

    # блок переменных
    $Hidden_URL = $db->get_var("SELECT `Hidden_URL` FROM `Subdivision` WHERE `Subdivision_ID` = '".(int) $sub."'");
    if ($ignore_access) {
        $ignore_access = (array) $ignore_access;
        $ignore_access = array_map("mysql_real_escape_string", $ignore_access);
    }

    $SQLres = $db->get_results("SELECT * FROM `Subdivision`
		WHERE `Parent_Sub_ID` = '".(int) $sub."' AND `Checked` = 1", ARRAY_A);

    if ($SQLres) {
        # пробегаем по массиву
        foreach ($SQLres AS $key => $value) {

            $BlogLink = "";

            # считаем сообщения если указана псевдо переменная
            $messCount = $db->get_var("SELECT COUNT(*) FROM `Message".$MODULE_VARS['blog']['BLOG_MESSAGES_CLASS_ID']."`
				WHERE `Subdivision_ID` = '".$value['Subdivision_ID']."'".($ignore_access ? " AND `Access` NOT IN (".join(",", $ignore_access).")" : ""));

            # считаем пользователи если указана псевдо переменная
            $usersCount = $db->get_var("SELECT COUNT(*) FROM `Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."`
				WHERE `Subdivision_ID` = '".$value['Subdivision_ID']."'");

            # формируем ответ
            if (!$admin_mode) {
                $BlogLink = $SUB_FOLDER.$Hidden_URL.$value['EnglishName']."/";
            } else {
                $BlogLink = $SUB_FOLDER.$HTTP_ROOT_PATH."?catalogue=".$value['Catalogue_ID']."&amp;sub=".$value['Subdivision_ID'];
            }

            # формируем массив
            $result[] = array_merge($value, array("BlogLink" => $BlogLink, "Messages" => $messCount, "Users" => $usersCount));
        }
    }

    # функционал сортировки многомерного массива, на основе ключа(ей) массивов, которые являются его значениями
    if ($orderBy && !empty($result)) {

        if ($orderBy) $orderArr = explode(",", $orderBy);

        if (is_array($orderArr) && !empty($orderArr)) {

            $orderCond = array();
            # получаем названия и направление по которым сортировать
            $orderArr = array_map("trim", $orderArr);

            # делаем массив array[] = array("field" => "Subdivision_ID", "order" => "DESC") и заодно проверяем
            foreach ($orderArr AS $key => $value) {
                preg_match("/^(.*?)( ASC| DESC)?$/is", $value, $matches);
                if (!empty($matches) && $matches[1]) {
                    $orderCond[] = array("field" => trim($matches[1]), "order" => $matches[2] ? trim($matches[2]) : "ASC");
                }
            }

            # делаем вспомогательные массивы для сортировки основного
            foreach ($result AS $key => $value) {
                foreach ($orderCond AS $k => $orderByValue) {
                    if (isset($value[$orderByValue['field']])) {
                        $value[$orderByValue['field']] = str_replace('"', '\"', $value[$orderByValue['field']]); //array('\\', '"'), array('\\\\', '\"')
                        eval("\$sortRow{$k}[$key]  = \"".$value[$orderByValue['field']]."\";");
                    }
                }
            }

            $orderStr = "";
            # значения для функции array_multisort()
            foreach ($orderCond AS $k => $orderByValue) {
                $sortArr = "sortRow{$k}";
                if (isset($$sortArr)) {
                    $orderStr.= "\$sortRow$k, SORT_".$orderByValue['order'].", ";
                }
            }

            # сортировка результирующего массива
            if ($orderStr) eval("array_multisort($orderStr\$result);");
        }
    }

    return $result;
}

/**
 * Функция возвращает форматированный список блогов из раздела $sub,
 * сколько блогов, сами блоги, ссылки на них, сколько сообщений и юзеров.
 *
 * @param int - идентификатор директории с блогами
 * @param array - шаблон вывода списка
 * @param string - порядок сортировки списка, например "Users DESC, Subdivision_Name ASC"
 * @param mixed - что игнорировать
 *
 * @return string - HTML-текст
 */
function nc_blog_list($sub, $template = "", $orderBy = "", $ignore_access = "") {
    global $db, $current_sub, $current_cc, $admin_mode, $admin_url_prefix, $REQUEST_URI;

    # проверим основной параметр
    $subExists = $db->get_var("SELECT `ID` FROM `Blog_Parent` WHERE `Subdivision_ID` = '".(int) $sub."'");
    if (!$subExists) return false;

    # шаблон вывода списка блогов "по-умолчанию"
    if (!$template) {
        $template['prefix'] = "";
        $template['active'] = "<a href='%URL'>%NAME</a>";
        $template['active_link'] = "%NAME";
        $template['unactive'] = "<a href='%URL'>%NAME</a>";
        $template['divider'] = ", ";
        $template['suffix'] = "";
    }

    $blogsArray = nc_blog_get_blogs($sub, $orderBy, $ignore_access);
    # subvariables
    $search = array("%NAME", "%URL", "%PARENT_SUB", "%KEYWORD", "%SUB", "%COUNTER", "%MESSAGES", "%USERS");

    # prefix
    eval("\$result = \"".$template['prefix']."\";");

    $totalUsers = 0;
    $totalMessages = 0;
    $data = $blogsArray;
    $data_count = count($data);
    for ($i = 0; $i < $data_count; $i++) {

        if ($admin_mode) {
            $subFullLink = $admin_url_prefix."?catalogue=".$current_sub["Catalogue_ID"]."&sub=".$data[$i]["Subdivision_ID"];
        } else {
            $subFullLink = $current_sub["Hidden_URL"].$current_cc["EnglishName"].".html";
        }

        # общие значения
        $totalUsers = $totalUsers + $data[$i]['Users'];
        $totalMessages = $totalMessages + $data[$i]['Messages'];
        # массив для сопоставления и замены
        $replace = array(0 => $data[$i]['Subdivision_Name'], $data[$i]['BlogLink'], $data[$i]['Parent_Sub_ID'], $data[$i]['EnglishName'], $data[$i]['Subdivision_ID'], $i, $data[$i]['Messages'], $data[$i]['Users']);

        switch (true) {
            case $current_sub['Subdivision_ID'] == $data[$i]['Subdivision_ID'] && $subFullLink != $REQUEST_URI:
                # если текущий раздел совпадает с блогом
                $resEval = $template['active_link'];
                break;
            case $current_sub['Subdivision_ID'] == $data[$i]['Subdivision_ID'] && $subFullLink == $REQUEST_URI:
                # если текущий раздел совпадает с блогом, но ссылка не совпадает с адресом текущей страницы
                $resEval = $template['active'];
                break;
            default:
                # если текущий раздел совпадает с блогом
                $resEval = $template['unactive'];
        }

        # object
        eval("\$res = \"".$resEval."\";");
        $result.= str_replace($search, $replace, $res);

        # divider
        if ($i <> ($data_count - 1))
                eval("\$result.= \"".$template['divider']."\";");
    }

    # suffix
    eval("\$result.= \"".$template['suffix']."\";");

    $result = str_replace(array("%ALL_USERS", "%ALL_MESSAGES"), array($totalUsers, $totalMessages), $result);

    return $result;
}

# системные сообщения

function nc_blog_make_notice($info, $link, $action="") {
    global $MODULE_VARS, $inside_admin, $admin_url_prefix;

    if (!$inside_admin) {
        $notice = str_replace("%BLOG_NOTICE_BLOCK_INFO", $info, $MODULE_VARS['blog']['BLOG_NOTICE_BLOCK']);
        $notice = str_replace("%BLOG_NOTICE_BLOCK_LINK", $link, $notice);
    } else {
        if (!in_array($action, array("error", "info", "ok"))) $action = "info";
        nc_print_status($info, $action);
        $notice = "<a href='".$link."'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
    }

    return $notice;
}

# функция редиректа и вывода ссылки, если редиректа нет

function nc_blog_redirect($url, $text="", $redirection=false) {
    global $SUB_FOLDER;

    if ($redirection) {
        ob_end_clean();
        header("Location: ".$SUB_FOLDER.($url ? $url : "/")."");
        exit;
    } elseif (!$text) {
        $text = "<a href='".$SUB_FOLDER.$url."'>".NETCAT_MODERATION_BACKTOSECTION."</a>";
    } else {
        $text = str_replace("%URL", $SUB_FOLDER.$url, $text);
    }

    return $text;
}

# функция проверяет тип блога через визуальную переменную
/*
  по умолчанию смотрит тип блога для раздела на уровень выше от текущего, значение 1.
  параметр $inc задаёт вложеность в обратном порядке.
 */

function nc_blog_get_blogtype($paramName, $inc="", $sub="", $cc="") {
    global $db, $parent_sub_tree, $MODULE_VARS;

    $blogType = nc_blog_get_type((int) $parent_sub_tree[($inc ? $inc : 0)]['Subdivision_ID']);

    return $blogType;
}

/**
 * Функция возвращает создателя (Creator_ID) раздела $sub
 * @param int - идентификатор раздела
 * @param int - идентификатор пользователя
 */
function nc_blog_get_creator($sub) {
    global $db;

    $sub = (int) $sub;

    $result = $db->get_var("SELECT `Creator_ID`
		FROM `Blog_Subdivision`
		WHERE `Subdivision_ID` = '".$sub."'");

    return $result;
}

/**
 * Функция возвращает тип (Type) раздела $sub
 * @param int - идентификатор родительского раздела с блогами
 * @param bool - значение указывает, что $sub - идентификатор блога, а не родительского раздела с блогами
 * @param string - тип блога
 */
function nc_blog_get_type($sub, $is_child = false) {
    global $db;

    $sub = (int) $sub;

    if (!$is_child) {
        $result = $db->get_var("SELECT `Type`
			FROM `Blog_Parent`
			WHERE `Subdivision_ID` = '".$sub."'");
    } else {
        $result = $db->get_var("SELECT bp.`Type`
			FROM `Blog_Parent` AS bp
			LEFT JOIN `Blog_Subdivision` AS bs
			ON bp.`Subdivision_ID` = bs.`Parent_Sub_ID`
			WHERE bs.`Subdivision_ID` = '".$sub."'");
    }

    return $result;
}

# функция проверки вакантности имени блога

function nc_blog_check_name($sub, $blogName) {
    global $db;

    $sub = (int) $sub;
    $blogName = $db->escape($blogName);

    if (!$sub || !$blogName) return false;

    # проверим такое имя
    $subID = $db->get_var("SELECT Subdivision_ID FROM Subdivision
							WHERE (EnglishName='".$blogName."' OR Hidden_URL='/".$blogName."/') AND Parent_Sub_ID=".$sub."");

    return $subID;
}

# права пользователя на раздел или компонент

function nc_blog_user_permission($sub="", $cc="") {
    global $db, $current_user, $current_sub, $current_cc, $MODULE_VARS;

    # переменные
    $sub = (int) $sub;
    $cc = (int) $cc;
    if (!$sub && !$cc) {
        $sub = $current_sub['Subdivision_ID'];
        $cc = $current_cc['Sub_Class_ID'];
    }
    $user = $current_user['User_ID'];
    $usersPermissions = $MODULE_VARS['blog']['BLOG_USER_PERMISSION'];
    $moderPermissions = $MODULE_VARS['blog']['BLOG_MODERATOR_PERMISSION'];
    $adminPermissions = $MODULE_VARS['blog']['BLOG_ADMIN_PERMISSION'];

    if (!$user || !$sub || !$cc) return false;

    # владелец
    $ExistAdmin = $db->get_var("SELECT Permission_ID FROM Permission WHERE User_ID=".$user."
								AND (AdminType=3 AND Catalogue_ID=".$sub.") AND PermissionSet=".$adminPermissions."");
    # модератор
    if (!$ExistAdmin) {
        $ExistModer = $db->get_var("SELECT Permission_ID FROM Permission WHERE User_ID=".$user."
									AND (AdminType=9 AND Catalogue_ID=".$cc.") AND PermissionSet=".$moderPermissions."");
    }
    # смотрим есть ли уже доступ пользователя
    if (!$ExistAdmin && !$ExistModer) {
        $ExistUser = $db->get_var("SELECT Permission_ID FROM Permission WHERE User_ID=".$user."
								   AND (AdminType=9 AND Catalogue_ID=".$cc.") AND PermissionSet=".$usersPermissions."");
    }

    return ($ExistAdmin ? "a" : ($ExistModer ? "m" : ($ExistUser ? "u" : false)));
}

# is this user my friend? I - current user!

function nc_blog_is_friend($friendID) {
    global $db, $current_user, $parent_sub_tree, $MODULE_VARS;

    $friendID = (int) $friendID;
    $userID = $current_user['User_ID'];
    $parentSub = $parent_sub_tree[1]['Subdivision_ID'];

    # так потому что логин может быть русский
    $subdivisions = $db->get_col("SELECT Subdivision_ID FROM Subdivision WHERE Parent_Sub_ID='".$parentSub."'");
    if ($subdivisions)
            $usersub = $db->get_var("SELECT Catalogue_ID FROM Permission
						     WHERE User_ID='".$userID."'
						     AND AdminType=3
						     AND PermissionSet='".$MODULE_VARS['blog']['BLOG_ADMIN_PERMISSION']."'
						     AND Catalogue_ID IN (".join(",", $subdivisions).")");

    if ($usersub)
            $usercc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID=".$usersub." AND EnglishName='users'");

    if ($usercc)
            $ConUsers = $db->get_col("SELECT User_ID FROM Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
										  WHERE Subdivision_ID=".$usersub." AND Sub_Class_ID=".$usercc." AND Permission=1");

    if ($ConUsers) {
        $ConUsers = (array) $ConUsers;
        if (in_array($friendID, $ConUsers)) $result = true; else $result = false;
    }

    return $result;
}

# функция подключения друзей
/*
  1 - друг
  2 - участник
  3 - модератор (владелец)
  4 - игнорируемый
 */

function nc_blog_addrop_user($sub, $cc, $userID, $action, $permision="") {
    global $db, $current_user, $parent_sub_tree, $MODULE_VARS;

    // system superior object
    $nc_core = nc_Core::get_object();

    # переменные
    $sub = (int) $sub;
    $cc = (int) $cc;
    $blog_main_sub = $parent_sub_tree[1]['Subdivision_ID'];
    $userID = (int) $userID;
    $date = date('Y-m-d H:i:s');
    $usersPermissions = $MODULE_VARS['blog']['BLOG_USER_PERMISSION'];
    $moderPermissions = $MODULE_VARS['blog']['BLOG_MODERATOR_PERMISSION'];
    $adminPermissions = $MODULE_VARS['blog']['BLOG_ADMIN_PERMISSION'];
    if ($permision && in_array($permision, array(1, 2, 3, 4)))
            $permision = (int) $permision;
    else $permision = 1;

    $userName = $db->get_var("SELECT Login FROM User WHERE User_ID=".$userID."");

    # есть ли такой в списке пользователей моего блога
    $res = $db->get_row("SELECT Message_ID, Permission FROM Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
							   WHERE Subdivision_ID=".$sub." AND Sub_Class_ID=".$cc."
							   AND Name='".$userName."'", ARRAY_A);

    $userExist = $res['Message_ID'];
    $userPerm = $res['Permission'];

    if ($userPerm == 4) return false;

    #  если нет такого пользователя, или он просто читатель или друг
    if ((!$userExist || ($userExist && ($userPerm == 1 || $userPerm == 5) && $permision != $userPerm ) ) && $action == "add") {
        # данные блога $sub
        list($catalogue, $blogName) = $db->get_row("SELECT `Catalogue_ID`, `EnglishName` FROM `Subdivision` WHERE `Subdivision_ID` = '".$sub."'", ARRAY_N);
        $blogcc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID=".$sub." AND EnglishName='".$blogName."'");
        $editcc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID=".$sub." AND EnglishName='edit'");
        # добавляем права в таблицу Permission, чтобы пользователю были доступны операции с этими разделами
        $db->query("INSERT INTO Permission
					(User_ID, AdminType, Catalogue_ID, PermissionSet, PermissionGroup_ID)
					VALUES
					(".$userID.", 9, ".$blogcc.", ".$usersPermissions.", 0),
					(".$userID.", 9, ".$editcc.", ".$usersPermissions.", 0)");
        # пишем пользователя в таблицу этого блога
        $db->query("INSERT INTO Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
					(User_ID, Subdivision_ID, Sub_Class_ID, Checked, Created, LastUpdated, Name, Permission)
					VALUES
					(".$userID.", ".$sub.", ".$cc.", 1, '".$date."', '".$date."', '".$userName."', ".$permision.")");
        # получаем ID созданного сообщения
        $result = $db->insert_id;

        // execute core action
        $nc_core->event->execute("addMessage", $catalogue, $sub, $cc, $MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID'], $result);

        # узнаём идентификатор блога, добавляемого пользователя, чтобы сделать запись в его таблицу пользователей о новом читателе
        if ($permision == 1 && $result && $blog_main_sub) {
            # все разделы (блоги)
            $blog_subdivs = $db->get_col("SELECT Subdivision_ID FROM Subdivision WHERE Parent_Sub_ID=".$blog_main_sub."");
            # находим блог добавляемого пользователя
            if (!empty($blog_subdivs)) {
                $user_blog_ID = $db->get_var("SELECT Catalogue_ID FROM Permission
										      WHERE User_ID=".$userID." AND AdminType=3 AND Catalogue_ID IN (".join(", ", $blog_subdivs).")
											  AND PermissionSet=".$adminPermissions."");
                $user_user_cc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID=".$user_blog_ID." AND EnglishName='users'");
            }
            # пишем пользователю нового читателя
            $db->query("INSERT INTO Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
						(User_ID, Subdivision_ID, Sub_Class_ID, Checked, Created, LastUpdated, Name, Permission, RelationSub, RelationMessage)
						VALUES
						(".$current_user['User_ID'].", ".$user_blog_ID.", ".$user_user_cc.", 1, '".$date."', '".$date."', '".$current_user['Login']."', 5, ".$sub.", ".$result.")");
            # получаем ID созданного сообщения
            $rel_result = $db->insert_id;

            // execute core action
            $nc_core->event->execute("addMessage", $catalogue, $user_blog_ID, $user_user_cc, $MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID'], $rel_result);

            # обновим связь
            if ($rel_result) {
                $db->query("UPDATE Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
							SET RelationSub=".$user_blog_ID.", RelationMessage=".$rel_result."
							WHERE Message_ID=".$result."");

                // execute core action
                $nc_core->event->execute("updateMessage", $catalogue, $sub, $cc, $MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID'], $result);
            }
        }
    } elseif ($userExist && $action == "add" && $permision != $userPerm) {
        # обновляем права пользователя и запись в таблице
        nc_blog_users_settings($sub, array($userExist => $permision), false);
        $result = true;
    } elseif ($userExist && $action == "drop") {
        # удаляем права пользователя и запись в таблице (при отключении от блога)
        nc_blog_users_settings($sub, array($userExist => $permision), array($userExist => true));
        $result = true;
    }

    return $result;
}

# heavy brain killer function
# функция установки юзеров через фронт оффис

function nc_blog_users_settings($sub, $f_Permission, $f_Delete) {
    global $db, $MODULE_VARS;

    // system superior object
    $nc_core = nc_Core::get_object();
    $sub = intval($sub);

    # смотрим тип блога
    $blogType = nc_blog_get_type($sub, 1);
    $usersPermissions = $MODULE_VARS['blog']['BLOG_USER_PERMISSION'];
    $moderPermissions = $MODULE_VARS['blog']['BLOG_MODERATOR_PERMISSION'];
    $adminPermissions = $MODULE_VARS['blog']['BLOG_ADMIN_PERMISSION'];
    # данные блога $sub
    list($catalogue, $blogName) = $db->get_row("SELECT `Catalogue_ID`, `EnglishName` FROM `Subdivision` WHERE `Subdivision_ID` = '".$sub."'", ARRAY_N);
    $blogcc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID=".$sub." AND EnglishName='".$blogName."'");
    $editcc = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID=".$sub." AND EnglishName='edit'");

    # получаем актуальные значения из базы для пользователей, которых меняют
    $keys = array_keys((array) $f_Permission);
    if (empty($keys)) return false;

    $keys_array = array();
    foreach ($keys AS $value) {
        $keys_array[] = "Message_ID=".intval($value);
    }
    $key_str = join(" OR ", $keys_array);
    $SQLres = $db->get_results("SELECT User_ID, Name, Permission, Message_ID
    FROM Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
    WHERE (".$key_str.") AND Subdivision_ID=".$sub."", ARRAY_A);
    # приводим массив к нормальному виду
    $actual = array();
    if ($SQLres)
            foreach ($SQLres AS $value) {
            $actual[$value['Message_ID']] = array("ID" => $value['User_ID'], "Name" => $value['Name'], "Permission" => $value['Permission']);
        }

    foreach ($f_Permission AS $key => $value) {
        # переменные
        $key = (int) $key;
        $value = (int) $value;
        $_user_ID = $actual[$key]['ID'];
        $_user_name = $actual[$key]['Name'];
        $_permission = $actual[$key]['Permission'];

        # получаем текущие права для пользователя которого меняем
        $curr_permission_ID = $db->get_col("SELECT Permission_ID FROM Permission
      WHERE User_ID=".$_user_ID." AND AdminType=9 AND (Catalogue_ID=".$blogcc." OR Catalogue_ID=".$editcc.")
      AND PermissionSet=".($_permission == 2 || $_permission == 1 ? $usersPermissions : ($_permission == 3 ? $moderPermissions : "") )."");

        $key_cc = $db->get_var("SELECT `Sub_Class_ID` FROM `Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."`
      WHERE `Message_ID` = '".$key."'");

        if (!$f_Delete[$key]) {
            # в персональных нет участников и модераторов, в коллективных нет друзей(1) и читателей(5)
            if ($blogType == "collective" && !($value == 1 || $value == 5)) {
                # обновляем значения в объектах, если есть изменения
                if ($_permission != $value) {
                    $db->query("UPDATE Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
            SET Permission=".$value."
            WHERE Message_ID=".$key." AND Subdivision_ID=".$sub."");

                    // execute core action
                    $nc_core->event->execute("updateMessage", $catalogue, $sub, $key_cc, $MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID'], $key);

                    # если менялись права пользователя, делаем соответствующую запись в таблицу Permission
                    if ($curr_permission_ID) {
                        # если занесли в игнор - удаляем записи с правами для этого блога
                        if ($value == 4) {
                            $db->query("DELETE FROM Permission WHERE Permission_ID IN (".$curr_permission_ID[0].",".$curr_permission_ID[1].")");
                        } else {
                            $db->query("UPDATE Permission SET PermissionSet=".($value == 2 ? $usersPermissions : ($value == 3 ? $moderPermissions : "") )."
                WHERE Permission_ID IN (".$curr_permission_ID[0].",".$curr_permission_ID[1].")");
                        }
                    }
                }
            }
        } elseif ($_permission != 5) {
            # удаляем пользователя если так угодно владельцу раздела
            $db->query("DELETE FROM Message".$MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."
        WHERE (Message_ID=".$key." AND Subdivision_ID=".$sub.") OR (RelationSub=".$sub." AND RelationMessage=".$key.")");

            // execute core action
            $nc_core->event->execute("dropMessage", $catalogue, $sub, $key_cc, $MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID'], $key);

            # удаляем из Permission, только для коллективных блогов, т.к. в частных только друзья - без прав)))
            if ($curr_permission_ID) {
                # удаляем права из таблицы Permission
                $db->query("DELETE FROM Permission WHERE Permission_ID IN (".$curr_permission_ID[0].",".$curr_permission_ID[1].")");
            }
        }
    }

    return true;
}

# функция создания блога

function nc_blog_create_blog($sub, $catalogue, $Hidden_URL, $subName, $blogName, $blogType, $blogTitle = "") {
    global $db, $current_user, $MODULE_VARS;

    // system superior object
    $nc_core = nc_Core::get_object();

    # переменные
    $usersPermissions = $MODULE_VARS['blog']['BLOG_USER_PERMISSION'];
    $moderPermissions = $MODULE_VARS['blog']['BLOG_MODERATOR_PERMISSION'];
    $adminPermissions = $MODULE_VARS['blog']['BLOG_ADMIN_PERMISSION'];
    # для всех действий с settings и users устанавливаем - уполномоченные
    $hiddenSubPermissions = 3;
    $error = false;
    $errorBackUp = array();
    $sub = (int) $sub;
    $catalogue = (int) $catalogue;
    $Hidden_URL = $db->escape($Hidden_URL);
    if ($blogTitle) $blogTitle = $db->escape($blogTitle);
    $blogName = $db->escape($blogName);
    $subName = $db->escape($subName);
    $blogCreator = $current_user['User_ID'];
    $date = date('Y-m-d H:i:s');

    # проверяем тип блога, одновременно это говорит о крректном уровне вложенности
    $blogType = nc_blog_get_type($sub);
    if (!$blogType) return $error = true;

# Сам блог
    # создаём подраздел
    $db->query("INSERT INTO `Subdivision`
		(`Catalogue_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Template_ID`, `ExternalURL`, `EnglishName`, `LastUpdated`, `Created`, `Hidden_URL`, `Read_Access_ID`, `Write_Access_ID`, `Priority`, `Checked`, `Edit_Access_ID`, `Subscribe_Access_ID`".($nc_core->modules->get_by_keyword('cache') ? ", `Cache_Access_ID`" : "").")
		VALUES
		('".$catalogue."', '".$sub."', '".($blogTitle ? $blogTitle : $blogName)."', 0, '', '".$blogName."', '".$date."', '".$date."', '".$Hidden_URL.$blogName."/', 0, 0, 0, 1, 0, 0".($nc_core->modules->get_by_keyword('cache') ? ", 0" : "").")");
    # получаем ID созданного подраздела
    $subID = $db->insert_id;
    if (!$subID) $error = true;
    else {
        // execute core action
        $nc_core->event->execute("addSubdivision", $catalogue, $subID);
        $errorBackUp[] = "DELETE FROM `Subdivision` WHERE `Subdivision_ID` = '".$subID."'";
    }

    # Запись в таблицу Blog_Subdivision
    if (!$error) {
        $db->query("INSERT INTO `Blog_Subdivision`
			(`Parent_Sub_ID`, `Subdivision_ID`, `Creator_ID`)
			VALUES
			('".$sub."', '".$subID."', '".$blogCreator."')");

        $bs = $db->insert_id;
        if (!$bs) $error = true;
        else $errorBackUp[] = "DELETE FROM `Blog_Subdivision` WHERE `ID` = '".$bs."'";
    }

    if (nc_module_check_by_keyword("comments")) {
        $db->query("INSERT INTO `Comments_Rules`
      (`Catalogue_ID`, `Subdivision_ID`, `Access_ID`, `Edit_Rule`, `Delete_Rule`)
      VALUES
      ('".$catalogue."', '".$subID."', '2', '3', '3')");
        $db->query("UPDATE `Subdivision` SET `Comment_Rule_ID` = '".$db->insert_id."' WHERE `Subdivision_ID` = '".$subID."'");
    }

    if (!$error) {
        # Название шаблона
        $def_cust_settings = "\$CustomSettings = array('BlogTitle' => '".$blogName."', 'RSS' => 'disabled', 'Comments_Access' => '2', 'Comments_Edit' => '3', 'Comments_Delete' => '3');";
        $Sub_Class_Name = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '".(int) $MODULE_VARS['blog']['BLOG_MESSAGES_CLASS_ID']."'");
        # кладем туда шаблон
        $db->query("INSERT INTO `Sub_Class`
			(`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `EnglishName`, `Checked`, `Catalogue_ID`, `AllowTags`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`)
			VALUES
			('".$subID."', '".(int) $MODULE_VARS['blog']['BLOG_MESSAGES_CLASS_ID']."', '".$Sub_Class_Name."', 0, '".$blogName."', 1, '".$catalogue."', -1, '".$date."', '".$date."', 'index', -1, -1, '".$db->escape($def_cust_settings)."')");
        # получаем ID созданного "шаблона в разделе"
        $ccID = $db->insert_id;
        if (!$ccID) $error = true;
        else {
            // execute core action
            $nc_core->event->execute("addSubClass", $catalogue, $subID, $ccID);
            $errorBackUp[] = "DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = ".$ccID."";
        }

        # кладем туда шаблон для добавления и редактирования сообщений
        /*
          $db->query("INSERT INTO `Sub_Class`
          (`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `EnglishName`, `Checked`, `Catalogue_ID`, `AllowTags`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`, `Read_Access_ID`, `Write_Access_ID`, `Edit_Access_ID`, `Subscribe_Access_ID`)
          VALUES
          ('".$subID."', '".(int)$MODULE_VARS['blog']['BLOG_MESSAGES_CLASS_ID']."', '".NETCAT_MODULE_BLOG_COMPONENT_MESSAGE_EDIT."', 1, 'edit', 1, '".$catalogue."', -1, '".$date."', '".$date."', 'add', -1, -1, '', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."')");
          # получаем ID созданного "шаблона в разделе"
          $cc2ID = $db->insert_id;
          if(!$cc2ID) $error = true;
          else {
          // execute core action
          $nc_core->event->execute("addSubClass", $catalogue, $subID, $cc2ID);
          $errorBackUp[] = "DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = '".$cc2ID."'";
          } */

        # кладем туда шаблон
        $db->query("INSERT INTO `Sub_Class`
			(`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `EnglishName`, `Checked`, `Catalogue_ID`, `AllowTags`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`, `Read_Access_ID`, `Write_Access_ID`, `Edit_Access_ID`, `Subscribe_Access_ID`)
			VALUES
			('".$subID."', '".(int) $MODULE_VARS['blog']['BLOG_MESSAGES_USERS_CLASS_ID']."', '".NETCAT_MODULE_BLOG_COMPONENT_MESSAGE_USERS."', 2, 'users', 1, '".$catalogue."', -1, '".$date."', '".$date."', 'index', -1, -1, '', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."')");
        # получаем ID созданного "шаблона в разделе" настройки
        $cc3ID = $db->insert_id;
        if (!$cc3ID) $error = true;
        else {
            // execute core action
            $nc_core->event->execute("addSubClass", $catalogue, $subID, $cc3ID);
            $errorBackUp[] = "DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = '".$cc3ID."'";
        }

        # кладем туда шаблон
        $db->query("INSERT INTO `Sub_Class`
			(`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `EnglishName`, `Checked`, `Catalogue_ID`, `AllowTags`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`, `Read_Access_ID`, `Write_Access_ID`, `Edit_Access_ID`, `Subscribe_Access_ID`)
			VALUES
			('".$subID."', '".(int) $MODULE_VARS['blog']['BLOG_MESSAGES_SETTINGS_CLASS_ID']."', '".NETCAT_MODULE_BLOG_COMPONENT_MESSAGE_SETTINGS."', 3, 'settings', 1, '".$catalogue."', -1, '".$date."', '".$date."', 'index', -1, -1, '', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."', '".$hiddenSubPermissions."')");

        # получаем ID созданного "шаблона в разделе" настройки
        $cc4ID = $db->insert_id;
        if (!$cc4ID) $error = true;
        else {
            // execute core action
            $nc_core->event->execute("addSubClass", $catalogue, $subID, $cc4ID);
            $errorBackUp[] = "DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = '".$cc4ID."'";
        }
    }

    # Доступ к блогу
    if (!$error) {
        if ($blogType == "personal") {
            # проверяем есть ли запись
            $ExistWritePermission = $db->get_var("SELECT Permission_ID FROM Permission
											       WHERE User_ID=".$current_user['User_ID']."
											       AND AdminType=3 AND Catalogue_ID=".$subID." AND PermissionSet=".$adminPermissions."");
            if (!$ExistWritePermission)
                    $db->query("INSERT INTO Permission
						(User_ID, AdminType, Catalogue_ID, PermissionSet, PermissionGroup_ID)
						VALUES
						(".$current_user['User_ID'].", 3, ".$subID.", ".$adminPermissions.", 0)");
            # получаем ID созданного сообщения
            $message_ID = $db->insert_id;
            if (!$message_ID) $error = true;
            else
                    $errorBackUp[] = "DELETE FROM Permission WHERE Permission_ID=".$message_ID."";
        }
        elseif ($blogType == "collective") {
            # проверяем есть ли запись
            $ExistWritePermission = $db->get_var("SELECT Permission_ID FROM Permission
												  WHERE User_ID=".$current_user['User_ID']."
												  AND AdminType=3 AND Catalogue_ID=".$subID." AND PermissionSet=".$adminPermissions."");
            if (!$ExistWritePermission)
                    $db->query("INSERT INTO Permission
						(User_ID, AdminType, Catalogue_ID, PermissionSet, PermissionGroup_ID)
						VALUES
						(".$current_user['User_ID'].", 3, ".$subID.", ".$adminPermissions.", 0)");
            # получаем ID созданного сообщения
            $message_ID = $db->insert_id;
            if (!$message_ID) $error = true;
            else
                    $errorBackUp[] = "DELETE FROM Permission WHERE Permission_ID=".$message_ID."";
        }
    }

    # если возникли проблемы - делаем это
    if ($error && $errorBackUp) nc_blog_backup($errorBackUp);

    return $error;
}

# функция вывода визуальных настроек шаблона (вместо альтернативной формы добавления)

function nc_blog_visual_settings($sub, $template_header="", $template_object="", $template_footer="", $CustomSettings="") {
    global $db, $current_sub, $admin_mode, $ADMIN_FOLDER;

    // system superior object
    $nc_core = nc_Core::get_object();

    # в админке без этого ругается
    require_once($ADMIN_FOLDER."array_to_form.inc.php");

    # Subdivision_ID для этого блога
    $parentSub = (int) $current_sub['Subdivision_ID'];
    $parentSubName = $current_sub['EnglishName'];
    # Смотрим какой шаблон сюда прилеплен
    $res = $db->get_row("SELECT Catalogue_ID, Sub_Class_ID, Class_ID FROM Sub_Class WHERE Subdivision_ID=".$parentSub." AND EnglishName='".$parentSubName."'", ARRAY_A);
    # значения
    $catalogue = (int) $res['Catalogue_ID'];
    $ClassID = (int) $res['Class_ID'];
    $SubClassID = (int) $res['Sub_Class_ID'];

    # шаблон настроек
    $settings = $db->get_var("SELECT CustomSettingsTemplate FROM Class WHERE Class_ID=".$ClassID."");
    $settings_array = array();
    
    if ($settings) {
        $settings = nc_preg_replace("/;\s*$/", "", $settings);
        // тут появляется $CustomSettings
        eval("\$settings_array = $settings;");
    }

    # обновляем настройки или выводим форму
    if ($CustomSettings) {
        $array_form = new nc_a2f($settings_array, 'CustomSettings'); #_info: $CustomSettings, 'CustomSettings'
        $array_form->save($CustomSettings);
        $updated_values = $array_form->get_values_as_string();
        if ($updated_values) {
            $db->query("UPDATE Sub_Class SET CustomSettings='".$db->escape($updated_values)."' WHERE Sub_Class_ID=".$SubClassID."");

            // execute core action
            $nc_core->event->execute("updateSubClass", $catalogue, $parentSub, $SubClassID);

            $result = true;
            # заголовок блога
            if ($CustomSettings['BlogTitle']) {
                $db->query("UPDATE Subdivision SET Subdivision_Name='".$db->escape($CustomSettings['BlogTitle'])."'
							WHERE Subdivision_ID=".$parentSub."");
                // execute core action
                $nc_core->event->execute("updateSubdivision", $catalogue, $parentSub);
            }
            if (nc_module_check_by_keyword("comments") && $CustomSettings['Comments_Access']) {
                $existCommentRule = $db->get_var("SELECT `ID` FROM `Comments_Rules` WHERE `Subdivision_ID` = '".$parentSub."'");
                if ($existCommentRule) {
                    $db->query("UPDATE `Comments_Rules`
            SET `Access_ID` = '".intval($CustomSettings['Comments_Access'])."',
            `Edit_Rule` = '".intval($CustomSettings['Comments_Edit'])."',
            `Delete_Rule` = '".intval($CustomSettings['Comments_Delete'])."'
  					WHERE `ID` = '".$existCommentRule."'");
                } else {
                    $db->query("INSERT INTO `Comments_Rules`
            (`Catalogue_ID`, `Subdivision_ID`, `Access_ID`, `Edit_Rule`, `Delete_Rule`)
            VALUES
            ('".(int) $current_sub['Catalogue_ID']."', '".$parentSub."', '".intval($CustomSettings['Comments_Access'])."', '".intval($CustomSettings['Comments_Edit'])."', '".intval($CustomSettings['Comments_Delete'])."')");
                    $db->query("UPDATE `Subdivision` SET `Comment_Rule_ID` = '".$db->insert_id."' WHERE `Subdivision_ID` = '".$parentSub."'");
                }
            }
        }
    } else {
        # значения настроек
        $values = $db->get_var("SELECT CustomSettings FROM Sub_Class WHERE Sub_Class_ID=".$SubClassID."");
        if ($values) eval("\$values_array = $values;");

        if (empty($values_array))
                $values_array['BlogTitle'] = $current_sub['Subdivision_Name'];

        $array_form = new nc_a2f($settings_array, 'CustomSettings'); #_info: 'CustomSettings'
        $array_form->set_value($values_array);
        # рендерим форму по шаблону
        $VisualForm = $array_form->render($template_header, $template_object, $template_footer);
    }

    return $VisualForm;
}

# simple back up optional function, restore MySQL to prev. status, step-by-step use back up array

function nc_blog_backup($errorBackUp) {
    global $db;
    # back up if array exist
    if ($errorBackUp) {
        foreach ($errorBackUp AS $query) {
            $db->query(mysql_real_escape_string($query));
        }
        return true;
    }
# if array not exist
    return false;
}
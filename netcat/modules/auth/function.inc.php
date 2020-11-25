<?php

/* $Id: function.inc.php 5319 2011-09-08 14:08:57Z andrey $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");
if (!class_exists("nc_System")) die("Unable to load file.");
global $MODULE_FOLDER;
// функционал друзей-врагов
include ($MODULE_FOLDER."auth/nc_auth.relation.php");

// include need classes
include_once ($MODULE_FOLDER."auth/nc_auth.class.php");
include_once ($MODULE_FOLDER."auth/authtype/nc_auth_hash.class.php");
include_once ($MODULE_FOLDER."auth/authtype/nc_authex.class.php");
include_once ($MODULE_FOLDER."auth/authtype/nc_auth_token.class.php");
include_once ($MODULE_FOLDER."auth/templates/nc_auth_template.class.php");



global $nc_core, $nc_auth, $nc_auth_fb, $nc_auth_vk, $nc_auth_openid, $nc_auth_twitter;
$nc_auth = nc_auth::get_object();
$nc_auth_fb = new nc_authEx_fb();
$nc_auth_vk = new nc_authEx_vk();
$nc_auth_twitter = new nc_authEx_twitter();
$nc_auth_openid = new nc_authEx_openid();

// для совместимости
$MODULE_VARS['auth']['USER_PREMODERATION'] = $nc_core->get_settings('premoderation', 'auth');
$MODULE_VARS['auth']['USER_CONFIRMATION'] = $nc_core->get_settings('confirm', 'auth');
$MODULE_VARS['auth']['USER_GROUP'] = $nc_core->get_settings('group', 'auth');
$MODULE_VARS['auth']['USER_REG_NOTIFY'] = $nc_core->get_settings('notify_admin', 'auth');
$MODULE_VARS['auth']['USER_MESSAGES_CLASS'] = $nc_core->get_settings('pm_class_id', 'auth');
$MODULE_VARS['auth']['USER_MODIFY_SUB'] = $nc_core->get_settings('modify_sub', 'auth');
$MODULE_VARS['auth']['USER_LIST_CC'] = $nc_core->get_settings('user_list_cc', 'auth');
$MODULE_VARS['auth']['USER_BIND_TO_CATALOGUE'] = $nc_core->get_settings('bind_to_catalogue', 'auth');
$MODULE_VARS['auth']['USER_ONLINE_TIME_LEFT'] = $nc_core->get_settings('online_timeleft', 'auth');
$MODULE_VARS['auth']['IP_CHECK_LEVEL'] = $nc_core->get_settings('ip_check_level', 'auth');
$MODULE_VARS['auth']['COOKIES_WITH_SUBDOMAIN'] = $nc_core->get_settings('with_subdomain', 'auth');
$MODULE_VARS['auth']['PERSONAL_MESSAGES_ENABLED'] = $nc_core->get_settings('pm_allow', 'auth');

function AttemptToAuthorize() {
    $sname = session_name();

    global $db, $perm, $nc_core;
    global $AUTHORIZE_BY, $AUTH_TYPE, $ADMIN_AUTHTYPE;
    global $PHP_AUTH_USER, $PHP_AUTH_PW, $PHP_AUTH_SID, $PHP_AUTH_LANG;
    global $AUTHORIZATION_TYPE, $MODULE_VARS, $ADMIN_AUTHTIME, $HTTP_HOST, $SUB_FOLDER;
    global $AUTH_USER_ID, $AUTH_USER_GROUP;
    global $$sname, $current_user, $catalogue;

    if (is_object($perm)) return $AUTH_USER_ID;

    $IP_CHECK_LEVEL = &$MODULE_VARS['auth']['IP_CHECK_LEVEL'];
    $IpCheckLevel = ( $IP_CHECK_LEVEL != '' && $IP_CHECK_LEVEL >= 0 && $IP_CHECK_LEVEL <= 4 ? (int) $IP_CHECK_LEVEL : 2);

    $AUTH_USER_ID = 0;
    $AUTH_USER_GROUP = 0;

    if ($AUTHORIZATION_TYPE != 'http') {
        if ($$sname) {
            $_GET[session_name()] = $$sname;
            $_POST[session_name()] = $$sname;
        } else {
            $session_id = md5(uniqid(rand(), 1));
            session_id($session_id);
        }

        $glbSid = session_id();
        if (isset($_SESSION['User']['IsLogin'])) {
            if ($_SESSION['User']['IP'] != getenv("REMOTE_ADDR"))
                    header("Location: ".$SUB_FOLDER);
            if ((time() - $_SESSION['User']['datetime']) > ini_get('session.gc_maxlifetime'))
                    unset($_SESSION['User']);
        }
        $_SESSION['User']['datetime'] = time();
        if ($AUTHORIZATION_TYPE == 'session' && !$_SESSION['User']['ID'] && $_SESSION_USER_ID != "0") {
            $AUTH_USER_ID = $_SESSION['User']['ID'];
        }
    }


    // Проверка IP адреса
    if ($AUTH_TYPE != 'http') {

        if ($IpCheckLevel == 0) {
            $SqlCheckIp = '';
        } elseif ($IpCheckLevel == 4) {
            $SqlCheckIp = ' AND s.UserIP = '.sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
        } else {
            $IpVal = explode('.', $_SERVER['REMOTE_ADDR'], $IpCheckLevel + 1);
            array_pop($IpVal);
            $IpVal = implode('.', $IpVal);
            $UserIPBegin = $IpVal.str_repeat('.0', 4 - $IpCheckLevel);
            $UserIPEnd = $IpVal.str_repeat('.255', 4 - $IpCheckLevel);
            $SqlCheckIp = ' AND s.UserIP >= '.sprintf("%u", ip2long($UserIPBegin)).' AND s.UserIP <= '.sprintf("%u", ip2long($UserIPEnd));
        }
    }

    $query_where_cat = $MODULE_VARS['auth']['USER_BIND_TO_CATALOGUE'] ? " AND u.Catalogue_ID IN(0,".$catalogue.")" : "";

    // HTTP валидация
    if ($AUTHORIZATION_TYPE == 'http') {
        $user_result = $db->get_results("SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID
			                           FROM `User` as u, `User_Group` as ug
			                           WHERE u.`".$AUTHORIZE_BY."` = '".$PHP_AUTH_USER."'
			                           AND Password=".$nc_core->MYSQL_ENCRYPT."('".$PHP_AUTH_PW."')
			                           AND u.`User_ID` = ug.`User_ID`
			                           ", ARRAY_A);
    }
    // SESSION валидация
    elseif ($AUTHORIZATION_TYPE == 'session') {
        $user_result = $db->get_results("SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID, s.`LoginSave`, s.`AuthVariant`
			                            FROM (`User` as u, `User_Group` as ug)
			                            RIGHT JOIN `Session` AS s
			                            ON u.User_ID = s.User_ID
			                            WHERE u.Checked = 1
			                            AND u.`User_ID` = ug.`User_ID`
			                            AND s.Session_ID = '".$db->escape($$sname)."'
			                            AND s.SessionTime > ".time().$SqlCheckIp.$query_where_cat, ARRAY_A);
    }
    // COOKIE валидация
    elseif ($AUTHORIZATION_TYPE == 'cookie') {
        $user_result = $db->get_results("SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID,  s.`LoginSave`, s.`AuthVariant`, s.SessionTime
			                            FROM (`User` as u, `User_Group` as ug)
			                            RIGHT JOIN Session AS s
			                            ON u.User_ID = s.User_ID
			                            WHERE u.Checked = 1 AND u.`User_ID` = ug.`User_ID`
			                            AND s.Session_ID = '".$db->escape($PHP_AUTH_SID)."' AND s.SessionTime > ".time().$SqlCheckIp.$query_where_cat, ARRAY_A);
    }


    // на случай, есил поля не существует
    if (strstr($db->last_error, 'AuthVariant')) {
        $db->query("ALTER TABLE `Session` ADD `AuthVariant` ENUM ('normal', 'hash', 'open_id') DEFAULT 'normal';");
        return AttemptToAuthorize();
    }

    if ($user_result[0]['AuthVariant'] == 'hash') {
        $nc_auth = nc_auth::get_object();
        if (!$nc_auth->hash->check(0, 0)) {
            unset($user_result); // проверка не прошла
            return false;
        }
    }




    // Авторизованные пользователи
    if ($user_result) {
        $current_user = $user_result[0];
        unset($current_user['PermissionGroups_ID']);
        foreach ($user_result as $row) {
            $current_user["Permission_Group"][] = $row['PermissionGroups_ID'];
        }
        $AUTH_USER_ID = $current_user['User_ID'];
        $AUTH_USER_GROUP = $current_user['PermissionGroup_ID'];
        $PHP_AUTH_USER = $current_user[$AUTHORIZE_BY];
        //if ( $AUTHORIZATION_TYPE != 'http') $PHP_AUTH_PW = $Password;
        $LoginSave = ($current_user['LoginSave'] || $ADMIN_AUTHTYPE == 'always' ? 1 : 0);
        $Catalogue_ID = $current_user['Catalogue_ID'];

        $SessionTime = ($ADMIN_AUTHTIME ? time() + $ADMIN_AUTHTIME : time() + 30 * 24 * 3600);

        if ($AUTHORIZATION_TYPE == 'session') {
            $db->query("UPDATE Session SET SessionTime = ".$SessionTime." WHERE Session_ID = '".$$sname."'");
        } elseif ($AUTHORIZATION_TYPE == 'cookie' && ($SessionTime - $user_result[0]['SessionTime']) > 60) {
            $db->query("UPDATE Session SET SessionTime = ".$SessionTime." WHERE Session_ID = '".$PHP_AUTH_SID."'");
            if (!$LoginSave) $SessionTime = 0;
            $cookie_domain = ($MODULE_VARS['auth']['COOKIES_WITH_SUBDOMAIN'] ? str_replace("www.", "", $HTTP_HOST) : NULL);
            setcookie('PHP_AUTH_SID', $PHP_AUTH_SID, $SessionTime, "/", $cookie_domain);
            setcookie('PHP_AUTH_LANG', $PHP_AUTH_LANG, $SessionTime, "/", $cookie_domain);
        }
    }

    // Гости
    else {
        $SessionTime = time() + ($ADMIN_AUTHTIME ? $ADMIN_AUTHTIME : 24 * 3600);
        $PHP_AUTH_SID = ($$sname ? $$sname : $glbSid);
        $PHP_AUTH_USER = '';
        $PHP_AUTH_PW = '';

        $update_res = $db->query("UPDATE Session SET SessionTime = IF (SessionTime=".$SessionTime.", SessionTime+1,".$SessionTime.") WHERE Session_ID = '".$db->escape($PHP_AUTH_SID)."'");
        if (!$update_res) {
            $db->query("INSERT INTO Session (Session_ID, User_ID, SessionStart, SessionTime, UserIP, Catalogue_ID) VALUES ('".$db->escape($PHP_AUTH_SID)."', 0, ".time().", ".$SessionTime.", ".sprintf("%u", ip2long($_SERVER['REMOTE_ADDR'])).", ".$catalogue.")");
            // чистим гостевые сессии
            if (!rand(0, 50))
                    $db->query("DELETE FROM Session WHERE User_ID = 0 AND SessionTime < ".($SessionTime - 300));
        }
    }

    if ($AUTH_USER_ID) {
        $perm = new Permission($AUTH_USER_ID, 0, $user_result);
        return $AUTH_USER_ID;
    } else {
        return false;
    }
}

function GetAllProjectDomains() {
    global $db, $DOMAIN_NAME, $SUB_FOLDER;

    $res = $db->get_results("SELECT IF(LOCATE('.',Domain),Domain,IF(Domain<>'',CONCAT(Domain,'.".$DOMAIN_NAME."'),'".$DOMAIN_NAME."')),Mirrors FROM Catalogue ORDER BY Priority", ARRAY_N);
    $k = 0;
    $CatCount = $db->num_rows;
    for ($i = 0; $i < $CatCount; $i++) {
        list($Domain, $Mirrors) = $res[$i];
        $DomainArray[$k] = $Domain;
        $MirrorArray = explode("\n", $Mirrors);
        for ($j = 0; $j < count($MirrorArray); $j++) {
            $k++;
            $DomainArray[$k] = trim($MirrorArray[$j]);
        }
    }
    return ($DomainArray);
}

/* ======= USER FUNCTIONS ======= */

/**
 * Получение пути к странице "Регистрация пользователя"
 *
 * @param integer ID сайта (optional)
 * @return string путь к разделу от корня
 */
function nc_auth_regform_url($catalogue_id = 0, $with_cc_url = 1) {
    global $db, $SUB_FOLDER;

    $catalogue_id = ($catalogue_id ? $catalogue_id : $GLOBALS['catalogue']);

    $url = $db->get_var("SELECT ".($with_cc_url ? "CONCAT(sub.Hidden_URL, cc.EnglishName, '.html')" : "sub.Hidden_URL" )."
		FROM Subdivision as sub, Sub_Class as cc, Class as class
		WHERE class.System_Table_ID=3
		AND class.Class_ID=cc.Class_ID
		AND cc.Catalogue_ID = '".intval($catalogue_id)."'
		AND cc.DefaultAction='add'
		AND cc.Checked = 1
		AND cc.Subdivision_ID=sub.Subdivision_ID
		LIMIT 1");
    return $SUB_FOLDER.$url;
}

/**
 * url адрес на профайл пользователя
 *
 * @param mixed $User_ID Идентификатор(ы) пользователя (optional)
 * @param int учитывать keyword пользователя или нет (optional)
 *
 * @global $nc_core
 *
 * @return mixed путь к профайлу от корня
 */
function nc_auth_profile_url($User_ID = 0, $allow_keyword = 0, $sub_url = 0) {
    global $nc_core;

    $db = $nc_core->db;
    $catalogue = $nc_core->catalogue->get_current('Catalogue_ID');
    $SUB_FOLDER = $nc_core->SUB_FOLDER;

    static $keywords = array(); // массив с keywordами пользователей
    static $url; // путь от корня до пользователей без их id (kweyword'ов)
    // нужно узнать ключевые слова пользователей
    if ($allow_keyword && empty($keywords)) {
        $res = $db->get_results("SELECT `User_ID`, `Keyword` FROM `User`", ARRAY_A);
        if (!empty($res)) {
            foreach ($res as $v) {
                $keywords[$v['User_ID']] = $v['Keyword'];
            }
        }
    }

    // номера компонентов в разделе с пользователями
    $UserListCc = $nc_core->get_settings('user_list_cc', 'auth');

    if ($UserListCc) {  
        if (!$url) {
            $db_url = $db->get_row("SELECT sd.Hidden_URL as HURL, sc.EnglishName as EN
				FROM Sub_Class AS sc
				LEFT JOIN Subdivision AS sd
				ON sc.Subdivision_ID = sd.Subdivision_ID
				WHERE sc.Catalogue_ID = ".(int) $catalogue." AND sc.Sub_Class_ID IN (".$UserListCc.")", ARRAY_A);
        
            if ($sub_url) return $SUB_FOLDER.$db_url['HURL'];
            $url = $db_url['HURL'].$db_url['EN']; 
        }

        if (is_array($User_ID)) {
            foreach ($User_ID as $id) {
                if ($allow_keyword && $keywords[$id]) {
                    $result[] = $SUB_FOLDER.substr($url, 0, strrpos($url, '/')).'/'.$keywords[$id].'.html';
                } else {
                    $result[] = $SUB_FOLDER.$url.'_'.$id.'.html';
                }
            }
            return $result;
        } elseif ($User_ID) {
            $id = $User_ID;

            if ($allow_keyword && $keywords[$id]) {
                return $SUB_FOLDER.substr($url, 0, strrpos($url, '/')).'/'.$keywords[$id].'.html';
            } else {
                return $SUB_FOLDER.$url.'_'.$id.'.html';
            }
        } else {
            return $SUB_FOLDER.$url.'_';
        }
    } else {
        return false;
    }

    return false;
}

/**
 * пользователи online
 *
 * @param mixed $template Шаблон вывода списка пользователей или режим вывода (optional)
 * @param string $select_fields Альтернативное поле имени пользователя (optional)
 * @return mixed
 */
function nc_auth_users_online($template = NULL, $select_fields = NULL) {
    global $nc_core, $db, $catalogue;
    global $MODULE_VARS, $ADMIN_AUTHTIME, $AUTHORIZE_BY, $SUB_FOLDER;

    $result = NULL;
    $TimeLeft = ($ADMIN_AUTHTIME ? time() + $ADMIN_AUTHTIME : time() + 24 * 3600) - $nc_core->get_settings('online_timeleft', 'auth');
    $tags = array('%NAME', '%URL');
    $tags2 = array('%GUESTS', '%REGISTERED', '%ONLINE');
    $select_fields = ($select_fields ? $select_fields.', ' : " IF(u.`ForumName` <> '', u.`ForumName`, u.`Login`) AS Name, ");
    $query_where_cat = $nc_core->get_settings('bind_to_catalogue', 'auth') ? "Catalogue_ID IN(0,".$catalogue.")" : "";

    switch ($template) {
        case ARRAY_N:
            return (array) $db->get_col("SELECT User_ID
				FROM Session
				WHERE User_ID != 0 AND SessionTime>".$TimeLeft.($query_where_cat ? " AND ".$query_where_cat : "")."
				GROUP BY User_ID
				ORDER BY User_ID");
            break;

        case ARRAY_A:
            return (array) $db->get_results("SELECT u.User_ID, ".$select_fields."CONCAT('".nc_auth_profile_url()."', u.User_ID, '.html') AS `Url`
				FROM User AS u
				INNER JOIN Session AS s
				ON u.User_ID=s.User_ID
				WHERE s.SessionTime>".$TimeLeft.($query_where_cat ? " AND s.".$query_where_cat : "")."
				GROUP BY u.User_ID
				ORDER BY Name", ARRAY_A);
            break;
    }

    if (!$template) {
        unset($template);
        $template['prefix'] = "";
        $template['suffix'] = "";
        $template['divider'] = ", ";
        $template['link'] = "<a href='%URL'>%NAME</a>";
    }

    $Guests = $db->get_var("SELECT COUNT(Session_ID)
		FROM Session
		WHERE User_ID=0 AND SessionTime>".$TimeLeft.($query_where_cat ? " AND ".$query_where_cat : ""));

    if (is_array($template)) {

        $OnlineUsers = $db->get_results("
                        SELECT IF(u.`ForumName` <> '', u.`ForumName`, u.`Login`) AS Name, CONCAT('".nc_auth_profile_url()."', u.User_ID, '.html') AS `Url`
			FROM User AS u
			INNER JOIN Session AS s
			ON u.User_ID=s.User_ID
			WHERE s.User_ID!=0 AND s.SessionTime>".$TimeLeft.($query_where_cat ? " AND s.".$query_where_cat : "")."
			GROUP BY u.User_ID
			ORDER BY Name", ARRAY_A);

        if ($Registered = count($OnlineUsers)) {
            if ($template['link']) {
                foreach ($OnlineUsers as $user)
                    $result[] = str_replace($tags, $user, $template['link']);
                $result = join(($template['divider'] ? $template['divider'] : ''), $result);
            }
            $result = ($template['prefix'] ? $template['prefix'] : '').$result.($template['suffix'] ? $template['suffix'] : '');

            $result = str_replace($tags2, array($Guests, $Registered, $Guests + $Registered), $result);
        }
    } else {
        $Registered = count($db->get_results("SELECT User_ID
			FROM Session
			WHERE User_ID != 0 AND SessionTime>".$TimeLeft.($query_where_cat ? " AND ".$query_where_cat : "")."
			GROUP BY User_ID", ARRAY_N));
        $result = str_replace($tags2, array($Guests, $Registered, $Guests + $Registered), $template);
    }


    return $result;
}

/**
 * проверка статуса пользователя
 *
 * @param int $User_ID Идентификатор пользователя
 */
function nc_auth_is_online($User_ID) {
    static $online;

    if (!is_array($online)) {
        $online = nc_auth_users_online(ARRAY_N);
    }

    return is_array($online) && in_array($User_ID, $online) ? true : false;
}

/**
 * Количество новых сообщений для текущего или заданного пользователя
 *
 * @param int $User_ID Идентификатор пользователя (optional)
 * @return int
 */
function nc_auth_messages_new($User_ID = 0, $Sub_Class_ID = 0) {
    global $db, $current_user, $catalogue;
    global $MODULE_VARS;

    if (!$current_user) return false;

    if (!$User_ID) $User_ID = $current_user['User_ID'];

    $User_ID = intval($User_ID);
    $catalogue = intval($catalogue);
    if (!isset($MODULE_VARS['auth']['UserMessagesNew'])) {
        $MODULE_VARS['auth']['UserMessagesNew'] = $db->get_var("SELECT COUNT(m.Message_ID)
			FROM `Message".intval(nc_Core::get_object()->get_settings('pm_class_id', 'auth'))."` AS m
			RIGHT JOIN Sub_Class AS s
			ON m.Sub_Class_ID = s.Sub_Class_ID
			WHERE m.Status = 0 AND m.ToUser = '".$User_ID."' AND s.Catalogue_ID = ".$catalogue.($Sub_Class_ID ? " AND m.Sub_Class_ID = ".(int) $Sub_Class_ID : ""));
    }
    return $MODULE_VARS['auth']['UserMessagesNew'];
}

/**
 * URL Адрес страницы с сообщениями / адрес отправки сообщения
 *
 * @param int $User_ID Идентификатор пользователя (получателя) для отправки сообщения (optional)
 * @param int $Sub_Class_ID Идентификатор компонента личных сообщений в разделе (optional)
 * @return string
 */
function nc_auth_messages_url($User_ID = 0, $Sub_Class_ID = 0) {
    global $db, $catalogue;
    global $MODULE_VARS, $SUB_FOLDER;
    $catalogue = intval($catalogue);

    if (!isset($MODULE_VARS['auth']['UserMessagesUrl'])) {
        $res = $db->get_row("SELECT sd.Hidden_URL, sc.EnglishName
			FROM Sub_Class AS sc
			LEFT JOIN Subdivision AS sd
			ON sc.Subdivision_ID = sd.Subdivision_ID
			WHERE sc.Class_ID = ".nc_Core::get_object()->get_settings('pm_class_id', 'auth')." AND sc.Catalogue_ID = ".$catalogue.($Sub_Class_ID ? " AND sc.Sub_Class_ID = ".(int) $Sub_Class_ID : "")."
			LIMIT 1", ARRAY_A);

        $MODULE_VARS['auth']['UserMessagesClassEnName'] = $res['EnglishName'];
        $MODULE_VARS['auth']['UserMessagesUrl'] = $res['Hidden_URL'];
    }

    return $SUB_FOLDER.$MODULE_VARS['auth']['UserMessagesUrl'].($User_ID ? "add_".$MODULE_VARS['auth']['UserMessagesClassEnName'].".html?uid=".$User_ID : "");
}

function nc_auth_time_left() {
    global $ADMIN_AUTHTIME, $MODULE_VARS;
    return ($ADMIN_AUTHTIME ? time() + $ADMIN_AUTHTIME : time() + 24 * 3600) - nc_Core::get_object()->get_settings('online_timeleft', 'auth');
}

/** DEPRECATED FUNCTIONS */
function nc_auth_openid_field_exist() {
    return false;
}

function nc_auth_openid_possibility() {
    return false;
}

function nc_auth_get_settings($item = '') {
    return nc_Core::get_object()->get_settings($item, 'auth');
}
?>
<?php

/* $Id: function.inc.php 8329 2012-11-02 11:31:02Z vadim $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -3)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");
require_once($INCLUDE_FOLDER."unicode.inc.php");
require_once ($ROOT_FOLDER.'connect_io.php');

$nc_core->inside_admin = $inside_admin = true;
$nc_core->admin_mode = $admin_mode = true;

$nc_core->load_default_extensions();
$nc_core->load_files(1);

$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER."lang/".$lang.".php");
$PHP_AUTH_LANG = $lang;

$load_all_modules = strstr($REQUEST_URI, $HTTP_ROOT_PATH."modules") ? true : false;


$nc_core->modules->load_env($nc_core->lang->acronym_from_full($lang), !$load_all_modules);


if ($nc_core->modules->get_by_keyword('auth')) {
    $nc_auth = nc_auth::get_object();
    // доступ только по https
    if ($nc_core->NC_ADMIN_HTTPS && $_SERVER['HTTPS'] != 'on') {
        header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        exit;
    }
    // нужна ли каптча
    if ($AuthPhase && $AUTH_USER && $nc_auth->need_captcha() && !nc_captcha_verify_code($nc_core->input->fetch_get_post('nc_captcha_code'))) {
        $nc_auth->set_invalid_captcha();
        $AuthPhase = 0;
    }
}
// Admin Interface Initialization
$admin_mode = true;
LoadSettings();

$nc_token_login = $nc_core->input->fetch_get_post('nc_token_login');
$nc_token_signature = $nc_core->input->fetch_get_post('nc_token_signature');
if ($nc_token_login && $nc_token_signature) {
    $nc_core->user->authorize_by_token($nc_token_login, $nc_token_signature, $_SESSION['nc_token_rand']);
}
Authorize(0, null, null, 1);

if (!$AUTH_USER_ID) {
    Refuse();
    exit;
}


# Загрузка файла локализации
if ($lang) {
    $AUTH_LANG = $lang;
    $db->query("UPDATE `User` SET `Language` = '".$db->escape($lang)."' WHERE `User_ID` = '".$AUTH_USER_ID."'");
}




// установить заголовок с кодировкой, чтобы избежать проблем с
// "автоопределением" в браузерах
header("Content-Type: text/html; charset=".$nc_core->NC_CHARSET);


// не пускать кого попало в административную часть системы

if (!$perm->isInsideAdmin()) {
    BeginHtml(BEGINHTML_TITLE, BEGINHTML_TITLE, "http://".$DOC_DOMAIN."/admin/");
    nc_print_status($NO_RIGHTS_MESSAGE, 'error');
    echo "<div><a href='/netcat/modules/auth/?logoff=1&REQUESTED_FROM=/'>".BEGINHTML_LOGOUT."</a></div>";
    EndHtml ();
    exit;
}

//Что показать в карте сайта
$treeMode = $perm->isAccessSiteMap() ? 'sitemap' : ( $perm->isAccessDevelopment() ? 'developer' : 'users');

// --- Common functions

function LoadSettings() {
    global $nc_core, $db;
    global $IsInsideAdmin, $system_env;
    global $PROJECT_NAME, $VERSION_ID, $ANY_SYSTEM_MESSAGE, $SYSTEM_ID, $ADMIN_FOLDER;
    global $SPAM_FIELD, $SPAM_MAIL, $SPAM_FROM, $SPAM_FROM_NAME, $NO_RIGHTS_MESSAGE, $PATCH_CHECK_DATE;
    global $LAST_PATCH, $LAST_LOCAL_PATCH, $SYSTEM_NAME, $SYSTEM_COLOR;
    global $HTTP_HOST, $REMOTE_ADDR, $HTTP_USER_AGENT, $HTTP_ROOT_PATH;
    global $EDITOR_TYPE, $EMBED_EDITOR, $SUB_FOLDER;
    global $REMIND_SAVE;

    $Array = $nc_core->get_settings();

    // probably system was not installed:
    if ( !sizeof($Array) ) {
        // probably system was not installed
		if ( $this->check_system_install() ) {
			// DB error
			print "<p><b>".NETCAT_ERROR_DB_CONNECT."</b></p>";
			exit;
		}
    }

    $PROJECT_NAME = $Array['ProjectName'];
    $SYSTEM_ID = $Array['SystemID'];
    $VERSION_ID = $Array['VersionNumber'];
    $SPAM_FIELD = $Array['UserEmailField'];
    $SPAM_MAIL = $Array['UserEmailField'];
    $SPAM_FROM = $Array['SpamFromEmail'];
    $SPAM_FROM_NAME = $Array['SpamFromName'];
    $EDITOR_TYPE = $Array['EditorType'];
    $EMBED_EDITOR = $Array['EmbedEditor'];
    $REMIND_SAVE = $Array['RemindSave'];

    list($SYSTEM_NAME, $SYSTEM_COLOR) = nc_system_name_by_id($SYSTEM_ID);

    if (isset($Array['InstallationID']) || $Array['InstallationDateOut'])
            $nc_core->is_trial = true;

    $LAST_LOCAL_PATCH = $db->get_var("SELECT `Patch_Name` FROM `Patch` ORDER BY `Patch_Name` DESC LIMIT 1");
    $LAST_LOCAL_PATCH += 0;

    // следующий патч
    $LAST_PATCH = $Array['LastPatch'];
    // время последнего обращения за обновлениями
    $PATCH_CHECK_DATE = $Array['PatchCheck'];
    // спрашиваем раз в неделю
    $PATCH_CHECK_NEEDED = ($PATCH_CHECK_DATE + 2 * 24 * 3600) < time();
    if (!$PATCH_CHECK_DATE || $PATCH_CHECK_NEEDED || $IsInsideAdmin) {
        $an = new nc_AdminNotice();
        $LAST_PATCH = $an->update();
    }

    $ANY_SYSTEM_MESSAGE = $db->get_var("SELECT COUNT(*) FROM `SystemMessage` WHERE `Checked` = 0");

    $system_env = $nc_core->get_settings();
    $system_env['Powered'] = "PHP/".phpversion();
}

/**
 * Get favorites subdivision
 * @param  Return type: object, array_a, array_n
 *
 * @return 2xarray. Keys: id, name, catalogueID, urls, checked. domain, catalogueName
 */
function GetFavorites($returnType = 'ARRAY_A') {
    global $db, $perm;
    $select = "sub.Subdivision_ID, sub.Subdivision_Name, sub.Catalogue_ID, sub.ExternalURL, sub.Hidden_URL, sub.Checked AS SubChecked,
             catalogue.Domain, catalogue.Catalogue_Name, catalogue.Checked AS CatalogueChecked";

    // показать только те разделы, админом\модер. которго пол-ль является
    $allow_id = $perm->GetAllowSub($CatalogueID, MASK_ADMIN | MASK_MODERATE, false, true, false);
    $security_limit = is_array($allow_id) ? "Subdivision_ID IN(".join(',', (array) $allow_id).")" : "1";

    $favorites = $db->get_results("SELECT ".$select."
                                   FROM Subdivision AS sub
                              LEFT JOIN Catalogue AS catalogue ON catalogue.Catalogue_ID = sub.Catalogue_ID
                                  WHERE sub.Favorite = '1' AND ".$security_limit."
                               ORDER BY Catalogue_ID,
                                        Subdivision_ID", $returnType);


    return $favorites;
}

# проверка на записать в директори, $dir_name - относительный путь, например, /netcat/tmp/

function checkPermissions($dir_name, $DOCUMENT_ROOT="") {
    global $SUB_FOLDER;

    if (substr(php_uname(), 0, 7) == "Windows")
            $filepath = $DOCUMENT_ROOT.$SUB_FOLDER.$dir_name."n123.tmp";
    else $filepath=dirname(__FILE__)."/../..".$dir_name."n123.tmp";

    $h = @fopen($filepath, "w");
    if ($h === FALSE) {
        nc_print_status(NETCAT_FILEUPLOAD_ERROR, 'error', $SUB_FOLDER.$dir_name);
        $result = FALSE;
    } else {
        fclose($h);
        @unlink($filepath);
        $result = TRUE;
    }

    return $result;
}

/**
 * Варианты (<option>) для списка объектов (<select>)
 *
 * @param array нумерованный массив, содержащий ассоциативные массивы со
 *    значениями (должны содержать ключи value, description; опционально -
 *    parent; если есть parent, результат будет перегруппирован по данному
 *    полю; optgroup; если есть optgroup создается контейнеры, объединяющие группу списка)
 * @param mixed выбранный элемент
 * @param ingeger текущий уровень иерархического списка (для рекурсивного вызова функции)
 *
 * @return string
 */
function nc_select_options(&$data, $selected_value="", $level=0, $current_parent=0) {

    if (!is_array($data)) {
        trigger_error("nc_select_options: first parameter is not an array", E_USER_WARNING);
        return "";
    }

    $str = "";
    if (!$level) { // первый вызов функции
        if (array_key_exists('parent', $data[0])) { // перегруппировать по parent
            foreach ((array) $data as $row) {
                $values[$row['parent']][] = $row;
            }
        } else { // чтобы не делить циклы для случаев с группировкой и без нее
            $values = array(&$data);
        }
    } else { // рекурсивный вызов функции
        $values = &$data;
    }

    foreach ((array) $values[$current_parent] as $row) {
        if (!$level && $row['optgroup'] && ($optgroup != $row['optgroup'])) {
            $optgroup = $row['optgroup'];
            $str .= "<optgroup label='".$optgroup."'>\n";
        }
        $str .= "<option ".($row['without_cc'] ? 'style=\'color: #cccccc;\'' : '')." value=\"".htmlspecialchars_decode($row['value'])."\"".
                ($row['value'] == $selected_value ? ' selected' : '').
                ">".
                str_repeat("&nbsp; &nbsp; &nbsp;", $level).
                htmlspecialchars_decode($row['description'])."</option>\n";

        if ($values[$row['value']]) {
            $str .= nc_select_options($values, $selected_value, $level + 1, $row['value']);
        }
    }

    return $str;
}

/**
 * Поиск элемента в многомерном массиве
 *
 * @param string $value искомое значение
 * @param array $array массив, в котором ищется значение $value
 * @return bool
 */
function nc_deep_in_array($value, $array) {
    foreach ($array as $item) {
        if (!is_array($item)) {
            if ($item == $value) return true;
            else continue;
        }

        if (in_array($value, $item)) return true;
        else if (nc_deep_in_array($value, $item)) return true;
    }
    return false;
}

/**
 * Формирование ссылки "просмотр раздела" внутри админки с учётом ExternalURL
 * (дерево слева, карта сайта, избранные разделы)
 *
 * @param array $data массив с данными по разделу из Subdivision
 * @param bool $sid_off принудительно выключить id сессии
 * @return string
 */
function nc_subdivision_preview_link($data, $sid_off = false) {
    global $DOMAIN_NAME, $SUB_FOLDER;

    if (!is_array($data)) {
        if (is_object($data)) {
            $data = (array) $data;
        }
        else return false;
    }
    if (!array_key_exists("Hidden_URL", $data)) return false;

    if ($data['ExternalURL']) {
        $result = strchr($data['ExternalURL'], ":") ? $data['ExternalURL'] : "http://".($data['Domain'] ? $data['Domain'] : $DOMAIN_NAME
                ).$SUB_FOLDER.($data['ExternalURL'] ? (!preg_match("/^\/.*$/", $data['ExternalURL']) ? $data['Hidden_URL'].$data['ExternalURL'] : $data['ExternalURL']
                        ) : $data['Hidden_URL']
                );
    } else {
        $result = "http://".($data['Domain'] ? $data['Domain'] : $DOMAIN_NAME).$SUB_FOLDER.$data['Hidden_URL'];
    }

    if (!$sid_off) {
        $sid_suffix = (session_id() ? "?".session_name()."=".session_id() : "");
        if (isset($_GET[session_name()])) $result.= $sid_suffix;
    }

    return $result;
}

function nc_system_name_by_id($id) {
    $nc_core = nc_Core::get_object();
    switch ($id) {
        case 1:
            $system_name = "Small Business";
            $system_color = "#FF9900";
            break;
        case 2:
            $system_name = "Standard";
            $system_color = "#2690CF";
            break;
        case 3:
            $system_name = "Extra";
            $system_color = "#CC3300";
            break;
        case 4:
            $system_name = "Community";
            $system_color = "#65C11A";
            break;
        case 5:
            $system_name = "Lite";
            $system_color = "#000000";
            break;
        case 6:
            $system_name = "E-Commerce";
            $system_color = "#F07D22";
            break;
        case 7:
            $system_name = "SEO";
            $system_color = "#FDCC6D";
            break;
        case 8:
            $system_name = "Corporate";
            $system_color = "#525F67";
            break;
        case 10:
            $system_name = "Personal";
            $system_color = "#990066";
        case 12:
            $system_name = "Business";
            $system_color = "#FDCC6D";
    }

    if ($nc_core->beta) $system_name .= " <b>BETA</b>";

    return array($system_name, $system_color);
}

/* Возвращает true если версия совместима, иначе false
 * @param string
 *        - номер проверяемой версии
 */

function nc_version_control($tpl_version) {
    $tpl_version = preg_replace('/[^0-9\.]/', '', $tpl_version);
    $nc_core = nc_Core::get_object();
    $VersionNumber = $nc_core->get_settings("VersionNumber");
    $versions = array();
    $versions[$VersionNumber] = array();
    $versions['4.6'] = array('4.5');
    $versions['4.7'] = array('4.5', '4.6');
    return ($tpl_version == $VersionNumber || in_array($tpl_version, $versions[$VersionNumber]));
}


function nc_print_admin_save_scritp($form_id) {
    ?>
        <script>
            $nc('#<?= $form_id; ?>').ready(function() {
                    if ($nc('#<?= $form_id; ?>').html()) {

                        var nc_class_save_button = $nc('#nc_class_save', parent.document);
                        nc_class_save_button.attr('onClick', '');

                        nc_class_save_button.click(function() {
                            CMSaveAll();
                            formAsyncSave('<?= $form_id; ?>', 0 , 'formSaveStatus(1);');
                        });
                    }
                });

            </script>
        <?
}

function nc_naked_action_header() {
    if (!$_REQUEST['isNaked']) {
        echo "<div id='nc_admin_mode_content'>";
    } else {
        ob_clean();
    }
}

function nc_naked_action_footer() {
    if (!$_REQUEST['isNaked']) {
        echo "</div>";
    } else {
        exit;
    }
}

function nc_get_simple_modal_header($name) {
    ob_start();
    ?>
        <div class='nc_admin_form_menu' style='padding-top: 20px;'>
        <h2><?= $name; ?></h2>
            <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
                <ul>
                    <li id='nc_template_form_edit' class=''></li>
                </ul>
            </div>
            <div class='nc_admin_form_menu_hr'></div>
        </div>

        <div class='nc_admin_form_body'>
    <?
    return ob_get_clean();
}

function nc_get_simple_modal_footer() {
    ob_start();

    ?>
        </div>

        <div class='nc_admin_form_buttons'>
            <input class='nc_admin_metro_button' type='button' value='<?= NETCAT_REMIND_SAVE_SAVE; ?>' title='<?= NETCAT_REMIND_SAVE_SAVE; ?>' disable />
            <input class='nc_admin_metro_button_cancel' style='color: black; margin-right: 16px; background-color: #EEEEEE; border: 1px solid red;' type='button' value='<?= CONTROL_BUTTON_CANCEL ?>' title='<?= CONTROL_BUTTON_CANCEL ?>' />
        </div>

        <style>
            a { color:#1a87c2; }
            a:hover { text-decoration:none; }
            a img { border:none; }
            p { margin:0px; padding:0px 0px 18px 0px; }
            h2 { font-size:20px; font-family:'Segoe UI', SegoeWP, Arial; color:#333333; font-weight:normal; margin:0px; padding:20px 0px 10px 0px; line-height:20px; }
            form { margin:0px; padding:0px; }
            input { outline:none; }
            .clear { margin:0px; padding:0px; font-size:0px; line-height:0px; height:1px; clear:both; float:none; }
            select, input, textarea { border:1px solid #dddddd; }
            :focus { outline:none;}
            .input { outline:none; border:1px solid #dddddd; }
        </style>

        <script>
            var nc_admin_metro_buttons = $nc('.nc_admin_metro_button');

            $nc(function() {
                $nc('#adminForm').html('<div class="nc_admin_form_main">' + $nc('#adminForm').html() + '</div>');
            });

            nc_admin_metro_buttons.click(function() {
                $nc('#adminForm').submit();
            });
        </script>
    <?
    return ob_get_clean();
}

function nc_get_array_2json_button($label, $action_hash, $icon_class) {
    return array(
            'label' => $label,
            'action' => "parent.location.hash = '$action_hash'",
            'icon' => "icons $icon_class");
}
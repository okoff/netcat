<?php
/* $Id: full.php 8441 2012-11-19 11:36:44Z vadim $ */

// вывод полной информации об объекте
$action = "full";

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER . "vars.inc.php");
require ($INCLUDE_FOLDER . "index.php");

$nc_core = nc_Core::get_object();
if ($File_Mode && !$templatePreview) {
    $template_view = new nc_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
    $template_view->load_template($template, $template_env['File_Path']);
    $array_settings_path = $template_view->get_all_settings_path_in_array();
    foreach ($array_settings_path as $path) {
        include $path;
    }
}
do {
    ob_start();

    $ignore_array = array('cond_user', 'cond_mod', 'cond_date', 'cond_where',
            'query_select', 'query_from', 'query_group', 'query_join', 'query_where', 'query_order',
            'ignore_all', 'ignore_sub', 'ignore_cc', 'ignore_check',
            'result_vars', 'f_ncTitle', 'f_ncKeywords', 'f_ncDescription');

    foreach ($ignore_array as $v) {
        unset($$v);
    }

    if (!$_db_cc) {
        $_db_cc = $cc;
    }

    $nc_ctpl = +$nc_ctpl;
    if(!$nc_ctpl) {
        $nc_ctpl = null;
    }
    
    $cc_env = $nc_core->sub_class->get_by_id($_db_cc, null, $nc_ctpl);
    $sub = $cc_env['Subdivision_ID'];

    $mirror_cc = $cc_env['SrcMirror'];

    if ($mirror_cc) {
        $mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
        $cc = $mirror_data['Sub_Class_ID'];
        $sub = $mirror_data['Subdivision_ID'];
        $catalogue = $mirror_data['Catalogue_ID'];
    }

    $classPreview = $_GET['classPreview'] + 0;
	// Если режим предпросмотра то заменим $current_cc данными из сессии.
    if ($classPreview == ($cc_env["Class_Template_ID"] ? $cc_env["Class_Template_ID"] : $cc_env["Class_ID"]) && (isset($_SESSION["PreviewClass"][$classPreview])) && ($_SESSION["PreviewClass"][$classPreview])) {
        $magic_gpc = get_magic_quotes_gpc();
        foreach ($_SESSION["PreviewClass"][$classPreview] as $tkey => $tvalue) {
            $cc_env[$tkey] = $magic_gpc ? stripslashes($tvalue) : $tvalue;
        }
        // Отключим кеширование в режиме предпросмотра.
        $cc_env['Cache_Access_ID'] = 2;
    }


// cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1) {
        // startup values
        $cached_data = "";
        $cached_eval = false;

        try {
            $nc_cache_full = nc_cache_full::getObject();
            // cache auth addon string
            $cache_for_user = $nc_cache_full->authAddonString($cc_env['CacheForUser'], $current_user);
            // check cached data
            $cached_result = $nc_cache_full->read($classID, $message, $REQUEST_URI . $cache_for_user, $current_cc['Cache_Lifetime']);
            if ($cached_result != -1) {
                // get cached parameters
                list ($cached_data, $cached_eval, $cache_vars) = $cached_result;
                // debug info
                $cache_debug_info = "Readed, sub[" . $sub . "], cc[" . $cc . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "], eval[" . (int) $cached_eval . "]";
                $nc_cache_full->debugMessage($cache_debug_info, __FILE__, __LINE__);

                // extract cached object variables
                if (!empty($cache_vars)) {
                    extract($cache_vars);
                    if ($f_ncTitle)
                        $nc_core->page->set_metatags('title', $f_ncTitle);
                    if ($f_ncKeywords)
                        $nc_core->page->set_metatags('keywords', $f_ncKeywords);
                    if ($f_ncDescription)
                        $nc_core->page->set_metatags('description', $f_ncDescription);
                }

                // return cache if cache data without "nocache" blocks
                if (!$cached_eval) {
                    echo $cached_data;
                    break;
                }
            }
            // set marks into the fields
            $no_cache_marks = $nc_cache_full->nocacheStore($cc_env);
        } catch (Exception $e) {
            // for debug
            $nc_cache_full->errorMessage($e);
        }
    }

// component custom settings
    $cc_settings = &$cc_env["Sub_Class_Settings"];

    $ignore_user = true;

    $subHost = "http://" . ($current_cc['Hidden_Host'] ? ( strchr($current_cc['Hidden_Host'], '.') ? $current_cc['Hidden_Host'] : $current_cc['Hidden_Host'] . '.' . $DOMAIN_NAME ) : $DOMAIN_NAME);

    $message_level_count = 0;
    $parent_message_tree[$message_level_count] = $message;

    if (!$user_table_mode) {
        while ($parent_message_tree[$message_level_count]) {
            $parent_mess_res = $db->get_var("SELECT `Parent_Message_ID` FROM `Message" . $classID . "` WHERE `Message_ID` = '" . (int) $parent_message_tree[$message_level_count] . "'");
            if ($db->num_rows) {
                $message_level_count++;
                $parent_message_tree[$message_level_count] = $parent_mess_res;
            } else {
                break;
            }
        }
    }

    if ($cc_env['File_Mode']) {
        $file_class = new nc_class_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $file_class->load($cc_env['Class_ID'], $cc_env['File_Path'], $cc_env['File_Hash']);
        $nc_parent_field_path = $file_class->get_parent_fiend_path('Settings');
        $nc_field_path = $file_class->get_field_path('Settings');
        // check and include component part
		try {
			if ( nc_check_php_file($nc_field_path) ) {
				include $nc_field_path;
			}
		}
		catch (Exception $e) {
			if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
				// error message
				echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM);
			}
		}
        $nc_parent_field_path = null;
        $nc_field_path = null;
    } else {
        if ($cc_env["Settings"]) {
            eval($cc_env["Settings"]);
        }
    }


// cache eval section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_full) && $cached_eval && $cached_result != -1) {
        eval("echo \"" . $cached_data . "\";");
        break;
    }

    $component = new nc_Component($classID, $cc_env['System_Table_ID']);
    $component->make_query();

    $field_vars = $component->get_fields_vars();
    $date_field = $component->get_date_field();

    $cond_date = false;
    if (!$ignore_user)
        $cond_user = " AND a.`User_ID` = '" . (int) $AUTH_USER_ID . "'";
    if (!$admin_mode && !$ignore_check)
        $cond_mod = " AND a.`Checked` = 1";
    if ($date)
        $cond_date = " AND a.`" . $date_field . "` LIKE '" . $db->escape($date) . "%'";

// ignore section
    if (!$ignore_sub && !$user_table_mode)
        $cond_where .= " AND a.`Subdivision_ID` = '" . (int) $sub . "'";
    if (!$ignore_cc && !$user_table_mode)
        $cond_where .= " AND a.`Sub_Class_ID` = '" . (int) $cc . "'";
// query_where
    if ($query_where)
        $cond_where .= " AND " . $query_where;

    if (!$ignore_all) {
        $message_select = "SELECT " . $component->get_fields_query() . ( $query_select ? ", " . $query_select : "") . "
                     FROM (`" . ($user_table_mode ? "User" : "Message" . $classID) . "` AS a
                     " . ( $query_from ? ", " . $query_from : "") . ")
                     " . $component->get_joins() . " " . ($query_join ? " " . $query_join : "") .
                " WHERE 1=1 " . $cond_where . $cond_user . $cond_mod . $cond_date . "
                       AND a.`" . ($user_table_mode ? "User" : "Message") . "_ID` = '" . (int) $message . "'";
    } else {
        $message_select = "SELECT " . $query_select . " FROM " . $query_from .
                ($query_join ? " " . $query_join : "") .
                ($query_where ? " WHERE " . $query_where : "") .
                ($query_group ? " GROUP BY " . $query_group : "");
    }

    $db->num_rows = 0;

    $resMsg = $db->get_row($message_select, ($cc_env['File_Mode'] ? ARRAY_A : ARRAY_N ));

    if (!$db->num_rows) {
        $nc_is_error = $db->is_error;

        if ($nc_is_error) {
            echo NETCAT_FUNCTION_FULL_SQL_ERROR_USER;
        }

        break;
    }
    /*
     * в списке объектов при fs переменные экстрактятся, и соответственно не работает $result_vars
     * нужно сдесь тоже сделать экстракт переменных при fs
     */
    if ($cc_env['File_Mode'] ) {
        
        if ($resMsg instanceof Iterator) {
            extract($resMsg->to_array(), EXTR_PREFIX_ALL, 'f');
            //добываем старые переменные
            extract($component->get_old_vars($resMsg->to_array()), EXTR_PREFIX_ALL, 'f');
        } else {
            extract($resMsg, EXTR_PREFIX_ALL, 'f');
            //добываем старые переменные
            extract($component->get_old_vars($resMsg), EXTR_PREFIX_ALL, 'f');
        }
    }
    else {

        if (!$ignore_all) {
            $fetch_row = "list(" . $field_vars . ($result_vars ? ", " . $result_vars : "") . ") = \$resMsg;";
        } else {
            $fetch_row = "list(" . $result_vars . ") = \$resMsg;";
        }

        eval($fetch_row);
        if ($ignore_link || $cc_env['SrcMirror']) {
                $subLink = $SUB_FOLDER . $cc_env['Hidden_URL'];
                $cc_keyword = $cc_env['EnglishName'];
        }
    }



// разрешить html-теги и перенос строки
    $cc_env['convert2txt'] = "";
    $text_fields = $component->get_fields(3);
    foreach ($text_fields as $field) {
        $format = nc_field_parse_format($field['format'], 3);
        // разрешить html
        if (!$cc_env['AllowTags'] && !$format['html'] || $format['html'] == 2)
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = htmlspecialchars(\$f_" . $field['name'] . ");";
        // перенос строки
        if ($cc_env['NL2BR'] && !$format['br'] || $format['br'] == 1)
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nl2br(\$f_" . $field['name'] . ");";
        if ($format['bbcode'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nc_bbcode(\$f_" . $field['name'] . ",  '', 1 );";
    }
    $text_fields = $component->get_fields(1);
    foreach ($text_fields as $field) {
        if (!$cc_env['AllowTags'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = htmlspecialchars(\$f_" . $field['name'] . ");";
        if ($cc_env['NL2BR'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nl2br(\$f_" . $field['name'] . ");";
    }
    unset($format);
    unset($text_fields);

    eval($cc_env['convert2txt']);



// Multiselect
    $multilist_fileds = $component->get_fields(10);
    if (!empty($multilist_fileds)) {
        // просмотр каждого поля типа multiselect
        foreach ($multilist_fileds as $multilist_filed) {
            $multilist_filed['table'] = strtok($multilist_filed['table'], ':');
            // таблицу с элементами можно взять их кэша, если ее там нет - то добавить
            if (!$_cache['classificator'][$multilist_filed['table']]) {
                $db_res = $db->get_results("SELECT `" . $multilist_filed['table'] . "_ID` AS ID, `" . $multilist_filed['table'] . "_Name` AS Name, `Value`
                                   FROM `Classificator_" . $multilist_filed['table'] . "`", ARRAY_A);
                if (!empty($db_res)) {
                    foreach ($db_res as $v) { // запись в кэш
                        $_cache['classificator'][$multilist_filed['table']][$v['ID']] = array($v['Name'], $v['Value']);
                    }
                }
                unset($db_res);
            }

            ${"f_" . $multilist_filed['name'] . "_id"} = array();
            ${"f_" . $multilist_filed['name'] . "_value"} = array();

            if (($value = ${"f_" . $multilist_filed['name']})) { // значение из базы
                ${"f_" . $multilist_filed['name']} = array();
                $ids = explode(',', $value);
                if (!empty($ids)) {
                    foreach ($ids as $id) { // для каждого элемента по id определяем имя
                        if ($id) {
                            array_push(${"f_" . $multilist_filed['name']}, $_cache['classificator'][$multilist_filed['table']][$id][0]);
                            array_push(${"f_" . $multilist_filed['name'] . "_value"}, $_cache['classificator'][$multilist_filed['table']][$id][1]);
                            array_push(${"f_" . $multilist_filed['name'] . "_id"}, $id);
                        }
                    }
                }
            }
            // default value
            if (!is_array(${"f_" . $multilist_filed['name']}))
                ${"f_" . $multilist_filed['name']} = array();
        }
        unset($ids);
        unset($id);
        unset($value);
    }

// 'left join' used to provide compatibility with old fs ($f_File_url)
    $res = $db->get_results("SELECT fd.`Field_ID` AS field_id, fd.`Field_Name` AS field, ft.`File_Path` AS path, ft.`Virt_Name` AS name, ft.`Download` AS download
  FROM `Field` AS fd
  LEFT JOIN `Filetable` AS ft
  ON (fd.`Field_ID` = ft.`Field_ID` AND ft.`Message_ID` = '" . (int) $message . "')
  WHERE " . ($user_table_mode ? "fd.`Class_ID` = 0 AND `System_Table_ID` = 3" : "fd.`Class_ID` = '" . (int) $classID . "'") . "
  AND fd.`TypeOfData_ID` = 6", ARRAY_A);

    foreach ((array) $res AS $row) {
        $field_value = ${"f_" . $row['field']}; // то, что хранится в базе
        // возможен случай, что файла нет.
        if (!$field_value)
            continue;
        $field_value = explode(':', $field_value);

        // оригинальное имя, тип, размер
        ${"f_" . $row['field'] . "_name"} = $field_value[0];
        ${"f_" . $row['field'] . "_type"} = $field_value[1];
        ${"f_" . $row['field'] . "_size"} = $field_value[2];


        if ($row['name']) { // Protected FileSystem
            ${"f_" . $row['field']} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, '/') . $row['path'] . "h_" . $row['name'];
            ${"f_" . $row['field'] . "_url"} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, '/') . $row['path'] . $row['name'];
            ${"f_" . $row['field'] . "_download"} = $row['download'];
        } else if ($field_value[3]) { // Original FileSystem
            ${"f_" . $row['field']} = ${"f_" . $row['field'] . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $field_value[3];
        } else { // Simple FileSystem
            $ext = substr($field_value[0], strrpos($field_value[0], "."));
            ${"f_" . $row['field'] . "_url"} = ${"f_" . $row['field']} = $SUB_FOLDER . $HTTP_FILES_PATH . $row['field_id'] . "_" . $f_RowID . $ext;
        }
    }
// free memory
    unset($field_value);
    unset($ext);
    unset($res);

    $multifile_fileds = $component->get_fields(11);
    if (!empty($multifile_fileds)) {
        extract(nc_get_arrays_multifield_for_nc_object_list_and_full_php($multifile_fileds, (array) $message));

        if (!empty($multifile_filed_names)) {
            foreach ($multifile_filed_names as $field_name) {
                ${'f_' . $field_name} = new nc_multifield($field_name);
                ${'f_' . $field_name}->set_data($multifiles[$message][$field_name])->template->set(${'f_' . $field_name . '_tpl'});
            }
        }
    }
// user group
    if ($user_table_mode) {
        $nc_user_group = $db->get_results("SELECT ug.`User_ID`, ug.`PermissionGroup_ID`, g.`PermissionGroup_Name`
                                     FROM `User_Group` AS ug,`PermissionGroup` AS g
                                     WHERE User_ID = '" . intval($message) . "'
                                     AND g.`PermissionGroup_ID` = ug.`PermissionGroup_ID` ", ARRAY_A);
        if (!empty($nc_user_group)) {
            foreach ($nc_user_group as $v) {
                $f_PermissionGroup[$v['PermissionGroup_ID']] = $v['PermissionGroup_Name'];
            }
        }
        unset($nc_user_group);
    }

// date values
    $f_Created_year = substr($f_Created, 0, 4);
    $f_Created_month = substr($f_Created, 5, 2);
    $f_Created_day = substr($f_Created, 8, 2);
    $f_Created_hours = substr($f_Created, 11, 2);
    $f_Created_minutes = substr($f_Created, 14, 2);
    $f_Created_seconds = substr($f_Created, 17, 2);

    $f_LastUpdated_year = substr($f_LastUpdated, 0, 4);
    $f_LastUpdated_month = substr($f_LastUpdated, 4, 2);
    $f_LastUpdated_day = substr($f_LastUpdated, 6, 2);
    $f_LastUpdated_hours = substr($f_LastUpdated, 8, 2);
    $f_LastUpdated_minutes = substr($f_LastUpdated, 10, 2);
    $f_LastUpdated_seconds = substr($f_LastUpdated, 12, 2);

    if ($date_field) {
        if (!$admin_mode) {
            eval("\$dateLink = \$f_" . $date_field . "_year.\"/\".\$f_" . $date_field . "_month.\"/\".\$f_" . $date_field . "_day.\"/\";");
        } else {
            eval("\$dateLink = \$f_" . $date_field . "_year.\"-\".\$f_" . $date_field . "_month.\"-\".\$f_" . $date_field . "_day;");
        }
    }

// title и метатеги
    if ($cc_env['TitleTemplate'])
        eval("\$f_title = \"" . $cc_env['TitleTemplate'] . "\";");
    if ($cc_env['UseAltTitle'] && $f_title)
        $nc_core->page->set_metatags('title', $f_title);
    if ($f_ncTitle)
        $nc_core->page->set_metatags('title', $f_ncTitle);
    if ($f_ncKeywords)
        $nc_core->page->set_metatags('keywords', $f_ncKeywords);
    if ($f_ncDescription)
        $nc_core->page->set_metatags('description', $f_ncDescription);
    
    $nc_core->page->set_h1($f_title);

    if ($no_cache_marks || $f_title || $f_ncTitle || $f_ncKeywords || $f_ncDescription) {
        // caching variables array
        $cache_vars = array();
        if ($f_title)
            $cache_vars['f_title'] = $f_title;
        if ($f_ncTitle)
            $cache_vars['f_ncTitle'] = $f_ncTitle;
        if ($f_ncKeywords)
            $cache_vars['f_ncKeywords'] = $f_ncKeywords;
        if ($f_ncDescription)
            $cache_vars['f_ncDescription'] = $f_ncDescription;
        // get variables names string
        preg_match("/^list\((.*?)\).*?$/", $fetch_row, $matches);
        if ($matches[1]) {
            // variables by name array
            $cache_vars_name = explode(",", $matches[1]);
            if (!empty($cache_vars_name)) {
                // correcting
                foreach ($cache_vars_name as $k => $v) {
                    $_variable_name = trim(str_replace('$', "", $v));
                    $cache_vars[$_variable_name] = $$_variable_name;
                    // clear
                    unset($_variable_name);
                }
                // clear
                unset($cache_vars_name);
            }
        }
    }
    $subLink = $SUB_FOLDER . $cc_env['Hidden_URL'];
    $ccLink = $subLink . $cc_keyword . ".html";

    

    if (!$cc_env['File_Mode']) {
        // get component body
        $component_body = $cc_env['RecordTemplateFull'] . $cc_env['Settings'];
        // other forms
        $cc_env["AddTemplate"] = $cc_env["AddTemplate"] ? $cc_env["AddTemplate"] : $component->add_form($catalogue, $sub, $cc);
        $cc_env["FullSearchTemplate"] = $cc_env["FullSearchTemplate"] ? $cc_env["FullSearchTemplate"] : $component->search_form(1);
    }
    else {
		// get component body
		$component_body = nc_check_file( $file_class->get_field_path('RecordTemplateFull') ) ? nc_get_file( $file_class->get_field_path('RecordTemplateFull') ) : null;	
		$component_body.= nc_check_file( $file_class->get_field_path('Settings') ) ? nc_get_file( $file_class->get_field_path('Settings') ) : null;	
	}

    $nc_search_form = "<form method='get' action='" . $SUB_FOLDER . $current_sub['Hidden_URL'] . "'>" . showSearchForm($field_descript, $field_type, $field_search, $field_format) . "<input type='submit' value='" . NETCAT_SEARCH_FIND_IT . "' /></form>";


    if ($admin_mode) {
        $addLink = $admin_url_prefix . "add.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc;
        // full link section
        $fullLink = $admin_url_prefix . "full.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID;
        $fullDateLink = $fullLink . $dateLink;
        // ID объекта в шаблоне
        $f_AdminButtons_id = $f_RowID;
        // Приоритет объекта
        $f_AdminButtons_priority = $f_Priority;
        // ID добавившего пользователя
        $f_AdminButtons_user_add = $f_UserID;
        // ID изменившего пользователя
        $f_AdminButtons_user_change = ($f_LastUserID ? $f_LastUserID : "");
        // копировать объект
        $f_AdminButtons_copy = $ADMIN_PATH . "objects/copy_message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&classID=" . $classID . "&message=" . $f_RowID;
        // изменить
        $f_AdminButtons_change = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID . "&curPos=" . $curPos;
        $editLink = $f_AdminButtons_change;
        // удалить
        $f_AdminButtons_delete = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID . "&delete=1&curPos=" . $curPos;
        $deleteLink = $f_AdminButtons_delete;
        $dropLink = $deleteLink . "&posting=1";
        // включить-выключить
        $f_AdminButtons_check = $any_url_prefix . $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&classID=" . $classID . "&message=" . $f_RowID . "&checked=" . ($f_Checked ? 1 : 2) . "&posting=1&curPos=" . $curPos . ($admin_mode ? "&admin_mode=1" : "");
        $checkedLink = $f_AdminButtons_check;
        $f_AdminButtons = "";
        if (!$user_table_mode) {
            if ($system_env['AdminButtonsType']) {
                eval("\$f_AdminButtons = \"" . $system_env['AdminButtons'] . "\";");
            } else {
                $f_AdminButtons_buttons = "
                        <div class='nc_idtab_buttons_id'>".$f_RowID."</div>
                        <div class='nc_idtab_buttons_obj_".($f_Checked ? 'on' : 'off' )."'>
                            <a onClick='parent.nc_action_message(this.href); return false;' href='".$f_AdminButtons_check."'>
                            ".($f_Checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF )."
                            </a>
                        </div>

                        <div class='nc_idtab_buttons_copy' style='top: 6px;'>
                            <a href='#' onclick=\"window.open('" . $f_AdminButtons_copy . "', 'nc_popup_test1', 'width=800,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">
                                <div class='icons icon_copy' title ='" . NETCAT_MODERATION_COPY_OBJECT . "'></div>
                            </a>
                        </div>

                        <div class='nc_idtab_buttons_change' style='top: 6px;'>
                            <a target='_blank' href='" . $f_AdminButtons_change . "'>
                                <div class='icons icon_pencil' title='" . NETCAT_MODERATION_CHANGE . "'></div>
                            </a>
                        </div>

                        <div class='nc_idtab_buttons_delete' style='top: 6px;'>
                            <a onClick='parent.nc_action_message(this.href); return false;' href='" . $f_AdminButtons_delete . "'>
                                <div class='icons icon_delete' title='" . NETCAT_MODERATION_DELETE . "'></div>
                            </a>
                        </div> ";

                    $f_AdminButtons = "<div class='nc_idtab" . ($f_Checked ? "" : " nc_idtab_disabled") . "'>";
                    #$f_AdminButtons.= "<div class='nc_idtab_handler' ".(nc_Core::get_object()->inside_admin ? '' : "style='display: none;' ")."id='message" . $classID . "-" . $f_RowID . "_handler'></div>";
                    $f_AdminButtons.= "<div class='nc_idtab_buttons'>" . $f_AdminButtons_buttons . "</div>";
                    $f_AdminButtons.= "</div>";
                    $f_AdminButtons.= "<div class='ncf_row nc_clear'></div>";
            }
        }
    } else {
        $msgLink = $f_Keyword ? $f_Keyword : $cc_keyword . "_" . $f_RowID;
        $fullLink = $subLink . $msgLink . ".html";
        $fullRSSLink = $cc_env['AllowRSS'] ? $subLink . $msgLink . ".rss" : "";
        $fullDateLink = $subLink . $dateLink . $msgLink . ".html";
        $editLink = $subLink . "edit_" . $msgLink . ".html"; // ccылка для редактирования
        $deleteLink = $subLink . "delete_" . $msgLink . ".html"; //            удаления
        $dropLink = $subLink . "drop_" . $msgLink . ".html"; //            удаления без подтверждения
        $checkedLink = $subLink . "checked_" . $msgLink . ".html"; //            включения\выключения
        $subscribeMessageLink = $subLink . "subscribe_" . $msgLink . ".html"; // подписка на объект
        // действие с компонентом
        $subscribeLink = $SUB_FOLDER . $current_cc['Hidden_URL'] . "subscribe_" . $current_cc['EnglishName'] . ".html";
        $searchLink = $SUB_FOLDER . $current_cc['Hidden_URL'] . "search_" . $current_cc['EnglishName'] . ".html";
        $addLink = $SUB_FOLDER . $current_cc['Hidden_URL'] . "add_" . $current_cc['EnglishName'] . ".html";
    }


    /* Следующий и предыдущий объект */
    $nc_next_object = "";
    $nc_prev_object = "";

    if (nc_strpos($component_body, '$nc_next_object') !== false || nc_strpos($component_body, '$nc_prev_object') !== false) {

        // сортировка и запрос
        $sort_by = $query_order ? $query_order : $cc_env['SortBy'] ? $cc_env['SortBy'] : "a." . ($user_table_mode ? "`" . $AUTHORIZE_BY . "`" : "`Priority` DESC") . ", a.`LastUpdated` DESC";
        $nc_res = $db->get_results("SELECT a.`Message_ID`, a.`Keyword`
                               FROM `" . ($user_table_mode ? "User" : "Message" . $classID) . "` AS a
                               WHERE 1=1 " . $cond_where . $cond_mod . "
                               ORDER BY " . $sort_by, ARRAY_A);
        // предыдущий и следующий объект находятся рядом с текущим
        for ($i = 0; $i < $db->num_rows; $i++) {
            if ($nc_res[$i]['Message_ID'] == $message) {
                if ($i > 0) {
                    $nc_prev_object = $admin_mode ? $admin_url_prefix . "full.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $nc_res[$i - 1]['Message_ID'] : $cc_env['Hidden_URL'] . ( $nc_res[$i - 1]['Keyword'] ? $nc_res[$i - 1]['Keyword'] : $cc_env['EnglishName'] . "_" . $nc_res[$i - 1]['Message_ID']) . ".html";
                }
                if ($i < $db->num_rows - 1) {
                    $nc_next_object = $admin_mode ? $admin_url_prefix . "full.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $nc_res[$i + 1]['Message_ID'] : $cc_env['Hidden_URL'] . ( $nc_res[$i + 1]['Keyword'] ? $nc_res[$i + 1]['Keyword'] : $cc_env['EnglishName'] . "_" . $nc_res[$i + 1]['Message_ID']) . ".html";
                }
                break;
            }
        }
        unset($nc_res);
    }
	
	// add form from the AddTemplate
    if (nc_strpos($component_body, '$addForm') !== false) {
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('AddTemplate');
            $nc_field_path = $file_class->get_field_path('AddTemplate');
            $addForm = '';
            // check and include component part
			try {
				if ( nc_check_php_file($nc_field_path) ) {
					ob_start();
					include $nc_field_path;
					$addForm = ob_get_clean();
				}
			}
			catch (Exception $e) {
				if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
					// error message
					echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDFORM);
				}
			}
            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval("\$addForm = \"" . $cc_env["AddTemplate"] . "\";");
        }
    } 
    
    // search form from the FullSearchTemplate
    if (nc_strpos($component_body, '$searchForm') !== false) {
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('FullSearchTemplate');
            $nc_field_path = $file_class->get_field_path('FullSearchTemplate');
            $searchForm = '';
            // check and include component part
            try {
            	if ( nc_check_php_file($nc_field_path) ) {
            		ob_start();
					include $nc_field_path;
					$searchForm = ob_get_clean();
            	}
            }
            catch (Exception $e) {
            	if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
            		// error message
            		echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_QSEARCH);
            	}
            }
            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval("\$searchForm = \"" . $cc_env["FullSearchTemplate"] . "\";");
        }
    }
    
    // exterminate
    unset($component_body);

// save changed $current_cc["RecordTemplateFull"]
// this array is updated in $template_header
    $cache_record_template_full = $cc_env['RecordTemplateFull'];

    if ($admin_mode && !$GLOBALS['isNaked']) {
        echo "<div id='nc_admin_mode_content'>";
    }

    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] != 2 && !$cc_env['File_Mode']) {
        ob_start("nc_full_message_parse_buffer");
        eval("echo \"" . $cache_record_template_full . "\";");    
        ob_end_flush();
    } else {
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('RecordTemplateFull');
            $nc_field_path = $file_class->get_field_path('RecordTemplateFull');
			// check and include component part
            try {
            	if ( nc_check_php_file($nc_field_path) ) {
            		include $nc_field_path;
            	}
            }
            catch (Exception $e) {
            	if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
            		// error message
            		echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_OBJECTVIEW);
            	}
            }
            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval("echo \"" . $cc_env['RecordTemplateFull'] . "\";");
        }
    }

    if ($admin_mode && !$GLOBALS['isNaked']) {
        echo "</div>";
    }
} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';
    
    if (!$templatePreview) {
		if ($nc_core->inside_admin && $UI_CONFIG) {
			// для админки
			$UI_CONFIG->locationHash = "object.view(" . $cc . "," . $message . ")";
			// edit button
			$UI_CONFIG->actionButtons = array();
			$UI_CONFIG->actionButtons[] = array(
					"id" => "editObject",
					"caption" => NETCAT_MODERATION_CHANGE,
					"action" => "parent.nc_form('{$SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}message.php?inside_admin=1&cc=$cc&message=$message')",
					"align" => "left"
			);
			/*$UI_CONFIG->actionButtons[] = array(
					"id" => "deleteObject",
					"caption" => NETCAT_MODERATION_DELETE,
					"style" => "delete",
					"action" => "urlDispatcher.load('object.delete(" . $cc . ", " . $message . ")')"
			);*/
				
			$template_header = nc_insert_in_head($template_header, $UI_CONFIG->to_json(), true);
		}
        echo $template_header;
        echo $nc_result_msg;
        echo $template_footer;
    } else {
        eval('?>' . $template_header);
        echo $nc_result_msg;
        eval('?>' . $template_footer);
    }
} else {
    eval("echo \"" . $template_header . "\";");
    echo $nc_result_msg;
    eval("echo \"" . $template_footer . "\";");
}

function nc_full_message_parse_buffer($buffer) {
    global $REQUEST_URI, $classID, $message, $cache_vars;
    global $MODULE_VARS, $nc_cache_full, $current_sub, $current_cc, $cache_for_user;

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_full)) {
        try {
            $bytes = $nc_cache_full->add($classID, $message, $REQUEST_URI . $cache_for_user, $buffer, $cache_vars);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, sub[" . $current_sub['Subdivision_ID'] . "], cc[" . $current_cc['Sub_Class_ID'] . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_full->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_full->errorMessage($e);
        }
    }

    return $buffer;
}

?>

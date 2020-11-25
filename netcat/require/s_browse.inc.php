<?php

/* $Id: s_browse.inc.php 8423 2012-11-14 12:39:47Z lemonade $ */

function s_browse_path($browse_template) {
    global $sub_level_count;
    return s_browse_path_range(-1, $sub_level_count, $browse_template);
}

/**
 * Путь к объекту в виде строки
 * @var integer Какое количество пунктов отсчитать от конца пути.
 *       -1 - вывести в т.ч. текущий путь
 *       0  - до последнего пункта
 *       1  - до предпоследнего etc
 * @var integer ОТ какого пункта от конца начать путь:
 *       $sub_level_count - от корня
 *       $sub_level_count-1 - от первого уровня etc.
 * @var array
 * @var integer Как выводить путь
 *       0 - (по умолчанию) по порядку
 *       1 - в обратном порядке
 * @var integer Выводить ли имя компонента в разделе
 *       0 - (по умолчанию) только если компонент неосновной (не первый) в разделе
 *       1 - выводить всегда
 *       2 - никогда не выводить
 * @return string
 */
function s_browse_path_range($from, $to, $browse_template, $reverse = 0, $show = 0) {
    global $REQUEST_URI, $f_title;
    global $admin_mode, $admin_url_prefix;
    global $current_catalogue, $current_sub, $current_cc, $cc_array;
    global $parent_sub_tree, $sub_level_count;
    global $titleTemplate, $action, $message, $classID;
    global $user_table_mode, $db, $SUB_FOLDER;

    $result = '';

    if ($to > $sub_level_count) {
        $to = $sub_level_count;
    }

    if ($from < -1) {
        $from = -1;
    }

    if (isset($browse_template['prefix'])) {
        eval("\$result = \"".$browse_template['prefix']."\";");
    }

    $result_array_name = array();
    $result_array_url = array();

    if ($show == 0 && $current_catalogue['Title_Sub_ID'] == $current_sub['Subdivision_ID']) {
        $from++;
    }

    for ($i = $to; $i > $from; $i--) {
        $result_array_name[] = $parent_sub_tree[$i]['Subdivision_Name'];
        if ($admin_mode) {
            $result_array_url[] = $admin_url_prefix."?catalogue=".$parent_sub_tree[$i]['Catalogue_ID']
                    .($parent_sub_tree[$i]["Subdivision_ID"] ? "&amp;sub=".$parent_sub_tree[$i]["Subdivision_ID"] : "");
        } else {
            if (isset($parent_sub_tree[$i]["ExternalURL"]) && ($ext_url = $parent_sub_tree[$i]["ExternalURL"])) {
                $result_array_url[] = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $parent_sub_tree[$i]['Hidden_URL'].$ext_url;
            } else {
                $result_array_url[] = $parent_sub_tree[$i]['Hidden_URL'];
            }
        }
    }

    switch ($show) {
        case 0:
            if ($current_cc['Sub_Class_ID'] != $cc_array[0] && $current_cc['Checked']) {
                $result_array_name[] = $current_cc['Sub_Class_Name'];
                if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                    $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $current_cc[$i]['Hidden_URL'].$ext_url).".html";
                } else {
                    $result_array_url[] = $current_sub['Hidden_URL'].$current_cc['EnglishName'].".html";
                }
            }
            break;
        case 1:
            if ($current_cc['Checked']) {
                $result_array_name[] = $current_cc['Sub_Class_Name'];
                if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                    $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $current_cc[$i]['Hidden_URL'].$ext_url).".html";
                } else {
                    $result_array_url[] = $current_sub['Hidden_URL'].$current_cc['EnglishName'].".html";
                }
            }
            break;
    }

    if ($titleTemplate && $action == 'full') {
        $result_array_name[] = $f_title;
        $result_array_url[] = $_SERVER['REQUEST_URI'];
    }

    if (!$reverse) {
        $result_array_name = array_reverse($result_array_name);
        $result_array_url = array_reverse($result_array_url);
    }

    for ($j = -1, $i = count($result_array_name) - 1; $i > -1; $i--) {

        if ($reverse) {
            $j++;
        } else {
            $j = $i;
        }

        if (isset($parent_sub_tree[$j]["Subdivision_ID"]) && $current_sub["Subdivision_ID"] == $parent_sub_tree[$j]["Subdivision_ID"]) {
            if ($browse_template['active_link'] && ($result_array_url[$j] == $_SERVER['REQUEST_URI'])) {
                eval("\$result.= \"".$browse_template['active_link']."\";");
            } else {
                eval("\$result.= \"".$browse_template['active']."\";");
            }
        } else {
            eval("\$result.= \"".$browse_template['unactive']."\";");
        }

        $result = str_replace("%NAME", $result_array_name[$i], $result);
        $result = str_replace("%URL", $SUB_FOLDER.$result_array_url[$i], $result);

        if (0 < $i) {
            eval("\$result .= \"".$browse_template['divider']."\";");
        }
    }

    if (isset($browse_template['suffix'])) {
        eval("\$result.= \"".$browse_template['suffix']."\";");
    }

    return $result;
}

function nc_browse_path($browse_template) {
    global $sub_level_count;
    return nc_browse_path_range(-1, $sub_level_count, $browse_template);
}

function nc_browse_path_range($from, $to, $browse_template, $reverse = 0, $show = 0) {
    global $REQUEST_URI, $f_title;
    global $admin_mode, $admin_url_prefix;
    global $current_catalogue, $current_sub, $current_cc, $cc_array;
    global $parent_sub_tree, $sub_level_count;
    global $titleTemplate, $action, $message, $classID;
    global $user_table_mode, $db, $SUB_FOLDER;

    $result = '';

    if ($to > $sub_level_count) {
        $to = $sub_level_count;
    }

    if ($from < -1) {
        $from = -1;
    }

    $result = $browse_template['prefix'];

    $result_array_name = array();
    $result_array_url = array();

    if ($show == 0 && $current_catalogue['Title_Sub_ID'] == $current_sub['Subdivision_ID']) {
        $from++;
    }

    for ($i = $to; $i > $from; $i--) {
        $result_array_name[] = $parent_sub_tree[$i]['Subdivision_Name'];
        if ($admin_mode) {
            $result_array_url[] = $admin_url_prefix."?catalogue=".$parent_sub_tree[$i]['Catalogue_ID']
                    .($parent_sub_tree[$i]["Subdivision_ID"] ? "&amp;sub=".$parent_sub_tree[$i]["Subdivision_ID"] : "");
        } else {
            if (isset($parent_sub_tree[$i]["ExternalURL"]) && ($ext_url = $parent_sub_tree[$i]["ExternalURL"])) {
                $result_array_url[] = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $parent_sub_tree[$i]['Hidden_URL'].$ext_url;
            } else {
                $result_array_url[] = $parent_sub_tree[$i]['Hidden_URL'];
            }
        }
    }

    switch ($show) {
        case 0:
            if ($current_cc['Sub_Class_ID'] != $cc_array[0] && $current_cc['Checked']) {
                $result_array_name[] = $current_cc['Sub_Class_Name'];
                if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                    $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $current_cc[$i]['Hidden_URL'].$ext_url).".html";
                } else {
                    $result_array_url[] = $current_sub['Hidden_URL'].$current_cc['EnglishName'].".html";
                }
            }
            break;
        case 1:
            if ($current_cc['Checked']) {
                $result_array_name[] = $current_cc['Sub_Class_Name'];
                if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                    $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $current_cc[$i]['Hidden_URL'].$ext_url).".html";
                } else {
                    $result_array_url[] = $current_sub['Hidden_URL'].$current_cc['EnglishName'].".html";
                }
            }
            break;
    }

    if ($titleTemplate && $action == 'full') {
        $result_array_name[] = $f_title;
        $result_array_url[] = $_SERVER['REQUEST_URI'];
    }

    if (!$reverse) {
        $result_array_name = array_reverse($result_array_name);
        $result_array_url = array_reverse($result_array_url);
    }

    $array_result = array();
    for ($j = -1, $i = count($result_array_name) - 1; $i > -1; $i--) {

        if ($reverse) {
            $j++;
        } else {
            $j = $i;
        }

        if (isset($parent_sub_tree[$j]["Subdivision_ID"]) && $current_sub["Subdivision_ID"] == $parent_sub_tree[$j]["Subdivision_ID"]) {
            if ($browse_template['active_link'] && ($result_array_url[$j] == $_SERVER['REQUEST_URI'])) {
                $array_result[$j] = $browse_template['active_link'];
            } else {
                $array_result[$j] = $browse_template['active'];
            }
        } else {
            $array_result[$j] = $browse_template['unactive'];
        }

        $array_result[$j] = str_replace("%NAME", $result_array_name[$i], $array_result[$j]);
        $array_result[$j] = str_replace("%URL", $SUB_FOLDER.$result_array_url[$i], $array_result[$j]);

    }

    $result .= join($browse_template['divider'], $array_result);

    if (isset($browse_template['suffix'])) {
        $result .= $browse_template['suffix'];
    }
    return $result;
}

function s_browse_catalogue($browse_template) {
    global $db, $current_catalogue;
    global $DOMAIN_NAME, $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $system_table_fields, $HTTP_FILES_PATH, $SUB_FOLDER;

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        try {
            $nc_cache_browse = nc_cache_browse::getObject();
            // check cached data
            $cached_data = $nc_cache_browse->read($current_catalogue, $browse_template, $REQUEST_URI, $current_catalogue['Cache_Lifetime']);
            if ($cached_data != -1) {
                // debug info
                $cache_debug_info = "Readed, catalogue[".$current_catalogue['Catalogue_ID']."], Access_ID[".$current_catalogue['Cache_Access_ID']."], Lifetime[".$current_catalogue['Cache_Lifetime']."], bytes[".strlen($cached_data)."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    if ($browse_template['sortby']) {
        $sort_by = $browse_template['sortby'];
    } else {
        $sort_by = "`Priority`";
    }

    $file_fields = array();

    for ($i = 0; $i < count($system_table_fields['Catalogue']); $i++) {
        // file field
        if ($system_table_fields['Catalogue'][$i]['type'] == 6) {
            $field_string.= "IF(`".$system_table_fields['Catalogue'][$i]['name']."` <> '', CONCAT('".$HTTP_FILES_PATH.$system_table_fields['Catalogue'][$i]['id']."_', `Catalogue_ID`, RIGHT(SUBSTRING_INDEX(`".$system_table_fields['Catalogue'][$i]['name']."`, ':', 1), LOCATE('.', REVERSE(SUBSTRING_INDEX(`".$system_table_fields['Catalogue'][$i]['name']."`, ':', 1))))), '') AS ".$system_table_fields['Catalogue'][$i]['name'].", ";
            $file_fields[$system_table_fields['Catalogue'][$i]['id']] = $system_table_fields['Catalogue'][$i]['name'];
        } else {
            $field_string.= "`".$system_table_fields['Catalogue'][$i]['name']."`, ";
        }
    }

    $data = $db->get_results("SELECT ".$field_string."`Catalogue_ID`, `Catalogue_Name`, `Domain`
    FROM `Catalogue` WHERE `Checked` = 1 ORDER BY ".$sort_by, ARRAY_A);
    $data_count = sizeof($data);

    if (!$data_count) return;

    eval("\$result = \"".$browse_template['prefix']."\";");

    for ($i = 0; $i < $data_count; $i++) {
        $nav_name = $data[$i]["Catalogue_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix."?catalogue=".$data[$i]["Catalogue_ID"];
        } else {
            $nav_url = "http://".$data[$i]["Domain"].(!strchr($data[$i]["Domain"], ".") && $data[$i]["Domain"] ? ".".$DOMAIN_NAME : (!$data[$i]["Domain"] ? $DOMAIN_NAME : "") );
        }

        foreach ($file_fields AS $file_field_id => $file_field_name) {
            if ($data[$i][$file_field_name]) {
                $file_name = $db->get_var("SELECT `Virt_Name` FROM `Filetable`
          WHERE `File_Path` = '/c/'
          AND `Message_ID` = '".$data[$i]['Catalogue_ID']."'
          AND `Field_ID` = '".(int) $file_field_id."'");
                if ($file_name) {
                    $data[$i][$file_field_name] = $SUB_FOLDER.$HTTP_FILES_PATH."c/h_".$file_name;
                    $data[$i][$file_field_name."_url"] = $SUB_FOLDER.$HTTP_FILES_PATH."c/".$file_name;
                } else {
                    $data[$i][$file_field_name."_url"] = $data[$i][$file_field_name];
                }
            }
        }

        if ($data[$i]["Catalogue_ID"] == $current_catalogue["Catalogue_ID"]) {
            if ($REQUEST_URI == "/" && $browse_template['active_link']) {
                eval("\$result.= \"".$browse_template['active_link']."\";");
            } else {
                eval("\$result.= \"".$browse_template['active']."\";");
            }
        } else {
            eval("\$result.= \"".$browse_template['unactive']."\";");
        }

        if ($i <> ($data_count - 1))
                eval("\$result.= \"".$browse_template['divider']."\";");

        $result = str_replace("%NAME", $nav_name, $result);
        if (!$admin_mode) $nav_url = $SUB_FOLDER.$nav_url;
        $result = str_replace("%URL", $nav_url, $result);
        $result = str_replace("%CATALOGUE", $data[$i]["Catalogue_ID"], $result);
        $result = str_replace("%COUNTER", $i, $result);

        for ($j = 0; $j < count($system_table_fields['Catalogue']); $j++) {
            $result = str_replace("%".$system_table_fields['Catalogue'][$j]['name'], $data[$i][$system_table_fields['Catalogue'][$j]['name']], $result);
        }
    }
    eval("\$result.= \"".$browse_template['suffix']."\";");

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_catalogue, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, catalogue[".$current_catalogue['Catalogue_ID']."], Access_ID[".$current_catalogue['Cache_Access_ID']."], Lifetime[".$current_catalogue['Cache_Lifetime']."], bytes[".$bytes."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function nc_browse_catalogue($browse_template) {
    global $db, $current_catalogue;
    global $DOMAIN_NAME, $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $system_table_fields, $HTTP_FILES_PATH, $SUB_FOLDER;

    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        try {
            $nc_cache_browse = nc_cache_browse::getObject();
            $cached_data = $nc_cache_browse->read($current_catalogue, $browse_template, $REQUEST_URI, $current_catalogue['Cache_Lifetime']);

            if ($cached_data != -1) {
                $cache_debug_info = "Readed, catalogue[".$current_catalogue['Catalogue_ID']."], Access_ID[".$current_catalogue['Cache_Access_ID']."], Lifetime[".$current_catalogue['Cache_Lifetime']."], bytes[".strlen($cached_data)."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                return $cached_data;
            }
        } catch (Exception $e) {
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = $browse_template['sortby'] ? $browse_template['sortby'] : "`Priority`";

    $file_fields = array();

    for ($i = 0; $i < count($system_table_fields['Catalogue']); $i++) {
        if ($system_table_fields['Catalogue'][$i]['type'] == 6) {
            $field_string.= "IF(`".$system_table_fields['Catalogue'][$i]['name']."` <> '', CONCAT('".$HTTP_FILES_PATH.$system_table_fields['Catalogue'][$i]['id']."_', `Catalogue_ID`, RIGHT(SUBSTRING_INDEX(`".$system_table_fields['Catalogue'][$i]['name']."`, ':', 1), LOCATE('.', REVERSE(SUBSTRING_INDEX(`".$system_table_fields['Catalogue'][$i]['name']."`, ':', 1))))), '') AS ".$system_table_fields['Catalogue'][$i]['name'].", ";
            $file_fields[$system_table_fields['Catalogue'][$i]['id']] = $system_table_fields['Catalogue'][$i]['name'];
        } else {
            $field_string.= "`".$system_table_fields['Catalogue'][$i]['name']."`, ";
        }
    }

    $data = $db->get_results("SELECT ".$field_string."`Catalogue_ID`, `Catalogue_Name`, `Domain`
    FROM `Catalogue` WHERE `Checked` = 1 ORDER BY ".$sort_by, ARRAY_A);
    $data_count = sizeof($data);

    if (!$data_count) return;

    $result = $browse_template['prefix'];
    $array_result = array();

    for ($i = 0; $i < $data_count; $i++) {
        $nav_name = $data[$i]["Catalogue_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix."?catalogue=".$data[$i]["Catalogue_ID"];
        } else {
            $nav_url = "http://".$data[$i]["Domain"].(!strchr($data[$i]["Domain"], ".") && $data[$i]["Domain"] ? ".".$DOMAIN_NAME : (!$data[$i]["Domain"] ? $DOMAIN_NAME : "") );
        }

        foreach ($file_fields AS $file_field_id => $file_field_name) {
            if ($data[$i][$file_field_name]) {
                $file_name = $db->get_var("SELECT `Virt_Name` FROM `Filetable`
          WHERE `File_Path` = '/c/'
          AND `Message_ID` = '".$data[$i]['Catalogue_ID']."'
          AND `Field_ID` = '".(int) $file_field_id."'");
                if ($file_name) {
                    $data[$i][$file_field_name] = $SUB_FOLDER.$HTTP_FILES_PATH."c/h_".$file_name;
                    $data[$i][$file_field_name."_url"] = $SUB_FOLDER.$HTTP_FILES_PATH."c/".$file_name;
                } else {
                    $data[$i][$file_field_name."_url"] = $data[$i][$file_field_name];
                }
            }
        }

        if ($data[$i]["Catalogue_ID"] == $current_catalogue["Catalogue_ID"]) {
            if ($REQUEST_URI == "/" && $browse_template['active_link']) {
                $array_result[$i] = $browse_template['active_link'];
            } else {
                $array_result[$i] = $browse_template['active'];
            }
        } else {
            $array_result[$i] = $browse_template['unactive'];
        }

        $array_result[$i] = str_replace("%NAME", $nav_name, $array_result[$i]);
        if (!$admin_mode) $nav_url = $SUB_FOLDER.$nav_url;
        $array_result[$i] = str_replace("%URL", $nav_url, $array_result[$i]);
        $array_result[$i] = str_replace("%CATALOGUE", $data[$i]["Catalogue_ID"], $array_result[$i]);
        $array_result[$i] = str_replace("%COUNTER", $i, $array_result[$i]);

        for ($j = 0; $j < count($system_table_fields['Catalogue']); $j++) {
            $array_result[$i] = str_replace("%".$system_table_fields['Catalogue'][$j]['name'], $data[$i][$system_table_fields['Catalogue'][$j]['name']], $array_result[$i]);
        }
    }

    $result .= join($browse_template['divider'], $array_result);
    $result .= $browse_template['suffix'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_catalogue, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, catalogue[".$current_catalogue['Catalogue_ID']."], Access_ID[".$current_catalogue['Cache_Access_ID']."], Lifetime[".$current_catalogue['Cache_Lifetime']."], bytes[".$bytes."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

/**
 * Вывод подразделов для меню
 *
 * @param int $browse_parent_sub номер раздела, подразделы которого попадут в меню
 * @param array $browse_template - массив-шаблон
 * @param int $ignore_check - игнорировать только включенные разделы, по умолчанию - 0
 * @param string $where_cond - дополнительное условие в запрос на выбор разделов
 *
 * @return string меню разделов
 */
function s_browse_sub($browse_parent_sub, $browse_template, $ignore_check = 0, $where_cond = "") {
    global $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $current_sub;
    global $parent_sub_tree, $sub_level_count, $system_table_fields;
    global $db, $nc_core, $HTTP_FILES_PATH, $SUB_FOLDER;


    // this happens when non-existant sub requested in admin mode
    if (!$current_sub["Subdivision_ID"]) return "";

    $query_string = $REQUEST_URI.$ignore_check.$where_cond;

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_sub['Cache_Access_ID'] == 1 && !isset($browse_template['nocache'])) {
        try {
            $nc_cache_browse = nc_cache_browse::getObject();
            // check cached data
            $cached_data = $nc_cache_browse->read($current_sub, $browse_template, $query_string, $current_sub['Cache_Lifetime'], $browse_parent_sub);
            if ($cached_data != -1) {
                // debug info
                $cache_debug_info = "Readed, catalogue[".$current_sub['Catalogue_ID']."], sub[".$current_sub['Subdivision_ID']."], Access_ID[".$current_sub['Cache_Access_ID']."], Lifetime[".$current_sub['Cache_Lifetime']."], bytes[".strlen($cached_data)."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $field_6_types = array();

    if (isset($browse_template['sortby']) && $browse_template['sortby']) {
        $sort_by = $browse_template['sortby'];
    } else {
        $sort_by = "`Priority`";
    }

    // поля таблицы Subdivision
    $table_fields = $nc_core->get_system_table_fields('Subdivision');
    $field_string = '';
    $count_fields = count($table_fields);

    for ($i = 0; $i < $count_fields; $i++) {
        // file field
        if ($table_fields[$i]['type'] == 6) {
            $field_6_types[$table_fields[$i]['id']] = $table_fields[$i]['name'];
        }
        $field_string.= "`".$table_fields[$i]['name']."`, ";
    }

    $SQL = ("SELECT ".$field_string."
    `Subdivision_ID`,
    `Subdivision_Name`,
    `ExternalURL`,
    `Hidden_URL`,
    `EnglishName`,
    `Catalogue_ID`,
    `Parent_Sub_ID`,
    `Template_ID`,
    `LastUpdated`,
    `Created`,
    `Read_Access_ID`,
    `Write_Access_ID`,
    `Priority`,
    `Checked`,
    `Edit_Access_ID`,
    `Subscribe_Access_ID`,
    `Moderation_ID`,
    `Favorite`,
    `Description`
    FROM `Subdivision`
    WHERE `Parent_Sub_ID` = '".(int) $browse_parent_sub."'
    ".( $ignore_check ? "" : "AND `Checked` = 1" )."
    ".( $where_cond ? " AND ".$where_cond : "")."
    AND `Catalogue_ID` = '".(int) $current_sub["Catalogue_ID"]."'
    ORDER BY ".$db->escape($sort_by));
$data = $db->get_results($SQL, ARRAY_A);

    // кол-во подразделов
    $data_count = sizeof($data);

    // id всех подразелов (для запроса к таблица с файлами)
    $child_subs_id = array();
    for ($i = 0; $i < $data_count; $i++) {
        $child_subs_id[] = $data[$i]['Subdivision_ID'];
    }

    // зарпос к Filetable
    if (!empty($child_subs_id) && !empty($field_6_types)) {
        $file_in_table = $db->get_results("SELECT `Virt_Name`, `File_Path`, `Message_ID`, `Field_ID`
                                  FROM `Filetable`
                                  WHERE `Message_ID` IN (".join(',', $child_subs_id).")", ARRAY_A);
        if (!empty($file_in_table)) {
            foreach ($file_in_table as $v) {
                $filetable[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['File_Path']);
            }
        }
    }


    if (!$data_count) return;

    // prefix
    eval("\$result = \"".$browse_template['prefix']."\";");

    $current_page_url = urldecode(strtok($REQUEST_URI, '?'));
    $current_sub_url = substr($current_page_url, 0, strrpos($current_page_url, "/") + 1);

    // Проход по всем подразделам
    for ($i = 0; $i < $data_count; $i++) {
        // поле тип файл обрабатывается отдельно
        if (!empty($field_6_types)) {
            foreach ($field_6_types as $field_id => $field_name) {
                if ($data[$i][$field_name]) { // если есть файл
                    $file_data = explode(':', $data[$i][$field_name]);
                    $data[$i][$field_name."_name"] = $file_data[0]; // оригинальное имя
                    $data[$i][$field_name."_size"] = $file_data[1]; // размер
                    $data[$i][$field_name."_type"] = $file_data[2]; // тип
                    $ext = substr($file_data[0], strrpos($file_data[0], "."));  // расширение
                    // запись в таблице Filetable
                    $row = $filetable[$data[$i]['Subdivision_ID']][$field_id];
                    if ($row) { // Proteced FileSystem
                        $data[$i][$field_name] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.ltrim($row[1], '/')."h_".$row[0];
                        $data[$i][$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.ltrim($row[1], '/').$row[0];
                    } else {
                        if ($file_data[3]) { // Original FileSystem
                            $data[$i][$field_name] = $data[$i][$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.$file_data[3];
                        } else { // Simple FileSysytem
                            $data[$i][$field_name] = $data[$i][$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.$field_id."_".$data[$i]["Subdivision_ID"].$ext;
                        }
                    }
                }
            }
        }

        $is_active_sub = 0;

        $nav_name = nc_quote_convert($data[$i]["Subdivision_Name"]);

        if ($admin_mode) {
            $nav_url = $admin_url_prefix."?catalogue=".$current_sub["Catalogue_ID"]."&amp;sub=".$data[$i]["Subdivision_ID"];
        } else {
            if ($ext_url = $data[$i]["ExternalURL"]) {
                $nav_url = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $data[$i]["Hidden_URL"].$ext_url;
            } else {
                $nav_url = $data[$i]["Hidden_URL"];
            }
        }


        for ($j = 0; $j < $sub_level_count; $j++) {
            if ($parent_sub_tree[$j]["Subdivision_ID"] == $data[$i]["Subdivision_ID"])
                    $is_active_sub = 1;
        }

        if ($SUB_FOLDER.$data[$i]['Hidden_URL'] == $current_page_url || $SUB_FOLDER.$data[$i]['ExternalURL'] == urldecode(strtok($REQUEST_URI, '?'))) {
            $thisActive = $browse_template['active_link'] ? $browse_template['active_link'] : $browse_template['active'];
        } elseif ($is_active_sub ||
                ($SUB_FOLDER.$data[$i]['ExternalURL'] == $current_sub_url)) {
            $thisActive = $browse_template['active'];
        } else {
            $thisActive = $browse_template['unactive'];
        }

        eval("\$result.= \"".$thisActive."\";");
        
        $result = str_replace("%NAME", $nav_name, $result);
        if (!$admin_mode) $nav_url = $SUB_FOLDER.$nav_url;
        $result = str_replace("%URL", $nav_url, $result);
        $result = str_replace("%PARENT_SUB", $browse_parent_sub, $result);
        $result = str_replace("%KEYWORD", $data[$i]['EnglishName'], $result);
        $result = str_replace("%SUB", $data[$i]["Subdivision_ID"], $result);
        $result = str_replace("%COUNTER", $i, $result);

        for ($j = 0; $j < count($system_table_fields['Subdivision']); $j++) {
            $result = str_replace("%".$system_table_fields['Subdivision'][$j]['name'], nc_quote_convert($data[$i][$system_table_fields['Subdivision'][$j]['name']]), $result);
        }

        if ($i <> ($data_count - 1))
                eval("\$result .= \"".$browse_template['divider']."\";");
    }
    eval("\$result.= \"".$browse_template['suffix']."\";");

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_sub['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && !isset($browse_template['nocache'])) {
        try {
            $bytes = $nc_cache_browse->add($current_sub, $browse_template, $query_string, $result, $browse_parent_sub);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, catalogue[".$current_sub['Catalogue_ID']."], sub[".$current_sub['Subdivision_ID']."], Access_ID[".$current_sub['Cache_Access_ID']."], Lifetime[".$current_sub['Cache_Lifetime']."], bytes[".$bytes."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function nc_browse_sub($browse_parent_sub, $browse_template, $ignore_check = 0, $where_cond = "", $level = 0) {
    global $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $current_sub;
    global $parent_sub_tree, $sub_level_count, $system_table_fields;
    global $db, $nc_core, $HTTP_FILES_PATH, $SUB_FOLDER;

    $all_browse_template = $browse_template;
    $browse_template = $browse_template[$level];

    if (!is_array($browse_template)) {
        $browse_template = $all_browse_template;
    }

    if (!is_array($browse_template)) return "";
    if (!$current_sub["Subdivision_ID"]) return "";

    $query_string = $REQUEST_URI.$ignore_check.$where_cond;

    if (nc_module_check_by_keyword("cache") && $current_sub['Cache_Access_ID'] == 1 && !isset($browse_template['nocache'])) {
        try {
            $nc_cache_browse = nc_cache_browse::getObject();
            // check cached data
            $cached_data = $nc_cache_browse->read($current_sub, $browse_template, $query_string, $current_sub['Cache_Lifetime'], $browse_parent_sub);
            if ($cached_data != -1) {
                // debug info
                $cache_debug_info = "Readed, catalogue[".$current_sub['Catalogue_ID']."], sub[".$current_sub['Subdivision_ID']."], Access_ID[".$current_sub['Cache_Access_ID']."], Lifetime[".$current_sub['Cache_Lifetime']."], bytes[".strlen($cached_data)."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache 
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $field_6_types = array();

    if (isset($browse_template['sortby']) && $browse_template['sortby']) {
        $sort_by = $browse_template['sortby'];
    } else {
        $sort_by = "`Priority`";
    }

    // поля таблицы Subdivision
    $table_fields = $nc_core->get_system_table_fields('Subdivision');
    $field_string = '';
    $count_fields = count($table_fields);

    for ($i = 0; $i < $count_fields; $i++) {
        if ($table_fields[$i]['type'] == 6) {
            $field_6_types[$table_fields[$i]['id']] = $table_fields[$i]['name'];
        }
        $field_string.= "`".$table_fields[$i]['name']."`, ";
    }
    
    static $cache = array();

    if (!is_array($cache[md5($query_string)])) {
        $cache[md5($query_string)] = array();
      
        $SQL = "SELECT ".$field_string."
                    `Subdivision_ID`,
                    `Subdivision_Name`,
                    `ExternalURL`,
                    `Hidden_URL`,
                    `EnglishName`,
                    `Catalogue_ID`,
                    `Parent_Sub_ID`,
                    `Template_ID`,
                    `LastUpdated`,
                    `Created`,
                    `Read_Access_ID`,
                    `Write_Access_ID`,
                    `Priority`,
                    `Checked`,
                    `Edit_Access_ID`,
                    `Subscribe_Access_ID`,
                    `Moderation_ID`,
                    `Favorite`,
                    `Description`
                 FROM `Subdivision`
                     WHERE 1
                       ".( $ignore_check ? "" : "AND `Checked` = 1" )."
                       ".( $where_cond ? " AND ".$where_cond : "")."
                       AND `Catalogue_ID` = '".(int) $current_sub["Catalogue_ID"]."'
                           ORDER BY ".$db->escape($sort_by);

        $data_res = (array) $db->get_results($SQL, ARRAY_A);
        
        foreach ($data_res as $row) {
            $cache[md5($query_string)][$row['Parent_Sub_ID']][] = $row;
        }
    }
    
    $data = $cache[md5($query_string)][+$browse_parent_sub];
    $data_count = sizeof($data);

    // id всех подразелов (для запроса к таблица с файлами)
    $child_subs_id = array();
    for ($i = 0; $i < $data_count; $i++) {
        $child_subs_id[] = $data[$i]['Subdivision_ID'];
    }

    if (!empty($child_subs_id) && !empty($field_6_types)) {
        $file_in_table = $db->get_results("SELECT `Virt_Name`, `File_Path`, `Message_ID`, `Field_ID`
                                  FROM `Filetable`
                                  WHERE `Message_ID` IN (".join(',', $child_subs_id).")", ARRAY_A);
        if (!empty($file_in_table)) {
            foreach ($file_in_table as $v) {
                $filetable[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['File_Path']);
            }
        }
    }

    if (!$data_count) return;

    $result = $browse_template['prefix'];

    $current_page_url = urldecode(strtok($REQUEST_URI, '?'));
    $current_sub_url = substr($current_page_url, 0, strrpos($current_page_url, "/") + 1);

    // Проход по всем подразделам
    $array_result = array();
    for ($i = 0; $i < $data_count; $i++) {
        // поле тип файл обрабатывается отдельно
        if (!empty($field_6_types)) {
            foreach ($field_6_types as $field_id => $field_name) {
                if ($data[$i][$field_name]) { // если есть файл
                    $file_data = explode(':', $data[$i][$field_name]);
                    $data[$i][$field_name."_name"] = $file_data[0]; // оригинальное имя
                    $data[$i][$field_name."_size"] = $file_data[1]; // размер
                    $data[$i][$field_name."_type"] = $file_data[2]; // тип
                    $ext = substr($file_data[0], strrpos($file_data[0], "."));  // расширение
                    // запись в таблице Filetable
                    $row = $filetable[$data[$i]['Subdivision_ID']][$field_id];
                    if ($row) { // Proteced FileSystem
                        $data[$i][$field_name] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.ltrim($row[1], '/')."h_".$row[0];
                        $data[$i][$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.ltrim($row[1], '/').$row[0];
                    } else {
                        if ($file_data[3]) { // Original FileSystem
                            $data[$i][$field_name] = $data[$i][$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.$file_data[3];
                        } else { // Simple FileSysytem
                            $data[$i][$field_name] = $data[$i][$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.$field_id."_".$data[$i]["Subdivision_ID"].$ext;
                        }
                    }
                }
            }
        }

        $is_active_sub = 0;

        $nav_name = nc_quote_convert($data[$i]["Subdivision_Name"]);

        if ($admin_mode) {
            $nav_url = $admin_url_prefix."?catalogue=".$current_sub["Catalogue_ID"]."&amp;sub=".$data[$i]["Subdivision_ID"];
        } else {
            if ($ext_url = $data[$i]["ExternalURL"]) {
                $nav_url = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/") ? $ext_url : $data[$i]["Hidden_URL"].$ext_url;
            } else {
                $nav_url = $data[$i]["Hidden_URL"];
            }
        }


        for ($j = 0; $j < $sub_level_count; $j++) {
            if ($parent_sub_tree[$j]["Subdivision_ID"] == $data[$i]["Subdivision_ID"])
                    $is_active_sub = 1;
        }

        if ($SUB_FOLDER.$data[$i]['Hidden_URL'] == $current_page_url || $SUB_FOLDER.$data[$i]['ExternalURL'] == urldecode(strtok($REQUEST_URI, '?'))) {
            $thisActive = $browse_template['active_link'] ? $browse_template['active_link'] : $browse_template['active'];
        } elseif ($is_active_sub ||
                ($SUB_FOLDER.$data[$i]['ExternalURL'] == $current_sub_url)) {
            $thisActive = $browse_template['active'];
        } else {
            $thisActive = $browse_template['unactive'];
        }

        $thisActive = str_replace("%NAME", $nav_name, $thisActive);
        if (!$admin_mode) $nav_url = $SUB_FOLDER.$nav_url;
        $thisActive = str_replace("%URL", $nav_url, $thisActive);
        $thisActive = str_replace("%PARENT_SUB", $browse_parent_sub, $thisActive);
        $thisActive = str_replace("%KEYWORD", $data[$i]['EnglishName'], $thisActive);
        $thisActive = str_replace("%SUB", $data[$i]["Subdivision_ID"], $thisActive);
        $thisActive = str_replace("%COUNTER", $i, $thisActive);

        for ($j = 0; $j < count($system_table_fields['Subdivision']); $j++) {
            $thisActive = str_replace("%".$system_table_fields['Subdivision'][$j]['name'], nc_quote_convert($data[$i][$system_table_fields['Subdivision'][$j]['name']]), $thisActive);
        }
        $thisActive = str_replace("%NEXT_LEVEL", nc_browse_sub($data[$i]["Subdivision_ID"], $all_browse_template, $ignore_check, $where_cond, $level + 1), $thisActive);
        $array_result[] = $thisActive;
    }

    $result .= join($browse_template['divider'], $array_result);
    $result .= $browse_template['suffix'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_sub['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && !isset($browse_template['nocache'])) {
        try {
            $bytes = $nc_cache_browse->add($current_sub, $browse_template, $query_string, $result, $browse_parent_sub);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, catalogue[".$current_sub['Catalogue_ID']."], sub[".$current_sub['Subdivision_ID']."], Access_ID[".$current_sub['Cache_Access_ID']."], Lifetime[".$current_sub['Cache_Lifetime']."], bytes[".$bytes."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function s_browse_level($level, $browse_template) {
    global $parent_sub_tree, $sub_level_count;

    $level_id = $sub_level_count - $level;
    if ($level_id < 0 || (!isset($parent_sub_tree[$level_id]["Subdivision_ID"]) && $level))
            return;
    $sub = isset($parent_sub_tree[$level_id]["Subdivision_ID"]) ? $parent_sub_tree[$level_id]["Subdivision_ID"] : 0;

    return s_browse_sub($sub, $browse_template);
}

function nc_browse_level($level, $browse_template) {
    global $parent_sub_tree, $sub_level_count;

    $level_id = $sub_level_count - $level;
    if ($level_id < 0 || (!isset($parent_sub_tree[$level_id]["Subdivision_ID"]) && $level))
            return;
    $sub = isset($parent_sub_tree[$level_id]["Subdivision_ID"]) ? $parent_sub_tree[$level_id]["Subdivision_ID"] : 0;

    return nc_browse_sub($sub, $browse_template);
}

function s_browse_cc($browse_template) {
    global $db;
    global $admin_mode, $admin_url_prefix;
    global $current_cc, $current_sub;
    global $cc_in_sub, $cc_array, $cc_keyword, $use_multi_sub_class;
    global $REQUEST_URI, $SUB_FOLDER;

    // this happens when non-existant sub requested in admin mode
    if (!$current_sub["Subdivision_ID"]) return "";

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        try {
            $nc_cache_browse = nc_cache_browse::getObject();
            // check cached data
            $cached_data = $nc_cache_browse->read($current_cc, $browse_template, $REQUEST_URI, $current_cc['Cache_Lifetime']);
            if ($cached_data != -1) {
                // debug info
                $cache_debug_info = "Readed, catalogue[".$current_cc['Catalogue_ID']."], sub[".$current_cc['Subdivision_ID']."], cc[".$current_cc['Sub_Class_ID']."], Access_ID[".$current_cc['Cache_Access_ID']."], Lifetime[".$current_cc['Cache_Lifetime']."], bytes[".strlen($cached_data)."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    if ($browse_template['sortby']) {
        $sort_by = $browse_template['sortby'];
    } else {
        $sort_by = "`Priority`";
    }
    if (!$admin_mode) $check_cond = " AND `Checked` = 1";

    // cc_in_sub has all templates in sub
    $data = array();

    if ($sort_by == 'Priority' && $GLOBALS['sub'] == $current_sub["Subdivision_ID"]) {
        foreach ((array) $cc_in_sub AS $row) {
            if ($admin_mode || $row["Checked"] == 1) {
                $data[] = $row;
            }
        }
    }

    if (empty($data)) {
        $data = $db->get_results("SELECT `Sub_Class_ID`, `Sub_Class_Name`, `EnglishName` FROM `Sub_Class`
      WHERE `Subdivision_ID` = '".$current_sub["Subdivision_ID"]."'".$check_cond." ORDER BY ".$sort_by, ARRAY_A);
    }
    $data_count = sizeof($data);

    if (!$data_count || $data_count < 2) return;

    eval("\$result.= \"".$browse_template['prefix']."\";");

    for ($i = 0; $i < $data_count; $i++) {
        $nav_name = $data[$i]["Sub_Class_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix."?catalogue=".$current_sub["Catalogue_ID"]."&amp;sub=".$current_sub["Subdivision_ID"]."&amp;cc=".$data[$i]["Sub_Class_ID"];
        } else {
            $nav_url = $i ? $current_sub["Hidden_URL"].$data[$i]["EnglishName"].".html" : $current_sub["Hidden_URL"];
        }

        if ($data[$i]["Sub_Class_ID"] == $current_cc["Sub_Class_ID"] && ($cc_keyword || !$use_multi_sub_class)) {
            if ($browse_template['active_link'] && ($SUB_FOLDER.$current_sub['Hidden_URL'] == urldecode(strtok($REQUEST_URI, '?')) || $SUB_FOLDER.$current_sub['Hidden_URL'].$current_cc['EnglishName'].".html" == urldecode(strtok($REQUEST_URI, '?')) )) {
                $thisActive = $browse_template['active_link'];
            } else {
                $thisActive = $browse_template['active'];
            }
            eval("\$result.= \"".$thisActive."\";");
        } else {
            eval("\$result.= \"".$browse_template['unactive']."\";");
        }

        $result = str_replace("%NAME", $nav_name, $result);
        if (!$admin_mode) $nav_url = $SUB_FOLDER.$nav_url;
        $result = str_replace("%URL", $nav_url, $result);

        if ($i != ($data_count - 1))
                eval("\$result.= \"".$browse_template['divider']."\";");
    }
    eval("\$result.= \"".$browse_template['suffix']."\";");

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_cc, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, catalogue[".$current_cc['Catalogue_ID']."], sub[".$current_cc['Subdivision_ID']."], cc[".$current_cc['Sub_Class_ID']."], Access_ID[".$current_cc['Cache_Access_ID']."], Lifetime[".$current_cc['Cache_Lifetime']."], bytes[".$bytes."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function nc_cond_browse_sub($browse_parent_sub, $browse_template, $where_cond = "") {
    return s_browse_sub($browse_parent_sub, $browse_template, 0, $where_cond);
}

function nc_browse_cc($browse_template) {
    global $db;
    global $admin_mode, $admin_url_prefix;
    global $current_cc, $current_sub;
    global $cc_in_sub, $cc_array, $cc_keyword, $use_multi_sub_class;
    global $REQUEST_URI, $SUB_FOLDER;

    // this happens when non-existant sub requested in admin mode
    if (!$current_sub["Subdivision_ID"]) return "";

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        try {
            $nc_cache_browse = nc_cache_browse::getObject();
            // check cached data
            $cached_data = $nc_cache_browse->read($current_cc, $browse_template, $REQUEST_URI, $current_cc['Cache_Lifetime']);
            if ($cached_data != -1) {
                // debug info
                $cache_debug_info = "Readed, catalogue[".$current_cc['Catalogue_ID']."], sub[".$current_cc['Subdivision_ID']."], cc[".$current_cc['Sub_Class_ID']."], Access_ID[".$current_cc['Cache_Access_ID']."], Lifetime[".$current_cc['Cache_Lifetime']."], bytes[".strlen($cached_data)."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = $browse_template['sortby'] ? $browse_template['sortby'] : "`Priority`";

    // cc_in_sub has all templates in sub
    $data = array();

    if ($sort_by == 'Priority' && $GLOBALS['sub'] == $current_sub["Subdivision_ID"]) {
        foreach ((array) $cc_in_sub AS $row) {
            if ($admin_mode || $row["Checked"] == 1) {
                $data[] = $row;
            }
        }
    }

    if (empty($data)) {
        $data = $db->get_results("SELECT `Sub_Class_ID`, `Sub_Class_Name`, `EnglishName` FROM `Sub_Class`
      WHERE `Subdivision_ID` = '".$current_sub["Subdivision_ID"]."'".$check_cond." ORDER BY ".$sort_by, ARRAY_A);
    }
    $data_count = sizeof($data);

    if (!$data_count || $data_count < 2) return;

    $result = $browse_template['prefix'];
    $array_result = array();

    for ($i = 0; $i < $data_count; $i++) {
        $nav_name = $data[$i]["Sub_Class_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix."?catalogue=".$current_sub["Catalogue_ID"]."&amp;sub=".$current_sub["Subdivision_ID"]."&amp;cc=".$data[$i]["Sub_Class_ID"];
        } else {
            $nav_url = $current_sub["Hidden_URL"].$data[$i]["EnglishName"].".html";
        }

        if ($data[$i]["Sub_Class_ID"] == $current_cc["Sub_Class_ID"] && ($cc_keyword || !$use_multi_sub_class)) {
            if ($browse_template['active_link'] && ($SUB_FOLDER.$current_sub['Hidden_URL'] == urldecode(strtok($REQUEST_URI, '?')) || $SUB_FOLDER.$current_sub['Hidden_URL'].$current_cc['EnglishName'].".html" == urldecode(strtok($REQUEST_URI, '?')) )) {
                $thisActive = $browse_template['active_link'];
            } else {
                $thisActive = $browse_template['active'];
            }
            $array_result[$i] = $thisActive;
        } else {
            $array_result[$i] = $browse_template;
        }

        $array_result[$i] = str_replace("%NAME", $nav_name, $array_result[$i]);
        if (!$admin_mode) $nav_url = $SUB_FOLDER.$nav_url;
        $array_result[$i] = str_replace("%URL", $nav_url, $array_result[$i]);

    }
    $result .= join($browse_template['divider'], $array_result);
    $result .= $browse_template['suffix'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_cc, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, catalogue[".$current_cc['Catalogue_ID']."], sub[".$current_cc['Subdivision_ID']."], cc[".$current_cc['Sub_Class_ID']."], Access_ID[".$current_cc['Cache_Access_ID']."], Lifetime[".$current_cc['Cache_Lifetime']."], bytes[".$bytes."]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}
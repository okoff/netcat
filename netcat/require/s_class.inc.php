<?

/* $Id: s_class.inc.php 8253 2012-10-23 11:25:53Z ewind $ */

function opt($flag, $output) {
    if ($flag) return $output;
}

function opt_case($flag, $output1, $output2 = "") {
    if ($flag) {
        return $output1;
    } else {
        return $output2;
    }
}

function is_even($input) {
    if (round($input / 2) == $input / 2) {
        return 0;
    } else {
        return 1;
    }
}

/**
 * формирует листинг страниц с объектами
 *
 * @param array переменные окружения сс
 * @param int количество выводимых страниц
 *
 * @global $browse_msg, $classPreview
 *
 * Примечание: массив-шаблон $browse_msg должен быть определен
 *
 * @return string html-текст с листингом
 */
function browse_messages($cc_env, $range, $user_template = false) {
    global $classPreview, $admin_mode, $inside_admin;
    
    if ($user_template) $browse_msg = $user_template;
    else global $browse_msg;
    
    // system superior object
    $nc_core = nc_Core::get_object();
    if (isset($classPreview)) $classPreview+= 0;

    if ($cc_env['cur_cc'] == $nc_core->input->fetch_get("cur_cc")) {
        $curPos = $cc_env['curPos'] + 0;
    }
    $maxRows = $cc_env['maxRows'];
    $totRows = $cc_env['totRows'];

    if ($cc_env['cur_cc']) $cur_cc = $cc_env['cur_cc'];

    if (!$maxRows || !$totRows) return;

    $page_count = ceil($totRows / $maxRows);
    $half_range = ceil($range / 2);
    $cur_page = ceil($curPos / $maxRows) + 1;

    if ($page_count < 2) return;

    $maybe_from = $cur_page - $half_range;
    $maybe_to = $cur_page + $half_range;

    if ($maybe_from < 0) {
        $maybe_to = $maybe_to - $maybe_from;
        $maybe_from = 0;

        if ($maybe_to > $page_count) $maybe_to = $page_count;
    }

    if ($maybe_to > $page_count) {
        $maybe_from = $page_count - $range;
        $maybe_to = $page_count;

        if ($maybe_from < 0) $maybe_from = 0;
    }

    // формируем ссылку
    // const_url не меняется для каждой страницы
    $const_url = $cc_env['LocalQuery'];
    if ($const_url == '?') $const_url = '';
    
    //$const_url = rawurlencode ($const_url);
    // добавим в ссылку cur_cc
    if (isset($cur_cc)) $get_param['cur_cc'] = $cur_cc;

    // добавим get-парметры
    
    $_get_arr = $nc_core->input->fetch_get();
    if (!empty($_get_arr)) {
        $ignore_arr = array('sid', 'ced', 'inside_admin', 'catalogue', 'sub', 'cc', 'curPos', 'cur_cc', 'REQUEST_URI', 'srchPat');
        if ($inside_admin || $admin_mode) {
            $ignore_arr[] = 'isNaked';
        }
        foreach ($_get_arr as $k => $v) {
            if (!in_array($k, $ignore_arr)) $get_param[$k] = $nc_core->input->recursive_striptags_escape($v);
        }
    }
    
    $const_url .= $nc_core->url->build_url($get_param) ? ( strstr($const_url, "?") ? "&" : "?" ).$nc_core->url->build_url($get_param) : "";

    // prefix
    eval("\$result = \"".$browse_msg['prefix']."\";");
    $result = str_replace("%URL", $nc_core->url->get_parsed_url('path').$const_url, $result);
    $result = str_replace("%FIRST", "1", $result);

    for ($i = $maybe_from; $i < $maybe_to; $i++) {
        $page_number = $i + 1;
        $page_from = $i * $maxRows;
        $page_to = $page_from + $maxRows;

        // ссылка не на первую страницу
        if ($page_from && !$admin_mode) {
            $url = $nc_core->url->get_parsed_url('path').$const_url.( strpos($const_url, "?") !== false ? "&" : "?" )."curPos=".$page_from;
        } elseif ($page_from && $admin_mode) {
            $url = $const_url.( strpos($const_url, "?") !== false ? "&" : "?" )."curPos=".$page_from;
        } else { // ссылка на первую страницу, curPos не нужен
            $url = $const_url ? $const_url : $nc_core->url->get_parsed_url('path');
        }
        
        $url = $nc_core->SUB_FOLDER.$url;

        // clear already existance &amp; and replace all & to &amp; view
        $url = nc_preg_replace(array("/&amp;/", "/&/"), array("&", "&amp;"), $url);

        if ($curPos == $page_from) {
            eval("\$result .= \"".$browse_msg['active']."\";");
        } else {
            eval("\$result .= \"".$browse_msg['unactive']."\";");
        }

        $result = str_replace("%URL", $url, $result);
        $result = str_replace("%PAGE", $page_number, $result);
        $result = str_replace("%FROM", $page_from + 1, $result);
        $result = str_replace("%TO", $page_to, $result);

        if ($i != ($maybe_to - 1))
                eval("\$result .= \"".$browse_msg['divider']."\";");
    }

    eval("\$result .= \"".$browse_msg['suffix']."\";");
    
    $last = $maxRows * ($page_count - 1);
    if (!$admin_mode) {
        $url = $nc_core->url->get_parsed_url('path') . $const_url . ( strpos($const_url, "?") !== false ? "&" : "?" ) . "curPos=" . $last;
    } else {
        $url = $const_url . ( strpos($const_url, "?") !== false ? "&" : "?" ) . "curPos=" . $last;
    }
    $result = str_replace("%URL", $url, $result);
    $result = str_replace("%LAST", $page_count, $result);

    return $result;
}

function nc_browse_messages($cc_env, $range, $user_template = false) {
    
    if ($user_template) $browse_msg = $user_template;
    else global $browse_msg;

    global $classPreview, $admin_mode, $inside_admin;
    $nc_core = nc_Core::get_object();

    if (isset($classPreview)) $classPreview+= 0;

    if ($cc_env['cur_cc'] == $nc_core->input->fetch_get("cur_cc")) {
        $curPos = $cc_env['curPos'] + 0;
    }
    
    $maxRows = $cc_env['maxRows'];
    $totRows = $cc_env['totRows'];

    if ($cc_env['cur_cc']) $cur_cc = $cc_env['cur_cc'];

    if (!$maxRows || !$totRows) return;

    $page_count = ceil($totRows / $maxRows);
    $half_range = ceil($range / 2);
    $cur_page = ceil($curPos / $maxRows) + 1;

    if ($page_count < 2) return;

    $maybe_from = $cur_page - $half_range;
    $maybe_to = $cur_page + $half_range;

    if ($maybe_from < 0) {
        $maybe_to = $maybe_to - $maybe_from;
        $maybe_from = 0;

        if ($maybe_to > $page_count) $maybe_to = $page_count;
    }

    if ($maybe_to > $page_count) {
        $maybe_from = $page_count - $range;
        $maybe_to = $page_count;

        if ($maybe_from < 0) $maybe_from = 0;
    }

    $result = $browse_msg['prefix'];
    
    // формируем ссылку
    // const_url не меняется для каждой страницы
    $const_url = $cc_env['LocalQuery'];
    if ($const_url == '?') $const_url = '';

    //$const_url = rawurlencode ($const_url);
    // добавим в ссылку cur_cc
    if (isset($cur_cc)) $get_param['cur_cc'] = $cur_cc;

    // добавим get-парметры
    $_get_arr = $nc_core->input->fetch_get();
    if (!empty($_get_arr)) {
        $ignore_arr = array('sid', 'ced', 'inside_admin', 'catalogue', 'sub', 'cc', 'curPos', 'cur_cc', 'REQUEST_URI', 'srchPat');
        if ($inside_admin || $admin_mode) {
            $ignore_arr[] = 'isNaked';
        }
        foreach ($_get_arr as $k => $v) {
            if (!in_array($k, $ignore_arr)) $get_param[$k] = $nc_core->input->recursive_striptags_escape($v); 
        }
    }

    $const_url .= $nc_core->url->build_url($get_param) ? ( strstr($const_url, "?") ? "&" : "?" ).$nc_core->url->build_url($get_param) : "";
    $array_result = array();
    for ($i = $maybe_from; $i < $maybe_to; $i++) {
        $page_number = $i + 1;
        $page_from = $i * $maxRows;
        $page_to = $page_from + $maxRows;

        // ссылка не на первую страницу
        if ($page_from && !$admin_mode) {
            $url = $nc_core->url->get_parsed_url('path').$const_url.( strpos($const_url, "?") !== false ? "&" : "?" )."curPos=".$page_from;
        } elseif ($page_from && $admin_mode) {
            $url = $const_url.( strpos($const_url, "?") !== false ? "&" : "?" )."curPos=".$page_from;
        } else { // ссылка на первую страницу, curPos не нужен
            $url = $const_url ? $const_url : $nc_core->url->get_parsed_url('path');
        }

        // clear already existance &amp; and replace all & to &amp; view
        $url = nc_preg_replace(array("/&amp;/", "/&/"), array("&", "&amp;"), $url);

        if ($curPos == $page_from) {
            $array_result[$i] = $browse_msg['active'];
        } else {
            $array_result[$i] = $browse_msg['unactive'];
        }

        $array_result[$i] = str_replace("%URL", $url, $array_result[$i]);
        $array_result[$i] = str_replace("%PAGE", $page_number, $array_result[$i]);
        $array_result[$i] = str_replace("%FROM", $page_from + 1, $array_result[$i]);
        $array_result[$i] = str_replace("%TO", $page_to, $array_result[$i]);
    }
    
    $result .= join($browse_msg['divider'], $array_result);
    $result .= $browse_msg['suffix'];
    
    return $result;
}


function parentofmessage($message, $classID) {
    global $db;

    $parent = $message;
    $classID = intval($classID);
    $parent = intval($parent);

    while ($parent) {
        $old_parent = $parent;

        $parent = $db->get_var("SELECT Parent_Message_ID FROM Message".$classID." WHERE Message_ID='".$parent."'");
    }

    return $old_parent;
}
?>
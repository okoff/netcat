<?php

/* $Id: s_e404.inc.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * Return arache headers as array
 *
 * @return array server headers
 */
if (!function_exists("apache_request_headers")) {

    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER AS $key => $val) {
            if (nc_preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 AND strlen($arh_key) > 2) {
                    foreach ($rx_matches AS $ak_key => $ak_val)
                        $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
                    $arh_key = implode('-', $rx_matches);
                }
                if ($val != '') $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }

}

/**
 * Format timestamp as GMT date "D, d M Y H:i:s"
 *
 * @param mixed TIMESTAMP
 *
 * @return string GMT date
 */
function nc_timestamp_to_gmt($timestamp) {
    // format timestamp as GMT date
    return gmdate("D, d M Y H:i:s", $timestamp);
}

/**
 * Redirect 304 Not Modified
 *
 * @param string TIMESTAMP
 *
 * return bool false (not attempted)
 */
function nc_attempt_last_modified_redirect($timestamp) {
    // system superior object
    $nc_core = nc_Core::get_object();

    // format timestamp as GMT date
    $last_modified = nc_timestamp_to_gmt($timestamp);

    // get apache headers
    $request_headers = apache_request_headers();

    // check headers
    foreach ($request_headers AS $key => $value) {
        if (nc_preg_match("/^If-Modified-Since$/is", $key)) {
            if ($value && strtotime($value) >= strtotime($last_modified)) {
                if ($nc_core->PHP_TYPE != "cgi") {
                    header($_SERVER['SERVER_PROTOCOL']." 304 Not Modified");
                } else {
                    header("Status: 304 Not Modified");
                }
                exit;
            }
        }
    }

    // not attempted
    return false;
}

function ObjectExists($classID, $sysTbl, $cc, $keyword, $date = "") {
    static $storage = array(1 => NULL, 2 => NULL, 3 => NULL);
    global $db;

    switch ($sysTbl) {
        // system table
        case true:
            if (is_null($storage[1][$keyword])) {
                $storage[1][$keyword] = $db->get_var("SELECT `User_ID` FROM `User` WHERE `Keyword` = '".$db->escape($keyword)."'");
            }
            $result = $storage[1][$keyword];
            break;
        // simple component
        default:
            $nc_core = nc_Core::get_object();
            
            $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
            $cc = $mirror_cc ? $mirror_cc : $cc;

            if ($date && strtotime($date) > 0) {
                if (is_null($storage[2][$classID])) {
                    $storage[2][$classID] = $db->get_var("SELECT `Field_Name` FROM `Field`
            WHERE (`Format` REGEXP \"^((event_date|event)(;)?(calendar)?)$\")
            AND `TypeOfData_ID` = 8 AND `Class_ID` = '".intval($classID)."'");
                }
                $Field_Name = $storage[2][$classID];
                if (!$Field_Name) return false;
                // set date condition
                $cond_date = " AND m.`".$db->escape($Field_Name)."` LIKE '".$db->escape($date)."%'";
            }

            if (is_null($storage[3][$classID][$cc][$keyword][$cond_date])) {
                $storage[3][$classID][$cc][$keyword][$cond_date] = $db->get_var("SELECT m.`Message_ID`
          FROM `Message".intval($classID)."` AS m
          LEFT JOIN `Sub_Class` AS sc ON sc.`Sub_Class_ID` = m.`Sub_Class_ID`
          WHERE m.`Keyword` = '".$db->escape($keyword)."' AND sc.`Sub_Class_ID` = '".intval($cc)."'".$cond_date);
            }
            $result = $storage[3][$classID][$cc][$keyword][$cond_date];
    }

    return $result;
}

function ObjectExistsByID($classID, $sysTbl, $id, $date = "") {
    static $storage = array(1 => NULL, 2 => NULL, 3 => NULL);
    global $db;

    switch ($sysTbl) {
        // system table
        case true:
            if (is_null($storage[1][$id])) {
                $storage[1][$id] = $db->get_var("SELECT `User_ID` FROM `User` WHERE `User_ID` = '".intval($id)."'");
            }
            $result = $storage[1][$id];
            break;
        // simple component
        default:
            if ($date && strtotime($date) > 0) {
                if (is_null($storage[2][$classID])) {
                    $storage[2][$classID] = $db->get_var("SELECT `Field_Name` FROM `Field`
            WHERE (`Format` REGEXP \"^((event_date|event)(;)?(calendar)?)$\")
            AND `TypeOfData_ID` = 8 AND `Class_ID` = '".intval($classID)."'");
                }
                $Field_Name = $storage[2][$classID];
                if (!$Field_Name) return false;
                // set date condition
                $cond_date = " AND m.`".$db->escape($Field_Name)."` LIKE '".$db->escape($date)."%'";
            }

            if (is_null($storage[3][$classID][$cond_date])) {
                $storage[3][$classID][$cond_date] = intval($db->get_var("SELECT m.`Message_ID`
          FROM `Message".intval($classID)."` AS m
          WHERE m.`Message_ID` = '".intval($id)."'".$cond_date));
            }
            $result = $storage[3][$classID][$cond_date];
    }

    return $result;
}

function AttemptToRedirect($url) {
    // system superior object
    $nc_core = nc_Core::get_object();
    // GET data
    $get_data = $nc_core->input->fetch_get();
    // REQUEST_URI не надо учитывать
    if ($get_data['REQUEST_URI']) unset($get_data['REQUEST_URI']);

    if (!empty($get_data)) $url .= '?'.$nc_core->url->build_url($get_data);    
    
    $nc_core->db->num_rows = 0;
    
    $SQL = "
        SELECT REPLACE(NewURL,'*','$'),
               REPLACE(OldURL,'*','([[:alnum:]-]+)'),
               `Header` 
            FROM `Redirect`
                WHERE '".$nc_core->db->escape($url)."' LIKE CONCAT('http://', REPLACE(REPLACE(OldURL,'_','\\\_'),'*','%'))
                   OR '".$nc_core->db->escape($url)."' LIKE CONCAT('http://www.',REPLACE(REPLACE(OldURL,'_','\\\_'),'*','%')) 
                    ORDER BY LENGTH(OldURL) DESC 
                        LIMIT 1";
    $res = $nc_core->db->get_row($SQL, ARRAY_N);

    if (!$nc_core->db->num_rows) return 0;

    list($new_url, $old_url, $header_code) = $res;

    // заголовок по умолчанию
    if ($header_code != 301 && $header_code != 302) $header_code = 301;

    if (strchr($new_url, '$')) {
        $result_url = preg_replace('@'.$old_url.'@i', $new_url, $url, -1, $c);
    } else {
        $result_url = "http://".$new_url;
    }

    if ($nc_core->REDIRECT_STATUS == "on") {
        if ($nc_core->AUTHORIZATION_TYPE == 'session') {
            if (substr($result_url, -1) != 'l' && substr($result_url, -1) != '/') {
                $result_url.= "&".session_name()."=".session_id();
            } else {
                $result_url.= "?".session_name()."=".session_id();
            }
        }

        if ($nc_core->PHP_TYPE == 'cgi')
                header('Status: 301 Moved Permanently');

        header("Location: ".$result_url, true, $header_code);
    }
    else {
        if ($nc_core->PHP_TYPE == 'cgi') header('Status: 200 OK');
        echo "<meta http-equiv='refresh' content='0;url=http://".$result_url."'>";
    }
    exit;
}
?>
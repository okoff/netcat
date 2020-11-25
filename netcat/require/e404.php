<?php

/* $Id: e404.php 8457 2012-11-23 12:41:19Z aix $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -3)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");

require ($ROOT_FOLDER."connect_io.php");

/*
  ///////////////////////
  require("Benchmark/Timer.php");
  $nccttimer = new Benchmark_Timer();
  $nccttimer->start();
  ///////////////////////
  $db->debug_all = true;
  $db->benchmark = true;
  ///////////////////////////////////
 */


//if ( substr($nc_core->REQUEST_URI, 0, 1) != "/" ) $nc_core->REQUEST_URI="/".$nc_core->REQUEST_URI;

$HTTP_FILES_PATH_PREG = str_replace("/", "\/", $nc_core->HTTP_FILES_PATH);
$subHost = $_SERVER['SERVER_NAME'];
// уберём из начала строки $SUB_FOLDER
$SUB_FOLDER_PREG = str_replace("/", "\/", $nc_core->SUB_FOLDER);
$nc_core->url->set_parsed_url_item('path', nc_preg_replace("/^(".$SUB_FOLDER_PREG.")(.*?)$/is", "\$2", $nc_core->url->get_parsed_url('path')));
unset($SUB_FOLDER_PREG);

if (preg_match("/^".$HTTP_FILES_PATH_PREG."([0-9uct]+)\/([0-9]+\/)?h_([0-9A-Z]{32})$/i", $nc_core->url->get_parsed_url('path'), $matches)) {

    if ($matches[1] != "u" && $matches[1] != "c" && $matches[1] != "t")
            $matches[1] = intval($matches[1]);

    if (nc_strlen($matches[2])) {
        $File_Path = $matches[1]."/".$matches[2];
    } else {
        $File_Path = $matches[1]."/";
    }

    $file_path = $nc_core->FILES_FOLDER.$File_Path.$matches[3];

    if (file_exists($file_path)) {

        while (ob_get_level() && @ob_end_clean())
            continue;

        // sic (remove header)
        if ($use_gzip_compression) header("Content-Encoding: ");

        // get filetime
        $file_time = filemtime($file_path);
        // format timestamp as GMT date
        $last_modified = nc_timestamp_to_gmt($file_time);

        // check If-Modified-Since and REDIRECT 304, if need it
        nc_attempt_last_modified_redirect($file_time);

        $file_data = $db->get_row("SELECT f.`ID`, f.`Real_Name`, f.`File_Type`, f.`Content_Disposition`, fl.`Format`
  		FROM `Filetable` as f, `Field` as `fl`
  		WHERE `Virt_Name` = '".$matches[3]."'
  		AND `File_Path` = '/".$File_Path."'
      AND fl.`Field_ID` = f.`Field_ID`
  		LIMIT 1", ARRAY_N);

        if (!empty($file_data)) {

            list ($ID, $Real_Name, $File_Type, $Attachment, $Format) = $file_data;
            $File_Size = @filesize($file_path);

            if (!nc_strlen($File_Type)) $File_Type = "application/octet-stream";

            header($_SERVER['SERVER_PROTOCOL']." 200 OK");
            // for CGI header
            if ($nc_core->PHP_TYPE == "cgi") header("Status: 200 OK");

            header("Last-Modified: ".$last_modified);
            header("Content-type: ".$File_Type);
            header("Content-Disposition: ".($Attachment ? 'attachment' : 'inline')."; filename=\"".urldecode($Real_Name)."\"");
            header('Content-Transfer-Encoding: binary');

            if ($File_Size) {
                header("Content-Length: ".$File_Size);
                header("Connection: close");
            }
            if (strstr($Format, 'download') !== false) {
                $db->query("UPDATE `Filetable` SET `Download` = `Download`+1 WHERE `ID` = '".$ID."'");
            }

            echo @file_get_contents($file_path);

            exit;
        }
    }
}
unset($HTTP_FILES_PATH_PREG);

$client_source_url = $nc_core->url->source_url();

$catalogue = 0;
$sub = 0;
$cc = 0;
$classID = 0;
$message = 0;
$user_table_mode = false;
$admin_mode = false;
$system_table_fields = array();
$cc_array = array();
$redirect_to_url = '';
$cc_keyword = '';
$developer_mode = false;

$current_catalogue = $nc_core->catalogue->get_by_host_name($nc_core->HTTP_HOST, true);
$catalogue = $nc_core->catalogue->get_current("Catalogue_ID");

if (!$catalogue) {
	exit;
}

// if robots.txt wanted
if ($nc_core->url->get_parsed_url('path') == '/robots.txt') {
    header($_SERVER['SERVER_PROTOCOL']." 200 OK");
    // for CGI header
    if ($PHP_TYPE == "cgi") header("Status: 200 OK");
    header("Last-Modified: ".$nc_core->catalogue->get_current('LastUpdated'));
    header("Content-type: text/plain");
    header("Content-Length: ".strlen($nc_core->catalogue->get_current('Robots')));
    echo $nc_core->catalogue->get_current('Robots');
    exit;
}

// if sitemap.xml wanted
if ($nc_core->url->get_parsed_url('path') == '/sitemap.xml' && $nc_core->modules->get_by_keyword('search')) {
    require_once $nc_core->MODULE_FOLDER.'search/sitemap.php';
    exit;
}

if (!$nc_core->subdivision->get_current("Subdivision_ID"))
        $nc_core->subdivision->set_current_by_uri();
$sub = $nc_core->subdivision->get_current("Subdivision_ID");

// modules
$MODULE_VARS = $nc_core->modules->load_env();

$e404_sub = $nc_core->catalogue->get_current("E404_Sub_ID");
$title_sub = $nc_core->catalogue->get_current("Title_Sub_ID");
$page_not_found = false;

$req_file = strrchr($nc_core->url->get_parsed_url('path'), '/');

if ($req_file != '/') {

    $req_file = substr($req_file, 1);
    $fext = '';
    if (strpos($req_file, '.')) {
        $req_file_parts = explode(".", $req_file);
        $fname = $req_file_parts[0];
        $fext = strtolower($req_file_parts[count($req_file_parts) - 1]);
    }

    if (in_array($fext, array('html', 'rss', 'xml'))) {
        // name without extension
        $nc_core->url->set_parsed_url_item('path', nc_substr($nc_core->url->get_parsed_url('path'), 0, nc_strlen($nc_core->url->get_parsed_url('path')) - nc_strlen($req_file)));
    } else {
        // append trailing slash
        $nc_core->url->set_parsed_url_item('path', rtrim($nc_core->url->get_parsed_url('path'), "/")."/");
    }

    // determine subdivision and set as current
    $nc_core->subdivision->set_current_by_uri();
    $sub = $_db_sub = $nc_core->subdivision->get_current("Subdivision_ID");

    if (in_array($fext, array('html', 'rss', 'xml'))) {
        $nc_core->set_page_type($fext);
        $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");
        $cc_in_sub = $nc_core->sub_class->get_by_subdivision_id();

        // keyword.html - совпадение по ключевому слову объекта
        if (nc_preg_match("/^([_a-zа-я0-9-]+)$/i", $fname, $regs) && ($fname == $regs[1])) {
            if (!empty($cc_in_sub)) {
                foreach ($cc_in_sub AS $row) {
                    if ($fext == 'rss' && !$row['AllowRSS']) continue;
                    if ($fext == 'xml' && !$row['AllowXML']) continue;
                    // find message with need params
                    if ($result = ObjectExists($row['Class_ID'], $row['sysTbl'], $row['Sub_Class_ID'], $fname, $nc_core->url->get_uri_date())) {
                        $action = "full";
                        $message = $result;
                        $classID = $row['Class_ID'];
                        $cc = $_db_cc = $row['Sub_Class_ID'];
                        $sub = $nc_core->subdivision->get_current("Subdivision_ID");
                        $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
                        if ($mirror_cc) {
                            $cc_mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
                            $cc = $cc_mirror_data['Sub_Class_ID'];
                            $sub = $cc_mirror_data['Subdivision_ID'];
                        }
                        break;
                    }
                }
            }
        }

        // news.html - ключевое слово компонента, при условии, что нет такого объекта
        if (!$message && nc_preg_match("/^([a-zа-я0-9-]+)$/i", $fname, $regs) && ($fname == $regs[1])) {
            if (!empty($cc_in_sub)) {
                foreach ($cc_in_sub as $row) {
                    if ($row['EnglishName'] == $regs[1]) {
                        if ($fext == 'rss' && !$row['AllowRSS']) continue;
                        if ($fext == 'xml' && !$row['AllowXML']) continue;
                        //action может быть задан в get'e или post'e
                        if (!$action) $action = $row['DefaultAction'];
                        $cc = $_db_cc = $row['Sub_Class_ID'];
                        $cc_keyword = $regs[1];
                        $sub = $nc_core->subdivision->get_current("Subdivision_ID");
                        $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
                        if ($mirror_cc) {
                            $cc_mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
                            $cc = $cc_mirror_data['Sub_Class_ID'];
                            $sub = $cc_mirror_data['Subdivision_ID'];
                        }
                    }
                }
            }
        }
        // add_news.html, search_news.html, subscribe_news.html - добавление, поиск, подписка в компоненте
        if (nc_preg_match("/^([a-z]+)_([a-zа-я0-9-]+)$/i", $fname, $regs) && ($fname == $regs[1]."_".$regs[2]) && ($regs[1] == "add" || $regs[1] == "search" || $regs[1] == "subscribe")) {
            if (!empty($cc_in_sub)) {
                foreach ($cc_in_sub as $row) {
                    $cc = $_db_cc = $row['Sub_Class_ID'];
                    $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
                    if ($mirror_cc) {
                        $cc_mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
                        $action = $regs[1];
                        $cc = $cc_mirror_data['Sub_Class_ID'];
                        $sub = $cc_mirror_data['Subdivision_ID'];
                        $cc_keyword = $regs[2];
                        break;
                    }
                    // find message with need params
                    else if ($row['EnglishName'] == $regs[2]) {
                        $action = $regs[1];
                        $sub = $nc_core->subdivision->get_current("Subdivision_ID");
                        $cc_keyword = $regs[2];
                        break;
                    }
                }
            }
        }

        // news_5.html - отображение объекта по компоненту и идентификатору
        if (nc_preg_match("/^([a-zа-я0-9-]+)_([0-9]+)$/i", $fname, $regs) && ($fname == $regs[1]."_".$regs[2])) {
            if (!empty($cc_in_sub)) {
                foreach ($cc_in_sub as $row) {
                    // check component in sub keyword
                    $cc = $_db_cc = $row['Sub_Class_ID'];
                    $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
                    if ($mirror_cc) $cc_mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
                    if ($row['EnglishName'] != $regs[1] && $cc_mirror_data['EnglishName'] != $regs[1]) continue;
                    if ($fext == 'rss' && !$row['AllowRSS']) continue;
                    if ($fext == 'xml' && !$row['AllowXML']) continue;
                    // find message with need params
                    if ($result = ObjectExistsByID($row['Class_ID'], $row['sysTbl'], $regs[2], $nc_core->url->get_uri_date())) {
                        $cc_keyword = $regs[1];
                        $sub = $nc_core->subdivision->get_current("Subdivision_ID");
                        if ($mirror_cc) {
                            $cc = $cc_mirror_data['Sub_Class_ID'];
                            $sub = $cc_mirror_data['Subdivision_ID'];
                        }
                        $message = $result;
                        $action = "full";
                        break;
                    }
                }
            }
        }

        // edit_5.html - изменение объекта по ДЕЙСТВИЮ и КЛЮЧЕВОМУ СЛОВУ!!!, при условии, что нет объекта по компоненту и идентификатору
        if (!$message && nc_preg_match("/^([a-z]+)_([_a-zа-я0-9-]+)$/i", $fname, $regs) && ($fname == $regs[1]."_".$regs[2]) && ($regs[1] == "edit" || $regs[1] == "delete" || $regs[1] == "drop" || $regs[1] == "checked" || $regs[1] == "subscribe" )) {
            if (!empty($cc_in_sub)) {
                foreach ($cc_in_sub AS $row) {
                    // find message with need params
                    if ($result = ObjectExists($row['Class_ID'], $row['sysTbl'], $row['Sub_Class_ID'], $regs[2])) {
                        $cc = $_db_cc = $row['Sub_Class_ID'];
                        $sub = $nc_core->subdivision->get_current("Subdivision_ID");
                        $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
                        if ($mirror_cc) {
                            $cc_mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
                            $cc = $cc_mirror_data['Sub_Class_ID'];
                            $sub = $cc_mirror_data['Subdivision_ID'];
                        }
                        $action = 'message';
                        $message = $result;
                        $cc_keyword = "";
                        // массив действий
                        if ($regs[1] == "delete") {
                            $delete = 1;
                            $posting = 0;
                        }
                        if ($regs[1] == "drop") {
                            $delete = 1;
                            $posting = 1;
                        }
                        if ($regs[1] == "checked") {
                            $checked = 1;
                            $posting = 1;
                        }
                        if ($regs[1] == "subscribe") {
                            $action = "subscribe";
                        }
                        break;
                    }
                }
            }
        }

        // edit_news_5.html - изменение объекта по действию, компоненту и идентификатору объекта
        if (nc_preg_match("/^([a-z]+)_([a-zа-я0-9-]+)_([0-9]+)$/i", $fname, $regs) && ($fname == $regs[1]."_".$regs[2]."_".$regs[3]) && ($regs[1] == "edit" || $regs[1] == "delete" || $regs[1] == "drop" || $regs[1] == "checked" || $regs[1] == "subscribe" )) {
            if (!empty($cc_in_sub)) {
                foreach ($cc_in_sub AS $row) {
                    // check component in sub keyword
                    $cc = $_db_cc = $row['Sub_Class_ID'];
                    $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
                    if ($mirror_cc) $cc_mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
                    if ($row['EnglishName'] != $regs[2] && $cc_mirror_data['EnglishName'] != $regs[2]) continue;
                    // find message with need params
                    if ($result = ObjectExistsByID($row['Class_ID'], $row['sysTbl'], $regs[3])) {
                        $cc = $row['Sub_Class_ID'];
                        $sub = $nc_core->subdivision->get_current("Subdivision_ID");
                        if ($mirror_cc) {
                            $cc = $cc_mirror_data['Sub_Class_ID'];
                            $sub = $cc_mirror_data['Subdivision_ID'];
                        }
                        $action = "message";
                        $message = $result;
                        $cc_keyword = $regs[2];
                        // массив действий
                        if ($regs[1] == "delete") {
                            $delete = 1;
                            $posting = 0;
                        }
                        if ($regs[1] == "drop") {
                            $delete = 1;
                            $posting = 1;
                        }
                        if ($regs[1] == "checked") {
                            $checked = 1;
                            $posting = 1;
                        }
                        if ($regs[1] == "subscribe") {
                            $action = "subscribe";
                        }
                    }
                }
            }
        }

        // set current subclass
        $nc_core->sub_class->set_current_by_id($cc);

        // isNaked
        if (false && !$isNaked && !empty($cc_in_sub)) {
            foreach ($cc_in_sub AS $row) {
                if ($row['Sub_Class_ID'] == $cc) $isNaked = $row['isNaked'];
            }
        }

        if (!$message && !$action) $page_not_found = true;
    }
    else {
        // redirect url
        $redirect_to_url = $client_source_url."/".($nc_core->url->get_parsed_url('query') ? "?".$nc_core->url->get_parsed_url('query') : "").($nc_core->url->get_parsed_url('fragment') ? "#".$nc_core->url->get_parsed_url('fragment') : "");
    }
}


if (!$nc_core->subdivision->get_current("Subdivision_ID"))
        $nc_core->subdivision->set_current_by_uri();
$sub = $nc_core->subdivision->get_current("Subdivision_ID");


if (!isset($use_multi_sub_class)) {
    // subdivision multisubclass option
    $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");
}

if ($sub && $redirect_to_url && $e404_sub != $sub) {
    if ($nc_core->REDIRECT_STATUS == "on") {
        if ($nc_core->AUTHORIZATION_TYPE == 'session') {
            $redirect_to_url.= substr($redirect_to_url, -1) != 'l' && substr($redirect_to_url, -1) != '/' ? "&" : "?";
            $redirect_to_url.= session_name()."=".session_id();
        }
        header("Location: ".$redirect_to_url, true, 301);
        exit;
    }
}

// переадресация

if (!$nc_core->NC_REDIRECT_DISABLED) AttemptToRedirect($client_source_url);

if ($sub) {
    // this array may be setted in full view processing on very top
    if (empty($cc_in_sub)) {
        // used in loadenv(); and set as current if only one row affected
        $cc_in_sub = $nc_core->sub_class->get_by_subdivision_id();
    }

    if (!empty($cc_in_sub)) {
        foreach ($cc_in_sub as $row) {
            if (
            // we've got keyword
                    ($cc_keyword && $row['EnglishName'] == $cc_keyword) ||
                    // Если сс включен или это системная таблица, то ее надо включить в $cc_array, при условии, что
                    // сс не определился выше ( например, по ссылке с действием)
                    (!$cc_keyword && !$cc && ($row['Checked'] || $row['sysTbl'] == 3) )
            ) {
                $cc = $row['Sub_Class_ID']; // current class
                $classID = $row['Class_ID'];
                $default_action = $row['DefaultAction'];
                $system_table = $row['sysTbl'];
                // set current subclass
                $nc_core->sub_class->set_current_by_id($cc);
                // isNaked
                if (false && !isset($isNaked)) $isNaked = $row['isNaked'];
            }
            // $cc_array, used in loadenv()
            if ($row['Checked']) $cc_array[] = $row["Sub_Class_ID"];
        }
    }

    if (!isset($action)) $action = $default_action;
    if ($system_table) $user_table_mode = true;

    // date found
    if ($nc_core->url->get_uri_date()) {
        // set date
        $date = $nc_core->url->get_uri_date();
        // if set date in URI segments and not "event" field
        if ($cc && $date) {
            $FieldID = $db->get_var("SELECT `Field_ID` FROM `Field`
        WHERE (`Format` REGEXP \"^((event_date|event)(;)?(calendar)?)$\")
        AND `TypeOfData_ID` = 8 AND `Class_ID` = '".intval($classID)."'");
            if (!$FieldID) $page_not_found = true;
        }
        // for fullDateLink
        if (( ($cc_keyword && !$message && $action == 'full') || (!$cc && !$cc_keyword) ) && $date) {
            $page_not_found = true;
        }
    }

    if ($cc_keyword && !$cc) $page_not_found = true;
}

// Front user mode
$action_arr = array("index", "full", "add", "search", "subscribe", "message");
if (!in_array($action, $action_arr)) $action = "index";

if ($cc && in_array($sub, nc_preg_split("/\s*,\s*/", $nc_core->get_settings('modify_sub', 'auth')))) {
    $action = "message";
    $user_table_mode = true;
}

if (!$catalogue) exit;
if (!$sub || ($sub == $e404_sub && $title_sub != $sub)) $page_not_found = true;

if ($page_not_found) {
    $sub = $e404_sub;
    $nc_core->subdivision->set_current_by_id($sub);
    // get 404 cc's
    $cc_in_sub = $nc_core->sub_class->get_by_subdivision_id($sub);
    $cc_array = array();
    if (!empty($cc_in_sub)) {
        foreach ($cc_in_sub as $row) {
            $cc_array[] = $row['Sub_Class_ID'];
        }
    }
    // определение сс
    $cc = $cc_array[0];
    $classID = $cc_in_sub[0]['Class_ID'];
    // reset variables
    $nc_core->sub_class->set_current_by_id($cc);
    @$cc_keyword = $cc_array[0]['EnglishName'];
    // isNaked
    if (!$isNaked) $isNaked = $cc_in_sub[0]['isNaked'];
    // 404 header
    header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
    unset($date);
    $action = "index";
}
else {
    // 200 OK
    header($_SERVER['SERVER_PROTOCOL']." 200 OK");
    // for CGI header
    if ($nc_core->PHP_TYPE == 'cgi') header("Status: 200 OK");

    header("Content-Type: ".$nc_core->get_content_type());

    switch ($_SERVER['SERVER_PROTOCOL']) {
        case "HTTP/1.0":
            header("Pragma: no-cache");
            break;
        default:
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            $nc_core->page->send_lastmodified();
    }
}

// требовались только заголовки, можно отдыхать
if ($_SERVER['REQUEST_METHOD'] == 'HEAD') exit;

if ($nc_core->AUTHORIZATION_TYPE == 'session') {
    $sname = session_name();

    if ($$sname != "") {
        // if "session.hash_function" set as "0", used md5() 128 bits alg else if "1" SHA-1 160 bits alg
        // if "raw_output" second parameter in md5() setted, hash length==16 else length==32
        // for SHA-1 alg:
        // 4 - 40 character string
        // 5 - 32 character string
        // 6 - 27 character string
        $session_hash_alg = ini_get("session.hash_function");
        switch (ini_get("session.hash_bits_per_character")) {
            case 5:
                $session_name_regexp = "/^[a-v0-9]{".($session_hash_alg ? "40" : "16,32")."}$/s";
                nc_preg_match($session_name_regexp, $$sname, $matches);
                break;
            case 6:
                $session_name_regexp = "/^[a-z0-9,\-]{".($session_hash_alg ? "32" : "16,32")."}$/is";
                break;
            default:
                $session_name_regexp = "/^[a-f0-9]{".($session_hash_alg ? "27" : "16,32")."}$/s";
        }
        if (!nc_preg_match($session_name_regexp, $$sname))
                header("Location: /");
        $_GET[session_name()] = $$sname;
        $_POST[session_name()] = $$sname;
    }
    else {
        srand((double) microtime() * 1000000);
        $randval = rand();
        $session_id = md5(uniqid($randval));
        session_id($session_id);
    }

    if ($_SESSION['User']['IsLogin'] == "1") {
        if ($_SESSION['User']['IP'] != getenv("REMOTE_ADDR"))
                header("Location: /");
        if ((time() - $_SESSION['User']['datetime']) > ini_get('session.gc_maxlifetime')) {
            unset($_SESSION['User']);
            session_destroy();
        }
    }
    $_SESSION['User']['datetime'] = time();
}

$passed_thru_404 = true;

require $nc_core->ROOT_FOLDER.$action.".php";

// ob_start может вызываться два раза
ob_end_flush();
if ($nc_core->use_gzip_compression && $nc_core->gzip->check()) ob_end_flush();

if (isset($nccttimer) && is_object($nccttimer)) {
    $nccttimer->stop();
    $nccttimer->display();
    dump($db->groupped_queries);
}

 ?>

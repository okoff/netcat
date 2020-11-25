<?php

require_once("header.inc.php");

$UI_CONFIG = new ui_config_module_netshop('admin', 'import');

/**
 * Get CML version from importing file
 * @param filename importing file path
 * @return cml_version
 */
function nc_netshop_get_cml_version($filename) {
    global $INCLUDE_FOLDER;
    // check file existance
    if (!file_exists($filename)) return false;
    // get info from file
    $import_file = fopen($filename, "r");
    $first_string = fgets($import_file);
    $second_string = fgets($import_file);
    fclose($import_file);
    nc_preg_match("/<\?xml\s+.*?encoding=\"([\w\d-]+)\".*?\?>/is", $first_string, $matches);
    $reqex = "/<КоммерческаяИнформация\s+.*?ВерсияСхемы=\"([\d\.]+)\".*?/is";

    nc_preg_match($reqex, $second_string, $matches);
    $cml_version = isset($matches[1]) && str_replace(".", "", $matches[1]) >= 203 ? 2 : 1;
    // return version
    return $cml_version;
}

echo "<form method='post' enctype='multipart/form-data'>";
# new source (name)
if ($_POST['name']) {
    $db->query("INSERT INTO `Netshop_ImportSources`
      SET `name` = '".$db->escape($_POST["name"])."',
      `catalogue_id` = '".intval($catalogue_id)."',
      `scheme` = '".intval($cml_version)."'");
    $source_id = $db->insert_id;
}

$source_id = (int) $source_id;

if (($_FILES["upload"] || $_POST['ftp_path']) && $source_id) {
    if ($_FILES["upload"]) {
        # save file
        $filename = uniqid("importcml");
        move_uploaded_file($_FILES["upload"]["tmp_name"], $TMP_FOLDER.$filename);
    }
    if ($_POST['ftp_path'] && file_exists($TMP_FOLDER.$_POST['ftp_path'])) {
        $filename = $_POST['ftp_path'];
    }
    if ($filename) {
        # save settings
        $settings = array("nonexistant", "auto_add_sections", "auto_add_goods");
        $qry = array();
        foreach ($settings AS $i) {
            $qry[] = "`".$i."` = '".$db->escape($_POST[$i])."'";
        }
        $db->query("UPDATE `Netshop_ImportSources` SET ".join(", ", $qry)." WHERE `source_id` = '".$source_id."'");
    }
}

# get CML version from file
if ($filename && file_exists($TMP_FOLDER.$filename) && !$cml_version)
        $cml_version = nc_netshop_get_cml_version($TMP_FOLDER.$filename);

if (!intval($catalogue_id)) {
    # don't ask if there's no choice (only one shop)
    $res = $db->get_results("SELECT sd.`Catalogue_ID` AS id, sd.`Subdivision_Name` AS name
      FROM `Sub_Class` AS sc, `Subdivision` AS sd
      WHERE `Class_ID` = ".$GLOBALS['MODULE_VARS']['netshop']['SHOP_TABLE']."
      AND sc.`Subdivision_ID` = sd.`Subdivision_ID`
      ORDER BY sd.`Priority`", ARRAY_A);

    if ($db->num_rows == 1) $catalogue_id = $res[0]["id"];
}


switch (true) {
    # Новый источник
    case!$source_id:

        echo "<h2 style='font-size: 16px;'>".NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_TITLE."</h2><br />";

        if (!$catalogue_id) {
            echo "<div>".NETCAT_MODULE_NETSHOP_SHOP.":</div>";
            echo "<div>
                <select name='catalogue_id'>";
            foreach ($res AS $key => $value) {
                echo "<option value='".$value['id']."'>".$value['name']."</option>\r\n";
            }
            echo "</select><div><br />";
        }

        $res = $db->get_results("SELECT `source_id` AS id, `name`, `scheme` FROM `Netshop_ImportSources`", ARRAY_A);

        if ($db->num_rows) {

            $js_scheme_rel = array();
            if (!empty($res)) {
                foreach ($res as $value) {
                    $js_scheme_rel[] = "'".$value['id']."':'".$value['scheme']."'";
                }
            }

            echo "<div>".NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NAME.":</div>";
            echo "<div>";
            echo "<script type='text/javascript'>";
            echo "function nc_import_scheme_rel (id) {".
            "  var scheme_rel = {".join(",", $js_scheme_rel)."};".
            "  var scheme_selector = document.getElementById('cml_version');".
            "  scheme_selector.selectedIndex = scheme_rel[id];".
            "}";
            echo "</script>";
            echo "<select name='source_id' onchange='nc_import_scheme_rel(this.value)'>\r\n";
            if (!empty($res)) {
                foreach ($res as $key => $value) {
                    echo "<option value='".$value['id']."'".($key == 0 ? " selected" : "").">".$value['name']."</option>\r\n";
                }
            }
            echo "</select><div><br />";
        }

        echo "<div>".NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER.":</div><div>";
        $detect_cml_arr = array(
                0 => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_0,
                1 => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_1,
                2 => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_2
        );
        echo "<select name='cml_version' id='cml_version'>\n";
        foreach ($detect_cml_arr as $key => $value) {
            echo "<option value='".$key."'".($key == $res[0]['scheme'] ? " selected" : "").">".$value."</option>\n";
        }
        echo "</select>\n";
        echo "</div><br />";

        echo "<div><input type='checkbox' name='new_source' id='cbNew'>";
        echo "<label for='cbNew'>".NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NEW."</label>\r\n";
        echo "</div>";

        echo "<div>
                <input type='text' size='62' name='name' onkeyup=\"document.getElementById('cbNew').checked = (this.value ? true : false);\">
              </div>";
        break;

    # выбор файла для загрузки
    case!$filename:

        $row = $db->get_row("SELECT * FROM `Netshop_ImportSources` WHERE `source_id` = '".$source_id."'", ARRAY_A);

        $options = array("disable" => NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DISABLE,
                "ignore" => NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_IGNORE); //"delete"=> NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DELETE,

        echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_FILE_UPLOAD_TITLE."</b><br/><br/>\r\n";
        echo "<table cellpadding='5' cellspacing='1' style='width:auto; '>\r\n";
        echo "<tr><td style=''>\r\n".NETCAT_MODULE_NETSHOP_IMPORT_FILE."\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td style=''>\r\n<input type='file' id='netshop_xml_import_upload' name='upload' size='50'>\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td style=''>\r\n".NETCAT_MODULE_NETSHOP_IMPORT_FILE_FTP_PATH."\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td style=''>\r\n<input type='text' name='ftp_path' size='62' onkeyup=\"document.getElementById('netshop_xml_import_upload').disabled = (this.value ? true : false); document.getElementById('netshop_xml_import_upload').value = (this.value ? '' : document.getElementById('netshop_xml_import_upload').value);\">\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td style=''>\r\n".NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT."\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td style=''>\r\n<select name='nonexistant'>\r\n";
        foreach ($options AS $option => $text) {
            echo "<option value='".$option."'".($row["nonexistant"] == $option ? " selected" : "").">".htmlspecialchars($text)."</option>\r\n";
        }
        echo "</select>\r\n</td>\r\n</tr>\r\n";
        echo "</table>\r\n";

        break;

    # source
    default:
        if ($nonexistant == 'disable') {
            $res = $db->get_row("SELECT DISTINCT c.`Class_ID` AS id
          FROM `Class` AS c, `Field` AS f
          WHERE c.`Class_ID` = f.`Class_ID`
          AND f.`Field_Name` LIKE 'Price%'
          AND  (c.Class_Group='Netshop' OR c.Class_Group='".NETCAT_MODULE_NETSHOP_TITLE."')
          ORDER BY c.`Class_ID`", ARRAY_A);

            foreach ($res AS $id) {
                $db->query("UPDATE `Message".$id."` SET `Checked` = 0 WHERE `ImportSourceID` = '".$source_id."'");
            }
        }
        require("import/commerceml".($cml_version == 2 ? "2" : "").".php");
}

# hidden save
$save = array("catalogue_id", "source_id", "filename", "current_num", "cml_version");

foreach ($save AS $i) {
    if ($$i)
            echo "<input type='hidden' name='".$i."' value='".htmlspecialchars($$i)."'>\r\n";
}

$UI_CONFIG->actionButtons[] = array("id" => "submit",
        "caption" => NETCAT_MODULE_NETSHOP_NEXT,
        "action" => "mainView.submitIframeForm()");

echo "</form>";

EndHtml();
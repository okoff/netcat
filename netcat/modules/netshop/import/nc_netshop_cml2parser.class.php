<?php

/**
 * CML2 import class
 */
class nc_netshop_cml2parser {

    var $source_id, $catalogue_id, $everything_clear;

    function nc_netshop_cml2parser(&$db, &$_UTFConverter, $source_id, $catalogue_id, $filename, $quite = false) {
        // system superior object
        $nc_core = nc_Core::get_object();
        $this->nc_core = $nc_core;
        global $MODULE_FOLDER;
        if ($this->nc_core->NC_UNICODE) {
            require_once $MODULE_FOLDER."netshop/ru_utf8.lang.php";
        } else {
            require_once $MODULE_FOLDER."netshop/ru_cp1251.lang.php";
        }
        // get netshop module's vars
        $this->MODULE_VARS = $nc_core->modules->get_vars('netshop');

        // set variables
        $this->db = $db;
        $this->uniconv = $_UTFConverter;
        $this->filedir = $GLOBALS['TMP_FOLDER'];
        $this->filename = $filename;
        $this->filename_path = $this->filedir.$this->filename;
        $this->source_id = intval($source_id);
        $this->catalogue_id = intval($catalogue_id);
        $this->everything_clear = true;
        //
        $this->not_mapped_sections = 0;
        $this->not_mapped_fields = 0;
        $this->not_mapped_packets = 0;
        $this->quite = $quite;
        $this->debug = true;

        $this->import_ignore_tags = array(); //"Группы", "ЗначенияРеквизитов", "СтавкиНалогов"
        // cml2 class
        require_once($GLOBALS['MODULE_FOLDER']."netshop/import/cml2.class.php");
        $this->cml2 = new cml2();

        // Load currencies, units
        $res = $this->db->get_results("SELECT `ShopCurrency_ID`, `ShopCurrency_Name` FROM `Classificator_ShopCurrency`", ARRAY_A);
        foreach ($res AS $value) {
            $this->currency[$value['ShopCurrency_Name']] = $value['ShopCurrency_ID'];
        }
        $res = $this->db->get_results("SELECT `ShopUnits_ID`, `ShopUnits_Name` FROM `Classificator_ShopUnits`", ARRAY_A);
        foreach ($res AS $value) {
            $this->units[$value['ShopUnits_Name']] = $value['ShopUnits_ID'];
        }

        // Get list of goods templates
        $this->shop_classes = $this->db->get_results("SELECT DISTINCT c.`Class_ID` AS id, c.`Class_Name` AS name
      FROM `Class` AS c
			LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
      WHERE f.`Field_Name` LIKE 'Price%'
      AND c.`Class_ID` IN (".$this->MODULE_VARS['GOODS_TABLE'].")
      ORDER BY c.`Class_ID`", ARRAY_A);

        // shop's subdivision data
        $this->shop = GetSubdivisionByType($this->MODULE_VARS["SHOP_TABLE"], "Subdivision_ID, Subdivision_Name", $this->catalogue_id);

        // logging
        if ($this->debug) {
            $this->debug("==================================================");
            $this->debug(__METHOD__." OK - source[".$source_id."], catalog[".$catalogue_id."], filename[".$filename."]");
        }
    }

    function debug($text, $clear = false) {
        // log message
        return file_put_contents($GLOBALS['TMP_FOLDER']."/1c8debug.log", date("Y-m-d H:i:s").' - '.$text.PHP_EOL, (!$clear ? FILE_APPEND : NULL));
    }

    function get_templates() {
        // data templates (classes)
        $sub_ids = array();
        // get data
        $sub_structure = $this->cache_data_out("sub_structure");
        // if template is unknown, get it
        if ($sub_structure) {
            foreach ($sub_structure as $value) {
                if ($value['Subdivision_ID'])
                        $sub_ids[] = $value['Subdivision_ID'];
            }
        }
        else {
            $sub_ids = $this->db->get_col("SELECT `value`
        FROM `Netshop_ImportMap`
        WHERE `source_id` = '".$this->source_id."'
        AND `type` = 'section'");
        }

        if (!empty($sub_ids)) {
            $res = $this->db->get_results("SELECT `Subdivision_ID`, `Class_ID`, `Sub_Class_ID`
        FROM `Sub_Class`
        WHERE `Subdivision_ID` IN (".join(",", $sub_ids).")
        ORDER BY `Priority` DESC", ARRAY_A);
            foreach ($res AS $value) {
                $templates[$value['Subdivision_ID']]['class_id'] = $value['Class_ID'];
                $templates[$value['Subdivision_ID']]['subclass_id'] = $value['Sub_Class_ID'];
            }
        }

        // cache file
        $this->cache_data_in($templates, "templates");

        // logging
        if ($this->debug) $this->debug(__METHOD__." OK");

        // return result
        return $templates;
    }

    /**
     * Update source in base
     * @return true if all good
     */
    function update_sources() {
        // get data
        $catalogue_data_properties = $this->cache_data_out("catalogue_data_properties");

        // return if no data found
        if (empty($catalogue_data_properties)) return false;

        // get properties
        $this_id = $catalogue_data_properties[NETCAT_MODULE_NETSHOP_1C_ID];
        $this_classificator_id = $catalogue_data_properties[NETCAT_MODULE_NETSHOP_1C_CLASSIFICATOR_ID];
        $this_name = trim($catalogue_data_properties[NETCAT_MODULE_NETSHOP_1C_NAME]);

        // actual `external_id`
        $actual_external_id = $this_classificator_id." ".$this_id;

        // get `external_id`
        $external_id = $this->db->get_var("SELECT `external_id`
			FROM `Netshop_ImportSources`
			WHERE `source_id` = '".$this->source_id."'");

        // another catalog import attempt
        if ($external_id && $external_id != $actual_external_id) {
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__." FAIL - another catalog import attempt");
            // return
            return false;
        }

        // update sources
        $this->db->query("UPDATE `Netshop_ImportSources`
      SET `external_id` = '".$actual_external_id."'
      WHERE `source_id` = '".$this->source_id."'");

        // logging
        if ($this->debug) $this->debug(__METHOD__." OK");

        // true or false
        return true;
    }

    /**
     * Put cache file in temp directory /netcat/tmp/
     * @param array to serialise
     * @param string suffix for file name
     * @return bytes in cached file or false
     */
    function cache_data_in(&$arr, $suffix) {
        // check
        if (empty($arr)) return false;

        $_path = $this->filename_path.".".$suffix.".cache";
        // make serialise
        $cached = serialize($arr);

        // write cache
        $bytes_writed = file_put_contents($_path, $cached);

        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$suffix."') OK - ".$bytes_writed." bytes writed");

        // return bytes in file or false
        return $bytes_writed;
    }

    /**
     * Get array from cache file in temp directory /netcat/tmp/
     * @param string suffix for file name
     * @return unserialise array or false
     */
    function cache_data_out($suffix) {
        $_path = $this->filename_path.".".$suffix.".cache";
        // check file existence
        if (!file_exists($_path)) return false;
        // get contents from file
        $data = file_get_contents($_path);
        // get array from serialise data
        $arr = unserialize($data);
        if (!$this->nc_core->NC_UNICODE)
                $arr = $this->nc_core->utf8->array_utf2win($arr);
        //echo $suffix;
        //dump($arr);
        // logging
        if ($this->debug) $this->debug(__METHOD__."('".$suffix."') OK");

        // return return array or false
        return!empty($arr) ? $arr : false;
    }

    /**
     * Delete cached file(s) in temp directory /netcat/tmp/
     * @param mixed file(s) to delete
     * @return true or false
     */
    function cache_data_destroy($suffix = "") {
        // deleted files
        $result = 0;

        // open folder
        if ($handle = opendir($this->filedir)) {
            // walk
            while (( $file = readdir($handle) ) !== false) {
                // determine file
                if (preg_match("/^".$this->filename."\.".$suffix."/is", $file)) {
                    // file path
                    $_path = $this->filedir.$file;
                    // delete
                    if (file_exists($_path) && unlink($_path)) $result++;
                }
                // oldest file
                if (
                        preg_match("/^importcml/is", $file) &&
                        !strstr($file, $this->filename)
                ) {
                    // file path
                    $_path = $this->filedir.$file;
                    // delete
                    if (file_exists($_path)) unlink($_path);
                }
            }
            // close folder
            closedir($handle);
        }

        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$suffix."') OK - ".$result." files deleted");

        // return result
        return $result;
    }

    /**
     * Check cached file(s) in temp directory /netcat/tmp/
     * @return true if any file cached or false
     */
    function cache_data_exist($suffix = "") {
        // deleted files
        $result = 0;

        // open folder
        if ($handle = opendir($this->filedir)) {
            // walk
            while (( $file = readdir($handle) ) !== false) {
                // determine file
                if (preg_match("/^".$this->filename."\.".$suffix."/is", $file))
                        $result++;
            }
            // close folder
            closedir($handle);
        }

        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$suffix."') OK - ".$result." files exist");

        // return result
        return $result;
    }

    /**
     * Count cached file(s) into the temp directory /netcat/tmp/
     *
     * @param string file suffix (for the multipart cache)
     *
     * @return integer counted value
     */
    function cache_data_count($suffix = "") {
        // counted value
        $result = 0;

        // open folder
        if ($handle = opendir($this->filedir)) {
            // walk
            while (( $file = readdir($handle) ) !== false) {
                // determine file
                if (preg_match("/^".$this->filename."\.".$suffix."/is", $file))
                        $result++;
            }
            // close folder
            closedir($handle);
        }

        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$suffix."') OK - ".$result." files counted");

        // return counted value
        return $result;
    }

    /**
     * Get classifier data function
     */
    function get_classifier_data() {
        // file existence
        if (!file_exists($this->filename_path)) {
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__." FAIL - file ".$this->filename_path." does't exist");
            // return
            return false;
        }

        $classifier_data = $this->cml2->get_groups($this->filename_path);

        // cache file
        $bytes_writed = $this->cache_data_in($classifier_data, "classifier_data");

        // logging
        if ($this->debug)
                $this->debug(__METHOD__."() OK - ".$bytes_writed." bytes cached");
    }

    function import_classifier_data() {
        // get data
        $classifier_data = $this->cache_data_out("classifier_data");
        $sub_structure = $this->cache_data_out("sub_structure");

        // return if no data found
        if (empty($classifier_data)) {
            // logging
            if ($this->debug) $this->debug(__METHOD__."() FAIL - no data");
            // return
            return false;
        }

        $parent_sub_id = $this->shop["Subdivision_ID"];

        // after map_sections_dialog(), this arrays is accessible
        if (isset($_POST['map_groups'])) {
            if (!$this->nc_core->NC_UNICODE)
                    $_POST['map_groups'] = $this->nc_core->utf8->array_win2utf($_POST['map_groups']);
            $map_groups = $_POST['map_groups'];
            // cache file
            $this->cache_data_in($map_groups, "map_groups");
        }
        else {
            // get data
            $map_groups = $this->cache_data_out("map_groups");
        }
        if (isset($_POST['new_group'])) {
            $this->new_group = $_POST['new_group'];
            // cache file
            $this->cache_data_in($this->new_group, "new_group");
        } else {
            // get data
            $this->new_group = $this->cache_data_out("new_group");
        }

        $current_num = 1;
        $total_objects = count($classifier_data);

        foreach ($classifier_data as $key => $value) {

            if (!$this->quite && $current_num == 1 && $map_groups) {
                echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_CATALOGUE_STRUCTURE."</b><br/>\r\n";
                $this->progress_bar_show("structure_progress");
                echo "<br/>\r\n";
            }

            $parent_gid = $value['parent_gid'];
            $group_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
            $name = $value[NETCAT_MODULE_NETSHOP_1C_NAME];
            $name = trim($name);

            if ($map_groups[$group_id])
                    $template_id = (int) $this->new_group[$group_id]["template"];

            switch (true) {
                // ignore this group
                case ($map_groups[$group_id] == -1):
                    $sub_id = -1;
                    $parent_sub_id = isset($parent_gid) && $sub_structure[$parent_gid] ? $sub_structure[$parent_gid]["Subdivision_ID"] : $this->shop["Subdivision_ID"];
                    break;
                // new group posted
                case ($map_groups[$group_id] == "new"):
                    // если у раздела есть родители в дзен-массиве то ставим соответствие с Subdivision_ID из массива $parent
                    $parent_sub_id = isset($parent_gid) && $sub_structure[$parent_gid] ? $sub_structure[$parent_gid]["Subdivision_ID"] : $this->shop["Subdivision_ID"];

                    // заменяем любой символ, не образующий "слово", и ставим заглавные буквы в начале каждого слова
                    $english_name = nc_preg_replace("/\W+/", "", ucwords(nc_transliterate($name)));

                    if (!$parent_data[$parent_sub_id]) {
                        $parent_data[$parent_sub_id] = $this->db->get_row("SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = '".$parent_sub_id."'", ARRAY_A);
                    }

                    $priority = (int) $this->db->get_var("SELECT MAX(`Priority`) + 1 FROM `Subdivision` WHERE `Parent_Sub_ID` = '".$parent_sub_id."'");

                    // если EnglishName повторяется, добавляем дополнительный индекс
                    $english_name_suffix = "";
                    while ($this->db->get_var("SELECT COUNT(*) FROM `Subdivision` WHERE `Parent_Sub_ID` = '".$parent_sub_id."' AND `EnglishName` = '".$english_name.$english_name_suffix."'")) {
                        $english_name_suffix += 1;
                    }
                    $english_name.= (string) $english_name_suffix;

                    $hidden_url = $parent_data[$parent_sub_id]['Hidden_URL'].$english_name."/";

                    // добавляем запись в базу
                    $this->db->query("INSERT INTO `Subdivision`
            SET `Catalogue_ID` = '".$this->catalogue_id."',
            `Parent_Sub_ID` = '".$parent_sub_id."',
            `Subdivision_Name` = '".$this->db->escape($name)."',
            `Template_ID` = 0,
            `EnglishName` = '".$english_name."',
            `LastUpdated` = NOW(),
            `Created` = NOW(),
            `Hidden_URL` = '".$hidden_url."',
            `Priority` = '".$priority."',
            `Checked` = 1");
                    //$this->db->debug();
                    // Subdivision_ID
                    $sub_id = $this->db->insert_id;

                    $this->db->query("INSERT INTO Sub_Class
            SET `Subdivision_ID` = '".$sub_id."',
            `Class_ID` = '".$template_id."',
            `Sub_Class_Name` = '".$this->db->escape($name)."',
            `EnglishName` = '".$english_name."',
            `Priority` = 0,
            `Checked` = 1,
            `Catalogue_ID` = '".$this->catalogue_id."',
            `DefaultAction` = 'index',
            `Created` = NOW(),
            `LastUpdated` = NOW()");
                    //$this->db->debug();

                    $sub_class_id = $this->db->insert_id;

                    // import map
                    $this->db->query("REPLACE INTO `Netshop_ImportMap`
            SET `source_id` = '".$this->source_id."',
            `type` = 'section',
            `source_string` = '".$this->db->escape($group_id)."',
            `value` = '".$sub_id."'");
                    //$this->db->debug();
                    break;

                case ( int($map_groups[$group_id]) ):
                    $sub_id = (int) $map_groups[$group_id];
                    // Sub_Class_ID value for $this->sub_structure array
                    $sub_class_id = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` = '".$template_id."' AND `Subdivision_ID` = '".$sub_id."'");
                    // указано соответствие
                    $this->db->query("REPLACE INTO `Netshop_ImportMap`
            SET `source_id` = '".$this->source_id."',
            `type` = 'section',
            `source_string` = '".$this->db->escape($group_id)."',
            `value` = '".$sub_id."'");
                    break;

                default:
                    // Найти соответствие разделу (по внешнему идентификатору)
                    $sub_id = $this->db->get_var("SELECT m.`value`
            FROM `Netshop_ImportMap` AS m, Subdivision AS s
            WHERE m.`type` = 'section'
            AND m.`source_string` = '".$this->db->escape($group_id)."'
            AND m.`source_id` = '".$this->source_id."'
            AND m.`value` = s.`Subdivision_ID`
            ORDER BY m.`source_id` = '".$this->source_id."' DESC");
                    // спросить потом
                    if (!$sub_id) $this->not_mapped_sections++;
            }
            $sub_structure[$group_id] = array(
                    // imperative
                    "Subdivision_ID" => $sub_id,
                    "Sub_Class_ID" => $sub_class_id,
                    // optional for default and selected compliance Subdivision
                    "Parent_Sub_ID" => $parent_sub_id,
                    "Name" => $name
            );
            // procents completed
            $percent = intval($current_num / $total_objects * 100);
            $this->progress_bar_update("structure_progress", $percent);
            // increment
            $current_num++;
        }
        if (!$this->nc_core->NC_UNICODE)
                $sub_structure = $this->nc_core->utf8->array_win2utf($sub_structure);
        // cache file
        if (!empty($sub_structure)) {
            $bytes_writed = $this->cache_data_in($sub_structure, "sub_structure");
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__."() OK - 'sub_structure' data ".$bytes_writed." bytes cached");
            // return
            return true;
        }
        else {
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__."() FAIL - no 'sub_structure' data");
            // return
            return false;
        }
    }

    function map_sections_dialog() {

        echo "<style type='text/css'>";
        echo ".divadd {border:1px solid #DDD; background-color:#F0F0F0; padding:3px;}";
        echo "select {width:auto}";
        echo "</style>";

        echo "<script type='text/javascript'>";
        echo "function switch_divadd(gid) {";
        echo "  var sel = document.getElementById('map_groups' + gid);";
        echo "  var val = sel.options[sel.selectedIndex].value;";
        echo "  document.getElementById('divadd' + gid).style.display = (val=='new' ? '' : 'none');";
        echo "}";
        echo "</script>";

        $this->everything_clear = false;

        $sections = GetStructure($this->shop["Subdivision_ID"], "Checked = 1");

        $sections_as_options = "";
        foreach ($sections AS $row) {
            $sections_as_options.= "<option value='".$row['Subdivision_ID']."'>".str_repeat("&nbsp;", ($row["level"] + 1) * 4).$row['Subdivision_Name']."</option>\r\n";
        }

        // Ask about groups we don't know
        echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_MAP_SECTION.":</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        $templates_count = count($this->shop_classes);
        $this->goods_template_ids = array();

        $templates_as_options = "";
        // netshop goods classes
        foreach ($this->shop_classes AS $value) {
            $templates_as_options.= "<option value=".$value['id'].(!$templates_as_options ? " selected" : "").">".$value['name']."</option>\r\n";
            $this->goods_template_ids[] = $value['id'];
        }

        // get data
        $sub_structure = $this->cache_data_out("sub_structure");
        // group -> sub list
        if (is_array($sub_structure) && !empty($sub_structure)) {
            foreach ($sub_structure as $gid => $group) {
                if (!$group['Subdivision_ID']) {
                    $parent = $group['Parent_Sub_ID'];
                    if (!$parent) $parent = "[root]";
                    echo "<tr valign=top>";
                    echo "<td title='".$gid." &larr; ".$parent."'>".$group['Name']."</td>";
                    echo "<td>&rarr;</td>";
                    echo "<td>";
                    echo "<select name='map_groups[".$gid."]'".($templates_count > 1 ? " onchange='switch_divadd(\"".$gid."\")'" : "")." id='map_groups".$gid."'>";
                    echo "<option value='new' style='color:navy'>".NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION.($templates_count > 1 ? " &nbsp; &darr; &nbsp;" : "")."</option>";
                    echo "<option value='-1'>".NETCAT_MODULE_NETSHOP_IMPORT_IGNORE_SECTION."</option>";
                    echo "<option value='-1'>----------------------------------------</option>";
                    echo $sections_as_options;
                    echo "</select>";
                    echo "</td>";
                    echo "<td>";
                    echo "<div class='divadd' id='divadd".$gid."'".($templates_count == 1 ? " style='display:none'" : "").">";
                    echo NETCAT_MODULE_NETSHOP_IMPORT_TEMPLATE.":&nbsp;<select name='new_group[".htmlspecialchars($gid)."][template]'>\r\n";
                    echo $templates_as_options;
                    echo "</select>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
        }

        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__."() OK");
    }

    function get_offers_data_callback($level = 0, $data) {
        // callbacks iterator
        static $i = 0;

        $offers_data_offers = array();
        $offers_data_offers_fields = $this->cache_data_out("offers_data_offers_fields");
        if (!$this->nc_core->NC_UNICODE)
                $data = $this->nc_core->utf8->array_utf2win($data);
        // walking...
        foreach ($data as $key => $value) {

            $index = $key + $level;

            foreach ($value as $name => $row) {
                /**
                 * $child['n'] - name
                 * $child['d'] - data
                 * $child['c'] - data array
                 */
                // put data in array
                if (!in_array($name, $this->import_ignore_tags) && !is_array($row)) {
                    $offers_data_offers[$index][$name] = $row;
                    $offers_data_offers_fields[$name]++;
                    continue;
                }
                // prices(s)_id
                if ($name == NETCAT_MODULE_NETSHOP_1C_PRICES) {
                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_PRICE])) {
                        $offers_data_offers[$index][$name] = $row;
                    } else {
                        $offers_data_offers[$index][$name][NETCAT_MODULE_NETSHOP_1C_PRICE][] = $row;
                    }

                    $offers_data_offers_fields[$name]++;
                    continue;
                }
            }
        }
        if (!$this->nc_core->NC_UNICODE) {
            $offers_data_offers = $this->nc_core->utf8->array_win2utf($offers_data_offers);
            $offers_data_offers_fields = $this->nc_core->utf8->array_win2utf($offers_data_offers_fields);
        }
        // store data
        $bytes_writed = $this->cache_data_in($offers_data_offers, "offers_data_offers".$i);
        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$level."') OK - 'offers_data_offers".$i."' data ".$bytes_writed." bytes cached");

        $bytes_writed = $this->cache_data_in($offers_data_offers_fields, "offers_data_offers_fields");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$level."') OK - 'offers_data_offers_fields' data ".$bytes_writed." bytes cached");

        // callbacks iterator
        $i++;

        // continue callback
        return;
    }

    /**
     * This function get catalogue data
     */
    function get_offers_data() {
        // file existence
        if (!file_exists($this->filename_path)) {
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__."() FAIL - file ".$this->filename_path." does't exist");
            // return
            return false;
        }

        // get offers (returned values)
        $offers_data_properties = $this->cml2->get_offers($this->filename_path, $this, 'get_offers_data_callback');

        if (!$this->nc_core->NC_UNICODE) {
            $offers_data_properties = $this->nc_core->utf8->array_utf2win($offers_data_properties);
        }
        $offers_data_price_type_arr = $offers_data_properties[NETCAT_MODULE_NETSHOP_1C_PRICES_TYPE];
        $offers_data_price_type = $offers_data_price_type_arr[NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE];
        if (!$this->nc_core->NC_UNICODE) {
            $offers_data_properties = $this->nc_core->utf8->array_win2utf($offers_data_properties);
            $offers_data_price_type = $this->nc_core->utf8->array_win2utf($offers_data_price_type);
        }
        // store price types
        $bytes_writed = $this->cache_data_in($offers_data_price_type, "offers_data_price_type");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$level."') OK - 'offers_data_price_type' data ".$bytes_writed." bytes cached");

        // store offers properties
        $bytes_writed = $this->cache_data_in($offers_data_properties, "offers_data_properties");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__."('".$level."') OK - 'offers_data_properties' data ".$bytes_writed." bytes cached");
    }

    function import_offers_data() {
        // clear?
        //if (!$this->everything_clear) return false;
        // get data
        $offers_data_price_type = $this->cache_data_out("offers_data_price_type");
        // return if no data found
        if (empty($offers_data_price_type)) {
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__."() FAIL - no data 'offers_data_price_type'");
            // return
            return false;
        }

        if (!$this->templates) $this->templates = $this->get_templates();

        // import price type
        $packets = array();
        if ($offers_data_price_type[0])
                foreach ($offers_data_price_type as $key => $value) {

                $offers_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
                $name = $value[NETCAT_MODULE_NETSHOP_1C_NAME];
                $name = trim($name);

                $packets[$offers_id]["name"] = $name;
                //if (!$this->nc_core->NC_UNICODE) $_POST['map_packets'] = $this->nc_core->utf8->array_win2utf($_POST['map_packets']);
                $map_packet = $_POST['map_packets'][urlencode($offers_id)];
                // write compliance (second pass)
                if ($map_packet) {
                    $this->db->query("REPLACE INTO `Netshop_ImportMap`
          SET `source_id` = '".$this->source_id."',
          `type` = 'price',
          `source_string` = '".$this->db->escape($offers_id)."',
          `value` = '".$this->db->escape($map_packet)."'");
                    //$this->db->debug();
                }
            } else {
            $offers_id = $offers_data_price_type[NETCAT_MODULE_NETSHOP_1C_ID];
            $name = trim($offers_data_price_type[NETCAT_MODULE_NETSHOP_1C_NAME]);

            $packets[$offers_id]["name"] = $name;
            $map_packet = $_POST['map_packets'][urlencode($offers_id)];
            if ($map_packet) {
                $this->db->query("REPLACE INTO `Netshop_ImportMap`
          SET `source_id` = '".$this->source_id."',
          `type` = 'price',
          `source_string` = '".$this->db->escape($offers_id)."',
          `value` = '".$this->db->escape($map_packet)."'");
            }
        }
        // destroy variable
        unset($offers_data_price_type);

        #// destroy old file, information may be updated
        $this->cache_data_destroy("offers_data_packets_arr");
        // count packets from base for "price" type
        if (!empty($packets)) {
            $packets_res = $this->db->get_results("SELECT `source_string` AS id, value
        FROM `Netshop_ImportMap`
        WHERE `source_id` = '".$this->source_id."'
        AND `type` = 'price'
        AND `source_string` IN ('".join("','", array_keys($packets))."')", ARRAY_A);
            //$this->db->debug();
            $packets_from_base = $this->db->num_rows;
        }
        // mapped packets
        if (!empty($packets_res)) {
            foreach ($packets_res AS $value) {
                $packets[$value['id']]["column"] = $value['value'];
            }
        }
        // not_mapped_packets value
        $this->not_mapped_packets = count($packets) - $packets_from_base;
        // for map_packets_dialog function
        // cache files
        if (!$this->nc_core->NC_UNICODE)
                $packets = $this->nc_core->utf8->array_win2utf($packets);
        $this->cache_data_in($packets, "offers_data_packets_arr");

        // get data from cache file
        $this->offers_data_offers_fields = $this->cache_data_out("offers_data_offers_fields");
        // action)
        if (!empty($this->offers_data_offers_fields)) {
            $need_tags = array(NETCAT_MODULE_NETSHOP_1C_QTY);
            foreach ($this->offers_data_offers_fields AS $xml_tag => $tag_count) {
                $xml_tag = trim($xml_tag);
                if (!in_array($xml_tag, $need_tags)) continue;
                if (!$xml_tag) continue;

                $fields[$xml_tag]['name'] = $xml_tag;
                foreach ($this->shop_classes AS $class) {
                    $map_field = $_POST['map_fields'][$class['id']][urlencode($xml_tag)];
                    // write compliance (second pass)
                    if ($map_field) {
                        $this->db->query("REPLACE INTO `Netshop_ImportMap`
              SET `source_id` = '".$this->source_id."',
              `type` = 'oproperty',
              `source_string` = '".$this->db->escape($xml_tag)."',
              `value` = '".$this->db->escape($map_field)."'");
                    }
                }
            }
            // destroy variable
            unset($this->offers_data_offers_fields);
        }
        // count fields from base for "oproperty" type
        if (!empty($fields)) {
            $fields_res = $this->db->get_results("SELECT `source_string` AS id, `value`
        FROM `Netshop_ImportMap`
        WHERE `source_id` = '".$this->source_id."'
        AND `type` = 'oproperty'
        AND `source_string` IN ('".join("','", array_keys($fields))."')", ARRAY_A);
            $fields_from_base = $this->db->num_rows;
        }
        // mapped fields
        if (!empty($fields_res)) {
            foreach ($fields_res AS $value) {
                $fields[$value['id']]['column'] = $value['value'];
            }
        }
        // not_mapped_fields value
        $this->not_mapped_fields = count($fields) - $fields_from_base;
        // for map_fields_dialog function
        $this->not_mapped_fields_arr = is_array($this->not_mapped_fields_arr) ? array_merge($this->not_mapped_fields_arr, $fields) : $fields;

        // if not_mapped values - return and call dialog(s)
        if ($this->not_mapped_packets || $this->not_mapped_fields || !$this->everything_clear)
                return false;

        // count components in this source
        foreach ($this->templates AS $data) {
            $messages_tbl[$data["class_id"]] = "`Message".$data["class_id"]."`";
            $this_class = $data["class_id"];
        }

        $current_num = 1;
        $total_objects = 0;
        $total_files = $this->cache_data_count("offers_data_offers(\d)*?\.cache");
        $i = 0;
        while ($offers_data_offers = $this->cache_data_out("offers_data_offers".$i)) {
            // once count total objects
            if (!$total_objects) {
                $total_objects = $total_files * count($offers_data_offers);
            }

            foreach ($offers_data_offers as $key => $value) {

                if (!$this->quite && $current_num == 1) {
                    echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_OFFERS."</b><br/>\r\n";
                    $this->progress_bar_show("offers_progress");
                    echo "<br/>\r\n";
                }

                $this_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
                $this_name = trim($value[NETCAT_MODULE_NETSHOP_1C_NAME]);
                $this_currency_arr = $value[NETCAT_MODULE_NETSHOP_1C_PRICES][NETCAT_MODULE_NETSHOP_1C_PRICE];
                // if components in source > 1,
                //check commodity in all components
                if (!empty($messages_tbl) && count($messages_tbl) > 1) {
                    $sub_id = $this->db->get_var("SELECT `Subdivision_ID`
						FROM ".join(", ", $messages_tbl)."
						WHERE `ItemID` = '".$this_id."'
						AND `ImportSourceID` = '".$this->source_id."'");
                    $this_class = $this->templates[$sub_id]["class_id"];
                }
                if (empty($this_currency_arr[0])) {
                    $this_currencies[$this_currency_arr[NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE_ID]] = $this_currency_arr;
                } else {
                    $this_currencies = array();
                    foreach ($this_currency_arr AS $object_currency) {
                        $this_currencies[$object_currency[NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE_ID]] = $object_currency;
                    }
                }
                // commodity currency
                foreach ($packets AS $packet_id => $packet) {

                    if (!$packet["column"] || $packet["column"] == -1) continue;
                    // get price
                    $this_prop[$packet["column"]] = $this_currencies[$packet_id][NETCAT_MODULE_NETSHOP_1C_PRICE_UNIT];
                    // get currency
                    $this_currency = $this_currencies[$packet_id][NETCAT_MODULE_NETSHOP_1C_CURRENCY];

                    if ($this_currency == NETCAT_MODULE_NETSHOP_1C_CURRENCY_DEFAULT)
                            $this_currency = "RUR";

                    // check if currency exists, else add it
                    if (!$this_currency) {
                        $this_currency = "RUR";
                    } else if (!$this->currency[$this_currency]) {
                        $this->db->query("INSERT INTO `Classificator_ShopCurrency` SET `ShopCurrency_Name` = '".$this_currency."'");
                        $this->currency[$this_currency] = $this->db->insert_id;
                    }
                    // !!! TODO: check (a) price column exists in template
                    $currency_add = str_replace("Price", "", $packet["column"]);
                    $this_prop["Currency".$currency_add] = $this->currency[$this_currency];
                }
                // additional fields compile
                $filetable_lastid = 0;
                if (!empty($fields)) {
                    foreach ($fields AS $field_key => $field_data) {
                        if ($field_data['column'] == -1) continue;
                        list($field_name, $field_type) = $this->db->get_row("SELECT `Field_Name`, `TypeOfData_ID` FROM `Field`
							WHERE `Field_ID` = '".(int) $field_data['column']."'", ARRAY_N);
                        $this_prop[$field_name] = trim($value[$field_key]);
                    }
                }

                // collect fields in temp array
                if (!empty($this_prop)) {
                    $query = array();
                    foreach ($this_prop AS $k => $v) {
                        $query[] = "`".$k."` = '".$this->db->escape($v)."'";
                    }
                    // equip MySQL append query
                    $query_str = "SET ".join(", ", $query);
                    unset($query);
                }

                // update object
                $this->db->query("UPDATE `Message".$this_class."` ".$query_str." WHERE `ItemID` = '".$this_id."' AND `ImportSourceID` = '".$this->source_id."'");
                //$this->db->debug();
                // procents completed
                $percent = intval($current_num / $total_objects * 100);
                $this->progress_bar_update("offers_progress", $percent);
                // increment
                $current_num++;
            }
            $i++;
        }
        // set 100% complete
        $this->progress_bar_update("offers_progress", 100);

        // logging
        if ($this->debug) $this->debug(__METHOD__."() OK");
    }

    function map_packets_dialog() {

        $this->everything_clear = false;

        echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_MAP_PRICE.":</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        $res = $this->db->get_results("SELECT f.`Field_Name` AS id, f.`Description` AS name
      FROM `Class` AS c
			LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
      WHERE f.`Field_Name` LIKE 'Price%'
      AND c.`Class_ID` = '".intval($this->MODULE_VARS['GOODS_TABLE'])."'
      GROUP BY f.`Field_Name`", ARRAY_A);

        $price_col_options = "";
        if (!empty($res)) {
            foreach ($res AS $value) {
                $price_col_options.= "<option value='".$value['id']."'>[".$value['id']."] ".$value['name']."</option>\r\n";
            }
        }

        // get data
        $offers_data_packets_arr = $this->cache_data_out("offers_data_packets_arr");
        // action)
        if (!empty($offers_data_packets_arr)) {
            foreach ($offers_data_packets_arr as $key => $value) {
                if (!$value['column']) {
                    echo "<tr>";
                    echo "<td>".$value['name']."</td><td>&rarr;</td>";
                    echo "<td><select name='map_packets[".urlencode($key)."]'>";
                    echo "<option value='-1'>----------------------------------------</option>";
                    echo $price_col_options;
                    echo "</select></td>";
                    echo "</tr>";
                }
            }
        }

        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__." OK");
    }

    function get_catalogue_data_callback($level = 0, $data) {
        // callbacks iterator
        static $i = 0;
        if (!$this->nc_core->NC_UNICODE)
                $data = $this->nc_core->utf8->array_utf2win($data);
        $catalogue_data_commodity = array();
        // collect recursive
        $catalogue_data_commodity_fields = $this->cache_data_out("catalogue_data_commodity_fields");
        $catalogue_data_commodity_characteristics = $this->cache_data_out("catalogue_data_commodity_characteristics");
        $catalogue_data_commodity_requisites = $this->cache_data_out("catalogue_data_commodity_requisites");
        $catalogue_data_commodity_tax = $this->cache_data_out("catalogue_data_commodity_tax");

        // walking...
        foreach ($data as $key => $value) {

            $index = $key + $level;

            foreach ($value as $name => $row) {
                /**
                 * $child['n'] - name
                 * $child['d'] - data
                 * $child['c'] - data array
                 */
                // put data in array, skip ignore tags
                if (!in_array($name, $this->import_ignore_tags) && !is_array($row)) {
                    $catalogue_data_commodity[$index][$name] = $row;
                    $catalogue_data_commodity_fields[$name]++;
                    continue;
                }
                // group(s)_id
                if ($name == NETCAT_MODULE_NETSHOP_1C_GROUPS) {
                    // [Группы] => Array([NETCAT_MODULE_NETSHOP_1C_NAME] => 12345678-a6b7-11de-9109-0025563c5a06)
                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_GROUP])) {
                        $catalogue_data_commodity[$index][$name] = $row;
                    } else {
                        $catalogue_data_commodity[$index][$name][NETCAT_MODULE_NETSHOP_1C_GROUP][] = $row;
                    }

                    // do not show this
                    ###$catalogue_data_commodity_fields[$name]++;
                    continue;
                }
                // characteristics
                if ($name == NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS) {
                    if (!$catalogue_data_commodity_characteristics)
                            $catalogue_data_commodity_characteristics = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR]);
                    }

                    foreach ($row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR] as $v) {
                        $_name = "";
                        $_value = "";
                        foreach ($v as $_n => $_v) {
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_NAME)
                                    $_name = $_v;
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_VALUE)
                                    $_value = $_v;
                        }
                        $catalogue_data_commodity[$index][$name][$_name] = $_value;
                        if (!in_array($_name, $catalogue_data_commodity_characteristics)) {
                            $catalogue_data_commodity_characteristics[] = $_name;
                        }
                        $catalogue_data_commodity_fields[$_name]++;
                    }
                    continue;
                }
                // requisites
                if ($name == NETCAT_MODULE_NETSHOP_1C_REC_VALUES) {
                    if (!$catalogue_data_commodity_requisites)
                            $catalogue_data_commodity_requisites = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE]);
                    }

                    foreach ($row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE] as $v) {
                        $_name = "";
                        $_value = "";
                        foreach ($v as $_n => $_v) {
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_NAME)
                                    $_name = $_v;
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_VALUE)
                                    $_value = $_v;
                        }
                        $catalogue_data_commodity[$index][$name][$_name] = $_value;
                        if (!in_array($_name, $catalogue_data_commodity_requisites)) {
                            $catalogue_data_commodity_requisites[] = $_name;
                        }
                        $catalogue_data_commodity_fields[$_name]++;
                    }
                    continue;
                }
                // tax
                if ($name == NETCAT_MODULE_NETSHOP_1C_TAXES) {
                    if (!$catalogue_data_commodity_tax)
                            $catalogue_data_commodity_tax = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_TAX]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_TAX][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_TAX] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_TAX]);
                    }

                    foreach ($row[NETCAT_MODULE_NETSHOP_1C_TAX] as $v) {
                        $_name = "";
                        $_value = "";
                        foreach ($v as $_n => $_v) {
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_NAME)
                                    $_name = $_v;
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_RATE)
                                    $_value = $_v;
                        }
                        $catalogue_data_commodity[$index][$name][$_name] = $_value;
                        if (!in_array($_name, $catalogue_data_commodity_tax)) {
                            $catalogue_data_commodity_tax[] = $_name;
                        }
                        $catalogue_data_commodity_fields[$_name]++;
                    }
                    continue;
                }
            }
        }
        if (!$this->nc_core->NC_UNICODE) {
            $catalogue_data_commodity = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity);
            $catalogue_data_commodity_fields = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_fields);
            $catalogue_data_commodity_characteristics = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_characteristics);
            $catalogue_data_commodity_tax = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_tax);
        }
        // store data
        $bytes_writed = $this->cache_data_in($catalogue_data_commodity, "catalogue_data_commodity".$i);
        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - 'catalogue_data_commodity".$i."' data ".$bytes_writed." bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_fields, "catalogue_data_commodity_fields");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - 'catalogue_data_commodity_fields' data ".$bytes_writed." bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_characteristics, "catalogue_data_commodity_characteristics");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - 'catalogue_data_commodity_characteristics' data ".$bytes_writed." bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_requisites, "catalogue_data_commodity_requisites");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - 'catalogue_data_commodity_requisites' data ".$bytes_writed." bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_tax, "catalogue_data_commodity_tax");
        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - 'catalogue_data_commodity_tax' data ".$bytes_writed." bytes cached");

        // callbacks iterator
        $i++;

        // continue callback
        return;
    }

    /**
     * This function get catalogue data
     */
    function get_catalogue_data() {
        // file existence
        if (!file_exists($this->filename_path)) return false;

        // get catalog goods and properties (returned values)
        $catalogue_data_properties = $this->cml2->get_catalog($this->filename_path, $this, 'get_catalogue_data_callback');

        // store catalog properties
        $bytes_writed = $this->cache_data_in($catalogue_data_properties, "catalogue_data_properties");

        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - ".$bytes_writed." bytes cached");

        return $bytes_writed;
    }

    function import_catalogue_data() {
        // get data
        $catalogue_data_commodity = $this->cache_data_out("catalogue_data_commodity0");
        $sub_structure = $this->cache_data_out("sub_structure");
        // return if no data found
        if (empty($catalogue_data_commodity)) {
            // logging
            if ($this->debug) $this->debug(__METHOD__." FAIL - no data");
            // return
            return false;
        }

        $map_field = array();
        // get data
        $catalogue_data_commodity_fields = $this->cache_data_out("catalogue_data_commodity_fields");
        $catalogue_data_commodity_characteristics = $this->cache_data_out("catalogue_data_commodity_characteristics");
        $catalogue_data_commodity_requisites = $this->cache_data_out("catalogue_data_commodity_requisites");
        $catalogue_data_commodity_tax = $this->cache_data_out("catalogue_data_commodity_tax");

        // action)
        if (!empty($catalogue_data_commodity_fields)) {
            ###$need_tags = array("Описание", "Статус", "Артикул", "Картинка", "Производитель");
            // добавляем характеристики как будто есть такие теги в родителе "Товар"
            // на самом деле есть только тэг "ХарактеристикиТовара"
            ###if ( isset($catalogue_data_commodity_characteristics) && is_array($catalogue_data_commodity_characteristics) ) {
            ###  $need_tags = array_merge($need_tags, $catalogue_data_commodity_characteristics);
            ###}
            foreach ($catalogue_data_commodity_fields as $xml_tag => $tag_count) {
                $xml_tag = trim($xml_tag);
                ###if ( !in_array($xml_tag, $need_tags) ) continue;
                if (in_array($xml_tag, $this->import_ignore_tags)) continue;
                if (!$xml_tag) continue;

                $fields[$xml_tag]['name'] = $xml_tag;
                foreach ($this->shop_classes as $class) {
                    $map_field = $_POST['map_fields'][$class['id']][urlencode($xml_tag)];

                    $parent_tag = '';
                    if (is_array($catalogue_data_commodity_characteristics) && in_array($xml_tag, $catalogue_data_commodity_characteristics))
                            $parent_tag = NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS;
                    if (is_array($catalogue_data_commodity_requisites) && in_array($xml_tag, $catalogue_data_commodity_requisites))
                            $parent_tag = NETCAT_MODULE_NETSHOP_1C_REC_VALUES;
                    if (is_array($catalogue_data_commodity_tax) && in_array($xml_tag, $catalogue_data_commodity_tax))
                            $parent_tag = NETCAT_MODULE_NETSHOP_1C_TAXES;

                    // write compliance (second pass)
                    if ($map_field) {
                        $this->db->query("REPLACE INTO `Netshop_ImportMap`
              SET `source_id` = '".$this->source_id."',
              `type` = 'property',
              `source_string` = '".$this->db->escape($xml_tag)."',
              `value` = '".$this->db->escape($map_field)."'".($parent_tag ? ", `parent_tag` = '".$parent_tag."'" : ""));
                    }
                }
            }
        }

        if (!empty($fields)) {
            $res = $this->db->get_results("SELECT `source_string` AS id, `value`
        FROM `Netshop_ImportMap`
        WHERE `source_id` = '".$this->source_id."'
        AND `type` = 'property'
        AND `source_string` IN ('".join("','", array_keys($fields))."')", ARRAY_A);
            //$this->db->debug();
            $fields_from_base = $this->db->num_rows;
        }

        if (!empty($res)) {
            foreach ($res AS $value) {
                $fields[$value['id']]['column'] = $value['value'];
            }
        }
        $this->not_mapped_fields = count($fields) - $fields_from_base;
        // for map_fields_dialog function
        $this->not_mapped_fields_arr = is_array($this->not_mapped_fields_arr) ? array_merge($this->not_mapped_fields_arr, $fields) : $fields;
        //if ($this->not_mapped_fields) return false;
        // clear?
        if ($this->not_mapped_fields || !$this->everything_clear || !$sub_structure) {
            // logging
            if ($this->debug)
                    $this->debug(__METHOD__." FAIL - unmapped fields or no 'sub_structure'");
            // return
            return false;
        }

        // get ralation array sub_id - sub_class_id - class_id
        if (!$this->templates) $this->templates = $this->get_templates();

        $current_num = 1;
        $total_objects = 0;
        $total_files = $this->cache_data_count("catalogue_data_commodity(\d)*?\.cache");
        $i = 0;
        while ($catalogue_data_commodity = $this->cache_data_out("catalogue_data_commodity".$i)) {
            // once count total objects
            if (!$total_objects) {
                $total_objects = $total_files * count($catalogue_data_commodity);
            }
            foreach ($catalogue_data_commodity as $key => $value) {
                // progress bar
                if (!$this->quite && $current_num == 1) {
                    echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_COMMODITIES_IN_CATALOGUE."</b><br/>\r\n";
                    $this->progress_bar_show("commodity_progress");
                    echo "<br/>\r\n";
                }

                // main XML values
                $this_units = isset($value[NETCAT_MODULE_NETSHOP_1C_BASE_UNIT]) ? $value[NETCAT_MODULE_NETSHOP_1C_BASE_UNIT] : false;
                $this_groups = $value[NETCAT_MODULE_NETSHOP_1C_GROUPS][NETCAT_MODULE_NETSHOP_1C_GROUP][0][NETCAT_MODULE_NETSHOP_1C_ID];
                $this_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
                $this_name = trim($value[NETCAT_MODULE_NETSHOP_1C_NAME]);
                $this_characteristics = $value[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS];
                $this_requisites = $value[NETCAT_MODULE_NETSHOP_1C_REC_VALUES];
                $this_tax = $value[NETCAT_MODULE_NETSHOP_1C_TAXES];

                // insert unknown units in base
                if ($this_units && !$this->units[$this_units]) {
                    $this->db->query("INSERT INTO `Classificator_ShopUnits` SET `ShopUnits_Name` = '".$this_units."'");
                    $this->units[$this_units] = $this->db->insert_id;
                }

                // values for base
                $this_prop = array();
                // ("Name", "ItemID", "Price", "Currency", "ImportSourceID") imperative fields
                $this_prop["Checked"] = 1;
                $this_prop["Name"] = $this_name;
                $this_prop["Units"] = $this->units[$this_units];
                $this_prop["ImportSourceID"] = $this->source_id;
                $this_prop["ItemID"] = $this_id;
                $this_prop["Subdivision_ID"] = $sub_structure[$this_groups]['Subdivision_ID'];
                // Class_ID
                $this_class = $this->templates[$this_prop["Subdivision_ID"]]["class_id"];
                // ignored group, disabled in "map_sections_dialog" dialog
                if ($this_prop["Subdivision_ID"] == -1) {
                    // increment
                    $current_num++;
                    continue;
                }
                // executed after MySQL insert
                $this_prop["Sub_Class_ID"] = $sub_structure[$this_groups]['Sub_Class_ID'];
                // Sub_Class data from base
                if (!$this_prop["Sub_Class_ID"])
                        $this_prop["Sub_Class_ID"] = $this->templates[$this_prop["Subdivision_ID"]]["subclass_id"];
                // Priority
                $this_prop["Priority"] = (int) $this->db->get_var("SELECT MAX(`Priority`) + 1 FROM `Message".$this_class."` WHERE `Sub_Class_ID` = '".$this_prop["Sub_Class_ID"]."'");

                // try to find goods with same Item ID
                $exist_id = $this->db->get_var("SELECT `Message_ID` FROM `Message".$this_class."`
          WHERE `ItemID` = '".$this_id."'
          AND `ImportSourceID` = '".$this->source_id."'");

                // additional fields compile
                $filetable_lastid = 0;
                if (!empty($fields))
                        foreach ($fields AS $field_key => $field_data) {
                        if ($field_data['column'] == -1) continue;
                        list($field_name, $field_type) = $this->db->get_row("SELECT `Field_Name`, `TypeOfData_ID` FROM `Field`
            WHERE `Field_ID` = '".(int) $field_data['column']."'", ARRAY_N);
                        $xml_value = trim($value[$field_key]);

                        switch (true) {
                            // в массиве $catalogue_data_commodity есть тэг "ЗначенияРеквизитов"
                            // но значение следует брать из массива $this_requisites, т.к. тэг составной
                            case is_array($this_requisites) && array_key_exists($field_key, $this_requisites):
                                $this_prop[$field_name] = $this_requisites[$field_key];
                                break;
                            // в массиве $catalogue_data_commodity есть тэг "ХарактеристикиТовара"
                            // но значение следует брать из массива $this_characteristics, т.к. тэг составной
                            case is_array($this_characteristics) && array_key_exists($field_key, $this_characteristics):
                                $this_prop[$field_name] = $this_characteristics[$field_key];
                                break;
                            // в массиве $catalogue_data_commodity есть тэг "СтавкиНалогов"
                            // но значение следует брать из массива $this_tax, т.к. тэг составной
                            case is_array($this_tax) && array_key_exists($field_key, $this_tax):
                                $this_prop[$field_name] = $this_tax[$field_key];
                                break;
                            // тэг "Картинка"
                            case $field_type == 6 && $field_key == NETCAT_MODULE_NETSHOP_1C_IMG:
                                /** filename similarly commodity ID ($this_id) */
                                if (!$xml_value || !file_exists($this->filedir.$xml_value))
                                        continue;
                                // image properties
                                list($filewidth, $fileheight, $filetype, $fileattr) = getimagesize($this->filedir.$xml_value);
                                $filetype = image_type_to_mime_type($filetype);
                                $filename = basename($this->filedir.$xml_value);
                                $filesize = filesize($this->filedir.$xml_value);
                                $this_prop[$field_name] = $filename.":".$filetype.":".$filesize;
                                $file_copy_path = $this_prop["Subdivision_ID"]."/".$this_prop["Sub_Class_ID"]."/";
                                // md5 name with salt
                                $uniq_file_name = md5($filename.microtime().uniqid("netcat"));

                                $exist_sql = $this->db->get_row("SELECT `ID`, `Real_Name`, `Virt_Name` FROM `Filetable`
                WHERE `Message_ID` = '".$exist_id."' AND `Field_ID` = '".(int) $field_data['column']."'", ARRAY_N);

                                if ($exist_sql) {
                                    list($_id, $_real_name, $_virt_name) = $exist_sql;
                                    $this->db->query("UPDATE `Filetable`
                  SET `Real_Name` = '".$filename."', `Virt_Name` = '".$uniq_file_name."', `File_Type` = '".$filetype."', `File_Size` = '".$filesize."'
                  WHERE `ID` = '".$_id."'");
                                    # Delete old file ...
                                    @unlink($GLOBALS['FILES_FOLDER'].$file_copy_path.$_virt_name);
                                } else {
                                    $this->db->query("INSERT INTO `Filetable`
                  (`Real_Name`, `File_Path`, `Virt_Name`, `File_Type`, `File_Size`, `Message_ID`, `Field_ID`)
                  VALUES
                  ('".$filename."', '/".$file_copy_path."', '".$uniq_file_name."', '".$filetype."', '".$filesize."', '0', '".(int) $field_data['column']."')");
                                    $filetable_lastid = $this->db->insert_id;
                                }

                                // create dirs
                                if (!isset($GLOBALS['DIRCHMOD']))
                                        $GLOBALS['DIRCHMOD'] = 0777;
                                @mkdir($GLOBALS['FILES_FOLDER'].$this_prop["Subdivision_ID"], $GLOBALS['DIRCHMOD']);
                                @mkdir($GLOBALS['FILES_FOLDER'].rtrim($file_copy_path, "/"), $GLOBALS['DIRCHMOD']);
                                // copy file
                                @copy($this->filedir.$xml_value, $GLOBALS['FILES_FOLDER'].$file_copy_path.$uniq_file_name);
                                break;
                            default:
                                if ($field_name != 'Units')
                                        $this_prop[$field_name] = $xml_value;
                        }
                    }


                // collect fields in temp array
                $query = array();
                foreach ($this_prop AS $k => $v) {
                    $query[] = "`".$k."` = '".$this->db->escape($v)."'";
                }
                // equip MySQL append query
                $query_str = "SET ".join(", ", $query);
                unset($query);

                // create new goods or update existed
                if (!$exist_id) {
                    $this->db->query("INSERT INTO `Message".$this_class."` ".$query_str.", `Created` = NOW()");
                    //$this->db->debug();
                    $message_id = $this->db->insert_id;
                    if ($filetable_lastid)
                            $this->db->query("UPDATE `Filetable` SET `Message_ID` = '".(int) $message_id."' WHERE `ID` = '".(int) $filetable_lastid."'");
                }
                else {
                    $this->db->query("UPDATE `Message".$this_class."` ".$query_str." WHERE `Message_ID` = '".$exist_id."'");
                    if ($filetable_lastid)
                            $this->db->query("UPDATE `Filetable` SET `Message_ID` = '".(int) $exist_id."' WHERE `ID` = '".(int) $filetable_lastid."'");
                    //$this->db->debug();
                }

                // procents completed
                $percent = intval($current_num / $total_objects * 100);
                $this->progress_bar_update("commodity_progress", $percent);
                // increment
                $current_num++;
            }
            $i++;
        }
        // set 100% complete
        $this->progress_bar_update("commodity_progress", 100);

        // logging
        if ($this->debug)
                $this->debug(__METHOD__." OK - ".$bytes_writed." bytes cached");
    }

    function map_fields_dialog() {

        $this->everything_clear = false;

        echo "<b>".NETCAT_MODULE_NETSHOP_IMPORT_FIELDS_AND_TAGS_COMPLIANCE."</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        //all fields: Name, Description, Details, ItemID, Currency, Price, PriceMinimum, Image, Units, Vendor, StockUnits, ImportSourceID, CurrencyMinimum, TopSellingMultiplier, TopSellingAddition, VAT
        ###$exlude_fields_arr = array("Name", "ItemID", "Currency", "Price", "PriceMinimum", "ImportSourceID", "CurrencyMinimum", "TopSellingMultiplier", "TopSellingAddition", "VAT");
        $exlude_fields_arr = array("ItemID", "Currency", "Price", "ImportSourceID", "TopSellingMultiplier", "TopSellingAddition");

        // netshop goods classes
        foreach ($this->shop_classes AS $class) {

            $class_fields = $this->db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `Class_ID` FROM `Field`
        WHERE `Class_ID` = '".$class['id']."'
        AND `Field_Name` NOT IN ('".join("', '", $exlude_fields_arr)."')", ARRAY_A);

            $fields_str = "";
            foreach ($class_fields AS $field) {
                $fields_str.= "<option value='".$field['Field_ID']."'>[".$field['Field_Name']."] - ".$field['Description']."</option>\r\n";
            }
            echo "<tr><td colspan='3' style='background:#EEE; padding:3px'>[".$class['id']."] ".$class['name']."</td></tr>";
            if (!empty($this->not_mapped_fields_arr)) {

                foreach ($this->not_mapped_fields_arr AS $key => $value) {
                    if (!$value['column']) {
                        echo "<tr>";
                        echo "<td>".$value['name']."</td><td>&rarr;</td>";
                        echo "<td><select name='map_fields[".$class['id']."][".urlencode($key)."]'>";
                        echo "<option value='-1'>----------------------------------------</option>";
                        echo $fields_str;
                        echo "</select></td>";
                        echo "</tr>";
                    }
                }
            }
        }
        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__." OK");
    }

    /**
     * Show HTML progress bar
     * @param html id
     * @param return or echo
     * @return html text
     */
    function progress_bar_show($html_id, $ret = false) {
        // quite mode
        if ($this->quite) return false;

        $result = "<div id='".$html_id."_line' style='position:absolute; border:1px solid #FFF; height:20px; width:0px; background:#5699c7;'></div>\r\n";
        $result.= "<div style='position:absolute; border:1px solid #333; text-align:center; height:20px; width:420px; background:none; color:#264863'><p id='".$html_id."_text' style='padding:0; margin:2px 0 0'>0%</p></div>\r\n";
        $result.= "<br clear='all'/>\r\n";
        if ($ret) return $result;
        echo $result;
    }

    /**
     * Update HTML progress bar
     * @param html id
     * @param percent
     * @param return or echo
     * @return html text
     */
    function progress_bar_update($html_id, $percent, $ret = false) {
        // quite mode
        if ($this->quite) return false;

        $result = "<script type='text/javascript'>nc_netshop_import_progress('".$percent."', '".$html_id."');</script>\r\n";
        if ($ret) return $result;
        echo $result;
        flush();
    }

    /**
     * From UTF to Win
     */
    function uc($text) {
        return $text;
        switch (true) {
            case extension_loaded("mbstring"):
                return mb_convert_encoding($text, "cp1251", "UTF-8");
            case extension_loaded("iconv"):
                return iconv("UTF-8", "cp1251", $text);
            default:
                return $this->uniconv->utf8ToStr($text);
        }
    }

    /**
     * From Win to UTF
     */
    function cu($text) {
        switch (true) {
            case extension_loaded("mbstring"):
                return mb_convert_encoding($text, "UTF-8", "cp1251");
            case extension_loaded("iconv"):
                return iconv("cp1251", "UTF-8", $text);
            default:
                return $this->uniconv->strToUtf8($text);
        }
    }

}
?>

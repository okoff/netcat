<?php

/* $Id: nc_component.class.php 8453 2012-11-22 12:50:34Z ewind $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Component extends nc_Essence {

    protected $db;
    protected $_class_id, $_system_table_id;
    protected $_fields, $_field_count;
    // массив полей, попадающих в запрос, и переменные, им соответствующие
    protected $_fields_query, $_fields_vars;
    protected $_joins;
    // все используемые поля всех компонентов
    protected static $all_fields;

    public function __construct($class_id = 0, $system_table_id = 0) {
        parent::__construct();

        $this->essence = "Class";

        $nc_core = nc_Core::get_object();

        if (is_object($nc_core->db)) {
            $this->db = $nc_core->db;
        }

        $class_id = intval($class_id);
        $system_table_id = intval($system_table_id);

        // загружаем конкретный компонент
        if ($class_id || $system_table_id) {
            $this->_class_id = $class_id;
            $this->_system_table_id = $system_table_id;
        }
    }

    public function get_by_id($id, $item = '', $reset = false) {
        $nc_core = nc_Core::get_object();
        $id = intval($id);

        $res = array();

        if (isset($this->data[$id])) $res = $this->data[$id];

        if (empty($res)) {
            $res = $nc_core->db->get_results("SELECT * FROM `Class` WHERE `Class_ID` = '".$id."' OR `ClassTemplate` = '".$id."' ", ARRAY_A);

            if (empty($res)) {
                throw new nc_Exception_Class_Doesnt_Exist($id);
            }

            for ($i = 0; $i < $nc_core->db->num_rows; $i++) {
                if (false && $res[$i]['File_Mode']) { //for debug
                    $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                    $class_editor->load($res[$i]['Class_ID'], $res[$i]['File_Path'], $res[$i]['File_Hash']);
                    $class_editor->fill_fields();
                    $res[$i] = array_merge($res[$i], $class_editor->get_fields());
                }
                $this->data[$res[$i]['Class_ID']] = $res[$i];
                $this->data[$res[$i]['Class_ID']]['_nc_final'] = 0;
                $this->data[$res[$i]['Class_ID']]['Real_Class_ID'] = $res[$i]['Class_ID'];
            }
        }
        
        if (!$this->data[$id]['_nc_final'] && $this->data[$id]['ClassTemplate']) {          
            // визуальные настройки берутся от компонента
            if ($this->data[$id]['Type'] != 'useful' && $this->data[$id]['Type'] != 'title') {
                $this->data[$id]['CustomSettingsTemplate'] = $this->get_by_id($this->data[$id]['ClassTemplate'], 'CustomSettingsTemplate');
            }

            if (!@$res[$i]['File_Mode']) {            
                $macrovars = array('%Prefix%' => 'FormPrefix',
                        '%Record%' => 'RecordTemplate',
                        '%Suffix%' => 'FormSuffix',
                        '%Full%' => 'RecordTemplateFull',
                        '%Settings%' => 'Settings',
                        '%TitleTemplate%' => 'TitleTemplate',
                        '%Order%' => 'SortBy',
                        '%AddForm%' => 'AddTemplate',
                        '%AddCond%' => 'AddCond',
                        '%AddAction%' => 'AddActionTemplate',
                        '%EditForm%' => 'EditTemplate',
                        '%EditCond%' => 'EditCond',
                        '%EditAction%' => 'EditActionTemplate',
                        '%DeleteForm%' => 'DeleteTemplate',
                        '%DeleteCond%' => 'DeleteCond',
                        '%DeleteAction%' => 'DeleteActionTemplate',
                        '%SearchForm%' => 'FullSearchTemplate',
                        '%Search%' => 'SearchTemplate',
                        '%CheckAction%' => 'CheckActionTemplate');

                foreach ($macrovars as $var => $field) {
                    if (strstr($this->data[$id][$field], $var)) {
                        $this->data[$id][$field] = str_replace($var, $this->get_by_id($this->data[$id]['ClassTemplate'], $field), $this->data[$id][$field]);
                    }
                }
            }
        }

        $this->data[$id]['_nc_final'] = 1;

        if ($item && is_array($this->data[$id])) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }
        return $this->data[$id];
    }

    public function get_for_cc($sub_class_id, $class_id, $nc_ctpl) {
        $nc_core = nc_Core::get_object();

        $class_id = intval($class_id);

        $this->get_by_id($class_id);

        $type = '';
        if ($nc_core->admin_mode) $type = 'admin_mode';
        if ($nc_core->inside_admin) $type = 'inside_admin';
        if ($nc_core->get_page_type() == 'rss') $type = 'rss';
        if ($nc_core->get_page_type() == 'xml') $type = 'xml';
        if ($nc_ctpl === 'title') {
            $type = 'title';
            $nc_ctpl = 0;
        }


        // поиск по типу шаблона компонента
        if ($type) {
            foreach ($this->data as $id => $v) {
                if ($v['ClassTemplate'] == $class_id && $v['Type'] == $type)
                        return $this->get_by_id($v['Class_ID']);
            }
        }

        // выбор по шаблону или номеру
        foreach ($this->data as $id => $v) {
            if (( $nc_ctpl && $v['Class_ID'] == $nc_ctpl)
                    || (!$nc_ctpl && $v['Class_ID'] == $class_id))
                return $this->get_by_id($v['Class_ID']);
                    }

        return false;
    }

    public function get_fields_query() {
        if (empty($this->_fields_query)) return "";

        return join(', ', $this->_fields_query);
    }

    public function get_fields_vars() {
        if (empty($this->_fields_vars)) return "";

        return join(', ', $this->_fields_vars);
    }

    protected function _load_fields() {
        // загрузка их статических данных
        // если их нет - то взять из базы
        if (!isset(self::$all_fields[$this->_class_id.'-'.$this->_system_table_id])) {
            self::$all_fields[$this->_class_id.'-'.$this->_system_table_id] =
                    $this->db->get_results("SELECT `Field_ID` as `id`, `Field_Name` as `name`,
                                     `TypeOfData_ID` as `type`, `Format` as `format`,
                                     `Description` AS `description`, `NotNull` AS `not_null`,
                                     `DefaultState` as `default`, `TypeOfEdit_ID` AS `edit_type`,
                                     ".(!$this->_system_table_id ? "`DoSearch` AS `search`" : "1 AS `search`")."
                                     FROM `Field`
                                     WHERE `Checked` = 1  AND ".($this->_system_table_id ? "
                                     `System_Table_ID` = '".$this->_system_table_id."'" : "
                                     `Class_ID` = '".$this->_class_id."'")."
                                     ORDER BY `Priority`", ARRAY_A);
            if (!self::$all_fields[$this->_class_id.'-'.$this->_system_table_id]) {
                self::$all_fields[$this->_class_id.'-'.$this->_system_table_id] = array();
            }
            if ($this->db->is_error)
                    throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        }

        $this->_fields = self::$all_fields[$this->_class_id.'-'.$this->_system_table_id];
        $this->_field_count = count($this->_fields);
    }

    public function get_date_field() {
        $this->_load_fields();

        for ($i = 0; $i < $this->_field_count; $i++) {
            if ($this->_fields[$i]['type'] == 8) {
                $format = nc_field_parse_format($this->_fields[$i]['format'], 8);
                if ($format['type'] == 'event' || $format['type'] == 'event_date')
                        return $this->_fields[$i]['name'];
            }
        }

        return 0;
    }

    public function get_joins() {
        return $this->_joins;
    }

    public function get_old_vars($res) {
        $old_array = array();
        for ($i=0; $i < count($this->_fields_vars); $i++) {
            $varname = str_replace('$', '', $this->_fields_vars[$i]);
            $varname = str_replace('f_', '', $varname);
            if (!isset($res[$varname])) {
                $fieldname = preg_replace('/^.*?([\w_]+?)[`]?$/i', '$1', $this->_fields_query[$i]);
                $old_vars[$varname] = $res[$fieldname];
            }
        }
        return $old_vars;
    }

    public function make_query() {
        $nc_core = nc_Core::get_object();

        $this->_load_fields();

        $convert2txt = "";
        $joins = "";
        $multilist_fileds = array();


        if ($this->_system_table_id == 3) {
            $this->_fields_query = array('a.`User_ID`', 'a.`PermissionGroup_ID`');
            $this->_fields_vars = array('$f_RowID', '$f_UserGroup');
        } else {
            $this->_fields_query = array('a.`Message_ID`', 'a.`User_ID`', 'a.`IP`', 'a.`UserAgent`',
                    'a.`LastUser_ID`', 'a.`LastIP`', 'a.`LastUserAgent`',
                    'a.`Priority`', 'a.`Parent_Message_ID`', 'a.`ncTitle`', 'a.`ncKeywords`',
                    'a.`ncDescription`', 'sub.`Subdivision_ID`',
                    'CONCAT( \''.$nc_core->SUB_FOLDER.'\', sub.`Hidden_URL`) as Hidden_URL',
                    'cc.`Sub_Class_ID`', 'cc.`EnglishName`');
            $this->_fields_vars = array('$f_RowID', '$f_UserID', '$f_IP', '$f_UserAgent',
                    '$f_LastUserID', '$f_LastIP', '$f_LastUserAgent',
                    '$f_Priority', '$f_Parent_Message_ID', '$f_ncTitle', '$f_ncKeywords',
                    '$f_ncDescription', '$f_Subdivision_ID',
                    '$subLink', '$f_Sub_Class_ID', '$cc_keyword');

            $this->_joins .= " LEFT JOIN `Subdivision` AS sub ON sub.`Subdivision_ID` = a.`Subdivision_ID`
                         LEFT JOIN `Sub_Class` AS cc ON cc.`Sub_Class_ID` = a.`Sub_Class_ID`";
        }

        $this->_fields_query[] = 'a.`Checked`';
        $this->_fields_query[] = 'a.`Created`';
        $this->_fields_query[] = 'a.`Keyword`';
        $this->_fields_query[] = 'a.`LastUpdated` + 0 AS LastUpdated';

        $this->_fields_vars[] = '$f_Checked';
        $this->_fields_vars[] = '$f_Created';
        $this->_fields_vars[] = '$f_Keyword';
        $this->_fields_vars[] = '$f_LastUpdated';



        if (!$this->_system_table_id && $nc_core->admin_mode && $nc_core->AUTHORIZE_BY !== 'User_ID') {
            $this->_fields_query[] = "uAdminInterfaceAdd.`".$nc_core->AUTHORIZE_BY."` AS f_AdminInterface_user_add ";
            $this->_fields_query[] = "uAdminInterfaceChange.`".$nc_core->AUTHORIZE_BY."` AS f_AdminInterface_user_change ";

            $this->_fields_vars[] = '$f_AdminInterface_user_add';
            $this->_fields_vars[] = '$f_AdminInterface_user_change';

            $this->_joins .= " LEFT JOIN `User` AS uAdminInterfaceAdd ON a.`User_ID` = uAdminInterfaceAdd.`User_ID`
                         LEFT JOIN `User` AS uAdminInterfaceChange ON a.`LastUser_ID` = uAdminInterfaceChange.`User_ID` ";
        }



        for ($i = 0; $i < $this->_field_count; $i++) {
            $field = & $this->_fields[$i];
            // skip OpenID type
            if ($field['type'] == 11) continue;

            switch ($field['type']) {
                // list field
                case 4:
                    $table = strtok($field['format'], ':');
                    $this->_joins .= " LEFT JOIN `Classificator_".$table."` AS tbl".$field['id']." ON a.`".$field['name']."` = tbl".$field['id'].".".$table."_ID ";

                    $this->_fields_query[] = "tbl".$field['id'].".".$table."_Name AS ".$field['name'];
                    $this->_fields_query[] = "tbl".$field['id'].".".$table."_ID AS ".$field['name']."_id";
                    $this->_fields_query[] = "tbl".$field['id'].".`Value` AS ".$field['name']."_value ";

                    $this->_fields_vars[] = "\$f_".$field['name'];
                    $this->_fields_vars[] = "\$f_".$field['name']."_id";
                    $this->_fields_vars[] = "\$f_".$field['name']."_value";
                    break;

                // date field
                case 8:
                    $this->_fields_query[] = "a.".$field['name'];
                    $this->_fields_vars[] = "\$f_".$field['name'];

                    $this->_fields_query[] = "DATE_FORMAT(a.`".$field['name']."`,'%Y') as ".$field['name']."_year";
                    $this->_fields_query[] = "DATE_FORMAT(a.`".$field['name']."`,'%m') as ".$field['name']."_month";
                    $this->_fields_query[] = "DATE_FORMAT(a.`".$field['name']."`,'%d') as ".$field['name']."_day";
                    $this->_fields_query[] = "DATE_FORMAT(a.`".$field['name']."`,'%H') as ".$field['name']."_hours";
                    $this->_fields_query[] = "DATE_FORMAT(a.`".$field['name']."`,'%i') as ".$field['name']."_minutes";
                    $this->_fields_query[] = "DATE_FORMAT(a.`".$field['name']."`,'%s') as ".$field['name']."_seconds";

                    $this->_fields_vars[] = "\$f_".$field['name']."_year";
                    $this->_fields_vars[] = "\$f_".$field['name']."_month";
                    $this->_fields_vars[] = "\$f_".$field['name']."_day";
                    $this->_fields_vars[] = "\$f_".$field['name']."_hours";
                    $this->_fields_vars[] = "\$f_".$field['name']."_minutes";
                    $this->_fields_vars[] = "\$f_".$field['name']."_seconds";


                    break;

                // MultiList
                case 10:
                    $multilist_fileds[] = array('name' => $field['name'], 'table' => strtok($field['format'], ':'));
                    $this->_fields_query[] = "a.".$field['name'];
                    $this->_fields_vars[] = "\$f_".$field['name'];
                    break;

                default:
                    $this->_fields_query[] = "a.".$field['name'];
                    $this->_fields_vars[] = "\$f_".$field['name'];
                    break;
            }
        }
    }

    public function get_fields($type = 0, $output_all = 1) {
        $this->_load_fields();

        $result = array();
        for ($i = 0; $i < $this->_field_count; $i++) {
            if ($type ? ($this->_fields[$i]['type'] == $type) : 1) {
                if ($type == 10 || $type == 4)
                        $this->_fields[$i]['table'] = strtok($this->_fields[$i]['format'], ':');

                if ($output_all) {
                    $result[] = $this->_fields[$i];
                } else {
                    $result[$this->_fields[$i]['id']] = $this->_fields[$i]['name'];
                }
            }
        }

        return $result;
    }

 public function get_search_query($srchPat) {
        $this->_load_fields();
        // return if search params not setted
        if (empty($srchPat)) return array("query" => "", "link" => "");

        $search_param = array();
        if(isset($srchPat['OR']) && $srchPat['OR'] == '1') $search_param[] = "srchPat[OR]=1";
        
        $search_string = $fullSearchStr = '';
        $or_and = '';
        for ($i = 0, $j = 0; $i < $this->_field_count; $i++) {
            $field = & $this->_fields[$i];
            if ($search_string > '') {
                $or_and = ((isset($srchPat['OR']) && $srchPat['OR'] == '1') ? 'OR' : 'AND');
            }
            if ($field['search']) {
                switch ($field['type']) {
                    case 1:   // Char
                        if ($srchPat[$j] == "") break;
                        $search_string .= " ".$or_and." a.".$field['name']." LIKE '%".$this->db->escape($srchPat[$j])."%'";
                        $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        break;
                    case 2:   // Int
                        if (trim($srchPat[$j]) != "") {
                            $search_string .= " ".$or_and." ";
                            if (trim($srchPat[$j+1]) != "") {
                                $search_string .= "(";
                            }
                            $search_string .= "a.".$field['name'].">=".trim(intval($srchPat[$j]));
                            $search_param[] = "srchPat[".$j."]=".trim(intval($srchPat[$j]));
                        }
                        $j++;
                        if (trim($srchPat[$j]) != "") {
                            if (trim($srchPat[$j-1]) != "") $search_string .= " AND ";
			    else $search_string .= " ".$or_and." ";
                            $search_string .= " a.".$field['name']."<=".trim(intval($srchPat[$j]));
                            if (trim($srchPat[$j-1]) != "") $search_string .= ")";
                            $search_param[] = "srchPat[".$j."]=".trim(intval($srchPat[$j]));
                        }
                        break;
                    case 3:   // Text
                        if ($srchPat[$j] == "") break;
                        $srch_str = $this->db->escape($srchPat[$j]);
                        $search_string .= " ".$or_and." a.".$field['name']." LIKE '%".$srch_str."%'";
                        $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        break;
                    case 4: // List
                        if ($srchPat[$j] == "") break;
                        $srchPat[$j] += 0;
                        $search_string .= " ".$or_and." a.".$field['name']."=".$srchPat[$j];
                        $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        break;
                    case 5:   // Boolean
                        if ($srchPat[$j] == "") break;
                        $srchPat[$j] += 0;
                        $search_string .= " ".$or_and." a.".$field['name']."=".$srchPat[$j];
                        $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        break;
                    case 6:   // File
                        if ($srchPat[$j] == "") break;
                        $srch_str = $this->db->escape($srchPat[$j]);
                        $search_string .= " ".$or_and." SUBSTRING_INDEX(a.".$field['name'].",':',1) LIKE '%".$srch_str."%'";
                        $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        break;
                    case 7:   // Float
                        if ($srchPat[$j] != "") {
                            $search_string .= " ".$or_and." ";
                            if ($srchPat[$j+1] != "") {
                                $search_string .= "(";
                            }
                            $srchPat[$j] = floatval($srchPat[$j]);
                            $search_string .= "a.".$field['name'].">=".$srchPat[$j];
                            $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        }
                        $j++;
                        if ($srchPat[$j] != "") {
                            $srchPat[$j] = floatval($srchPat[$j]);
                            $search_string .= " AND a.".$field['name']."<=".$srchPat[$j].")";
                            $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        }
                        break;
                    case 8:   // DateTime
                        $date_from['d'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['m'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['Y'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%04d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['H'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['i'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['s'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['d'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['m'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['Y'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%04d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['H'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['i'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['s'] = ($srchPat[$j] && ($search_param[] = "srchPat[".$j."]=".$srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);

                        $date_format_from = ($date_from['Y'] ? '%Y' : '').($date_from['m'] ? '%m' : '').($date_from['d'] ? '%d' : '').($date_from['H'] ? '%H' : '').($date_from['i'] ? '%i' : '').($date_from['s'] ? '%s' : '');
                        $date_format_to = ($date_to['Y'] ? '%Y' : '').($date_to['m'] ? '%m' : '').($date_to['d'] ? '%d' : '').($date_to['H'] ? '%H' : '').($date_to['i'] ? '%i' : '').($date_to['s'] ? '%s' : '');

                        if ($date_format_from) {
                                $search_string .= " ".$or_and." ";
                                if ($date_format_to) {
                                    $search_string .= "(";
                                }
                                $search_string .= " DATE_FORMAT(a.".$field['name'].",'".$date_format_from."')>=".$date_from['Y'].$date_from['m'].$date_from['d'].$date_from['H'].$date_from['i'].$date_from['s'];
                        }
                        if ($date_format_to) {
                                $search_string .= " AND DATE_FORMAT(a.".$field['name'].",'".$date_format_to."')<=".$date_to['Y'].$date_to['m'].$date_to['d'].$date_to['H'].$date_to['i'].$date_to['s'].")";
                        }
                        break;

                    case 10: // MultiList
                        if ($srchPat[$j] == "") {
                            $j++;
                            break;
                        }

                        $id = array(); // массив с id искомых элементов

                        if (is_array($srchPat[$j])) {
                            foreach ((array) $srchPat[$j] as $v) {
                                if (!$v) break;
                                $id[] = intval($v);
                            }
                        }
                        else {
                            $temp_id = explode('-', $srchPat[$j]);
                            foreach ((array) $temp_id as $v) {
                                if (!$v) break;
                                $id[] = intval($v);
                            }
                        }
                        $j++; //второй параметр - это тип посика

                        if (empty($id)) break;

                        $search_string .= " ".$or_and." (";
                        switch ($srchPat[$j]) {
                            case 1: //Полное совпадение
                                $search_string .= "a.".$field['name']." LIKE CONCAT(',' ,  '".join(',', $id)."', ',') ";
                                break;

                            case 2: //Хотя бы один. Выбор между LIKE и REGEXP выпал в сторону первого
                                foreach ($id as $v)
                                    $search_string .= "a.".$field['name']." LIKE CONCAT('%,', '".$v."', ',%') OR ";
                                $search_string .= "0 "; //чтобы "закрыть" последний OR
                                break;
                            case 0: // как минимум выбранные - частичное совпадение - по умолчанию
                            default:
                                $srchPat[$j] = 0;
                                $search_string .= "a.".$field['name']."  REGEXP  \"((,[0-9]+)*)";
                                $prev_v = -1;
                                foreach ($id as $v) {
                                    $search_string .= "(,".$v.",)([0-9]*)((,[0-9]+)*)";
                                    $prev_v = $v;
                                }
                                $search_string .= '"';
                                break;
                        }
                        $search_string .= ")";

                        $search_param[] = "srchPat[".($j - 1)."]=".join('-', $id);
                        $search_param[] = "srchPat[".$j."]=".$srchPat[$j];
                        break;
                }
                $j++;
            }
        }

        if (!empty($search_string)) {
            $fullSearchStr = " AND( ".$search_string.")";
        }
        if (!empty($search_param))
                $search_params['link'] = join('&amp;', $search_param);
                $search_params['query'] = $fullSearchStr;

        return $search_params;
    }

    public function add_form($catalogue, $sub, $cc, $eval = 0) {
        $nc_core = nc_Core::get_object();
        //список переменных, доступных в eval
        global $AUTH_USER_ID, $MODULE_VARS, $warnText;
        $alter_form = $nc_core->component->get_by_id($this->_class_id, 'AddTemplate');

        $File_Mode = nc_get_file_mode('Class', $this->_class_id);
        if ($File_Mode) {
            $file_class = new nc_class_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $file_class->load($this->_class_id);
            $nc_field_path = $file_class->get_field_path('AddTemplate');
            if (filesize($nc_field_path)) {
                return $nc_field_path;
            }
        }

        if ($alter_form) {
            $result = $alter_form;
        } else {
            $this->_load_fields();
            $result = nc_fields_form('add', $this->_fields, $this->_class_id);
        }
        if ($eval && !$File_Mode) {
            eval("\$addForm = \"".$result."\"; ");
            return $addForm;
        }
        return $result;
    }

    public function search_form($short = 1) {
        $nc_core = nc_Core::get_object();
        $alter_form = $nc_core->component->get_by_id($this->_class_id, $short ? 'FullSearchTemplate' : 'SearchTemplate');
        if ($alter_form) return $alter_form;

        $result = nc_fields_form('search', $this->_fields);

        return $result;
    }

    /**
     * Добавление нового компонета ( шаблона компонента )
     *
     * @param string $class_name - имя компонента
     * @param string $class_group - группа компонента
     * @param array $params - массив параметров компонента
     * @param int $class_template - номер класса, если идет создание шаблона
     * @param string $type - тип шаблона компонента
     *
     * @return int номер созданного компонент
     */
    public function add($class_name, $class_group, $params, $class_template = 0, $type = 'useful') {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $class_name = $db->escape($class_name);
        $class_group = $db->escape($class_group);
        $type = $db->escape($type);
        $class_template = intval($class_template);

        $File_Mode = nc_get_file_mode('Class', $class_template);

        if ($File_Mode) {
            $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $db);
            $class_editor->load($class_template);
            if (is_array($params)) {
                $_POST = array_merge($_POST, $params);
            }
        }

        // все парметры компонента
        $params_int = array('AllowTags', 'RecordsPerPage', 'NL2BR', 'UseCaptcha', 'CacheForUser');
        if (!$class_template) $params_int[] = 'System_Table_ID';
        $params_text = array('FormPrefix', 'FormSuffix', 'RecordTemplate', 'SortBy', 'RecordTemplateFull',
                'TitleTemplate', 'AddTemplate', 'EditTemplate', 'AddActionTemplate', 'EditActionTemplate', 'SearchTemplate',
                'FullSearchTemplate', 'SubscribeTemplate', 'Settings', 'AddCond', 'EditCond', 'SubscribeCond',
                'DeleteCond', 'CheckActionTemplate', 'DeleteActionTemplate', 'CustomSettingsTemplate',
                'ClassDescription', 'DeleteTemplate');

        if ($File_Mode) {
            $params_text = $class_editor->get_clear_fields($params_text);
            $params['File_Mode'] = 1;
            $params_text[] = 'File_Mode';
        }

        // добавление имени и группы
        $query = array("`Class_Name`", "`Class_Group`");
        $values = array("'".$class_name."'", "'".$class_group."'");
        // добавление шаблона компонента
        if ($class_template) {
            $query[] = "`ClassTemplate`";
            $values[] = "'".$class_template."'";
            // System Table ID в любом случае берется от компонента
            $query[] = "`System_Table_ID`";
            $values[] = "'".$this->get_by_id($class_template, 'System_Table_ID')."'";
        }
        // тип шаблона компонента
        if ($type) {
            $query[] = "`Type`";
            $values[] = "'".$type."'";
        }
        // добавление всех параметров компонента
        foreach ($params_int as $v) {
            if (!isset($params[$v])) continue;
            $query[] = "`".$v."`";
            $values[] = "'".intval($params[$v])."'";
        }

        foreach ($params_text as $v) {
            if (!isset($params[$v])) continue;
            $query[] = "`".$v."`";
            $values[] = "'".$db->prepare($params[$v])."'";
        }

        // сообственно, добавление
        $SQL = "INSERT INTO `Class` (".join(', ', $query).") VALUES (".join(', ', $values).") ";
        $db->query($SQL);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        $new_class_id = $db->insert_id;

        if ($File_Mode) {
            $class_editor->save_new_class($new_class_id);
        }

        // трансляция события создания компонента
        if (!$class_template) {
            CreateMessageTable($new_class_id, $db);
            $nc_core->event->execute("addClass", $new_class_id);
        } else {
            $nc_core->event->execute("addClassTemplate", $class_template, $new_class_id);
        }


        return $new_class_id;
    }

    public function update($id, $params = array()) {
        $nc_core = nc_Core::get_object();
        $db = $this->db;

        $id = intval($id);
        if (!$id || !is_array($params)) {
            return false;
        }

        $File_Mode = nc_get_file_mode('Class', $id);

        if ($File_Mode) {
            $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $db);
        }

        if ($params['action_type'] == 1) {
            $params_int = array(
                'CacheForUser'
            );
            $params_text = array(
                'Class_Name',
                'Class_Group'
            );
        } else {

        $params_int = array(
                'AllowTags',
                'RecordsPerPage',
                'System_Table_ID',
                'NL2BR',
                'UseCaptcha',
                'UseAltTitle');

        $params_text = array(
                'FormPrefix',
                'FormSuffix',
                'RecordTemplate',
                'SortBy',
                'RecordTemplateFull',
                'TitleTemplate',
                'AddTemplate',
                'EditTemplate',
                'AddActionTemplate',
                'EditActionTemplate',
                'SearchTemplate',
                'FullSearchTemplate',
                'SubscribeTemplate',
                'Settings',
                'AddCond',
                'EditCond',
                'SubscribeCond',
                'DeleteCond',
                'CheckActionTemplate',
                'DeleteActionTemplate',
                'CustomSettingsTemplate',
                'ClassDescription',
                'DeleteTemplate',
                'TitleList');
        }
        if ($File_Mode) {
            $class_editor->load($id);
            $class_editor->save_fields($only_isset_post = true);
            $params_text = $class_editor->get_clear_fields($params_text);
        }

        $query = array();

        foreach ($params as $k => $v) {
            if (!in_array($k, $params_int) && !in_array($k, $params_text)) { continue; }
            $query[] = "`".$db->escape($k)."` = '".$db->prepare($v)."'";
        }

        if (!empty($query)) {
            $SQL =  "\nUPDATE `Class`";
            $SQL .= "\n    SET " . join(",\n        ", $query);
            $SQL .= "\n        WHERE `Class_ID` = " . $id;
            $db->query($SQL);
            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }

            $ClassTemplate = $db->get_var("SELECT `ClassTemplate` FROM `Class` WHERE `Class_ID` = '".$id."' ");

            if (!$ClassTemplate) {
                $nc_core->event->execute("updateClass", $id);
            } else {
                $nc_core->event->execute("updateClassTemplate", $ClassTemplate, $id);
            }

            @$nc_core->event->execute("updateSystemTable", 3);
        }

        $this->data = array();
        return true;
    }

}
?>

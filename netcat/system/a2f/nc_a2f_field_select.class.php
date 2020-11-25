<?php

if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для реализации поля типа "Список"
 */
class nc_a2f_field_select extends nc_a2f_field {

    protected $has_default = 1;
    //  возможные значения (значение - описание)
    protected $values;

    public function render_value_field($html = true) {
        $ret = "<select name='".$this->get_field_name()."'  class='ncf_value_select'>\n";

        // текущее значение
        $current_value = $this->get_value(0);
        // значение по-умолчанию
        if (!$current_value) $current_value = $this->default_value;
        // если нет значения по-умолчанию - выводим пустую строку тоже
        if (!$this->default_value)
                $ret .= "<option value=''>".NETCAT_MODERATION_LISTS_CHOOSE."</option>\n";

        foreach ((array) $this->values as $k => $v) {
            $ret .= "<option value='".$k."'".($k == $current_value ? " selected='selected'" : "").">".$v."</option>\n";
        }

        if ($html) {
            $ret = "<div class='ncf_value'>".$ret."</select></div>\n";
        }

        return $ret;
    }

    public function render_default_value($html = true) {
        $ret = $this->values[$this->default_value];

        if ($html) {
            $ret = "<div class='ncf_default'>".$ret."</div>";
        }

        return $ret;
    }

    function get_subtypes() {
        return array('static', 'classificator', 'sql');
    }

}

class nc_a2f_field_select_sql extends nc_a2f_field_select {

    public function get_extend_parametrs() {
        return array('sqlquery' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_EX_QUERY));
    }

    public function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();

        // просто узнаем элементы списка
        $res = $nc_core->db->get_results($this->sqlquery, ARRAY_A);

        if ($nc_core->db->is_error) return NETCAT_CUSTOM_ONCE_ERROR_QUERY;

        if ($res)
                foreach ($res as $v) {
                $this->values[$v['id']] = $v['name'];
            }

        // сама прорисовка реализована в родительском класса
        return parent::render_value_field($html);
    }

}

class nc_a2f_field_select_static extends nc_a2f_field_select {

    public function get_extend_parametrs() {
        return array('values' => array('type' => 'static', 'caption' => NETCAT_CUSTOM_EX_ELEMENTS));
    }

}

class nc_a2f_field_select_classificator extends nc_a2f_field_select {

    public function get_extend_parametrs() {
        return array('classificator' => array('type' => 'classificator', 'caption' => NETCAT_CUSTOM_EX_CLASSIFICATOR));
    }

    public function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $clft = $db->escape($this->classificator);
        $res = $db->get_results("SELECT * FROM `Classificator` WHERE `Table_Name` = '".$clft."' ", ARRAY_A);

        if (!$res)
                return sprintf(NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR, $this->classificator);

        switch ($res['Sort_Type']) {
            case 1:
                $sort = "`".$clft."_Name`";
                break;
            case 2:
                $sort = "`".$clft."_Priority`";
                break;
            default:
                $sort = "`".$clft."_ID`";
        }

        // просто узнаем элементы списка
        $elements = $db->get_results("SELECT `".$clft."_ID` as `id`, `".$clft."_Name` as `name`
               FROM `Classificator_".$clft."`
               WHERE `Checked` = '1'
               ORDER BY ".$sort." ".( $res['Sort_Direction'] == 1 ? "DESC" : "ASC")."", ARRAY_A);

        if (!$elements)
                return sprintf(NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR_EMPTY, $res['Classificator_Name']);

        foreach ($elements as $v) {
            $this->values[$v['id']] = $v['name'];
        }

        // сама прорисовка реализована в родительском класса
        return parent::render_value_field($html);


        return $ret;
    }

}
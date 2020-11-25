<?php

if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * [Array to Form]
 * Класс для преобразования массива с данными + массива с настройками
 * в элементы html-формы и сохранения результатов формы в виде строки
 */
class nc_a2f {

    // поля формы
    public $fields = array();
    // ошибки, возникшие при проверке значений
    var $validation_errors = array();
    /**
     * префикс: использовать у элементов формы в названии элементов
     * (e.g. если prefix='settings', названия полей формы будут
     *  вида name='settings[field1]')
     */
    var $array_name;
    protected $settings_array;

    /**
     * конструктор
     * @param mixed массив с настройками или соответствующая строка
     *   E.g. $settings_array = array(
     *                            "field1_name" => array("type" => "string")
     *                          );
     * @param mixed массив со значениями или строка
     *        $values_array = array("field1_name" => 123)
     * @param string
     */
    public function __construct($settings, $array_name = "nc_a2f") {

        $this->array_name = $array_name;
        $settings_array = is_array($settings) ? $settings : $this->eval_value($settings);

        if (!is_array($settings_array)) {
            trigger_error("nc_a2f::nc_a2f() - first parameter is not an array or cannot be evaluated as an array", E_USER_WARNING);
            return;
        }

        $this->settings_array = $settings_array;

        foreach ($settings_array as $field_name => $field_settings) {
            if (!$field_settings['type']) {
                trigger_error("(nc_a2f) missing type for the field '".$field_name."'", E_USER_WARNING);
                continue;
            }
            $class_name = "nc_a2f_field_".$field_settings['type'];
            if (isset($field_settings['subtype']) && $field_settings['subtype'])
                    $class_name .= '_'.$field_settings['subtype'];
            if (!class_exists($class_name)) {
                trigger_error("(nc_a2f) wrong type '".$field_settings['type']."' for the field '".$field_name."'. ".$class_name." not found.", E_USER_WARNING);
                continue;
            }

            // push name into settings array
            $field_settings['name'] = $field_name;
            $this->fields[$field_name] = new $class_name($field_settings, $this);

        }
    }

    public function save($values) {
        foreach ($this->fields as $name => $f) {
            $f->save($values[$name]);
        }
    }

    public function set_value($values) {
        if (!(is_array($values) || (is_object($values) && $values instanceof ArrayAccess && $values instanceof Iterator))) {
            $values = $this->eval_value($values);
        }
        foreach ($this->fields as $name => $f) {
            $value = isset($values[$name]) ? $values[$name] : null;
            $f->set_value($value);
        }
    }

    /**
     * Eval PHP-кода в значение
     */
    function eval_value($value_string) {
        if (!$value_string) return $value_string;
        $value_string = nc_preg_replace("/;\s*$/", "", $value_string);
        @eval("\$ret = ($value_string);");
        if (strlen($value_string) > 1 && !is_array($ret)) {
            trigger_error("nc_a2f::eval_value - wrong parameter?<pre>".htmlspecialchars($value_string)."</pre>", E_USER_WARNING);
        }
        return $ret;
    }

    /**
     * Вывод элементов
     * @return string
     */
    function render($header = "", $template = "", $footer = "", $divider = "") {
        $ret = $this->render_header($header ? $header : "");

        foreach ($this->fields as $field) {
            $ret.= $field->render($field->type == 'divider' && $divider ? $divider : ($template ? $template : ""));
        }

        $ret.= $this->render_footer($footer ? $footer : "");

        return $ret;
    }

    /**
     * @access private
     */
    function render_header($template = "") {
        if (!$template) {
            $ret = "<div class='ncf_container'>\n".
                    "<div class='ncf_header_row'>".
                    "<div class='ncf_header_caption'>".CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER."</div>".
                    "<div class='ncf_header_default'>".CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT."</div>".
                    "<div class='ncf_header_value'>".CONTROL_CLASS_CUSTOM_SETTINGS_VALUE."</div>".
                    "</div>\n";
        } else {
            $ret = str_replace(
                            array("%CAPTION", "%DEFAULT", "%VALUE"),
                            array(CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER, CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT, CONTROL_CLASS_CUSTOM_SETTINGS_VALUE),
                            $template
            );
        }

        return $ret;
    }

    function render_footer($template="") {
        if (!$template) $ret = "</div>";
        else $ret = $template;

        return $ret;
    }

    public function settings_render($header = "", $template = "", $footer = "", $divider = "") {
        $ret = $header;

        foreach ($this->fields as $field) {
            $ret.= $field->settings_render($field->type == 'divider' && $divider ? $divider : ($template ? $template : ""));
        }

        $ret .= $footer;

        return $ret;
    }

    /**
     * Получить строку со значениями (php $values_array)
     * @return string  'f1'=>'value', 'f2=>'value'...
     */
    function get_values_as_string() {
        $all_values = array();
        foreach ($this->fields as $field_name => $field) {
            $val = $this->fields[$field_name]->get_value(); // don't use default value
            if (isset($val)) {
                $val = str_replace("'", "\\'", $val);
                $all_values[] = "'".$field_name."' => '".$val."'";
            }
        }

        if (sizeof($all_values)) {
            $ret = "$".$this->array_name." = array(".join(', ', $all_values).");";
            return $ret;
        }
        return "";
    }

    /**
     * Получить все значения в виде массива
     * @return array
     */
    function get_values_as_array() {
        $values = array();
        foreach ($this->fields as $field_name => $field) {
            $values[$field_name] = $this->fields[$field_name]->get_value();
        }
        return $values;
    }

    public static function array_to_string($ar, $l = 1) {
        if (empty($ar)) return "";
        $ret = "array(\r\n";
        foreach ($ar as $k => $v) {
            $ret .= str_repeat("\t", $l)."'".$k."' => ";
            $ret .= is_array($v) ? self::array_to_string($v, $l + 1) : "'".$v."'";
            $ret .= ",\r\n";
        }
        $ret = nc_substr($ret, 0, nc_strlen($ret) - 3);
        $ret .=')';
        return $ret;
    }

    /**
     * принять сообщение об ошибке от поля
     */
    function set_validation_error($field_name, $error_msg) {
        if (!$error_msg) {
            $error_msg = str_replace("%NAME", $this->fields[$field_name]->caption, NETCAT_MODERATION_MSG_TWO);
        }
        $this->validation_errors[$field_name] = $error_msg;
    }

    function get_validation_errors() {
        if (!sizeof($this->validation_errors)) return "";

        $ret = "";
        foreach ($this->validation_errors as $field => $error) {
            $ret .= $this->fields[$field]->caption.": $error<br>\n";
        }
        return $ret;
    }

    function get_field_error($field_name) {
        return $this->validation_errors[$field_name];
    }

    /**
     * были ли ошибки при заполнении формы?
     * @return boolean
     */
    function has_errors() {
        return (sizeof($this->validation_errors) ? true : false);
    }

    public function get_array_name() {
        return $this->array_name;
    }

    public function validate($values) {
        $r = 1;
        if (!is_array($values)) $values = $this->eval_value($values);

        foreach ($this->fields as $name => $f) {
            if (!$f->validate($values[$name])) $r = 0;
        }

        return $r;
    }

}
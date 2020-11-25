<?php

if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Базовый класс полей a2f
 */
class nc_a2f_field {

    public $name;
    public $value;
    public $default_value = "";
    public $validate_regexp;
    public $type, $typename;
    protected $has_default = 0;
    /**
     * родительская форма - чтобы
     */
    public $parent;

    /**
     * @param array
     */
    public function __construct($field_settings = '', nc_a2f $parent = null) {
        foreach ((array) $field_settings as $k => $v) {
            $this->$k = $v;
        }
        $this->parent = $parent;
    }

    public function save($value) {
        $this->value = $value;
    }

    function set_value($value) {
        $this->value = $value;
    }

    public function has_default() {
        return $this->has_default;
    }

    public function get_extend_parametrs() {
        return array();
    }

    /**
     * проверка значения на основе validate_regexp и оповещение
     * родительского объекта (nc_a2f) в случае, если значение не
     * соответствует условиям
     * @param mixed
     * @return boolean passed check
     */
    function validate_value($value) {
        if ($this->validate_regexp && !preg_match($this->validate_regexp, $value)) {
            $this->parent->set_validation_error($this->name, $this->validate_error);
            return false;
        }
        return true;
    }

    public function validate($value) {
        return true;
    }

    function get_value($use_default = true) {
        if (isset($this->value) || !$use_default) {
            return ($this->value);
        }
        return $this->default_value;
    }

    function render($template = "") {
        if (!$template) {
            $ret = $this->render_prefix().
                    $this->render_default_value().
                    $this->render_value_field().
                    $this->render_suffix();
        } else {
            $ret = str_replace(
                            array("%CAPTION", "%DEFAULT", "%VALUE"),
                            array($this->render_prefix(false), $this->render_default_value(false), $this->render_value_field(false)),
                            $template
            );
            $ret.= $this->render_suffix(false);
        }

        return $ret;
    }

    function settings_render($template = "") {
        $this->typename = constant('NETCAT_CUSTOM_TYPENAME_'.strtoupper($this->type));
        $ret = str_replace(array('%CAPTION', '%NAME', '%TYPENAME'), array($this->caption, $this->name, $this->typename), $template);

        return $ret;
    }

    /**
     * @access private
     */
    function render_prefix($html = true) {

        $err = $this->parent->get_field_error($this->name);

        if ($html) {
            $ret = "<div class='ncf_row".($err ? " ncf_error" : "")."'>".
                    "<div class='".( $this->type == 'divider' ? 'ncf_divider' : 'ncf_caption' )."'>{$this->caption}</div>";
        } else {
            $ret = $this->caption;
        }

        return $ret;
    }

    /**
     * @access private
     */
    function render_default_value($html = true) {

        if ($this->type != 'divider') {
            if ($html) {
                $ret = "<div class='ncf_default'>{$this->default_value}</div>";
            } else {
                $ret = $this->default_value;
            }
        }

        return $ret;
    }

    /**
     * @access private
     */
    function render_suffix($html = true) {

        if ($html) $ret = "</div>\n";

        return $ret;
    }

    /**
     * @access private
     */
    function get_field_name($added = '') {
        $array_name = $this->parent->array_name;
        if ($added) $added = '['.$added.']';
        return ($array_name ? $array_name."[".$this->name."]".$added : $this->name.$added );
    }

    function get_subtypes() {
        return array();
    }

}
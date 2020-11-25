<?php

if (!class_exists("nc_System")) die("Unable to load file.");

class nc_a2f_field_string extends nc_a2f_field {

    protected $has_default = 1;

    /**
     * @access private
     */
    function render_value_field($html = true) {

        if (!$this->size) $this->size = 20;
        $ret = "<input name='".$this->get_field_name()."' type='text' value='".htmlspecialchars(($this->value ? $this->value : $this->default_value), ENT_QUOTES)."' size='".$this->size."'  class='ncf_value_text'>";
        if ($html) {
            $ret = "<div class='ncf_value'>".$ret."</div>\n";
        }

        return $ret;
    }

    public function get_extend_parametrs() {
        return array('validate_regexp' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_REGEXP),
                'validate_error' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_ERROR),
                'size' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_SIZE_L)
        );
    }

    public function validate($value) {
        if (!$value) return true;
        if ($this->validate_regexp && !preg_match($this->validate_regexp, $value)) {
            $this->parent->set_validation_error($this->name, $this->validate_error);
            return false;
        }
        return true;
    }

}
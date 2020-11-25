<?php

if (!class_exists("nc_System")) die("Unable to load file.");

class nc_a2f_field_textarea extends nc_a2f_field {

    /**
     * @access private
     */
    function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();
        $system_env = $nc_core->get_settings();

        $textarea_id = $this->get_textarea_id();
        if ($this->embededitor) {
            $link = $system_env['EditorType'] == NC_FCKEDITOR ? "editors/FCKeditor/neditor.php" : "editors/ckeditor/neditor.php";
            $ret.= "<button type='button' onclick=\"window.open('".$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH.$link."?form=adminForm&control=".$textarea_id."', 'Editor', 'width=750,height=605,resizable=yes,scrollbars=no,toolbar=no,location=no,status=no,menubar=no');\">".TOOLS_HTML_INFO."</button><br />";
        }

        if (!$this->size) $this->size = 5;
        $ret .= "<textarea id='".$textarea_id."' name='".$this->get_field_name()."' rows='".$this->size."' class='ncf_value_textarea'>".htmlspecialchars(($this->value ? $this->value : $this->default_value), ENT_QUOTES)."</textarea>";
        if ($html) {
            $ret = "<div class='ncf_value'>".$ret."</div>\n";
        }



        return $ret;
    }

    public function get_extend_parametrs() {
        return array('embededitor' => array('type' => 'checkbox', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_VIZRED),
                'nl2br' => array('type' => 'checkbox', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_BR),
                'size' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_SIZE_H)
        );
    }

    protected function get_textarea_id() {
        $id = $this->get_field_name();
        return str_replace(array('[', ']'), '_', $id);
    }

}
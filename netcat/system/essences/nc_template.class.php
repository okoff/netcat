<?php

/* $Id: nc_template.class.php 5960 2012-01-17 17:25:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Template extends nc_Essence {

    protected $db;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) $this->db = $nc_core->db;
    }

    public function convert_subvariables($template_env) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // load system table fields
        $table_fields = $nc_core->get_system_table_fields($this->essence);
        // count
        $counted_fileds = count($table_fields);

        // %FIELD replace with inherited template field value
        for ($i = 0; $i < $counted_fileds; $i++) {
            $template_env["Header"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Header"]);
            $template_env["Footer"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Footer"]);
        }

        return $template_env;
    }

    protected function inherit($template_env) {
        global $perm, $AUTH_USER_ID, $templatePreview;

        // Блок для предпросмотра макетов дизайна
        $magic_gpc = get_magic_quotes_gpc();
        if ($template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
            foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                $template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
            }
        }

        $parent_template = $template_env["Parent_Template_ID"];

        if ($parent_template) {
            $parent_template_env = $this->get_by_id($parent_template);

            // Если мы вызываем предпросмотр для макета, а он используется в качестве родительского.
            if ($parent_template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
                foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                    $parent_template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
                }
            }

            $parent_template = $template_env["Parent_Template_ID"];

            if (!$template_env["Header"]) {
                $template_env["Header"] = $parent_template_env["Header"];
            } else {
                if ($parent_template_env["Header"]) {
                    $template_env["Header"] = str_replace("%Header", $parent_template_env["Header"], $template_env["Header"]);
                }
            }
            if (!$template_env["Footer"]) {
                $template_env["Footer"] = $parent_template_env["Footer"];
            } else {
                if ($parent_template_env["Footer"]) {
                    $template_env["Footer"] = str_replace("%Footer", $parent_template_env["Footer"], $template_env["Footer"]);
                }
            }
            $template_env["Settings"] = $parent_template_env["Settings"].$template_env["Settings"];

            $template_env = $this->inherit_system_fields($this->essence, $parent_template_env, $template_env);
            $parent_template = $parent_template_env["Parent_Template_ID"];
        }

        return $template_env;
    }

    public function update($template_id, $params = array()) {
        $db = $this->db;

        $template_id = intval($template_id);
        if (!$template_id || !is_array($params)) {
            return false;
        }

        $query = array();
        foreach ($params as $k => $v) {
            $query[] = "`".$k."` = '".(preg_match('/validate_regexp/', $v) ? $db->prepare($v) : $db->escape($v))."'";
        }

        if (!empty($query)) {
            $db->query("UPDATE `Template` SET ".join(', ', $query)." WHERE `Template_ID` = '".$template_id."' ");
            if ($db->is_error)
                    throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        //unset($this->data[$template_id]);
        $this->data = array();
        return true;
    }

    public function get_parent($id, $all = 0) {
        $id = intval($id);
        $ret = array();
        $parent_id = $this->db->get_var("SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = '".$id."' ");

        if (!$all) return intval($parent_id);

        if ($parent_id) {
            $ret[] = $parent_id;
            $ret = array_merge($ret, $this->get_parent($parent_id, 1));
        }

        return $ret;
    }

    public function get_childs($id) {
        $ret = array();
        $childs = $this->db->get_col("SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = '".intval($id)."'");

        if (!empty($childs))
                foreach ($childs as $v) {
                $ret[] = $v;
                $ret = array_merge($ret, $this->get_childs($v));
            }


        return $ret;
    }

}
?>

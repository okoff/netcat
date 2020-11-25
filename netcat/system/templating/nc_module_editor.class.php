<?php

class nc_module_editor {

    private $template = null;
    private $fields = array();
    private $module_types = array(
            'web' => null,
            'mobile' => null,
            'responsive' => null);
    private static $instanse = null;

    public function get_module_types() {
        return $this->module_types;
    }

    public function __construct() {
        self::$instanse = $this;
        $this->template = new nc_tpl(nc_Core::get_object()->MODULE_TEMPLATE_FOLDER, nc_Core::get_object()->db);
    }

    public function load($id) {
        foreach ($this->module_types as $type => $tmp) {
            $this->module_types[$type] = new self;
            $this->module_types[$type]->load_one($id, $type, "/$id/$type/");
        }
        return $this;
    }

    public function load_one($id, $type, $path) {
        $this->template->load($id, $type, $path);
    }

    public function fill() {
        foreach ($this->module_types as $type => $obj) {
            $this->fields[$type] = $obj->fill_fields()->get_fields();
        }
        return $this;
    }

    public function fill_fields() {
        foreach ($this->template->fields->standart as $field_name => $tmp) {
            $this->template->fields->standart[$field_name] = $this->get_content($field_name);
        }
        return $this;
    }

    private function get_content($field) {
        $field_path = $this->template->fields->get_path($field);
        return nc_check_file($field_path) ? nc_get_file($field_path) : false;
    }

    public function get_all_fields() {
        return $this->fields;
    }

    public function get_fields() {
         return $this->template->fields->standart;
    }

    public function save($post_fields) {
        foreach ($this->module_types as $module_type) {
            $module_type->save_fields($post_fields);
        }
    }

    public function save_fields($post_fields) {
	$magic_quotes = get_magic_quotes_gpc();
        foreach ($this->template->fields->standart as $field_name => $tmp) {
            $data = $magic_quotes ? stripslashes($post_fields[$this->template->type . '_' . $field_name]) : $post_fields[$this->template->type . '_' . $field_name];
            nc_save_file($this->template->fields->get_path($field_name), $data);
        }
    }

    public static function get_instanse() {
        return self::$instanse;
    }

}
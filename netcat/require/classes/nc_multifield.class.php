<?php

class nc_multifield {

    private $settings = null;
    private $template = null;
    private $records = array();
    private $name = null;
    private $desc = null;

    public function __construct($name, $desc = null) {
        $this->settings = new nc_multifield_settings($this);
        $this->template = new nc_multifield_template($this);
        $this->name = $name;
        $this->desc = $desc;
    }

    public function set_data($data) {
        $this->records = $data;
        return $this;
    }

    public function to_array() {
        return $this->records;
    }

    public function form() {
        return $this->template->get_form();
    }

    public function get_record($record_num = 1) {
        $multifield = new self($this->name, $this->desc);
        return $multifield->set_data(array($this->records[+$record_num - 1]))->template->set($this->template->template)->get_html();
    }

    public function get_random_record() {
        return $this->get_record(mt_rand(1, $this->count()));
    }

    public function count() {
        return count($this->records);
    }

    public function __toString() {
        return $this->template->get_html();
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }
}
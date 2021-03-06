<?php

class nc_tpl_parser {
    private $template = null;
    private $parser_working = null;

    private function __construct($template) {
        $this->template = $template;
        $this->parser_working = new nc_tpl_parser_working($this->template);
    }

    private function is_modify($hash) {
        return $hash != $this->get_current_hash();
    }

    private function get_current_hash() {
        $path = $this->template->fields->get_path($this->template->type);
        return md5(nc_check_file($path) ? nc_get_file($path) : false);
    }

    private function work($type) {
        $this->parser_working->$type()->save()->update_hash($this->get_current_hash());
    }

    static public function main2parts(nc_tpl $template, $hash) {
        $parser = new self($template);

        if ($parser->is_modify($hash)) {
            $parser->work('main2part');
        }
    }

    static public function parts2main(nc_tpl $template) {
        $parser = new self($template);
        $parser->work('part2main');
    }
}

class nc_tpl_parser_working {
    private $fields = array();
    private $template = null;
    private $main = null;
    private $all_fields = array(
            'Template' => array(
                    'Header' => null,
                    'Footer' => null),

            'Class' => array(
                    'FormPrefix' => null,
                    'RecordTemplate' => null,
                    'FormSuffix' => null));

    public function __construct(nc_tpl $template) {
        $this->template = $template;
        $this->fields = $this->all_fields[$this->template->type];
    }

    public function main2part() {
        $path = $this->template->fields->get_path($this->template->type);
        $main = nc_check_file($path) ? nc_get_file($path) : false;
        foreach ($this->fields as $field_name => $tmp) {
            if(preg_match("#<!-- ?$field_name ?-->(.*)<!-- ?/ ?$field_name ?-->#is", $main, $matches)) {
                $this->fields[$field_name] = $field_name == 'RecordTemplate' ? nc_add_service_string_suffix_for_RecordTemplate($matches[1]) : $matches[1];
            }
        }
        return $this;
    }

    public function part2main() {
        $fields = array();
        foreach ($this->fields as $field_name => $tmp) {
            $path = $this->template->fields->get_path($field_name);
            $content = nc_check_file($path) ? nc_get_file($path) : false;
            $fields[$field_name] = "<!-- $field_name -->" . ($field_name == 'RecordTemplate' ? nc_cleaned_RecordTemplate_of_string_service($content): $content) . "<!-- /$field_name -->";
        }

        $this->main = join("\n\n", $fields);
        return $this;
    }

    public function save() {
        if ($this->main) {
            nc_save_file($this->template->fields->get_path($this->template->type), $this->main);
        }

        foreach ($this->fields as $field_name => $content) {
            if ($content === null) { continue; }
            nc_save_file($this->template->fields->get_path($field_name), $content);
        }
        return $this;
    }

    public function update_hash($hash) {
        $SQL = "UPDATE {$this->template->type}
                    SET File_Hash = '$hash'
                        WHERE {$this->template->type}_ID = {$this->template->id}";
        return nc_Core::get_object()->db->query($SQL);
    }
}

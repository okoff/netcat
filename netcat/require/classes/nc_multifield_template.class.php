<?php

class nc_multifield_template {

    private $multifield = null;
    private $template = array();
    private static $dnd = null;

    public function __construct(nc_multifield $multifield) {
        $this->multifield = $multifield;
        if (self::$dnd === null) {
            $nc_core = nc_Core::get_object();
            /*self::$dnd = "<script>
                          if (typeof(jQuery) == 'undefined') {
                              document.write(\"<scr\" + \"ipt type='text/javascript' src='{$nc_core->NC_JQUERY_PATH}'></scr\" + \"ipt>\");
                          }
                          </script>";*/
            #self::$dnd .= "<script type='text/javascript' src='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . "jquery/tablednd.min.js'></script>";
            self::$dnd .= "<style> .DTDClass { background-color: #EEE; } .DTD { cursor: move; } </style>";
        }
    }
    
    public function set($template) {
        $this->template = $template;
        return $this;
    }
    
    public function get_html() {
        return !empty($this->template) && isset($this->multifield->records[0]) ? $this->template['prefix'].$this->create_record_template().$this->template['suffix'] : '';
    }
    
    private function create_record_template() {
        $records = array();
        $i = intval($this->template['i']);
        foreach($this->multifield->records as $record) {
            $records[] = str_replace('%i%', $i, $this->apply_record_tpl($record));
            $i++;
        }
        return join($this->template['divider'], $records);
    }
    
    private function apply_record_tpl($record) {
        $record_tpl = $this->template['record'];
        foreach($record as $key => $value) {
            $record_tpl = str_replace("%$key%", $value, $record_tpl);
        }
        return $record_tpl;
    }
    
    public function get_form() {
        $html = $this->multifield->desc ? "<div>{$this->multifield->desc}:</div>" : "";
        $html .= $this->get_edit_form();
        $html .= "<div id='div_{$this->multifield->name}'>";
        $html .= $this->get_setting_html('use_name');
        $html .= $this->get_setting_html('path');
        $html .= $this->get_img_settings_html('preview');
        $html .= $this->get_img_settings_html('resize');
        $html .= $this->get_setting_html('min');
        $html .= $this->get_setting_html('max');
        $html .= "<input type='hidden' name='settings_{$this->multifield->name}_hash' value='".$this->multifield->settings->get_setting_hash()."'/>";
        $html .= "<script>
                      var min_{$this->multifield->name} = {$this->multifield->settings->min};
                      var max_{$this->multifield->name} = {$this->multifield->settings->max};
                      var current_{$this->multifield->name} = 0;
                      function add_field_{$this->multifield->name}() {
                          var new_div = document.createElement('div');
                          new_div.innerHTML = \"".($this->multifield->settings->use_name ? "<br />{$this->multifield->settings->custom_name}: <input name='f_{$this->multifield->name}_name[]' />" : '')."\" +
                                          \"<div><input name='f_{$this->multifield->name}_file[]' type='file' size='50' />\" +                
                                          \"</div>\";                          
                          document.getElementById('div_{$this->multifield->name}').appendChild(new_div);
                          current_{$this->multifield->name}++;
                          if (max_{$this->multifield->name} && (current_{$this->multifield->name} >= max_{$this->multifield->name})) {
                              document.getElementById('add_{$this->multifield->name}').innerHTML = '';
                          }
                      }
                      var i = 0;
                      do {
                          i++;
                          add_field_{$this->multifield->name}();
                      } while (i < min_{$this->multifield->name});
                  </script>";
        $html .= "</div>";
        $html .= "<div id='add_{$this->multifield->name}'><a href='' onClick='add_field_{$this->multifield->name}(); return false;'>".NETCAT_MODERATION_ADD."</a></div>";
        return $html;
    }
    
    private function get_edit_form() {
        $result = null;
        if(isset($this->multifield->records[0]->Field_ID)) {
            $result .= self::$dnd;
            $result .= "<script type='text/javascript'>
                            \$nc(document).ready(function() {
                                \$nc('#table{$this->multifield->records[0]->Field_ID}').tableDnD({
                                    onDragClass: 'DTDClass',
                                    dragHandle: 'DTD'
                                });
                            });
                        </script>";
            $result .= "<table cellspacing='0' cellpadding='2' id='table{$this->multifield->records[0]->Field_ID}'>";
            foreach ($this->multifield->records as $record) {
                $file_name = $this->get_file_name($record->Path);
                $result .= "<tr>
                                <td class='DTD'>
                                    <div class='icons icon_type_file'></div>
                                    <input type='hidden' name='priority_multifile[{$record->Field_ID}][]' value='$record->ID' />
                                </td>
                                <td>
                                    <a style='vertical-align: top;' href='{$record->Path}'>$file_name</a> (".nc_bytes2size($record->Size).")
                                    ".NETCAT_MODERATION_DELETE." <input type='checkbox' name='del_multifile[]' value='{$record->ID}'>
                                </td>
                            </tr>";
            }
            $result .= "</table>";
            self::$dnd = '';
        }
        return $result;
    }
    
    private function get_setting_html($type) {
        return "<input type='hidden' name='settings_{$this->multifield->name}[$type]' value='{$this->multifield->settings->{$type}}' />";
    }
    
    private function get_file_name($path) {
        $file_name = explode('/', $path);
        return $file_name[count($file_name) - 1];
    }

    private function get_img_settings_html($type) {
        return "<input type='hidden' name='settings_{$this->multifield->name}[$type][width]' value='{$this->multifield->settings->{$type.'_width'}}' />
                 <input type='hidden' name='settings_{$this->multifield->name}[$type][height]' value='{$this->multifield->settings->{$type.'_height'}}' />
                   <input type='hidden' name='settings_{$this->multifield->name}[$type][mode]' value='{$this->multifield->settings->{$type.'_mode'}}' />";
    }
    
    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }

}
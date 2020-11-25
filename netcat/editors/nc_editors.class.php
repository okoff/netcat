<?php

class nc_Editors {
  protected $core, $editor_id;
  protected $editor, $html;
  // имя textarea и значение
  protected $name, $value;

  public function  __construct(  $editor_id, $name, $value = '' ) {
    $this->core = nc_Core::get_object();
    $this->name = $name;
    $this->value = $value;

    $editors = array( 2 => 'fckeditor', 3 => 'ckeditor');

    call_user_func(array( $this, "_make_".$editors[$editor_id])) ;
  }

  public function get_html () {
    return $this->html;
  }

  protected function _make_fckeditor () {
    $lang = $this->core->lang->detect_lang(1);
    if ( $lang == 'ru' ) $lang = $this->core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";

    if ( !class_exists("FCKeditor") ) include_once ($this->core->ROOT_FOLDER."editors/FCKeditor/fckeditor.php");
    $this->editor = new FCKeditor($this->name);
    $this->editor->BasePath = $this->core->SUB_FOLDER.$this->core->HTTP_ROOT_PATH."editors/FCKeditor/";
    $this->editor->Config["SmileyPath"] = $this->core->SUB_FOLDER."/images/smiles/";
    $this->editor->ToolbarSet = "NetCat1";
    $this->editor->Width = "100%";
    $this->editor->Height = "320";
    $this->editor->Value = $this->value;
    $this->editor->Config['DefaultLanguage'] = $lang;
    if ($nc_core->AUTHORIZATION_TYPE == 'session') $this->editor->Config['sid'] = session_id();
    $this->html = $this->editor->CreateHtml();
  }


  protected function _make_ckeditor () {
    if ( !class_exists("CKEditor") ) include_once ($this->core->ROOT_FOLDER."editors/ckeditor/ckeditor_php5.php");

    // загружаем тему
    $skin = 'kama';
    $data = @file_get_contents($this->core->ROOT_FOLDER."editors/nc_settings.xml");
    if ($data) {
      $parser = xml_parser_create();
      xml_parse_into_struct($parser, $data, $values, $indexes);
      xml_parser_free($parser);
      if ( !empty($values) ) foreach ( $values as $v  ) {
        if ( $v['attributes']['NAME'] == 'ck_skin' ) $skin = $v['value'];
      }
    }
    $lang = $this->core->lang->detect_lang(1);
    if ( $lang == 'ru' ) $lang = $this->core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";
    
    $config = array('width' => '100%', 'height' => 320,
                    'language' => $lang,
                    'smiley_path' => $this->core->SUB_FOLDER."/images/smiles/",
                    'skin' => $skin
                    );
    $this->editor = new  CKEditor();
    $this->editor->basePath = $this->core->SUB_FOLDER.$this->core->HTTP_ROOT_PATH."editors/ckeditor/";
    $this->editor->returnOutput = 1;
    $this->html  = $this->editor->editor($this->name, $this->value, $config);
    
  }
}

?>

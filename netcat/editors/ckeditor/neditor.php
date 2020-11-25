<?php
/*$Id */

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );

include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ROOT_FOLDER."connect_io.php");

if (!isset($_GET['form']) || !isset($_GET['control'])) {
  die("Incorrect params");
}

$lang = $nc_core->lang->detect_lang();
include($ADMIN_FOLDER."lang/".$lang.".php");
$lang = $nc_core->lang->acronym_from_full($lang);
if ( $lang == 'ru' ) $lang = $nc_core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";

$skin = 'kama';
$data = @file_get_contents($ROOT_FOLDER."editors/nc_settings.xml");
if ($data) {
  $parser = xml_parser_create();
  xml_parse_into_struct($parser, $data, $values, $indexes);
  xml_parser_free($parser);
  if ( !empty($values) ) foreach ( $values as $v  ) {
    if ( $v['attributes']['NAME'] == 'ck_skin' ) $skin = $v['value'];
  }
}


?>

<html>
  <head>
    <title>NetCat</title>
    <meta http-equiv='Content-Type' content='text/html; charset=<?=$nc_core->NC_CHARSET?>'>
    <style type='text/css'>
      body { margin: 2px; background-color: #EEEEEE; }
    </style>
    <script type='text/javascript' src='<?=$SUB_FOLDER.$HTTP_ROOT_PATH?>editors/ckeditor/ckeditor.js'></script>
  </head>
  <body>

  <form name='EditorBackForm' style='margin:0px;'>
    <textarea id='nc_editor'></textarea>
    <input type='button' value='<?=NETCAT_SETTINGS_EDITOR_SEND?>' onclick="OnCloseWindow();">
  </form>

  <script type='text/javascript'>
  <!--
  document.getElementById('nc_editor').value = opener.document.forms['<?=$_GET['form']?>'].elements['<?=$_GET['control']?>'].value;

  CKEDITOR.replace('nc_editor', {
                    skin : '<?=$skin?>',
                    width: '100%', height: 330,
                    language : '<?=$lang?>',
                    smiley_path : '<?=$nc_core->SUB_FOLDER?>/images/smiles/'
                    });


  function OnCloseWindow()  {
    var return_value = CKEDITOR.instances.nc_editor.getData(); ;
    opener.document.forms['<?=$_GET['form']?>'].elements['<?=$_GET['control']?>'].value = return_value;
    window.close();
  }



  </script>

</body>
</html>

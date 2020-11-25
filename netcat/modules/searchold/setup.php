<?php
/* $Id: setup.php 4001 2010-09-17 13:42:41Z denis $ */

$module_keyword = "search";
$main_section = "settings";
$item_id = 3;

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$Title1=NETCAT_MODULES_TUNING;
$Title2=NETCAT_MODULES;

if ( !($perm->isSupervisor() || $perm->isGuest()) ) {
  BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/modules/");
  nc_print_status($NO_RIGHTS_MESSAGE, 'error');
  EndHtml ();
  exit;
}

// проверка, установлен этот модуль или нет
$res = $db->get_row("SELECT * FROM `Module` WHERE `Keyword` = '".$db->escape($module_keyword)."' AND `Installed` = 0", ARRAY_A);

if(!$db->num_rows ){
  BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/modules/");
  nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
  EndHtml();
  exit;
}
else {
  $module_data = $res;
}

// load modules env
$lang = $nc_core->lang->detect_lang(1);
$MODULE_VARS = $nc_core->modules->load_env($lang);

$ClassID = intval($MODULE_VARS['search']['INDEX_TABLE']);

if ( !isset($phase) ) $phase=2;

switch ($phase) {
    case 1:
      BeginHtml ($Title1, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    break;

    case 2:
      BeginHtml ($Title1, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
      SelectParentSub();
    break;

    case 3:
      BeginHtml ($Title1, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");

      InsertSub(NETCAT_MODULE_SERCH_TITLE, "search", "", 0, 0, 0, 0, 0, $ClassID, $SubdivisionID, $CatalogueID, "search",1);
      UpdateHiddenURL("/", 0, $CatalogueID);
      
      // пометим как установленный
      $db->query("UPDATE `Module` SET `Installed` = 1 WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
      echo "<br><br>";
      nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
    break;
}

EndHtml ();
?>
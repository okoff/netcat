<?php
/* $Id: cron.php 3827 2010-06-18 14:20:18Z denis $ */

// ”далите эту и следующую строку, если вы используете этот скрипт
exit;

// if register_globals==off
$param = $_GET['param'];

// ”кажите значение параметра, заданного в '”правление задачами'
$check="test";

if ($check!=$param) {
	echo "Non-authorized access!";
	exit;
}

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($MODULE_FOLDER."search/admin.inc.php");
require ($ROOT_FOLDER."connect_io.php");


//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

$isConsole = 1;

$url = "http://".$MODULE_VARS['searchold']['START_URL'];
$allowed_urls = explode(",",$MODULE_VARS['searchold']['ALLOWED_URLS']);
if ($MODULE_VARS['searchold']['DISALLOWED_URLS']) $disallowed_urls = explode(",",$MODULE_VARS['searchold']['DISALLOWED_URLS']);
if ($MODULE_VARS['searchold']['DISALLOWED_REGEXP'])  { $disallowed_regexp = explode (", ",(get_magic_quotes_gpc()?stripslashes($MODULE_VARS['searchold']['DISALLOWED_REGEXP']):$MODULE_VARS['searchold']['DISALLOWED_REGEXP']) ); }
$index_table = intval($MODULE_VARS['searchold']['INDEX_TABLE']);

if (!$repeat) {
	$db->query("TRUNCATE TABLE `Message".$index_table."`");
	$db->query("INSERT INTO `Message".$index_table."` (URL,Checked,Created) VALUES ('".$url."',0,NOW())");
}
else {
	$res = $db->get_row("SELECT `URL`, `Message_ID` FROM `Message".$index_table."` WHERE `Checked` = 0 AND `Indexed` = 0 ORDER BY `Message_ID`");
	if ($db->num_res) {
		$url = $res->URL;
		$msgid = $res->Message_ID;
		$isurl = 1;
	}
}

echo "<html>";
echo "<body>";
echo "<ol>";

$count = 0;
while ($url) {

	if ($count && $count%$MODULE_VARS['searchold']['PAGES_COUNT']==0 ) echo "<script type='text/javascript' language='JavaScript'>\nwindow.location.href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/search/cron.php?param=".$param."&repeat=".($repeat+$MODULE_VARS['searchold']['PAGES_COUNT'])."';\n</script>";
	$count++;
	
  search_indexDocument($url);
  
  $res = $db->get_row("SELECT `URL`, `Message_ID` FROM `Message".$index_table."` WHERE `Checked` = 0 AND `Indexed` = 0");
  if ($db->num_rows) {
    $url = $res->URL;
    $msgid = $res->Message_ID;
  }
  else {
    $url = "";
  }

}

echo "</ol>";
echo "</body></html>";

$db->query("DELETE FROM `Message".$index_table."` WHERE `Body` = '' AND `Checked` = 1 AND `Indexed` = 1");
$db->query("UPDATE `Message".$index_table."` SET `Source` = '' WHERE `Checked` = 1 AND `Indexed` = 1");

print "DONE";

?>
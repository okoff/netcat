#!/usr/local/bin/php
<?
/* $Id: crontab.php 3843 2010-06-28 11:52:24Z denis $ */

// Определим путь до DOCUMENT_ROOT (на 3 уровня выше текущей директории):
#$DOCUMENT_ROOT = join('/', array_slice(explode('/', $_SERVER['SCRIPT_FILENAME']), 0, -4));
#putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$_SERVER['HTTP_HOST'] = "den.loc"; # Домен


$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");
require_once ($MODULE_FOLDER."search/admin.inc.php");



$res = $db->get_results("SELECT Keyword,Parameters FROM Module ORDER BY Module_ID");

foreach ($res as $ress) {
	$query_string = str_replace("\n","&",$ress->Parameters);
	parse_str ($query_string, $MODULE_VARS[$ress->Keyword]);

	while (list($var,$val)=each($MODULE_VARS[$ress->Keyword]))
		$MODULE_VARS[$ress->Keyword][$var] = trim($val);

}


$isConsole=1;
$url = "http://".$MODULE_VARS[searchold][START_URL];
$allowed_urls = explode(",",$MODULE_VARS[searchold][ALLOWED_URLS]);
$disallowed_urls = explode(",",$MODULE_VARS[searchold][DISALLOWED_URLS]);
$index_table = $MODULE_VARS[searchold][INDEX_TABLE];


$db->query("TRUNCATE TABLE Message${index_table}");
$db->query("INSERT INTO Message${index_table} (URL,Checked,Created) VALUES ('".$url."',0,NOW())");

while ($url) {
    search_indexDocument($url);
    $url = $db->get_var("SELECT URL FROM Message${index_table} WHERE Checked=0 AND Indexed=0 LIMIT 1");

}

print "DONE";

?>
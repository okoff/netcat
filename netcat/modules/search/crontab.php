#!/usr/local/bin/php
<?
/* $Id: crontab.php 1854 2008-03-11 08:16:31Z vadim $ */

// ��������� ���� �� DOCUMENT_ROOT (�� 3 ������ ���� ������� ����������):
#$DOCUMENT_ROOT = join('/', array_slice(explode('/', $_SERVER['SCRIPT_FILENAME']), 0, -4));
#putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$_SERVER['HTTP_HOST'] = "example.net"; # �����


$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($INCLUDE_FOLDER."s_loadenv.inc.php");
require_once ($MODULE_FOLDER."search/admin.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");


$res = $db->get_results("SELECT Keyword,Parameters FROM Module ORDER BY Module_ID");

foreach ($res as $ress) {
	$query_string = str_replace("\n","&",$ress->Parameters);
	parse_str ($query_string, $MODULE_VARS[$ress->Keyword]);

	while (list($var,$val)=each($MODULE_VARS[$ress->Keyword]))
		$MODULE_VARS[$ress->Keyword][$var] = trim($val);

}


$isConsole=1;
$url = "http://".$MODULE_VARS[search][START_URL];
$allowed_urls = explode(",",$MODULE_VARS[search][ALLOWED_URLS]);
$disallowed_urls = explode(",",$MODULE_VARS[search][DISALLOWED_URLS]);
$index_table = $MODULE_VARS[search][INDEX_TABLE];


$db->query("TRUNCATE TABLE Message${index_table}");
$db->query("INSERT INTO Message${index_table} (URL,Checked,Created) VALUES ('".$url."',0,NOW())");

while ($url) {
    search_indexDocument($url);
    $url = $db->get_var("SELECT URL FROM Message${index_table} WHERE Checked=0 AND Indexed=0 LIMIT 1");

}

print "DONE";

?>
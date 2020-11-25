<?
/* $Id: admin.php 3964 2010-09-03 15:36:32Z denis $ */

$main_section = "settings";
$item_id = 3;
error_reporting(E_ALL^E_NOTICE);
$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($MODULE_FOLDER."searchold/admin.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

require ($ADMIN_FOLDER."modules/ui.php");
$UI_CONFIG = new ui_config_module('searchold');

if (is_file($MODULE_FOLDER."searchold/".MAIN_LANG.".lang.php")) {
    require_once ($MODULE_FOLDER."searchold/".MAIN_LANG.".lang.php");
} else {
    require_once ($MODULE_FOLDER."searchold/en.lang.php");
}

$Delimeter = " &gt ";
$Title1 = "<a href=".$ADMIN_PATH."modules/>".NETCAT_MODULES."</a>".$Delimeter.NETCAT_MODULE_SERCH_TITLE;
$Title2 = NETCAT_MODULE_SERCH_TITLE;

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

if ($page=='brokenlinks') { $phase=3; }
if (!isset($phase)) $phase=1;

switch ($phase) {


    case 1: # main
        BeginHtml ($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/search/");
        // check permission
        $perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 0);

        // проверка allow_url_fopen
        if ( !ini_get('allow_url_fopen') ) {
          nc_print_status(NETCAT_MODULE_SERCH_ADMIN_ALLOW_URL_FOPEN, 'error');
          break;
        }
        
        $n=$db->get_var("SELECT COUNT(*) from Message".$MODULE_VARS['searchold']['INDEX_TABLE']);
        if($n>0){

            print "<table>";
            print "<tr><td>".NETCAT_MODULE_SERCH_ADMIN_LASTINDEX.":</td><td>".last_indexed()."</td></tr>";
            print "<tr><td>".NETCAT_MODULE_SERCH_ADMIN_DOCSINDEX.":</td><td>".getTotal()." (".round(getBytes()/1024)."K)</td></tr>";
			print "<tr><td colspan=2><a href=".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/searchold/admin.php?phase=3>".NETCAT_MODULE_SERCH_ADMIN_BROKENLINKS."</a></td></tr>";

            print "</table><hr size=1>";

            index_query();

        } else{
            print NETCAT_MODULE_SERCH_ADMIN_NOTINDEX;
            index_query();
        }

        break;

    case 2: #index
         // check permission
        $perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);
        $url = "http://".$MODULE_VARS['searchold']['START_URL'];
        $allowed_urls = explode(",",$MODULE_VARS['searchold']['ALLOWED_URLS']);
        if ($MODULE_VARS['searchold']['DISALLOWED_URLS']) $disallowed_urls = explode(",",$MODULE_VARS['searchold']['DISALLOWED_URLS']);
        if ($MODULE_VARS['searchold']['DISALLOWED_REGEXP'])  { $disallowed_regexp = explode (", ",(get_magic_quotes_gpc()?stripslashes($MODULE_VARS['searchold']['DISALLOWED_REGEXP']):$MODULE_VARS['searchold']['DISALLOWED_REGEXP']) ); }
        $index_table = $MODULE_VARS['searchold']['INDEX_TABLE'];

		if (!$repeat) {
			$db->query("TRUNCATE TABLE Message${index_table}");
			$db->query("INSERT INTO Message${index_table} (URL,Checked,Created) VALUES ('".$url."',0,NOW())");
		} else {
			$res = $db->get_row("SELECT URL,Message_ID FROM Message".$index_table." WHERE Checked=0 AND Indexed=0 ORDER BY Message_ID LIMIT 1");
			if ($db->num_rows) {
				$url=$res->URL;
				$msgid=$res->Message_ID;
				$isurl=1;
			}
		}

		while (@ob_end_clean());
    echo "<html>";
		echo "<body>";
		echo "<ol>";

		$count=0;
		while ($url) {
			if ($count && $count%$MODULE_VARS['searchold']['PAGES_COUNT']==0 ) {
				 exit("<script language=JavaScript>\nwindow.location.href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/searchold/admin.php?phase=2&repeat=".($repeat+$MODULE_VARS['searchold']['PAGES_COUNT'])."';\n</script>");
			}
			$count++;
			search_indexDocument($url);
			$res = $db->get_row("SELECT URL,Message_ID FROM Message".$index_table." WHERE Checked=0 AND Indexed=0 LIMIT 1");
			if ($db->num_rows) {
        		$url = $res->URL;
				$msgid = $res->Message_ID;
    		} else {
        		$url = "";
    		}
		}

        echo "</ol>";
		$db->query("DELETE FROM Message${index_table} WHERE Body='' AND Checked=1 AND Indexed=1");
		$db->query("UPDATE Message${index_table} SET Source='' WHERE Checked=1 AND Indexed=1");
		echo "DONE.</body></html>";
	break;

	case 3: #broken links
	  // check permission
    $perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 0);
	  $UI_CONFIG = new ui_config_tool(NETCAT_MODULE_SERCH_ADMIN_BROKENLINKS, NETCAT_MODULE_SERCH_ADMIN_BROKENLINKS, 'i_module_search_big.gif', "module.searchold(brokenlinks)");
		BeginHtml ($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/search/");
		ShowBrokenLinks();
	break;


    default:
        break;
}

if ($phase!=2) EndHtml ();


?>

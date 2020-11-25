<?
/* $Id: admin.inc.php 3678 2009-12-31 06:03:43Z denis $ */

/**
 *Возвращается дата последней индексации сайтов
 */
function last_indexed(){
    global $db,$MODULE_VARS;
    $last=$db->get_var("SELECT MAX(Created) from Message".$MODULE_VARS['search']['INDEX_TABLE']);
    return $last;
}

/**
 *Возвращается объем индекса (информации в БД)
 */
function getBytes(){
    global $db,$MODULE_VARS;
    $last=$db->get_var("SELECT SUM(Size) from Message".$MODULE_VARS['search']['INDEX_TABLE']);
    return $last;
}

/**
 *Возвращается проиндексированных страниц
 */
function getTotal(){
    global $db,$MODULE_VARS;
    $last=$db->get_var("SELECT count(*) from Message".$MODULE_VARS['search']['INDEX_TABLE']);
    return $last;
}

/**
 *Отображается список неработающих ссылок
 */
function ShowBrokenLinks() {
	global $db,$MODULE_VARS;

	$select="SELECT URL, Source, Message_ID FROM Message".$MODULE_VARS['search']['INDEX_TABLE']." WHERE Checked=0 ORDER BY URL";

	if ($Result = $db->get_results($select,ARRAY_N)) {
		echo "<b>".NETCAT_MODULE_SERCH_ADMIN_BROKENLINKS."</b><br><br>\r\n<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td bgcolor=cccccc>\r\n<table border=0 cellpadding=4 cellspacing=1 width=100%>\r\n<tr>\r\n";
		echo "<td bgcolor=eeeeee><font size=-2><b>Ссылка</td>\r\n";
		echo "<td bgcolor=eeeeee><font size=-2><b>Ссылающиеся страницы</td>\r\n";
		echo "</tr>\r\n";
		foreach ($Result as $Array ) {
			echo "<tr>\r\n";
			echo "<td bgcolor=white nowrap valign=top><a href=".$Array[0]." target=_blank>".$Array[0]."</a></td>\r\n";
			echo "<td bgcolor=white width=100%><a href=# onclick=\"if(document.getElementById('url".$Array[2]."').style.display=='block') {document.getElementById('url".$Array[2]."').style.display='none';} else {document.getElementById('url".$Array[2]."').style.display='block';} return false;\">".NETCAT_MODULE_SERCH_ADMIN_SHOWLINKS."</a><div id=url".$Array[2]." style='position:relative;display:none;'><br>".nl2br($Array[1])."</div><noscript><br>".nl2br($Array[1])."</noscript></td>\r\n";
			echo "</tr>\r\n";
		}
		echo "</table></td></tr></table>\r\n";
		echo "<br><i>".NETCAT_MODULE_SERCH_ADMIN_COMMENT."</i>";

	}
	else echo NETCAT_MODULE_SERCH_ADMIN_NONBROKENLINKS;
}


/**
 *Отображается форма индексирования сайтов
 */
function index_query(){
global $UI_CONFIG;

	print "<form method='get' action='admin.php' target='index' id='search_index_query'>";
	print "<div align='right'>";

	$UI_CONFIG->actionButtons[] = array("id" => "submit",
		"caption" => NETCAT_MODULE_SERCH_ADMIN_DOINDEX,
		"action" => "window.open('','index','width=400,height=500,resizable=yes,scrollbars=yes'); mainView.submitIframeForm('search_index_query');");

	print "<input type='submit' class='hidden'>";
	print "<input type='hidden' name='phase' value='2'>";
	print "</div></form>";

}

/**
 *Функция индексирования сайтов
 */
function search_indexDocument($url) {
    global $db, $index_table,$allowed_urls,$disallowed_urls, $disallowed_regexp, $isConsole, $msgid;

    $whatsid="sid=";
    $parsed_url = parse_url($url);

    $pos1 = strpos($parsed_url['query'], $whatsid);

    if ($pos1!=false) $parsed_url['query']=substr_replace($parsed_url['query'], '', $pos1, 36);

	echo ($isConsole?"":"<li value=".$msgid.">").date("H:i:s")." <br>".$url;

    $source_url = $parsed_url['scheme']."://".($parsed_url['user']?$parsed_url['user']:"").($parsed_url['pass']?":".$parsed_url['pass']:"").(($parsed_url['user'] || $parsed_url['pass'])?"@":"").$parsed_url['host'].($parsed_url['port']?":".$parsed_url['port']:"").$parsed_url['path'].($parsed_url['query']?"?".$parsed_url['query']:"");
    $url = $source_url;
    $url_root_break = $parsed_url['scheme']."://".($parsed_url['user']?$parsed_url['user']:"").($parsed_url['pass']?":".$parsed_url['pass']:"").(($parsed_url['user'] || $parsed_url['pass'])?"@":"").$parsed_url['host'].($parsed_url['port']?":".$parsed_url['port']:"");
    $url_path_break = $url_root_break.(($parsed_url['path'] && substr($parsed_url['path'],0,1)!="/")?"/":"").(substr($parsed_url['path'],strlen($parsed_url['path'])-1,1)=="/"?$parsed_url['path']:substr($parsed_url['path'],0,strlen($parsed_url['path']) - strlen(strrchr($parsed_url['path'],"/"))+1));
    if (substr($url_path_break,strlen($url_path_break)-1,1)!="/") $url_path_break .= "/";

//    $contents = @file($url);
//    $contents = join ("", $contents);
    
    if ($resource = @fopen($url, "rb"))
    {
       // read only first 2Mb to prevent memory limit error
       $max_page_length = 2097152;
       while (!feof($resource))
       {
          $contents .= fread($resource, 8192);
          if (strlen($contents) > $max_page_length) { break; }
       }
       fclose($resource);
    }

	if ($contents) {
		echo "<br> Ok";
	} else {
		echo "<br> <font color=red>Failed</font>\n";

	}

    if ($contents) {

        $sizeofpage = strlen($contents);
        echo ", $sizeofpage bytes\n";

        preg_match_all("/<a\s+[^>]*href\s*=\s*['\"]?([^ \"'>\t#]+)/ims", $contents, $document_url);

        $contents = str_replace("\n", " ", $contents);
        $contents = str_replace("\r", "",  $contents);
        $contents = str_replace("<", " <", $contents);

        preg_match("/<title>(.+?)<\/title>/i", $contents, $document_title);
        $title = trim($document_title[1]);

        preg_match("/<!-- content -->(.+)<!-- \/content -->/i", $contents, $searchable_content);
        $searchable_content = trim($searchable_content[1]);

        if ($searchable_content)
        {
           $search_re = array (0 => "'\s{2,}'",
                               1 => "'<script[^>]*?>.*?</script>'i",
                               2 => "'<title[^>]*?>.*?</title>'i",
                               3 => "'<style[^>]*?>.*?</style>'i",
                               4 => "'<!-- \/content -->.*?<!-- content -->'i",
                               5 => "'<[\/\!]*?[^<>]*?>'i",
                               6 => "'&#(\d+);'e");

           $replace_re = array(0 => " ",
                               1 => "",
                               2 => "",
                               3 => "",
                               4 => "",
                               5 => "",
                               6 => "chr(\\1)");

           $searchable_content = preg_replace($search_re, $replace_re, $searchable_content);
           $searchable_content = trim(strip_tags($searchable_content));

           $searchable_content = str_replace(
                                  array('&quot;', '«', '»', '&amp;', '&lt;', '&gt;', '&nbsp;', '&iexcl;', '&cent;', '&pound;', '©'),
                                  array('"',      '"',       '"',       "&",      "<",   ">",    " ",      chr(161),  chr(162), chr(163),  chr(169)),
                                  $searchable_content);
       }
    }

    ob_flush();
    flush();
    $update = "UPDATE Message${index_table} SET ".($contents?"Title='".addslashes($title)."',Body='".addslashes($searchable_content)."',Checked=1,Size=".$sizeofpage.",":"")."Indexed=1 WHERE URL='".$url."'";
    $db->query($update);


    for ($i=0;$i<count($document_url[1]);$i++) {
        
        $pos2 = strpos($document_url[1][$i], $whatsid);
        if ($pos2!=false) $document_url[1][$i]=substr_replace($document_url[1][$i], '', $pos2, 36);
        $cur_url = $document_url[1][$i];
        $cur_url = htmlspecialchars_decode($cur_url); 
        // 2.4 forum links for reply, quote and subscribe -- skip 'em
        if (preg_match("/\?Subdiv_ID=\d+&Topic_ID=\d+&Oper_ID=[34]/", $cur_url)) { continue; }
        if (preg_match("/\?Repl_ID=\d+&Subdiv_ID=\d+&Topic_ID=\d+&Oper_ID=1[01]/", $cur_url)) { continue; }
        if (preg_match("/\?Subdiv_ID=\d+&Topic_ID=\d+&Oper_ID=26/", $cur_url)) { continue; }
        // forum link for the first page
        if (preg_match("/\?Subdiv_ID=\d+&Topic_ID=\d+&Page_NUM=0/", $cur_url)) { continue; }

        if (substr($cur_url,-1)=="?" || substr($cur_url,-1)=="&") $cur_url=substr($cur_url,0,-1);

        // skip mailto and javascript
        if (  preg_match("/^((mailto)|(javascript))/i", $cur_url) ){ continue; }
      
        if (substr($cur_url,0,7)=="http://"){
            for ($j=0;$j<count($allowed_urls);$j++){
		$allowed_urls[$j]=trim($allowed_urls[$j]);
                $current_url=$cur_url;
                if (substr($current_url,0,7+strlen($allowed_urls[$j]))=="http://".$allowed_urls[$j]) break; else $current_url = "";
            }
            $cur_url=$current_url;
        }
        
        if ($cur_url) {
            if (substr($cur_url,0,7)!="http://") {
                if (substr($cur_url,0,1)=="/") $real_url = $url_root_break.$cur_url;
                else $real_url = $url_path_break.$cur_url;

                if (substr($cur_url,0,1)=="?") $real_url = $url_root_break.($parsed_url['path'] && substr($parsed_url['path'],0,1)!="/"?"/":"").$parsed_url['path'].$cur_url;
            } else {
                $real_url = $cur_url;
            }

            $no_index = false;
		if(count($disallowed_urls)>=1) 
		{
			for ($j=0;$j<count($disallowed_urls);$j++) 
			{
				$disallowed_urls[$j]=trim($disallowed_urls[$j]);
				$len = strlen($disallowed_urls[$j]);
				if (substr($real_url,0,$len+7)=="http://".$disallowed_urls[$j]) { $no_index = true; break; }
				
			}
		}
		if(count($disallowed_regexp)>=1 && !$no_index) 
		{
			for ($j=0;$j<count($disallowed_regexp);$j++) 
			{
				$disallowed_regexp[$j]=trim($disallowed_regexp[$j]);
				if (preg_match($disallowed_regexp[$j],$real_url)) {	$no_index = true; break; }
			}
		}

            if (!$no_index) {

        	    // links with ./ and ../
                    $real_url = str_replace("/./", "/", $real_url);
                    while (strpos($real_url, "/../")) {
                       $real_url = preg_replace("!/[^/]+/\.\./!", "/", $real_url);
                    }

				$db->hide_errors();
				$db->query("INSERT INTO Message${index_table} (URL,Checked,Created,Source) VALUES ('".$real_url."',0,NOW(),'".$db->escape($url)."')");
				if ($SHOW_MYSQL_ERRORS=='on') $db->show_errors();
				if (!$db->insert_id) {
					$db->query("UPDATE Message${index_table} SET Source=CONCAT_WS('\r\n',Source,'".$url."') WHERE URL='".$real_url."'");
				}
				unset($db->insert_id);
				$db->captured_errors = array();

			}
        }
    }

}

/***/
function char_str($str, $char)
{
    $test_str = "";
    $len = strlen($str);

    if ($len != 0)
    {
        for ($i=0; $i<$len; $i++)
        {
            $test_str .= $char;
        }

        if ($test_str == $str)
        return TRUE;
        else
        return FALSE;
    }
}
/***/

?>

<?php
/* $Id: imex_message.php 3830 2010-06-18 14:21:14Z denis $ */
if ( !class_exists("nc_System") ) die("Unable to load file.");

//XML Import
if ($import==2) {
  eval("echo \"$template_header\";");
  echo $goBack." <br> <br>";

  $filesize=$_FILES['upl_file']['size'];
  $filename=$_FILES['upl_file']['tmp_name'];
  $fileerror=$_FILES['upl_file']['error'];
  if ($fileerror==0 && $filesize>0 && $filename!=''){
    $f=join("",file($filename));

    preg_match_all("#<item>(.*?)</item>#si",$f,$items);

    echo "<table cellpadding=4 cellspacing=0>";
    for ($i=0; $i<count($items[1]); $i++){
      $input=array();

      //сначала получаем закрытые таги, если они есть
      preg_match_all("#<(.*?)/>#s",$items[1][$i],$line);
      for ($a=0; $a<count($line[1]); $a++){
        $tag=$line[1][$a];
        $input[trim($tag)]="";
        $items[1][$i]=preg_replace("#<".$tag."/>#si","",$items[1][$i]);
      }
      //теперь получаем таги с контентом
      preg_match_all("#<(.*?)>(.*?)</.*?>#s",$items[1][$i],$line);
      for ($a=0; $a<count($line[1]); $a++){
        $tag=trim($line[1][$a]);
        $val=trim($line[2][$a]);
        $val=stripslashes($val);
        $val=stripslashes($val);
        $val=stripslashes($val);
        $val=stripslashes($val);

        $val=preg_replace("#&gt;#si",">",$val);
        $val=preg_replace("#&lt;#si","<",$val);
        $val=preg_replace("#&amp;amp;#si","&amp;",$val);

        $val=addslashes($val);
        $input[$tag]=$val;
      }

      $insert=(round($input['Message_ID'])>0) ? "REPLACE" : "INSERT";

      $input['Message_ID']=(round($input['Message_ID'])==0) ? "" : round($input['Message_ID']);
      $input['Subdivision_ID']=$sub;
      $input['User_ID']=$AUTH_USER_ID;
      $input['Sub_Class_ID']=$cc;

      $vals=array_values($input);
      $cols=array_keys($input);

      for ($www=0; $www<count($vals); $www++){
        $vals[$www]=($vals[$www]=='') ? "\"\"" : "\"".$vals[$www]."\"";
      }


      $s_insert = "INSERT INTO `Message".$classID."` (".join(", ",$cols).") VALUES (".join(", ",$vals).");";

      $s_update="UPDATE Message".$classID." SET ";
      $id = $input['Message_ID'];
      unset($input['Message_ID']);
      reset ($input);
      $s = array();
      while (list($k,$v)=each($input)){
        $s[]=" `${k}`=\"${v}\"";
      }
      $s_update.=join(",",$s)." WHERE `Message_ID` = '".$id."'";
      $input['Message_ID'] = $id;


      if ($insert=='INSERT'){
        $res=$db->query($s_insert);
      }
      else{
        $res=$db->query($s_insert);
        if (! $res) {
          $res=$db->query($s_update);
        }
      }

      $id = ( $input['Message_ID']==0 ? $db->insert_id : round($input['Message_ID']) );

      $ret = ( $res && $id ? "Ok" : $db->vardump($EZSQL_ERROR) );
      $color = ($res && $id ? "style='background:#009900; color:#FFFFFF; font-weight:bold;'" : "style='background:#FF0000; color:#FFFFFF; font-weight:bold;'");
      echo  "<tr><td ${color}>Message_ID</td><td>${id}</td><td style='border-bottom:1px solid #999999;'>${ret}</td><td>${sql}</td></tr>";
    }

    echo "</table>";
    eval ("echo \"$template_footer\";");
    exit;
  }

  if ($fileerror!=0 && $filesize>0 && $filename!=''){
    echo "<b>Ошибка при загрузке файла</b> <br> <br>";
    eval("echo \"$template_footer\";");
  }

  echo "<center>";
  echo "<form method='post' enctype='multipart/form-data' action='message.php?catalogue=".$catalogue."&sub=".$sub."&cc=".$cc."&classID=".$classID."&import=2'>";
  echo "<table cellpadding='0' cellspacing='8' border='0'>";
  echo "<tr><td colspan='2'><b>".NETCAT_MODERATION_IMPORT_XML."</b></td></tr>";
  echo "<tr><td>File:</td><td><input type='file' name='upl_file'></td></tr>";
  echo "<tr><td>Action</td><td><input type='submit'></td></tr>";
  echo "</table>";
  echo "</form>";
  
  eval("echo \"$template_footer\";");
  exit;
}

if ($export) {
  $fieldstype = round($fieldstype);
}

//CSV Export - выбор полей
if ( ($export==1 || $export==2) && $fieldstype==0 ) {
  eval("echo \"$template_header\";");
  echo $goBack." <br> <br>";
  echo "<form method='post' action='message.php?catalogue=".$catalogue."&sub=".$sub."&cc=".$cc."&classID=".$classID."&export=".$export."'>";
  echo "<table cellpadding='16' cellspacing='0' border='0'>";
  echo "<tr valign=top><td>".NETCAT_MODERATION_EXPORT_TYPE."</td><td>";
  echo "<input type='radio' name='fieldstype' value='1'>".NETCAT_MODERATION_EXPORT_ALLFIELDS."<br/>";
  echo "<input type='radio' name='fieldstype' value='2' checked>".NETCAT_MODERATION_EXPORT_USERFIELDS."<br/>";
  echo "</td></tr>";

  if ($export==1){
    echo "<tr valign='top'><td>".NETCAT_MODERATION_IMPORT_DIVIDER."</td><td>";
    echo "<input type='radio' name='idivider' value='1' checked>".NETCAT_MODERATION_IMPORT_DTAB."<br/>";
    echo "<input type='radio' name='idivider' value='2'>".NETCAT_MODERATION_IMPORT_DZPT1."<br/>";
    echo "<input type='radio' name='idivider' value='3'>".NETCAT_MODERATION_IMPORT_DZPT2."<br/>";
    echo "<input type='radio' name='idivider' value='4'>".NETCAT_MODERATION_IMPORT_DSPACE;
    echo "</td></tr>";
  }
  
  echo "</table><input type='submit' value='".NETCAT_MODERATION_EXPORT_GET."'></form>";
  eval ("echo \"$template_footer\";");
  exit;
}

//CSV Import
if ($import==1) {
  eval("echo \"$template_header\";");
  echo $goBack." <br/><br/>";
  $d = array("", "\t", ";", ",", " ");
  $divider = $d[$idivider];

  $filesize=$_FILES['upl_file']['size'];
  $filename=$_FILES['upl_file']['tmp_name'];
  $fileerror=$_FILES['upl_file']['error'];
  if ($fileerror==0 && $filesize>0 && $filename!=''){
    $f=file($filename);
    $line=array_shift($f);

    $cols=split($divider,trim($line));
    $cnt=count($f);
    echo "<table>";

    for ($i=0; $i<$cnt; $i++){
      $line = array_shift($f);
      $line = preg_replace("#(.*)[\r\n]+#s", "$1", $line);

      $line = preg_replace("#\"\"#s", "\"", $line);
      $line = preg_replace( array("#\\\\r#s", "#\\\\n#s", "#\"\"#s"), array("\r", "\n", "\""), $line );

      $line = stripslashes($line);
      $line = stripslashes($line);
      $line = addslashes($line);

      $vals = split($divider, $line);
      $input = array_combine($cols, $vals);

      $insert = round($input['Message_ID']) > 0 ? "REPLACE" : "INSERT";

      $input['Message_ID'] = round($input['Message_ID'])==0 ? "" : round($input['Message_ID']);

      $input['Subdivision_ID'] = $sub;
      $input['User_ID'] = $AUTH_USER_ID;
      $input['Sub_Class_ID'] = $cc;

      $vals = array_values($input);
      $cols = array_keys($input);

      for ($www=0; $www < count($vals); $www++){
        $vals[$www]=($vals[$www]=='') ? "\"\"" : "\"".$vals[$www]."\"";
      }

      $s_insert = "INSERT INTO `Message".$classID."` (".join(", ", $cols).") VALUES (".join(", ", $vals).");";

      $s_update = "UPDATE `Message".$classID."` SET ";
      $id = $input['Message_ID'];
      unset($input['Message_ID']);
      reset ($input);
      $s = array();
      while ( list($k, $v) = each($input) ){
        $s[]=" `".$k."` = '".$v."'";
      }
      $s_update .= join(",",$s)." WHERE `Message_ID` = '".$id."'";
      $input['Message_ID'] = $id;

      if ($insert=='INSERT'){
        $res = $db->query($s_insert);
      }
      else{
        $res = $db->query($s_insert);
        if (!$res) {
            $res = $db->query($s_update);
        }
      }

      $id = $input['Message_ID']==0 ? $db->insert_id : round($input['Message_ID']);

      $ret = $res && $id ? "Ok" : $db->vardump($EZSQL_ERROR);
      $color = $res && $id ? "style='background:#009900; color:#FFFFFF; font-weight:bold;'" : "style='background:#FF0000; color:#FFFFFF; font-weight:bold;'";
      echo  "<tr><td ${color}>Message_ID</td><td>${id}</td><td style='border-bottom:1px solid #999999;'>${ret}</td><td>${sql}</td></tr>";
    }

    echo "</table>";
    eval ("echo \"$template_footer\";");
    exit;
  }

  if ($fileerror!=0 && $filesize>0 && $filename!='') {
    echo "<b>Ошибка при загрузке файла</b> <br> <br>";
    eval("echo \"$template_footer\";");
  }

  echo "<center>";
  echo "<form method='post' enctype='multipart/form-data' action='message.php?catalogue=".$catalogue."&sub=".$sub."&cc=".$cc."&classID=".$classID."&import=1'>";
  echo "<table cellpadding='0' cellspacing='8' border='0'>";
  echo "<tr><td colspan='2'><b>".NETCAT_MODERATION_IMPORT_CSV."</b></td></tr>";
  echo "<tr valign='top'><td>".NETCAT_MODERATION_IMPORT_DIVIDER."</td><td>";
  echo "<input type='radio' name='idivider' value='1'>".NETCAT_MODERATION_IMPORT_DTAB."<br/>";
  echo "<input type='radio' name='idivider' value='2'>".NETCAT_MODERATION_IMPORT_DZPT1."<br/>";
  echo "<input type='radio' name='idivider' value='3'>".NETCAT_MODERATION_IMPORT_DZPT2."<br/>";
  echo "<input type='radio' name='idivider' value='4'>".NETCAT_MODERATION_IMPORT_DSPACE;
  echo "</td></tr>";
  echo "<tr><td>File:</td><td><input type='file' name='upl_file'></td></tr>";
  echo "<tr><td>Action</td><td><input type='submit'></td></tr>";
  echo "</table>";
  echo "</form>";

  eval("echo \"$template_footer\";");
  exit;
}

if ($export) {
  $fieldstype = round($fieldstype);
}


//CSV Export - выбор полей
if ( ($export==1 || $export==2) && $fieldstype==0 ){
  eval("echo \"$template_header\";");
  echo $goBack." <br/><br/>";
  echo "<form method='post' action='message.php?catalogue=".$catalogue."&sub=".$sub."&cc=".$cc."&classID=".$classID."&export=".$export."'>";
  echo "<table cellpadding='16' cellspacing='0' border='0'>";
  echo "<tr valign='top'><td>".NETCAT_MODERATION_EXPORT_TYPE."</td><td>";
  echo "<input type='radio' name='fieldstype' value='1'>".NETCAT_MODERATION_EXPORT_ALLFIELDS."<br/>";
  echo "<input type='radio' name='fieldstype' value='2' checked>".NETCAT_MODERATION_EXPORT_USERFIELDS."<br/>";
  echo "</td></tr>";

  if ($export==1){
    echo "<tr valign='top'><td>".NETCAT_MODERATION_IMPORT_DIVIDER."</td><td>";
    echo "<input type='radio' name='idivider' value='1' checked>".NETCAT_MODERATION_IMPORT_DTAB."<br/>";
    echo "<input type='radio' name='idivider' value='2'>".NETCAT_MODERATION_IMPORT_DZPT1."<br/>";
    echo "<input type='radio' name='idivider' value='3'>".NETCAT_MODERATION_IMPORT_DZPT2."<br/>";
    echo "<input type='radio' name='idivider' value='4'>".NETCAT_MODERATION_IMPORT_DSPACE;
    echo "</td></tr>";
  }
  
  echo "</table><input type='submit' value='".NETCAT_MODERATION_EXPORT_GET."'></form>";
  eval("echo \"$template_footer\";");
  exit;
}

//CSV Export
if ($export==1 && $fieldstype!=0){
  //no cache
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  header("Content-type: text/csv;Charset: windows-1251");
  header ("Content-Disposition: inline; filename=\"Message${sub}.dat\"");

  $d = array("", "\t", ";", ",", " ");
  $divider = $d[$idivider];

  $res = $db->get_results("SHOW COLUMNS FROM `Message".$classID."`", ARRAY_A);

  $nofield = ($fieldstype==1) ? array() : array(
    "Subdivision_ID",
    "Priority",
    "TimeToDelete",
    "User_ID",
    "Sub_Class_ID",
    "IP",
    "Parent_Message_ID",
    "UserAgent",
    "Created",
    "LastUpdated",
    "LastUser_ID",
    "LastIP",
    "LastUserAgent",
    "Checked",
    "TimeToUncheck"
  );

  $columns=array();
  foreach ($res as $out){
    if (!in_array($out['Field'], $nofield)){
      $columns[] = $out['Field'];
    }
  }

  $res = $db->get_results("SELECT `".join("`, `",$columns)."` FROM `Message".$classID."` WHERE `Subdivision_ID` = '".$sub."' ORDER BY `Message_ID`", ARRAY_A);
  
  $n = 0;
  foreach ($res as $out ){
    if ($n==0){
      echo join($divider,array_keys($out))."\r\n";
    }
    $out = join( $divider, array_values($out) );
    $out = preg_replace( array("#\r#s", "#\n#s"), array("\\r", "\\n"), $out );
    echo $out."\r\n";
    $n++;
  }
  exit;
}

//XML Export
if ($export==2 && $fieldstype!=0){
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  header ("Content-type: application/test;Charset: windows-1251");
  header ("Content-disposition: inline; filename=\"Message${sub}.dat\"");

  echo '<'.'?xml version="1.0" encoding="windows-1251" ?'.'>'."\r\n".'<channel>'."\r\n";
  $res = $db->get_results("SHOW COLUMNS FROM `Message".$classID."`", ARRAY_A);

  $nofield = ($fieldstype==1) ? array() : array(
    "Subdivision_ID",
    "Priority",
    "TimeToDelete",
    "User_ID",
    "Sub_Class_ID",
    "IP",
    "Parent_Message_ID",
    "UserAgent",
    "Created",
    "LastUpdated",
    "LastUser_ID",
    "LastIP",
    "LastUserAgent",
    "Checked",
    "TimeToUncheck"
  );

  $columns = array();
  foreach ($res as $out){
    if ( !in_array($out['Field'], $nofield) ) {
        $columns[] = $out['Field'];
    }
  }

  $res = $db->get_results("SELECT `".join("`, `",$columns)."` FROM `Message".$classID."` WHERE `Subdivision_ID` = '".$sub."' ORDER BY `Message_ID`", ARRAY_A);

  foreach ($res as $out){
    reset ($out);
    $txt="";
    while ( list($k, $v) = each($out) ){
      $v = preg_replace( array("#<#s", "#>#s"), array("&lt;", "&gt;"), $v );
      $txt .= "<".$k.">".$v."</".$k.">";
    }
    echo "<item>\r\n".$txt."\r\n</item>\r\n";
  }
  echo "</channel>";
  exit();
}

?>
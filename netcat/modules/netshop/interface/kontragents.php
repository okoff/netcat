<?php
// 13.05.2015 Elen
// поиск и просмотр списка контрагентов
include_once ("../../../../vars.inc.php");

session_start();
function getkontragetnsList() {
	$sql="SELECT *
		FROM Classificator_kontragetns WHERE buyer=0
		ORDER BY kontragetns_Name ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/kontragents.php?id=".(getLastInsertID("Classificator_kontragetns","kontragetns_ID")+1)."&action=edit'>Добавить</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'><td>#</td>
		<td>Название</td>
		<td>Код 1С</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr><td>{$row['kontragetns_ID']}</td>
			<td><a href='/netcat/modules/netshop/interface/kontragents.php?id={$row['kontragetns_ID']}&action=edit'>{$row['kontragetns_Name']}</a></td>
			<td style='text-align:center;'>{$row['code1c']}</td>
		</tr>";
		}
	}
	$html.="</table>";
	return $html;
}
function editkontragetns($id=0) {
	$sname="";
	$sstat=1;
	$srus=1;
	$sql="SELECT * FROM Classificator_kontragetns WHERE kontragetns_ID=".$id;
	$html="";
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$sname=$row['kontragetns_Name'];
			$code1c=$row['code1c'];
			$buyer=$row['buyer'];
		}
	}
	$html.="<form action='/netcat/modules/netshop/interface/kontragents.php' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0'>
	<tr><td style='text-align:right;'>Название</td><td><input type='text' value='{$sname}' name='sname' id='sname' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Код 1С</td><td><input type='text' value='{$code1c}' name='code1c' id='code1c' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Покупатель</td><td><input type='checkbox' value='1' name='buyer' id='buyer' ".(($buyer) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'><input type='submit' value='Сохранить'></td><td>&nbsp;</td></tr>
	</table>
	<table cellpadding='2' cellspacing='0' border='0'>
	<tr>
		<td style='vertical-align:bottom;text-align:center;'>".(($row['Preview']) ? "<a href='/netcat_files/vendors/{$row['Preview']}' target='_blank'><img src='/netcat_files/vendors/{$row['Preview']}' width='200'></a><br>
		<a href='".$_SERVER['$_SERVER["REQUEST_URI"]']."?action=del&id={$id}&img=1'>удалить</a>" : "")."</td>
	</tr>
	</table>	
	</form>";
		
	return $html;
}

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$upload_dir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/vendors/";
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$show=1;
	//print_r($incoming);
	switch ($incoming['action']) {
		case "save":
			if (is_numeric($incoming['id'])) {
				//print_r($incoming)."<br>";
				if ($incoming['id']<=$all) {
					$sql="UPDATE Classificator_kontragetns SET 
						kontragetns_Name='".quot_smart($incoming['sname'])."',
						code1c='".((isset($incoming['code1c'])) ? $incoming['code1c'] : "0")."',
						buyer=".((isset($incoming['buyer'])) ? "1" : "0")."
						WHERE kontragetns_ID=".intval($incoming['id']);
				} else {
					$sql="INSERT INTO Classificator_kontragetns (kontragetns_Name, code1c,buyer)
						VALUES ('".quot_smart($incoming['sname'])."',
							'".((isset($incoming['code1c'])) ? $incoming['code1c'] : "0")."',
							".((isset($incoming['buyer'])) ? "1" : "0").")";
				}
				//echo $sql;
				mysql_query($sql);
				$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/kontragents.php'>Вернуться в список контрагентов.</a></p>";
			}
			break;
		case "del":
			if (isset($incoming["id"])) {
				$sql="";
				if ($incoming["img"]==1) {
					$sql="UPDATE Classificator_kontragetns SET Preview='' WHERE kontragetns_ID=".intval($incoming['id']);
				}
				//echo $sql;
				if ($sql) {
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$html.="<p>Изменения сохранены.</p>";
				}
				$html.="<p><a href='/netcat/modules/netshop/interface/kontragents.php'>Вернуться в список контрагентов.</a></p>";
			}
			$show=0;
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editkontragetns($incoming['id']);
			}
			break;
		default:
			//if (!isset($incoming['start'])) {
			$html=getkontragetnsList();
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Контрагенты</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	if ($show) {
?>
	<h1>Контрагенты</h1>
	
	<?php 
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

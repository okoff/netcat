<?php
// 25.02.2014 Elen
// поиск и просмотр списка поставщиков
include_once ("../../../../vars.inc.php");

session_start();
function getManufacturersList() {
	$sql="SELECT *
		FROM Classificator_Manufacturer 
		ORDER BY Manufacturer_Name ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/manufacturers.php?id=".(getLastInsertID("Classificator_Manufacturer","Manufacturer_ID")+1)."&action=edit'>Добавить</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'><td>#</td><td>Название</td><td>На сайте</td><td>Рус</td><td>Складные ножи</td><td>Превью</td></tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr><td>{$row['Manufacturer_ID']}</td>
			<td><a href='/netcat/modules/netshop/interface/manufacturers.php?id={$row['Manufacturer_ID']}&action=edit'>{$row['Manufacturer_Name']}</a></td>
			<td style='text-align:center;'>".(($row['Checked']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td style='text-align:center;'>".(($row['Rus']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td style='text-align:center;'>".(($row['Folding']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td style='text-align:center;'>".(($row['Preview']) ? "<img src='/netcat_files/vendors/{$row['Preview']}' width='200'>" : "-")."</td>
		</tr>";
		}
	}
	$html.="</table>";
	return $html;
}
function editManufacturer($id=0) {
	$sname="";
	$sstat=1;
	$srus=1;
	$sql="SELECT * FROM Classificator_Manufacturer WHERE Manufacturer_ID=".$id;
	$html="";
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$sname=$row['Manufacturer_Name'];
			$schecked=$row['Checked'];
			$srus=$row['Rus'];
			$sfolding=$row['Folding'];
			$preview=$row['Preview'];
		}
	}
	$html.="<form enctype='multipart/form-data' action='/netcat/modules/netshop/interface/manufacturers.php' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0'>
	<tr><td style='text-align:right;'>Название</td><td><input type='text' value='{$sname}' name='sname' id='sname' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Показывать на сайте</td><td><input type='checkbox' value='1' name='schecked' id='schecked' ".(($schecked) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>RUS</td><td><input type='checkbox' value='1' name='srus' id='srus' ".(($srus) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>Складные ножи</td><td><input type='checkbox' value='1' name='sfolding' id='sfolding' ".(($sfolding) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>Превью</td><td style='vertical-align:top;'><input type='file' name='fname' id='fname'></td></tr>
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
				$sman="";
				$all=getLastInsertID("Classificator_Manufacturer","Manufacturer_ID");
				for ($j=0; $j<=$all; $j++) {
					$sman.=($incoming['sman_'.$j]!=0) ? $incoming['sman_'.$j].";" : "";
				}
				print_r($_FILES);
				if ($_FILES["fname"]["size"]) {
					if ($_FILES["fname"]["error"] > 0) {
						$html.="<p>Error: " . $_FILES["fname"]["error"] . "</p>";
					} else {
						$tmp=explode(".",$_FILES["fname"]["name"]);
						$i=0;
						while (file_exists($upload_dir.$tmp[0].$i.".".$tmp[1])) {
							$i=$i+1;
						} 
						
						move_uploaded_file($_FILES["fname"]["tmp_name"],$upload_dir.$tmp[0].$i.".".$tmp[1]);
						$fname=$tmp[0].$i.".".$tmp[1];
						//$fcert="{$fname}";
						$html.="<p>Превью сохранено: " . $upload_dir. $fname."</p>";
						
					}
				}
				if ($incoming['id']<=$all) {
					$sql="UPDATE Classificator_Manufacturer SET 
						Manufacturer_Name='".quot_smart($incoming['sname'])."',
						Checked= ".((isset($incoming['schecked'])) ? "1" : "0").",
						Rus=".((isset($incoming['srus'])) ? "1" : "0").",
						Folding=".((isset($incoming['sfolding'])) ? "1" : "0").",
						Preview='{$fname}'
						WHERE Manufacturer_ID=".intval($incoming['id']);
				} else {
					$sql="INSERT INTO Classificator_Manufacturer (Manufacturer_Name, Checked, Rus, Folding,Preview)
						VALUES ('".quot_smart($incoming['sname'])."',
							".((isset($incoming['schecked'])) ? "1" : "0").",
							".((isset($incoming['srus'])) ? "1" : "0").", 
							".((isset($incoming['sfolding'])) ? "1" : "0").",'{$fname}')";
				}
				//echo $sql;
				mysql_query($sql);
				$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/manufacturers.php'>Вернуться в список производителей.</a></p>";
			}
			break;
		case "del":
			if (isset($incoming["id"])) {
				$sql="";
				if ($incoming["img"]==1) {
					$sql="UPDATE Classificator_Manufacturer SET Preview='' WHERE Manufacturer_ID=".intval($incoming['id']);
				}
				//echo $sql;
				if ($sql) {
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$html.="<p>Изменения сохранены.</p>";
				}
				$html.="<p><a href='/netcat/modules/netshop/interface/manufacturers.php'>Вернуться в список производителей.</a></p>";
			}
			$show=0;
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editManufacturer($incoming['id']);
			}
			break;
		default:
			//if (!isset($incoming['start'])) {
			$html=getManufacturersList();
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Производители</title>
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
	<h1>Производители</h1>
	
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

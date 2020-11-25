<?php
// 08.05.2015 Elen
// поиск и просмотр списка контрагентов 
include_once ("../../../../vars.inc.php");

session_start();
function printMansList($sman="") {
	$html="";
	$tt=explode(";",$sman);
	foreach ($tt as $t) {
		if ($t!="") {
			$sql="SELECT Manufacturer_Name FROM Classificator_Manufacturer WHERE Manufacturer_ID=".$t;
			if ($result=mysql_query($sql)) {
				while ($row=mysql_fetch_array($result)) {
					$html.=$row['Manufacturer_Name']."  ";
				}
			}
			
		}
	}
	return $html;
}
function getSuppliersList() {
	$sql="SELECT *
		FROM Classificator_Supplier 
		ORDER BY Supplier_Name ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/suppliers.php?id=".(getLastInsertID("Classificator_Supplier","Supplier_ID")+1)."&action=edit'>Добавить</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>Название</td>
			<td>Собирать статистику</td>
			<td>Рус</td>
			<td>Работа под реализацию</td>
			<td>Производители</td>
			<td>Дата начала списаний</td></tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr><td><a href='/netcat/modules/netshop/interface/suppliers.php?id={$row['Supplier_ID']}&action=edit'>{$row['Supplier_Name']}</a></td>
			<td style='text-align:center;'>".(($row['Statistic']) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
			<td style='text-align:center;'>".(($row['Rus']) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
			<td style='text-align:center;'>".(($row['Onreal']) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
			<td>".printMansList($row['Manufacturer'])."</td>
			<td>".(($row['Startdate']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['Startdate'])) : "&nbsp;")."</td>
		</tr>";
		}
	}
	$html.="</table>";
	return $html;
}
function printMans($sman="") {
	$tt=explode(";",$sman);
	//print_r($tt);
	$html="<table border='0'>";
	$j=0;
	$sql="SELECT Manufacturer_ID, Manufacturer_Name FROM Classificator_Manufacturer ORDER BY Manufacturer_Name ASC";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.=($j%3==0) ? "<tr>" : "";
			$chkd="";
			foreach($tt as $t) {
				if ($t==$row['Manufacturer_ID']) {
					$chkd="checked";
					break;
				}
			}
			$html.="<td><input type='checkbox' value='{$row['Manufacturer_ID']}' name='sman_{$j}' id='sman_{$j}' {$chkd}></td><td>{$row['Manufacturer_Name']}</td>";
			$html.=($j%3==2) ? "</tr>" : "";
			$j=$j+1;
		}
	}
	$html.="</table>";
	return $html;
}
function printKontr($sman="") {
	$tt=explode(";",$sman);
	//print_r($tt);
	$html="<h3>Контрагенты</h3>
	<table border='0'>";
	$j=0;
	$sql="SELECT kontragetns_ID, kontragetns_Name FROM Classificator_kontragetns WHERE buyer=0 ORDER BY kontragetns_Name ASC";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.=($j%3==0) ? "<tr>" : "";
			$chkd="";
			foreach($tt as $t) {
				if ($t==$row['kontragetns_ID']) {
					$chkd="checked";
					break;
				}
			}
			$html.="<td><input type='checkbox' value='{$row['kontragetns_ID']}' name='kontr_{$j}' id='kontr_{$j}' {$chkd}></td><td>{$row['kontragetns_Name']}</td>";
			$html.=($j%3==2) ? "</tr>" : "";
			$j=$j+1;
		}
	}
	$html.="</table>";
	return $html;
}
function editSupplier($id=0) {
	$sname="";
	$sstat=1;
	$srus=1;
	$sql="SELECT * FROM Classificator_Supplier WHERE Supplier_ID=".$id;
	$html="";
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$sname=$row['Supplier_Name'];
			$sstat=$row['Statistic'];
			$srus=$row['Rus'];
			$sinn=$row['INN'];
			$sonreal=$row['Onreal'];
			$sman=$row['Manufacturer'];
			$skontr=$row['Kontragent'];
			$sdate=(($row['Startdate']!="0000-00-00 00:00:00") ? date("d.m.Y H:i:s",strtotime($row['Startdate'])) : "");
		}
	}

	$html.="<form action='/netcat/modules/netshop/interface/suppliers.php' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0'>
	<tr><td style='text-align:right;width:150px;'>Название</td><td><input type='text' value='{$sname}' name='sname' id='sname' style='width:600px;'></td></tr>
	<tr><td style='text-align:right;'>Собирать статистику</td><td><input type='checkbox' value='1' name='sstat' id='sstat' ".(($sstat) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>RUS</td><td><input type='checkbox' value='1' name='srus' id='srus' ".(($srus) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>Работа под реализацию</td><td><input type='checkbox' value='1' name='sonreal' id='sonreal' ".(($sonreal) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>Дата начала списаний</td><td><input type='text' value='{$sdate}' name='sdate' id='sdate'></td></tr>
	<tr><td style='text-align:right;'>ИНН</td><td><input type='text' value='{$sinn}' name='sinn' id='sinn'></td></tr>
	<tr><td colspan='2'>".printMans($sman)."</td></tr>
	<tr><td colspan='2'>".printKontr($skontr)."</td></tr>
	<tr><td style='text-align:right;'><input type='submit' value='Сохранить'></td><td>&nbsp;</td></tr>
	</table>
	</form>";
		
	return $html;
}

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
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
				$skontr="";
				$all=getLastInsertID("Classificator_Supplier","Supplier_ID");
				//echo $all."<br>";
				for ($j=0; $j<=$all; $j++) {
					$sman.=($incoming['sman_'.$j]!=0) ? $incoming['sman_'.$j].";" : "";
				}
				//$allk=
				for ($j=0; $j<=$all; $j++) {
					$skontr.=($incoming['kontr_'.$j]!=0) ? $incoming['kontr_'.$j].";" : "";
				}
				$sdate="";
				if ($incoming['sdate']!="") {
					$sdate=date("Y-m-d H:i:s",strtotime($incoming['sdate']));
				} 
				if ($incoming['id']<=$all) {
					$sql="UPDATE Classificator_Supplier SET 
						Supplier_Name='".quot_smart($incoming['sname'])."',
						INN='".quot_smart($incoming['sinn'])."',
						Statistic= ".((isset($incoming['sstat'])) ? "1" : "0").",
						Rus=".((isset($incoming['srus'])) ? "1" : "0").",
						Onreal=".((isset($incoming['sonreal'])) ? "1" : "0").",
						Manufacturer='{$sman}',
						Kontragent='{$skontr}',
						Startdate='{$sdate}'
						WHERE Supplier_ID=".intval($incoming['id']);
				} else {
					$sql="INSERT INTO Classificator_Supplier (Supplier_Name,INN, Statistic, Rus,Onreal,Manufacturer,Kontragent,Startdate)
							VALUES ('".quot_smart($incoming['sname'])."','".quot_smart($incoming['sinn'])."',
								".((isset($incoming['sstat'])) ? "1" : "0").",
								".((isset($incoming['srus'])) ? "1" : "0").",
								".((isset($incoming['sonreal'])) ? "1" : "0").", '{$sman}','{$skontr}','{$sdate}')";
				}
				//echo $sql;
				mysql_query($sql);
				$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/suppliers.php'>Вернуться в список поставщиков.</a></p>";
			}
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editSupplier($incoming['id']);
			}
			break;
		default:
			//if (!isset($incoming['start'])) {
			$html=getSuppliersList();
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Поставщики</title>
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
	<h1>Поставщики</h1>
	<h3><a href='/netcat/modules/netshop/interface/kontragents.php'>Контрагенты</a></h3>
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

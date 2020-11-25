<?php
// 25.02.2014 Elen
// поиск и просмотр списка поставщиков
include_once ("../../../../vars.inc.php");

session_start();
function printMansList($sman="") {
	$html="";
	$tt=explode(";",$sman);
	foreach ($tt as $t) {
		if ($t!="") {
			$sql="SELECT Courier_Name FROM Classificator_Courier WHERE Courier_ID=".$t;
			if ($result=mysql_query($sql)) {
				while ($row=mysql_fetch_array($result)) {
					$html.=$row['Courier_Name']."  ";
				}
			}
			
		}
	}
	return $html;
}
function getCouriersList() {
	$sql="SELECT *
		FROM Classificator_Courier 
		ORDER BY Courier_Name ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/couriers.php?id=".(getLastInsertID("Classificator_Courier","Courier_ID")+1)."&action=edit'>Добавить</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'><td>Имя</td><td>Телефон</td></tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr><td><a href='/netcat/modules/netshop/interface/couriers.php?id={$row['Courier_ID']}&action=edit'>{$row['Courier_Name']}</a></td>
			<td style='text-align:center;'>{$row['Phone']}</td>
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
	$sql="SELECT Courier_ID, Courier_Name FROM Classificator_Courier ORDER BY Courier_Name ASC";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.=($j%3==0) ? "<tr>" : "";
			$chkd="";
			foreach($tt as $t) {
				if ($t==$row['Courier_ID']) {
					$chkd="checked";
					break;
				}
			}
			$html.="<td><input type='checkbox' value='{$row['Courier_ID']}' name='sman_{$j}' id='sman_{$j}' {$chkd}></td><td>{$row['Courier_Name']}</td>";
			$html.=($j%3==2) ? "</tr>" : "";
			$j=$j+1;
		}
	}
	$html.="</table>";
	return $html;
}
function editCourier($id=0) {
	$sname=$sphone="";
	$sstat=1;
	$srus=1;
	$sql="SELECT * FROM Classificator_Courier WHERE Courier_ID=".$id;
	$html="";
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$sname=$row['Courier_Name'];
			$sstat=$row['Checked'];
			$sphone=$row['Phone'];
		}
	}
	$html.="<form action='/netcat/modules/netshop/interface/couriers.php' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0'>
	<tr><td style='text-align:right;'>Имя</td><td><input type='text' value='{$sname}' name='sname' id='sname' style='width:700px;'></td></tr>
	<!--tr><td style='text-align:right;'>checked</td><td><input type='checkbox' value='1' name='sstat' id='sstat' ".(($sstat) ? "checked" : "")."></td></tr-->
	<tr><td style='text-align:right;'>Телефон</td><td><input type='text' value='{$sphone}' name='sphone' id='sphone' style='width:700px;'></td></tr>
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
				$all=getLastInsertID("Classificator_Courier","Courier_ID");
				for ($j=0; $j<=$all; $j++) {
					$sman.=($incoming['sman_'.$j]!=0) ? $incoming['sman_'.$j].";" : "";
				}
				
				if ($incoming['id']<=$all) {
					$sql="UPDATE Classificator_Courier SET 
						Courier_Name='".quot_smart($incoming['sname'])."',
						Phone='".quot_smart($incoming['sphone'])."'
						WHERE Courier_ID=".intval($incoming['id']);
				} else {
					$sql="INSERT INTO Classificator_Courier (Courier_Name,Phone)
						VALUES ('".quot_smart($incoming['sname'])."','".quot_smart($incoming['sphone'])."')";
				}
				//echo $sql;
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/couriers.php'>Вернуться в список курьеров.</a></p>";
			}
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editCourier($incoming['id']);
			}
			break;
		default:
			//if (!isset($incoming['start'])) {
			$html=getCouriersList();
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Курьеры</title>
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
	<h1>Курьеры</h1>
	
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

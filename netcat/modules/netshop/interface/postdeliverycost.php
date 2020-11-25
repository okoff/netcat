<?php
// 2.02.2015 Elen
// бонусные карты 
include_once ("../../../../vars.inc.php");

session_start();

$ScriptURL="/netcat/modules/netshop/interface/postdeliverycost.php";



function editInterval($id=0) {
	$html="";
	//print_r($_SESSION);
	$min=$max=$cost=0;
	$sql="SELECT * FROM Netshop_Postdeliverycost where id=".$id;
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$min=$row['min'];
			$max=$row['max'];
			$cost=$row['cost'];
		}
	}
	((isset($_SESSION['pdmin']))&&($_SESSION['pdmin']!="")) ? $min=$_SESSION['pdmin'] : "";
	((isset($_SESSION['pdmax']))&&($_SESSION['pdmax']!="")) ? $max=$_SESSION['pdmax'] : "";
	((isset($_SESSION['pdcost']))&&($_SESSION['pdcost']!="")) ? $cost=$_SESSION['pdcost'] : "";
	
	
	$html.="
	<h3>Редактирование стоимости оплаты</h3>
	<form action='{$_SERVER["REQUEST_URI"]}' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0' cellpadding='0' cellspacing='2'>
	<tr><td style='text-align:right;'>Минимальная сумма заказа</td><td><input type='text' value='{$min}' name='min' id='min'></td></tr>
	<tr><td style='text-align:right;'>Максимальная сумма заказа</td><td><input type='text' value='{$max}' name='max' id='max'></td></tr>
	<tr><td style='text-align:right;'>Стоимость доставки</td><td><input type='text' value='{$cost}' name='cost' id='cost'></td></tr>
	<tr><td style='text-align:right;'><input type='submit' value='Сохранить'></td><td>&nbsp;</td></tr>
	</table>	
	</form>";
		
	return $html;
}

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$upload_dir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/certificates/";
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
			//print_r($incoming);
			$sql="";
			$subaction="insert";
			if (is_numeric($incoming['id'])) {
				$sql="SELECT * FROM Netshop_Postdeliverycost WHERE id=".intval($incoming['id']);
				if ($result=mysql_query($sql)) {
					if ($row=mysql_fetch_array($result)) {
						$subaction="update";
					}
				} 
			}
				//echo $subaction;
			if($subaction=="insert") {
				$sql="INSERT INTO Netshop_Postdeliverycost (min,max,cost)
						VALUES (
						".floatval($incoming['min']).",
						".floatval($incoming['max']).",
						".floatval($incoming['cost']).")";
			}
			if($subaction=="update") {
				$sql="UPDATE Netshop_Postdeliverycost SET
						min=".floatval($incoming['min']).",
						max=".floatval($incoming['max']).",
						cost=".floatval($incoming['cost'])."
					WHERE id=".intval($incoming['id']);
			}
			//echo $sql;
			if (!mysql_query($sql)) {
				die(mysql_error());
			}
			$html.="<p>Изменения сохранены.</p><p><a href='{$ScriptURL}'>Вернуться в список.</a></p>";
				
			
			$show=0;
			break;
		case "del":
			if (isset($incoming["n"])) {
				$sql="DELETE FROM Netshop_Postdeliverycost WHERE id=".intval($incoming['n']);
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p>Изменения сохранены.</p>";
			
				$html.="<p><a href='{$ScriptURL}'>Вернуться в список тарифов.</a></p>";
			}
			$show=0;
			break;
		case "edit":
			if (isset($incoming['n'])) {
				$html=editInterval($incoming['n']);
			}
			$show=0;
			break;
		default:
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Стоимость оплаты по почте</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script type='text/javascipt'>
	function checkFrm(var all) {
		alert(all);
		//var alln=all.split(";");
		//alert(alln);
		//return false;
	}
	</script>
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	//print_r($_SERVER);
	
?>
	<h1>Стоимость оплаты по почте</h1>
<?php
if ($show) {
	echo "<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td colspan='2'><b>Cтоимость заказа</b></td>
		<td rowspan='2'><b>Стоимость доставки</b></td>
		<td rowspan='2'><b>Изменить</b></td>
		<td rowspan='2'><b>Удалить</b></td>
	</tr>
	<tr>
		<td><b>Минимальная</b></td>
		<td><b>Максимальная</b></td>
	</tr>
	";
	$sql="SELECT * FROM Netshop_Postdeliverycost ORDER BY min ASC";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			echo "<tr>
			<td>{$row['min']}</td>
			<td>{$row['max']}</td>
			<td>{$row['cost']}</td>
			<td><a href='{$ScriptURL}?action=edit&n={$row['id']}'><img src='/images/icons/edit.png' style='display:block;margin:0 auto;'></a></td>
			<td><a href='#' onclick='javascript:if(confirm(\"Удалить тариф {$row['min']}-{$row['max']}?\")){window.location=\"{$ScriptURL}?action=del&n={$row['id']}\";}'><img src='/images/icons/del.png' style='display:block;margin:0 auto;'></a></td></tr>";
		}
	}
	echo "</table><br>";
	echo "<p><a href='{$ScriptURL}?action=edit&n={$row['id']}'>добавить новый тариф</a></p>";
}
echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
<link type="text/css" href="/css/latest.css" rel="Stylesheet" />
<script type="text/javascript" src="/js/ui.datepicker.js"></script>
<script>
$(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 0// отключаем эффект появления
});
</script>
</body>
</html>
<?php

mysql_close($con);
?>

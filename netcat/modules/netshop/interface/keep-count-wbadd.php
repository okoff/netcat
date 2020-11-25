<?php
// 19.02.2015 Elen
// учёт
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");

function printKCaddWB($kc=0) {
	$id=0;
	$h3="<h3>Добавить накладную в учёт #{$kc}</h3>";
	if (!is_numeric($kc)) {
		die("Неверный номер учёта!");
	}
	
	$html=$h3."<form id='frm1' name='frm1' action='/netcat/modules/netshop/interface/keep-count-wbadd.php' method='post'>
		<input type='hidden' name='action' value='save'>
		<input type='hidden' name='kc' value='{$kc}'>
		<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td>ID накладной</td><td><input type='text' value='' name='wbid'></td></tr>
		</table><br>
		<input type='submit' value='Сохранить'>
		</form>";
	return $html;
}

// ------------------------------------------------------------------------------------------------
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
// ------------------------------------------------------------------------------------------------

$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$incoming = parse_incoming();
//print_r($incoming);
if (!(is_numeric($incoming['kc']))) {
	die("Неверный номер учёта!");
}

$html="";
switch ($incoming['action']) {
	case "save":
		$sql="SELECT * FROM Keepcount_WB WHERE keepcount_id=".intval($incoming['kc'])." AND waybill_id=".intval($incoming['wbid']);
		$res1=mysql_query($sql);
		$j=0;
		while ($row1 = mysql_fetch_array($res1)) {	
			$j=$j+1;
		}
		if (!$j) {
			$sql="INSERT INTO Keepcount_WB (keepcount_id,waybill_id) VALUES 
					(".intval($incoming['kc']).",".intval($incoming['wbid']).")";
			 
			if (mysql_query($sql)) {	
				$html.="<h2>Накладная #{$incoming['wbid']} сохранена в учёт {$incoming['kc']}</h2>
				<p><a href='/netcat/modules/netshop/interface/keep-count.php'>Продолжить</a></p>";
				//$showall=0;
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
		} else {
			$html.="<h2>Накладная #{$incoming['wbid']} уже была сохранена в учёте {$incoming['kc']}</h2>
			<p><a href='/netcat/modules/netshop/interface/keep-count-wbadd.php?kc={$incoming['kc']}'>добавить другую накладную</a></p>
			<p><a href='/netcat/modules/netshop/interface/keep-count.php'>вернуться в учёт</a></p>";
		}
		break;
	default:
		if (isset($incoming['kc'])) {
			$html=printKCaddWB($incoming['kc']);
		}
		break;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Учёт</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	img {
		border:0;
	}
	</style>
</head>
<body>
<?php
	echo printMenu();
?>
	<h1>УЧЁТ</h1>
<?php echo $html; ?>

</body>
</html>
<?php

mysql_close($con);
?>

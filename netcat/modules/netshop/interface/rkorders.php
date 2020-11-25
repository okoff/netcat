<?php
// 22.04.2016 Elen
// Заказы поставщикам
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-waybill.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pricelist/';
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

?>
<!DOCTYPE html>
<html>
<head>
	<title>Заказы поставщикам</title>
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
	echo printMenu();
?>
<h1>Заказы поставщикам</h1>
<p><a href="/netcat/modules/netshop/interface/rkorders-edit.php"><b>Создать новый заказ</b></a></p>
<form name='frm1' id='frm1' method='post'>
	<input type='hidden' name='action' id='action' value='filtr'>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>Поставщик:</td><td><?php echo selectSupplier($incoming); ?></td>
		<td><input type='submit' value='выбрать'></td></tr>
	</table><br>
<form>
<table cellpadding="2" cellspacing="0" border="1">
	<tr>
		<td><b>#</b></td>
		<td><b>Дата</b></td>
		<td><b>Поставщик</b></td>
		<td><b>Действия</b></td>
		<!--td><b></b></td>
		<td><b></b></td>
		<td><b></b></td-->
	</tr>
<?php
$sqlwhere=""; 
if ((isset($incoming['supplier']))&&($incoming['supplier']>0)) {
	$sqlwhere=" WHERE supplier_id=".intval($incoming['supplier']);
}
$sql="SELECT * FROM RKOrders {$sqlwhere} ORDER BY id DESC";
if ($result=mysql_query($sql)) {
	while($row = mysql_fetch_array($result)) {
		$fname=$row['fname'];
		echo "<tr>";
		echo "<td>{$row['id']}</td>";
		echo "<td>".date("d.m.Y",strtotime($row['created']))."</td>";
		echo "<td>".getSupplier($row['supplier_id'])."</td>";
		echo "<td><a href='/netcat/modules/netshop/interface/rkorders-edit.php?n={$row['id']}'><img alt='Изменить заказ #{$row['id']}' title='Изменить заказ #{$row['id']}' src='/images/icons/edit.png' style='display:block;margin:0 15px 0 0;border:0;float:left;'></a>
		<a target='_blank' href='/netcat/modules/netshop/interface/rkorders-print.php?n={$row['id']}'><img alt='Печать заказа #{$row['id']}' title='Печать заказа #{$row['id']}' src='/images/icons/print.png' style='display:block;margin:0 15px 0 0;border:0;float:left;'></a>
		</td>";
		
		//$html.="<p>{$fname} от ".."</p>";
		echo "</tr>";
	}
} else {
	die(mysql_error());
}

?>
</table>
</body>
</html>
<?php

mysql_close($con);
?>

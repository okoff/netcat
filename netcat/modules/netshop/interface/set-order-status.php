<?php
// 24.03.2014 Elen
// реквизиты компании для выставления счета
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
?>
<!DOCTYPE html>
<html>
<head>
	<title>Установка статуса для старых заказов</title>
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
	$err="";
	
	
//	if ((isset($_SESSION['admstat'])) && ($_SESSION['admstat']==1)) {
?>
	<h1>Установка статуса для старых заказов</h1>
<?php 
//$sql="SELECT * FROM Message57 WHERE Vendor=2 AND Subdivision_ID=141 AND Sub_Class_ID=176";
$sql="SELECT * FROM Message51 WHERE Status=1 AND Created<'2013-08-31 23:59:59' ORDER BY Message_ID ASC";
$i=0;
if ($res=mysql_query($sql)) {
	while($row=mysql_fetch_array($res)) {
		echo $row["Message_ID"]." ".$row["Status"]." [".$row["Created"]."]<br>";
		$sql="UPDATE Message51 SET Status=4,closed=1 WHERE Message_ID=".$row["Message_ID"];
		//echo $sql."<br>";
		if (!mysql_query($sql)) {
			echo ($sql." Error: ".mysql_error()."<br>");
		}
		$sql="INSERT INTO Netshop_OrderHistory (Order_ID, Item_Type, Item_ID, Qty,OriginalPrice,ItemPrice,orderstatus_id,created,comments)
			VALUES ({$row["Message_ID"]}, 57,0,0,0,0,4,'".date("Y-m-d H:i:s")."','заказ закрыт по истечении срока давности')";
		//echo $sql."<br>";
		if (!mysql_query($sql,$con)) {
			echo ($sql." Error: ".mysql_error()."<br>");
		}
		$i=$i+1;
		//if ($i==10) {
		//	break;
		//}
	}
}	


} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

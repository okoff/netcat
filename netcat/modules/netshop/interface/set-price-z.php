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
	<title>Установка закупочной цены</title>
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
	<h1>Установка закупочной цены</h1>
<?php 
//$sql="SELECT * FROM Message57 WHERE Vendor=2 AND Subdivision_ID=141 AND Sub_Class_ID=176";
$sql="SELECT * FROM Message57 WHERE Vendor=2 AND Subdivision_ID=138 AND Sub_Class_ID=173 AND (Name LIKE '%сталь Х12МФ%' AND Name LIKE '%береста%')";
if ($res=mysql_query($sql)) {
	while($row=mysql_fetch_array($res)) {
		echo $row["Message_ID"]." ".$row["Name"]." [".$row["Vendor"]."]<br>";
		$sql="INSERT INTO Message57_p (Message_ID,Price,created) VALUES ({$row["Message_ID"]},1300,'".date("Y-m-d H:i:s")."')";
		//echo $sql."<br>";
		if (!mysql_query($sql)) {
			echo ($sql." Error: ".mysql_error()."<br>");
		}
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

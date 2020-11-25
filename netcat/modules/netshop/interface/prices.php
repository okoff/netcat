<?php
// 19.10.2015 Elen
// обновление цен от поставщика
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
	<title>Загруженные прайслисты от поставщиков</title>
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
<h1>Загруженные прайслисты от поставщиков</h1>
<p><a href="/netcat/modules/netshop/interface/price-upload.php">Загрузить прайслист</a></p>
<table cellpadding="2" cellspacing="0" border="1">

<?php 
$sql="SELECT * FROM Netshop_Suppricelist ORDER BY created DESC";
if ($result=mysql_query($sql)) {
	if($row = mysql_fetch_array($result)) {
		$fname=$row['fname'];
		echo "<tr>";
		echo "<td>".(($row['processed']) ? "<img src='/images/icons/ok.png'" : "-")."</td>";
		echo "<td>".date("d.m.Y",strtotime($row['created']))."</td>";
		echo "<td>".getManufacturer($row['supplier_id'])."</td>";
		echo "<td>{$row['fname']}</td>";
		
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

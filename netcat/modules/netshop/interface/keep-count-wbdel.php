<?php
// 19.02.2015 Elen
// учёт
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");


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
if (!(is_numeric($incoming['id']))) {
	die("Неверный номер накладной!");
}
$sql="DELETE FROM Keepcount_WB WHERE id=".intval($incoming['id']);
if (!mysql_query($sql)) {	
	die($sql."<br>Ошибка: ".mysql_error());
}
mysql_close($con);
header("Location:/netcat/modules/netshop/interface/keep-count.php");
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


?>

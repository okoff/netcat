<?php
// 12.11.2013 Elen
// создание клиента из заказа 
include_once ("../../../../vars.inc.php");
error_reporting(E_ALL);
session_start();

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

?>
<!DOCTYPE html>
<html>
<head>
	<title>Поиск видео в описании</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>
<body>
<h1>Поиск видео в описании</h1>
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	$sql="SELECT * FROM Message57 WHERE Details LIKE '%youtube.com%' ORDER BY Message_ID";// LIMIT 0,1500";
	if ($result1=mysql_query($sql)) {
		while ($row1 = mysql_fetch_array($result1)) { 
			$sql="UPDATE Message57 SET video=1 WHERE Message_ID={$row1['Message_ID']}";
			$html.=$sql."<br>";
			if (mysql_query($sql)) {
				$html.="Телефон обновлен<br>";
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
			
		}
	}
	echo printMenu();
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

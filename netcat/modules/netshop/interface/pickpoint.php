<?php
// 14.03.2016 Elen
// работа с PickPoint отправлениями
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");
$incoming = parse_incoming();


// ------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
	<title>PickPoint</title>
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
	
	
?>
	<h1>Работа с PickPoint отпарвлениями</h1>
	<ul>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/order-pickpoint.php'>Список заказов. Формирование файла для отправки</a></li>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Списки для отправки</a></li>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/order-pickpointsend.php'>Отправленные заказы</a></li>
	</ul>
<?php
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

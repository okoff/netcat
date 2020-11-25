<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");

session_start();


// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$incoming = parse_incoming();

?>
<!DOCTYPE html>
<html>
<head>
	<title>Списания по накладным</title>
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
	<h1>Списания по накладным</h1>
	<ul>
	<li><a href="/netcat/modules/netshop/interface/selling.php">Провести списания</a></li>
	<li>Просмотр оплат</li>
	<li>Сохранить оплаты</li>
	</ul>
	<?php 

} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>


<?php
// 17.01.2014 Elen
// статистика по магазину
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

?>
<!DOCTYPE html>
<html>
<head>
	<title>Артикул</title>
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
	if ($incoming['request_method']=="post") {
		if (($incoming['articul']!="")&&(intval($incoming['articul']))) {
			$sql="UPDATE ItemSettings SET articul=".$incoming['articul'];
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			} else {
				$err="<tr><td colspan='2'><p style='color:#f30000;font-weight:bold;text-align:center;'>Сохранено</p></td></tr>";
			}
		}
	}
	
//	if ((isset($_SESSION['admstat'])) && ($_SESSION['admstat']==1)) {
?>
	<h1>Артикул</h1>
	
<?php 
//	} else {
?>
	<br clear='both'>
	
	<form method="post" name="frm1" id="frm1" action="/netcat/modules/netshop/interface/articul.php">
	<table cellpadding="0" cellspacing="5" border="0" style="width:500px; margin:0 auto;">
	<?php echo $err; ?>
	<tr><td style="align:right;">Последний добавленный артикул<br>("0-" добавляется автоматически):</td>
		<td><input type="text" name="articul" value="<?php echo getNewArticul(); ?>"></td></tr>
	<tr><td colspan="2" style="text-align:center;"><input type="submit" value="Сохранить"></td></tr>
	</table>
	</form>
<?php		
//	}
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

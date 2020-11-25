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
	<title>Реквизиты</title>
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
		$sql="UPDATE Netshop_Details SET name='".htmlspecialchars($incoming['name'])."',
									inn_kpp='".htmlspecialchars($incoming['inn_kpp'])."',
									address_ur='".htmlspecialchars($incoming['address_ur'])."',
									bank='".htmlspecialchars($incoming['bank'])."',
									count_rasch='".htmlspecialchars($incoming['count_rasch'])."',
									count_korr='".htmlspecialchars($incoming['count_korr'])."',
									bik='".htmlspecialchars($incoming['bik'])."',
									phone='".htmlspecialchars($incoming['phone'])."'";
			echo $sql;
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			} else {
				$err="<tr><td colspan='2'><p style='color:#f30000;font-weight:bold;text-align:center;'>Сохранено</p></td></tr>";
			}
		
	}
	
//	if ((isset($_SESSION['admstat'])) && ($_SESSION['admstat']==1)) {
?>
	<h1>Реквизиты</h1>
<?php 
$sql="SELECT * FROM Netshop_Details";
if ($res=mysql_query($sql)) {
	$row=mysql_fetch_array($res);
}	
?>
	<br clear='both'>
	
	<form method="post" name="frm1" id="frm1" action="/netcat/modules/netshop/interface/shop-details.php">
	<table cellpadding="0" cellspacing="5" border="0" style="width:500px;">
	<?php echo $err; ?>
	<tr><td style="align:right;">Название</td><td><input type="text" name="name" value="<?php echo $row['name']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">ИНН/КПП</td><td><input type="text" name="inn_kpp" value="<?php echo $row['inn_kpp']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Юр. адрес</td><td><input type="text" name="address_ur" value="<?php echo $row['address_ur']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Банк</td><td><input type="text" name="bank" value="<?php echo $row['bank']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Расчетный счёт</td><td><input type="text" name="count_rasch" value="<?php echo $row['count_rasch']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Корр. счёт</td><td><input type="text" name="count_korr" value="<?php echo $row['count_korr']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">БИК</td><td><input type="text" name="bik" value="<?php echo $row['bik']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Телефон</td><td><input type="text" name="phone" value="<?php echo $row['phone']; ?>" style="width:400px;"></td></tr>
	<tr><td colspan="2" style="text-align:center;"><input type="submit" value="Сохранить"></td></tr>
	</table>
	</form>
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

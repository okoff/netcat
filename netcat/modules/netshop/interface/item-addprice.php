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
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
if (!is_numeric($incoming['itm'])) {
	die("Неверный ID товара");
}
// ------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
	<title>Редактирование отпускной цены</title>
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
	$err="";
	if ($incoming['request_method']=="post") {
		if (($incoming['itm']!="")&&(intval($incoming['itm']))) {
			$sql="SELECT Price FROM Message57_p WHERE Message_ID=".intval($incoming["itm"]);
			$result=mysql_query($sql);
			$sql="INSERT INTO Message57_p (Message_ID, Price, created) VALUES ({$incoming['itm']},{$incoming['price']},'".date("Y-m-d H:i:s")."')";
			if ($row = mysql_fetch_array($result)) {
				if ($row['Price']!="") {
					$sql="UPDATE Message57_p SET Price={$incoming['price']} WHERE Message_ID={$incoming['itm']}";
				} 
			}
			//echo $sql;
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			} else {
				$err="<tr><td colspan='3'><p style='color:#f30000;font-weight:bold;text-align:center;'>Сохранено</p></td></tr>";
			}
		}
	}
?>
	<h1>Редактирование отпускной цены</h1>
<?php	
$sql="SELECT ItemID,Name FROM Message57 WHERE Message_ID=".intval($incoming["itm"]);
$result=mysql_query($sql);
if ($row = mysql_fetch_array($result)) {	
	echo "<h2>[{$row['ItemID']}] {$row['Name']}</h2>";
}
$sql="SELECT Price FROM Message57_p WHERE Message_ID=".intval($incoming["itm"]);
$result=mysql_query($sql);
if ($row = mysql_fetch_array($result)) {	
	echo "<p>Прежняя отпускная цена: {$row['Price']}</p>";
}
?>
	<br clear='both'>
	
	<form method="post" name="frm1" id="frm1" action="/netcat/modules/netshop/interface/item-addprice.php">
	<input type="hidden" name="itm" id="itm" value="<?php echo($incoming["itm"]);?>">
	<table cellpadding="0" cellspacing="5" border="0" style="width:350px; margin:0;">
	<?php echo $err; ?>
	<tr><td style="align:right;">Цена:</td>
		<td><input type="text" name="price" id="price" value=""></td>
		<td><input type="submit" value="Сохранить"></td></tr>
	</table>
	</form>
</body>
</html>
<?php

mysql_close($con);
?>

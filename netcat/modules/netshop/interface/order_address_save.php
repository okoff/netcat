<?php
// 2019/03/16 Elen 
// Add/change delivery address

include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once("CalculatePriceDeliveryCdek.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$where="";
//print_r($incoming);
//echo "<br>";
//cdek_cityid
$sql = "UPDATE `Message51` SET `cdek_cityid`=".intval($incoming['receiverCityId']).",
	`cdek_modeid`=".intval($incoming['modeId']).",
	`cdek_pvz`='".htmlspecialchars($incoming['cdek_pvzid'])."',
	`DeliveryMethod`=9,
	`Town`='".htmlspecialchars($incoming['city'])."',
	`DeliveryCost`=".intval($incoming['price'])." WHERE `Message_ID`=".intval($incoming['oid']);

//echo "<br>".$sql;
if (mysql_query($sql)) {
	echo "<p>Стоимость доставки сохранена.</p>
	<p><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message=".intval($incoming['oid'])."'>Вернуться в заказ</a></p>";
} else {
	die($sql."Ошибка: ".mysql_error());
}
?>

<?php
include_once ("utils.php");
include_once ("../../../../vars.inc.php");
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
mysql_set_charset("cp1251", $con);

$incoming = parse_incoming();
if (!isset($incoming['id'])) {
	die("Не указан номер заказа.");
}
if (isset($incoming['action'])) {
	switch ($incoming['action']) {
		case "delivery":
			$sql="UPDATE Message51 SET DeliveryMethod=".$incoming['val']." WHERE Message_ID={$incoming['id']}";
			if (mysql_query($sql)) {
				echo "Способ доставки изменен. <a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$incoming['id']}'>Вернуться в заказ.</a>";
			} else {
				die("Ошибка: {$sql} " . mysql_error());
			}
			break;
		case "payment":
			$sql="UPDATE Message51 SET PaymentMethod=".$incoming['val']." WHERE Message_ID={$incoming['id']}";
			if (mysql_query($sql)) {
				echo "Способ оплаты изменен. <a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$incoming['id']}'>Вернуться в заказ.</a>";
			} else {
				die("Ошибка: {$sql} " . mysql_error());
			}
			break;
	}
}

mysql_close($con);
?>

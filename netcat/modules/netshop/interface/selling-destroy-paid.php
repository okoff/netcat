<?php
// отмена списаний по поставщику
$supID=18;  // жбанов
//$supID=5;  // кустари
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");

$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$incoming = parse_incoming();

$sql="SELECT * FROM Waybills WHERE vendor_id={$supID} AND payment_id=3 AND status_id=2 ORDER BY id ASC";
$result=mysql_query($sql);
while ($row = mysql_fetch_array($result)) {	
	echo "<h1>{$row['id']}</h1>";
	$sql1="SELECT * FROM Waybills_goods WHERE waybill_id={$row['id']} ORDER BY id ASC";
	$res1=mysql_query($sql1);
	while ($row1 = mysql_fetch_array($res1)) {	
		// удаляем оплаченные позиции
		$sql3="UPDATE Waybills_goods SET paid=0 WHERE id={$row1['id']}";
		if (!mysql_query($sql3)) {	
			die($sql3."<br>Ошибка: ".mysql_error());
		}
		$sql4="UPDATE Waybills_selling SET payment_id=0 WHERE item_id={$row1['item_id']}";	
		if (!mysql_query($sql4)) {
			die($sql4." Error: ".mysql_error());
		}
	}

	//break;
}

mysql_close($con);
?>
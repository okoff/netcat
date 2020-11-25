<?php
// отмена второго зачисления накладной 629
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


$wbid=4428;
$sql="SELECT * FROM Waybills_goods WHERE waybill_id={$wbid} ORDER BY id ASC";
$result=mysql_query($sql);
while ($row = mysql_fetch_array($result)) {	
	$qtywb=$row['qty'];
	echo $row['id']."|".$row['item_id']."|".$row['qty']."-|";
	$sql1="SELECT StockUnits FROM Message57 WHERE Message_ID=".$row['item_id'];
	$result1=mysql_query($sql1);
	while ($row1 = mysql_fetch_array($result1)) {
		$qtystock=$row1['StockUnits'];
		echo $qtystock."+|";
	}
	$qty=intval($qtystock)+intval($qtywb);
	echo $qty;
	if ($qty>=0) {
		$sql2="UPDATE Message57 SET StockUnits={$qty} WHERE Message_ID=".$row['item_id'];
		echo "<br>".$sql2;
		if (!mysql_query($sql2)) {	
			die($sql2."<br>Ошибка: ".mysql_error());
		}
	}
	echo "<br>";
}

mysql_close($con);
?>
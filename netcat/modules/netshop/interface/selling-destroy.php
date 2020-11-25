<?php
// отмена списаний по поставщику
$supID=18;  // жбанов
//$supID=5;  // кустари
//$supID=45;  // мастер гарант
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
	//if ($row['id']<596) {
	$sql1="SELECT * FROM Waybills_goods WHERE waybill_id={$row['id']} AND paid<sales ORDER BY id ASC";
	$res1=mysql_query($sql1);
	while ($row1 = mysql_fetch_array($res1)) {	
		// удаляем из списаний
		$sqlt="SELECT * FROM Waybills_selling WHERE waybill_id={$row['id']} AND item_id={$row1['item_id']} AND payment_id=0";
		echo $sqlt."<br>";
		$rest=mysql_query($sqlt);
		while ($row2=mysql_fetch_array($rest)) {
			$sql2=$sql3=$sql4="";
			$sql2="DELETE FROM Waybills_selling WHERE id={$row2['id']}";
			// удаляем из позиции накладной списание
			if ($row1['sales']-$row2['qty']>=0) {
				$sql3="UPDATE Waybills_goods SET sales=sales-{$row2['qty']} WHERE id={$row1['id']} ";
			}
			if ($row2['order_id']>0) {
				
				$sql4="UPDATE Netshop_OrderGoods SET sales=0,closed=0 WHERE Item_ID={$row1['item_id']} AND order_id={$row2['order_id']}";
			} else {
				$sql4="UPDATE Retails_goods SET sales=0,closed=0 WHERE item_id={$row1['item_id']} AND retail_id={$row2['retail_id']}";
			}
			echo $sql2."<br>".$sql3."<br>".$sql4."<br><br>";
			if (!mysql_query($sql2)) {	
				die($sql2."<br>Ошибка: ".mysql_error());
			}
			if (!mysql_query($sql3)) {	
				die($sql3."<br>Ошибка: ".mysql_error());
			}
			if (!mysql_query($sql4)) {	
				die($sql4."<br>Ошибка: ".mysql_error());
			}
			/*if (!mysql_query($sql5)) {	
				die($sql5."<br>Ошибка: ".mysql_error());
			}*/
		}
	}
	//}
	//break;
}

mysql_close($con);
?>
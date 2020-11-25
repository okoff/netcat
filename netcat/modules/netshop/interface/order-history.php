<?php
// 16.09.2013 Elen
include_once ("../../../../vars.inc.php");

//print_r($_GET);
if (isset($_GET['oid'])) {
	$order_id=intval($_GET['oid']);
}
//echo $order_id;
if ($order_id) {

?>
<!DOCTYPE html>
<html>
<head>
	<title>История заказа <?php echo $order_id; ?></title>
	<script>
		function addToOrder(mesid, price) {
			//alert(mesid);
			document.getElementById('articul').value=mesid;
			document.getElementById('price').value=price;
			document.getElementById("frm2").submit();
		}
	</script>
	<style>
	body {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>
<body>
	<h1>История заказа <?php echo $order_id; ?></h1>
<?php
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	mysql_select_db($MYSQL_DB_NAME, $con);
	mysql_set_charset("cp1251", $con); 
	
	$sql="SELECT ShopOrderStatus_Name, Message51.Created FROM Classificator_ShopOrderStatus
				INNER JOIN Message51 ON ShopOrderStatus_ID=Status
				WHERE Message_ID={$order_id}";
	if ($ress=mysql_query($sql)) {
		while($s = mysql_fetch_array($ress)) {
			$status=$s['ShopOrderStatus_Name'];
			$created=$s['Created'];
		}
	}
	echo "<p>Дата создания заказа: <b>".date("d.m.Y",strtotime($created))."</b></p>
	<p><b>{$status}</b></p>";
?>
	<table cellpadding="2" cellspacing="0" border="1">
	<tr><td>#</td><td>&nbsp;</td><td>Артикул</td><td>Название</td><td>Кол.</td><td>Цена, руб.</td><td>Комментарий</td></tr>
<?php
	$sql="SELECT Netshop_OrderHistory.*, Message57.Name, Message57.ItemID AS articul, Message57.Status FROM Netshop_OrderHistory 
		LEFT JOIN Message57 ON (Netshop_OrderHistory.Item_ID=Message57.Message_ID)
		WHERE Order_ID={$order_id} ORDER BY id ASC";
	//echo $sql;
	$dtime=$dtimeold="";
	$i=1;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			//print_r($row);
			echo "<tr>
				<td>{$i}</td><td><b>".date("d.m.Y H:i:s", strtotime($row['created']))."</b></td><td>{$row['articul']}</td><td>{$row['Name']}</td><td>{$row['Qty']}</td>
				<td>{$row['ItemPrice']}</td><td>{$row['comments']}</td>
				</tr>";
			$i=$i+1;			
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	mysql_close($con);

?>
	</table>
</body>
</html>
<?php
} else {
	die("Неверный номер заказа!");
}
?>
<?php
// 26.08.2013 Elen
include_once ("../../../../vars.inc.php");
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
mysql_set_charset("cp1251", $con); 
	
	
function showFrmFindArticul($order_id,$item_id) {
	$name="";
	$sql="SELECT Name,ItemID FROM Message57 WHERE Message_ID=".$item_id;
	//echo $sql;
	$result = mysql_query($sql);
	while($row = mysql_fetch_array($result)) {
		$name="[{$row['ItemID']}] <b>{$row['Name']}</b>";
	}
	$html="<form id='frm1' name='frm1' method='post' action='/netcat/modules/netshop/interface/del-item.php'>
	<fieldset>
		<legend>Удаление товара ".$name." из заказа #".$order_id."</legend>
		<input type='hidden' value='".$order_id."' name='oid' id='oid'>
		<input type='hidden' value='".$item_id."' name='iid' id='iid'>
		<input type='submit' value='Удалить?'>
	</fieldset>
	</form>";
	return $html;
}

if (isset($_GET['oid'])) {
	$order_id=intval($_GET['oid']);
}
if (isset($_GET['iid'])) {
	$item_id=intval($_GET['iid']);
}
$show=1;
if ((isset($_POST['oid'])) && (isset($_POST['iid']))) {
	$order_id=intval($_POST['oid']);
	$item_id=intval($_POST['iid']);
	//print_r($_POST);
	
	$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID={$order_id} AND Item_ID={$item_id}";
	$result = mysql_query($sql);
	while($row = mysql_fetch_array($result)) {
		//print_r($row);
		$sql="INSERT INTO Netshop_OrderHistory (Order_ID, Item_Type, Item_ID, Qty,OriginalPrice,ItemPrice,created,comments)
				VALUES ({$order_id}, {$row['Item_Type']},{$item_id},1,{$row['OriginalPrice']},{$row['ItemPrice']},'".date("Y-m-d H:i:s")."','удаление товара из заказа')";
		if (mysql_query($sql,$con)) {
			
		} else {
			echo $sql."<p>Ошибка: " . mysql_error()."</p>";
		}
	}	
	$sql="DELETE FROM Netshop_OrderGoods WHERE Order_ID={$order_id} AND Item_ID={$item_id}";
	//echo $sql;
	if (mysql_query($sql,$con)) {
		echo "<p>Позиция удалена из заказа.</p>";
		echo "<p>Изменения сохранены в историю.</p>";
	} else {
		echo "Ошибка: " . mysql_error();
	}
	
	//header("Location:/netcat/message.php?catalogue=1&sub=57&cc=53&message=".$order_id);
	echo "<p><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$order_id}'>вернуться в заказ</a></p>";

	
	
	$show=0;
} 
if ($order_id && $item_id) {
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<title>Удаление товара из заказа <?php echo $order_id; ?></title>
	<script>
		function addToOrder(mesid, price) {
			//alert(mesid);
			document.getElementById('articul').value=mesid;
			document.getElementById('price').value=price;
			document.getElementById("frm2").submit();
		}
	</script>
</head>
<body>
<?php
	if ($show) {
		echo showFrmFindArticul($order_id, $item_id);
	}
}
?>

</body>
</html>
<?php
mysql_close($con);
?>
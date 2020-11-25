<?php
// 26.08.2013 Elen
include_once ("../../../../vars.inc.php");

function showFrmFindArticul($order_id) {
	$html="<form id='frm1' name='frm1' method='post' action='/netcat/modules/netshop/interface/add-item.php'>
	<fieldset>
		<legend>Добавление товара в заказ #".$order_id."</legend>
		<input type='hidden' value='".$order_id."' name='oid' id='oid'>
		<label for='art'>Артикул:</label><input type='text' id='art' name='art' value=''>
		<input type='submit' value='Найти'>
	</fieldset>
	</form>";
	return $html;
}
function showfindArticul($order_id,$articul,$MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME) {
	$html="";
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}

	mysql_select_db($MYSQL_DB_NAME, $con);
	mysql_set_charset("cp1251", $con); 
	$html.="
	<form name='frm2' id='frm2'  method='post' action='/netcat/modules/netshop/interface/add-item.php'>
	<fieldset>
	<input type='hidden' value='' name='articul' id='articul'>
	<input type='hidden' value='' name='price' id='price'>
	<input type='hidden' value='".$order_id."' name='oid' id='oid'>
	<table>";
	$result = mysql_query("SELECT * FROM Message57 WHERE ItemID LIKE '%".$articul."%' ORDER BY Message_ID DESC");
	while($row = mysql_fetch_array($result)) {
		$html.="<tr><td>".$row['ItemID']."</td>";
		$image_arr = explode(":", $row['Image']);
		$image_url = "/netcat_files/".$image_arr[3];
		$html.="<td><img src='".$image_url."' width='300'></td>";
		$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
		$html.="</tr>";
	}
	$html.="</table>
	</fieldset>
	</form>";
	mysql_close($con);
	return $html;
}

//print_r($_GET);
if (isset($_GET['oid'])) {
	$order_id=intval($_GET['oid']);
}
if (isset($_POST['oid'])) {
	$order_id=intval($_POST['oid']);
	//print_r($_POST);
	if (isset($_POST['art'])) {
		$articul = $_POST['art'];
		//showfindArticul($articul);
	}
}
//echo $order_id;
if ($order_id) {

?>
<!DOCTYPE html>
<html>
<head>
	<title>Добавление товара в заказ <?php echo $order_id; ?></title>
	<script>
		function addToOrder(mesid, price) {
			//alert(mesid);
			document.getElementById('articul').value=mesid;
			document.getElementById('price').value=price;
			document.getElementById("frm2").submit();
		}
	</script>
	<script type="text/javascript">
		//setTimeout('window.location.replace("/netcat/message.php?catalogue=1&sub=57&cc=53&message=<?php echo $order_id; ?>")', 2000);
		
	</script>
</head>
<body>
<?php
	echo showFrmFindArticul($order_id);
	if (isset($_POST['art'])) {
		$articul = $_POST['art'];
		echo showfindArticul($order_id,$articul,$MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);
	}
	if (isset($_POST['articul'])) {
		$articul = $_POST['articul'];
		$price = $_POST['price'];
		$itemprice = $_POST['price'];
		//echo $articul."-".$price;
		$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
		if (!$con) {
			die('Could not connect: ' . mysql_error());
		}
		
		mysql_select_db($MYSQL_DB_NAME, $con);
		mysql_set_charset("cp1251", $con); 
		
		$sql="SELECT * FROM Message54 WHERE Goods LIKE '%:{$articul},%' AND checked=1 AND CURDATE( ) BETWEEN ValidFrom AND ValidTo";
		$result = mysql_query($sql);
		while($row = mysql_fetch_array($result)) {
			if ($row['FunctionOperator']=="*=") {
				$itemprice=$price*$row['Function'];
			}
			if ($row['FunctionOperator']=="-=") {
				$itemprice=$price-$row['Function'];
			}
		}
		$sql="INSERT INTO Netshop_OrderGoods (Order_ID,Item_Type,Item_ID,Qty,OriginalPrice,ItemPrice) 
				VALUES ({$order_id}, 57,{$articul},1,{$price},{$itemprice})";
		//echo $sql;
		if (mysql_query($sql,$con)) {
			echo "<p>Товар добавлен.</p>";
		} else {
			echo "<p>Ошибка: " . mysql_error()."</p>";
		}
		$sql="INSERT INTO Netshop_OrderHistory (Order_ID, Item_Type, Item_ID, Qty,OriginalPrice,ItemPrice,created,comments)
			VALUES ({$order_id}, 57,{$articul},1,{$price},{$itemprice},'".date("Y-m-d H:i:s")."','добавление товара в заказ')";
		if (mysql_query($sql,$con)) {
			echo "<p>Изменения сохранены в историю.</p>";
		} else {
			echo "<p>Ошибка: " . mysql_error()."</p>";
		}
		mysql_close($con);
	}
?>

</body>
</html>
<?php
} else {
	die("Неверный номер заказа!");
}
?>
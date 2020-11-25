<?php
// 22.05.2014 Elen
// печать товарного чека
include_once ("../../../../vars.inc.php");
session_start();

// проверка авторизации ---------------------------------------------------------------------------
//if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
//	$url="/netcat/modules/netshop/interface/login.php?jump=".$_SERVER['SCRIPT_NAME'];
//	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
//}
// ------------------------------------------------------------------------------------------------
include_once ("utils.php");
include_once ("utils-numtotext.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$where="";
$id=$rid=0;
//print_r($incoming);
// order
if (isset($incoming['id'])) {
	if (is_numeric($incoming['id'])) {
		$id=intval($incoming['id']);
	} else {
		die('Неверный номер заказа');
	}
}
// retail
if (isset($incoming['rid'])) {
	if (is_numeric($incoming['rid'])) {
		$rid=intval($incoming['rid']);
	} else {
		die('Неверный номер заказа');
	}
}
$items="";
$deliverycost=0;
$discount=0;
if ($id!=0) {
	// check if order exists
	$sql="SELECT * FROM Message51 
		WHERE Message_ID=".intval($incoming['id']);
	//echo $sql;
	if ($result=mysql_query($sql)) {
		if ($row = mysql_fetch_array($result)) { 
			// deliverycost
			$deliverycost=$row['DeliveryCost'];
			// get order items
			$sql="SELECT Netshop_OrderGoods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Netshop_OrderGoods 
				INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
				WHERE Order_ID=".$row['Message_ID'];
			$result1=mysql_query($sql);
			while ($row1 = mysql_fetch_array($result1)) {
				$items.="[{$row1['ItemID']}] {$row1['Name']}|{$row1['Qty']}|{$row1['ItemPrice']}|;";
			}
			$sql="SELECT * FROM Netshop_OrderDiscounts WHERE Order_ID=".$row['Message_ID']." AND Item_ID=0";
			$result1=mysql_query($sql);
			while ($row1 = mysql_fetch_array($result1)) {
				$discount=$row1['Discount_Sum'];
			}
		}
	} else {
		die('Неверный номер заказа');
	}
	
}
if ($rid!=0) {
	// check if order exists
	$sql="SELECT * FROM Retails 
		WHERE id=".intval($incoming['rid']);
	//echo $sql;
	if ($result=mysql_query($sql)) {
		if ($row = mysql_fetch_array($result)) { 
			// deliverycost
			$deliverycost=0;
			$discount=$row['discount'];
			// get order items
			$sql="SELECT Retails_goods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Retails_goods 
				INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
				WHERE retail_id=".$rid." and deleted=0";
			$result1=mysql_query($sql);
			while ($row1 = mysql_fetch_array($result1)) {
				$items.="[{$row1['ItemID']}] {$row1['Name']}|{$row1['qty']}|{$row1['itemprice']}|;";
			}
			
		}
	} else {
		die('Неверный номер заказа');
	}
	
}

mysql_close($con);

?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Товарный чек</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	body {
		margin:0 10px;
		padding:0;
		width:800px;
	}
	table {
		border-top:1px solid #000;
		border-right:1px solid #000;
	}
	td {
		border-left:1px solid #000;
		border-bottom:1px solid #000;
	}
	</style>
</head>
<body>
<p><b>ООО &quot;Русская компания&quot;</b></p>
<!--p style='margin:0;padding:0;text-align:right;'>Дата &quot;<?php echo date("d", strtotime($row['Created'])); ?>&quot; <?php echo $mo[date("m", strtotime($row['Created']))]; ?> <?php echo date("Y", strtotime($row['Created'])); ?>г.</p-->
<p style='margin:0;padding:0;text-align:right;'>Дата &quot;____&quot; ______________________ ______г.</p>
<h2 style='margin:0;padding:0;text-align:center;'>ТОВАРНЫЙ ЧЕК</h2>
<br>
<table cellpadding='2' cellspacing='0' border='0' width='99%'>
	<tr><td style='text-align:center;'><b>Наименование товара</b></td>
	<td style='text-align:center;'><b>Кол-во</b></td>
	<td style='text-align:center;'><b>Цена</b></td>
	<td style='text-align:center;'><b>Сумма</b></td></tr>
<?php
	$itog=0;
	//echo $items."<br>";
	$tmp=explode("|;",$items);
	foreach($tmp as $t) {
		if (strlen($t)>3) {
			$item=explode("|",$t);
		
			echo "<tr>
				<td>{$item[0]}</td>
				<td style='text-align:center;'>{$item[1]}</td>
				<td style='text-align:right;'>{$item[2]}</td>
				<td style='text-align:right;'>".($item[1]*$item[2])."</td>
			</tr>";

			$itog=$itog+$item[1]*$item[2];
		}
	}
	$itog=$itog+$deliverycost;
?>
	<tr><td colspan='3'>Стоимость доставки:</td>
		<td style='text-align:right;'><b><?php echo $deliverycost; ?></b></td></tr>
<?php 
	if ($discount!=0) { ?>
	<tr><td colspan='3'>Скидка:</td>
		<td style='text-align:right;'><b><?php echo $discount; ?></b></td></tr>
<?php
		$itog=$itog-$discount;
	}
?>
	<tr><td colspan='3' style='font-size:10pt;'><b>ИТОГО</b></td>
			<td style='text-align:right;'><b><?php echo $itog; ?></b></td></tr>
		
	</table>
	<p><b>Всего:</b>
<?php			
	$mt = new ManyToText();
	echo $mt->Convert($itog);
?></p>
<p style='text-align:right;'>Продавец:_________________________________</p>

</body>
</html>


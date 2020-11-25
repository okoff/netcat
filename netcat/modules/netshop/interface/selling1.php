<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");

session_start();
function printWbCart($id) {
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:760px;'>
	<tr><td>Артикул</td><td>Название</td><td>Цена закуп.</td><td>Кол.</td><td>На&nbsp;складе</td><td>Продано</td><td>Оплачено</td></tr>";
	$sql="SELECT Waybills_goods.*, Message57.StockUnits,Message57.ItemID AS ItemID, Message57.Name AS Name FROM Waybills_goods 
		INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		WHERE waybill_id=".$id;
	//echo $sql;
	
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$style="";
		$sales=$row['sales'];
		if ($row['sales']==1) {
			$style="background:#FFFF99;";
			$sql="UPDATE Waybills_goods SET sales=0 WHERE id=".$row['id'];
			if (!mysql_query($sql)) {
				die($sql."<br>Error: ".mysql_error());
			}
		} else {
			if ($row['StockUnits']<$row['qty']) {
				$sql="INSERT INTO Waybills_selling (waybill_id,item_id,qty,created) VALUES ({$row['waybill_id']},{$row['item_id']},{$row['qty']},'".date("Y-m-d H:i:s")."')";
				$style="background:#99CCFF;";
				//echo $sql."<br>";
				//if (!mysql_query($sql)) {
				//	die($sql."<br>Error: ".mysql_error());
				//}
			}
			if ($row['StockUnits']==0) {
				// все продали
				// set sales=1
				$sql="UPDATE Waybills_goods SET sales=1 WHERE id=".$row['id'];
				//if (!mysql_query($sql)) {
				//	die($sql."<br>Error: ".mysql_error());
				//}
				$sql="INSERT INTO Waybills_selling (waybill_id,item_id,qty,created) VALUES ({$row['waybill_id']},{$row['item_id']},{$row['qty']},'".date("Y-m-d H:i:s")."')";
				//echo $sql."<br>";
				//if (!mysql_query($sql)) {
				//	die($sql."<br>Error: ".mysql_error());
				//}
				$sales=1;
				$style="background:#FFFF99;";
			}
		}
		$res.="<tr><td width='50' style='font-size:8pt;{$style}'>{$row['ItemID']}</td>
			<td style='width:500px;font-size:8pt;{$style}'>{$row['Name']}</td>
			<td width='50' style='font-size:8pt;{$style}'>".($row['originalprice']*$row['qty'])."</td>
			<td width='50' style='font-size:8pt;{$style}'>{$row['qty']}</td>
			<td width='50' style='font-size:8pt;{$style}'>{$row['StockUnits']}</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>".(($sales==1) ? "<img src='/images/icons/ok.png' style='dispaly:block;margin:0 auto;'>" : "-")."</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>".(($row['paid']==1) ? "<img src='/images/icons/ok.png' style='dispaly:block;margin:0 auto;'>" : "-" )."</td>
			</tr>";
	}
	$res.="</table>";
	return $res;
}
function getWaybillsRe($incoming) {
	$tmp="";
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
	}
	$sql="SELECT * FROM Waybills 
		WHERE payment_id=3 AND status_id=2 AND type_id=2 {$tmp}
		ORDER BY id ASC";
	//echo "<br>".$sql;
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'><td>#</td><td>Дата создания</td><td>Поставщик</td><td>Товар</td></tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
			<td style='vertical-align:top;'><b>{$row['id']}</b></td>
			<td style='vertical-align:top;'>".date("d.m.Y",strtotime($row['created']))."</td>
			<td style='vertical-align:top;'>".getSupplier($row['vendor_id'])."</td>
			<td>".printWbCart($row['id'])."</td>
		</tr>";
		}
	}
	$html.="</table>";
	return $html;
}
// проверить, если ли такая продажа в Waybills_goodssale
function getSelling($order_id=0,$retail_id=0,$item_id) {
	$res=0;
	$sql="SELECT id FROM Waybills_goodssale WHERE item_id={$item_id} AND order_id={$order_id} AND retail_id={$retail_id}";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$res=$row['id'];
		}
	}
	return $res;
}

// запись продаж в таблицу Waybills_goodssale 
function getWaybillsGoodsSale() {
	$startdate="01.03.2014"; // дата первой накладной в БД
	
	$sql="SELECT Netshop_OrderGoods.*,Message51.Created FROM Netshop_OrderGoods INNER JOIN Message51 ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID) 
		WHERE NOT Message51.Status=2 AND Message51.Created >= '".date("Y-m-d H:i:s",strtotime($startdate))."' ORDER BY Order_ID ASC";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if (getSelling($row['Order_ID'],0,$row['Item_ID'])==0) {
				// вставить новую продажу
				$sql="INSERT INTO Waybills_goodssale (item_id, order_id, retail_id, qty, created,closed,checked) 
					VALUES ({$row['Item_ID']},{$row['Order_ID']},0,{$row['Qty']},'".date("Y-m-d H:i:s", strtotime($row['Created']))."',0,0)";
				echo $sql."<br>";
				//if (!mysql_query($sql)) {
				//	die($sql."<br>Error: ".mysql_error());
				//}
			}
		}
	}
}

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$show=1;
	//print_r($incoming);
	switch ($incoming['action']) {
		default:
			// 1. заполняем Waybills_goodssale
			getWaybillsGoodsSale();
			
			// 2. пересчет продаж по каждой позиции накладной
			$html=getWaybillsRe($incoming);
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Списания по накладным</title>
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
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	if ($show) {
?>
	<h1>Списания по накладным</h1>
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Поставщик</td><td><?php echo selectSupplier($incoming); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	<?php 
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

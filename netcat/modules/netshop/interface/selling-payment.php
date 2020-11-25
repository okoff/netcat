<?php
// 21.05.2014 Elen
// накладные под реализацию. просмотр оплат, сохранение оплат
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();
function getNewPaymentsSumma($incoming) {
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
	}
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	$itog=0;
	$sql="SELECT Waybills_selling.*,Waybills_goods.originalprice,Waybills_goods.qty as wbqty,Waybills_goods.sales,Waybills_goods.paid,Waybills.vendor_id,Message57.ItemID,Message57.Name FROM Waybills_selling 
		INNER JOIN Waybills ON (Waybills_selling.waybill_id=Waybills.id)
		INNER JOIN Message57 ON (Message57.Message_ID=Waybills_selling.item_id)
		INNER JOIN Waybills_goods ON (Waybills_selling.waybill_id=Waybills_goods.waybill_id AND Waybills_selling.item_id = Waybills_goods.item_id)
		WHERE Waybills_selling.payment_id=0 AND Waybills.created>'".date("Y-m-d H:i:s", strtotime($startdate))."' {$tmp}";
	//echo "<br>".$sql;
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row['sales']-$row['paid']) {
				$itog=$itog+$row['originalprice']*$row['qty'];
			}
		}
	}
	return $itog;
}
function getNewPaymentsQty($incoming) {
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
	}
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	$itog=0;
	$sql="SELECT Waybills_selling.*,Waybills_goods.originalprice,Waybills_goods.qty as wbqty,Waybills_goods.sales,Waybills_goods.paid,Waybills.vendor_id,Message57.ItemID,Message57.Name FROM Waybills_selling 
		INNER JOIN Waybills ON (Waybills_selling.waybill_id=Waybills.id)
		INNER JOIN Message57 ON (Message57.Message_ID=Waybills_selling.item_id)
		INNER JOIN Waybills_goods ON (Waybills_selling.waybill_id=Waybills_goods.waybill_id AND Waybills_selling.item_id = Waybills_goods.item_id)
		WHERE Waybills_selling.payment_id=0 AND Waybills.created>'".date("Y-m-d H:i:s", strtotime($startdate))."' {$tmp}";
	//echo "<br>".$sql;
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row['sales']-$row['paid']) {
				$itog=$itog+$row['qty'];
			}
		}
	}
	return $itog;
}
function getNewPayments($incoming) {
	$tmp="";
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	
	//print_r($incoming);
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
	}
	$itog=0;
	$itogn=0;
	/*$sql="SELECT Waybills_goods.*,Waybills.vendor_id,Message57.ItemID,Message57.Name FROM Waybills_goods
		INNER JOIN Message57 ON (Message57.Message_ID=Waybills_goods.item_id)
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
		WHERE Waybills.payment_id=3 AND Waybills.status_id=2 AND Waybills.type_id=2 {$tmp}
		ORDER BY ItemID ASC,Waybills.created ASC";*/
	$sql="SELECT Waybills_selling.*,Waybills_goods.originalprice,Waybills_goods.qty as wbqty,Waybills_goods.sales,Waybills_goods.paid,Waybills.vendor_id,Message57.ItemID,Message57.Name FROM Waybills_selling 
		INNER JOIN Waybills ON (Waybills_selling.waybill_id=Waybills.id)
		INNER JOIN Message57 ON (Message57.Message_ID=Waybills_selling.item_id)
		INNER JOIN Waybills_goods ON (Waybills_selling.waybill_id=Waybills_goods.waybill_id AND Waybills_selling.item_id = Waybills_goods.item_id)
		WHERE Waybills_selling.payment_id=0 AND Waybills.created>'".date("Y-m-d H:i:s", strtotime($startdate))."' {$tmp}";
	//echo $sql."<br>";
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>Арт.</td>
			<td>Название</td>
			<td>Поставщик</td>
			<td>Накладная</td>
			<td>Кол. по накладной</td>
			<td>Продано</td>
			<td>Оплачено</td>
			<td>К оплате</td>
			<td>Цена закуп.</td>
			<td>Сумма</td>
			<td># заказа</td>
			<td># розн. продажи</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row['sales']-$row['paid']) {
				$html.="<tr>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
				<td>{$row['Name']}</td>
				<td>".getSupplier($row['vendor_id'])."</td>
				<td><a href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>{$row['waybill_id']}</a></td>
				<td>{$row['wbqty']}</td>
				<td>{$row['sales']}</td>
				<td>{$row['paid']}</td>
				<td style='text-align:center;'><b>".$row['qty']."</b></td>
				<td style='text-align:right;'>{$row['originalprice']}</td>
				<td style='text-align:right;'>".$row['originalprice']*$row['qty']."</td>
				<td>{$row['order_id']}</td>
				<td>{$row['retail_id']}</td>
			</tr>";
			$itog=$itog+$row['originalprice']*$row['qty'];
			$itogn=$itogn+$row['qty'];
			}
		}
	}
	$html.="<tr><td colspan='7' style='text-align:right;font-weight:bold;'>Итого:</td>
		<td style='text-align:center;'><b>{$itogn}</b></td><td>&nbsp;</td>
		<td><b>{$itog}</b></td></tr>";
	$html.="</table><br>";
	$html.="<form action='".$_SERVER['SCRIPT_NAME']."' method='post' id='frm2'>
		<textarea cols='70' rows='5' name='comment' id='comment'></textarea>
		<br><br>
		<input type='hidden' name='action' id='action' value='save'>
		<input type='hidden' name='supplier' value='".(((isset($incoming['supplier']))&&($incoming['supplier']!="")) ? $incoming["supplier"] : "" )."'>
		<input type='submit' value='Сохранить оплату'>
		</form>";
	return $html;
}

function saveNewPayments($incoming) {
	$tmp="";
	//print_r($incoming);
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
		$supplier=intval($incoming['supplier']);
	} else {
		return "<p style='font-weight:bold;color:#f30000;'>Необходимо выбрать поставщика!</p>";
	}
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	$dte=date("Y-m-d H:i:s");
	$summa=0;
	$qty=0;
	$summa=getNewPaymentsSumma($incoming);
	$qty=getNewPaymentsQty($incoming);
	$comment=quot_smart($incoming['comment']);
	$sql="INSERT INTO Waybills_transfer (supplier_id,summa,qty,created,comment) VALUES
				({$supplier},{$summa},{$qty},'{$dte}','{$comment}')";
	if (!mysql_query($sql)) {
		die($sql." Error: ".mysql_error());
	}
	$sql="SELECT id FROM Waybills_transfer ORDER BY id DESC LIMIT 1";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$transfer_id=$row['id'];
		}
	}
	$sql="SELECT Waybills_selling.*,Waybills_goods.originalprice FROM Waybills_selling 
		INNER JOIN Waybills ON (Waybills_selling.waybill_id=Waybills.id)
		INNER JOIN Waybills_goods ON (Waybills_selling.waybill_id=Waybills_goods.waybill_id AND Waybills_selling.item_id = Waybills_goods.item_id)
		WHERE Waybills_selling.payment_id=0 AND Waybills.created>'".date("Y-m-d H:i:s", strtotime($startdate))."' {$tmp}";
	//echo $sql."<br>";
	$j=0;
	
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			//echo $j."<br>";
			$sql="INSERT INTO Waybills_paid (transfer_id,waybill_id,item_id,qty,created,price) VALUES
				({$transfer_id},{$row['waybill_id']},{$row['item_id']},{$row['qty']},'{$dte}',{$row['originalprice']})";
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
			//echo $sql."<br>";
			$sql="UPDATE Waybills_goods SET paid=paid+".$row['qty']." WHERE waybill_id={$row['waybill_id']} AND item_id={$row['item_id']}";	
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
			//echo $sql."<br>";
			$sql="SELECT id FROM Waybills_paid ORDER BY id DESC LIMIT 1";
			if ($result1=mysql_query($sql)) {
				while ($row1=mysql_fetch_array($result1)) {
					$last=$row1['id'];
				}
			}
			$sql="UPDATE Waybills_selling SET payment_id=".$last." WHERE id={$row['id']}";	
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
			//echo $sql."<br><br>";
			$j=$j+1;
		}
	}

	return $html;
}
// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$incoming = parse_incoming();
	//print_r($incoming);
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$show=1;
	$preh1="";
	//print_r($incoming);
	switch ($incoming['action']) {
		case "save":
			$preh1="Сохранение";
			$html.=saveNewPayments($incoming);
			$html.="<p style='font-weight:bold;color:#f30000;'>Оплаты сохранены</p>";
			break;
		default:
			$preh1="Предварительный просмотр";
			$html.=getNewPayments($incoming);
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
	<script type="text/javascript">
	function chkAction() {
		//alert("chk");
		//document.getElementById("action").value="";
		//document.getElementById("frm1").submit();
		return true;
	}
	</script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	echo printSellingMenu();
	if ($show) {
?>
	<h1>Неоплаченные позиции. <?php echo $preh1; ?></h1>
	<p>Поставщик: <b><?php echo getSupplier($incoming['supplier']); ?></b></p>
	<br clear='both'>
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

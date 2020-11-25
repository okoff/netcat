<?php
// 21.05.2014 Elen
// накладные под реализацию. просмотр оплат, сохранение оплат
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();

function getNewPayments($incoming) {
	$tmp="";
	//print_r($incoming);
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" WHERE supplier_id=".intval($incoming['supplier']);
	}
	$itog=0;
	/*$sql="SELECT Waybills_selling.*,Waybills_goods.originalprice,Waybills.vendor_id,Message57.ItemID,Message57.Name FROM Waybills_selling 
		INNER JOIN Waybills ON (Waybills_selling.waybill_id=Waybills.id)
		INNER JOIN Message57 ON (Message57.Message_ID=Waybills_selling.item_id)
		INNER JOIN Waybills_goods ON (Waybills_selling.waybill_id=Waybills_goods.waybill_id AND Waybills_selling.item_id = Waybills_goods.item_id)
		WHERE NOT Waybills_selling.payment_id=0 {$tmp} ORDER BY ItemID ASC ";*/
	$sql="SELECT * FROM Waybills_transfer {$tmp} ORDER BY id DESC";
	//echo "<br>".$sql;
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>#</td>
			<td>Кол.</td>
			<td>Сумма</td>
			<td>Дата создания</td>
			<td>Оплачено</td>
			<td>Дата оплаты</td>
			<td>Комментарий</td>
			<td>Просмотр</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
				<td>{$row['id']}</td>
				<td>{$row['qty']}</td>
				<td>{$row['summa']}</td>
				<td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td>".(($row['paid']==1) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
				<td>".(($row['paiddate']=="0000-00-00 00:00:00") ? "&nbsp;" : date("d.m.Y", strtotime($row['paiddate'])))."</td>
				<td>{$row['comment']}<br>
				<a style='font-size:10px;' href='/netcat/modules/netshop/interface/selling-transfer.php?tid={$row['id']}&supplier={$incoming['supplier']}&action=addcom'>Добавить</a></td>
				<td style='text-align:center;'><a href='/netcat/modules/netshop/interface/selling-transfer.php?n={$row['id']}'>&gt;&gt;&gt;</a></td>
			</tr>";
			$itog=$itog+$row['summa'];
			
		}
	}
	$html.="<tr><td colspan='2' style='text-align:right;font-weight:bold;'>Итого:</td><td><b>{$itog}</b></td>
		<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	$html.="</table>";
	return $html;
}

function printFrmAddComment($tid,$sup_id) {
	$html="";
	$html.="
	<form name='frm1' id='frm1' action='/netcat/modules/netshop/interface/selling-transfer.php'>
	<input type='hidden' name='tid' value='{$tid}'>
	<input type='hidden' name='action' value='savecom'>
	<input type='hidden' name='supplier' value='{$sup_id}'>
	<table cellpadding='2' cellspacing='0' border='1'>";
	
	$sql="SELECT * FROM Waybills_transfer WHERE id=".intval($tid);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr><td>Комментарий</td>
				<td><textarea name='comment' cols='100' rows='3'>{$row['comment']}</textarea></td></tr>";
			$html.="<tr><td>Оплата</td>
				<td><input type='checkbox' name='paid' value='1' ".(($row['paid']==1) ? "checked" : "")."></td></tr>";
			$html.="<tr><td>Дата оплаты</td>
				<td><input type='text' name='paiddate' value='".(($row['paiddate']!="0000-00-00 00:00:00") ? date("d.m.Y H:i:s",strtotime($row['paiddate'])) : "")."' class='datepickerTimeField'>
			</td></tr>";
		}
	}
	$html.="</table>
	<br>
	<input type='submit' value='Сохранить'>
	</form>";
	
	return $html;
}

function showPayments($n,$print) {
	$html="";
	$supplier="";
	$sql="SELECT * FROM Waybills_transfer WHERE id={$n}";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$supplier=getSupplier($row['supplier_id']);
			$html.="<h3>Поставщик: <b>".$supplier."</b><br>
			Товары в оплате #{$n} от ";
			$html.=date("d.m.Y",strtotime($row['created']))."</h3>";
			if ($print!="on") {
				$html.="<h3><a target='_blank' href='/netcat/modules/netshop/interface/selling-transfer.php?n={$n}&print=on'>Версия для печати</a></h3>";
			}
		}
	}
	$html.="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT Waybills_paid . * , Message57.Name, Message57.ItemID, Waybills.created AS wbcreated
		FROM Waybills_paid
		INNER JOIN Message57 ON ( Message57.Message_ID = Waybills_paid.item_id )
		INNER JOIN Waybills ON ( Waybills.id = Waybills_paid.waybill_id )
		WHERE transfer_id ={$n}
		ORDER BY Waybills_paid.waybill_id ASC , item_id ASC";
	//echo $sql."<br>";
	$html.="<tr>
		<td style='font-weight:bold;'>Поставщик</td>
		<td style='font-weight:bold;'>№ накладной</td>
		<td style='font-weight:bold;'>Дата накладной</td>
		<td style='font-weight:bold;'>Артикул</td>
		<td style='font-weight:bold;'>Название</td>
		<td style='font-weight:bold;'>Кол.</td>
		<td style='font-weight:bold;'>Цена</td>
		<td style='font-weight:bold;'>Сумма</td>
		</tr>";
	$qty=0;
	$summa=0;
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
				<td>{$supplier}</td>
				<td>{$row['waybill_id']}</td>
				<td>".date("d.m.Y",strtotime($row['wbcreated']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
				<td>{$row['Name']}</td>
				<td>{$row['qty']}</td>
				<td>{$row['price']}</td>
				<td>".($row['price']*$row['qty'])."</td>
			</tr>";
			$qty=$qty+$row['qty'];
			$summa=$summa+$row['qty']*$row['price'];
		}
	}
	$html.="<tr>
		<td style='font-weight:bold;text-align:right;' colspan='5'>Итого:</td>
		<td style='font-weight:bold;'>{$qty}</td>
		<td>&nbsp;</td>
		<td style='font-weight:bold;'>{$summa}</td>
		</tr>";
	$html.="</table>";
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
	
	if (isset($incoming['action'])) {
		switch ($incoming['action']) {
			case "addcom":
				$html.=printFrmAddComment($incoming['tid'],$incoming['supplier']);
				break;
			case "savecom":
				//print_r($incoming);
				$sql="UPDATE Waybills_transfer SET comment='".$incoming['comment']."',
					paid=".(((isset($incoming['paid']))&&($incoming['paid']==1)) ? 1 : 0).",
					paiddate='".(($incoming['paiddate']=="") ? "0000-00-00 00:00:00" : date("Y-m-d H:i:s",strtotime($incoming['paiddate'])))."'
				WHERE id=".intval($incoming['tid']);
				//echo $sql."<br>";
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p>Комментарий добавлен.</p>
				<p><a href='/netcat/modules/netshop/interface/selling-transfer.php?supplier={$incoming['supplier']}'>Перейти на страницу оплат.</a></p>";
				break;
			default: break;
		}
	} else {
		if ((isset($incoming['n']))&&(is_numeric($incoming['n']))) {
			$tmp=((isset($incoming['print']))&&($incoming['print']=="on")) ? "on" : "";
			$html.=showPayments($incoming['n'],$tmp);
			
		} else {
			$html.=getNewPayments($incoming);
		}
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
	
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	if ((!(isset($incoming['print'])))||($incoming['print']!="on")) {
		echo printMenu();
		echo printSellingMenu();
?>
	<h1>Списания по накладным. Оплаты.</h1>
	
	<?php 
		
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
<link type="text/css" href="/css/latest.css" rel="Stylesheet" />
<script type="text/javascript" src="/js/ui.datepicker.js"></script>
<script>
$(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 0// отключаем эффект появления
});
</script>
</body>
</html>
<?php

mysql_close($con);
?>

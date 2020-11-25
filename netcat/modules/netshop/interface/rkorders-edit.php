<?php
// 22.04.2016 Elen
// Заказы поставщикам
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-waybill.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pricelist/';

function printRKOrderFrm1($n,$incoming) {
	$html="";
	//echo $n."<br>";
	$itemscount=array();
	if ($n!=0) {
		$sql="SELECT * FROM RKOrders WHERE id=".intval($n);
		if ($result=mysql_query($sql)) {
			if ($row = mysql_fetch_array($result)) {
				$incoming['supplier']=$row['supplier_id'];
			}
		}
		$sql="SELECT * FROM RKOrders_Goods WHERE rkorder_id=".intval($n)."
			ORDER BY id ASC";
		if ($result=mysql_query($sql)) {
			while ($row = mysql_fetch_array($result)) {
				$itemscount[$row['item_id']]=$row['qty'];
			}
		}
	}
	$html.="<form name='frm1' id='frm1' method='post'>
	<input type='hidden' name='action' id='action' value=''>
	<input type='hidden' name='n' id='n' value='{$n}'>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>Поставщик:</td><td>".selectSupplier($incoming)."</td>
		<td><input type='button' value='выбрать' onclick='selectSupplier();'></td></tr>
	<tr><td>Сталь:</td><td>".selectSteel($incoming)."</td>
		<td><input type='button' value='выбрать' onclick='selectSteel();'></td></tr>
	</table><br>";
	
	if ((isset($incoming['supplier']))&&($incoming['supplier']!=0)) {
		$sql="SELECT Message57.*,Message57_p.Price AS originalprice FROM Message57 
			RIGHT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
			WHERE supplier=".$incoming['supplier']." 
			".(((isset($incoming['steel']))&&($incoming['steel']!="")) ? " AND steel=".$incoming['steel'] : "") ."
			AND checked=1 
			ORDER BY ItemID ASC";
		//echo $sql."<br>";
		if ($result=mysql_query($sql)) {
			$html.="<table cellpadding='2' cellspacing='0' border='1'>
			<tr>
				<td rowspan='2'><b>Артикул<br>поставщика</b></td>
				<td rowspan='2'><b>Артикул</b></td>
				<td rowspan='2'><b>Название</b></td>
				<td rowspan='2'><b>На складе</b></td>
				<td rowspan='2' style='width:80px;'><b>Цена</b></td>
				<td rowspan='2' style='width:80px;'><b>Цена закуп.</b></td>
				<td colspan='2'><b>Заказать</b></td>
			</tr>
			<tr>
				<td><input type='checkbox' name='sall1' id='sall1' value='1' onclick='selectAll(1)'><b>1</b></td>
				<td><input type='checkbox' name='sall2' id='sall2' value='2' onclick='selectAll(2)'><b>2</b></td>	
			</tr>";
			$j=0;
			$itog=$itogz=0;
			while ($row = mysql_fetch_array($result)) {
				$html.="
				<tr>
				<td><b>{$row['vendor_itemid']}</b></td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['Message_ID']}'>{$row['ItemID']}</a></b></td>
				<td>{$row['Name']}</td>
				<td>{$row['StockUnits']}</td>
				<td style='text-align:right;'>".number_format($row['Price'], 2, ',', ' ')."</td>
				<td style='text-align:right;'>".number_format($row['originalprice'], 2, ',', ' ')."</td>
				<td style='text-align:center;' colspan='2'><input type='text' id='itemscount[{$j}]' name='itemscount[{$j}]' value='".((isset($itemscount[$row['Message_ID']])) ? $itemscount[$row['Message_ID']] : "0" )."' size='3' style='width:50px;text-align:right;'>
				<input type='hidden' id='itemsid[{$j}]' name='itemsid[{$j}]' value='{$row['Message_ID']}'></td>
				</tr>";
				$j=$j+1;
				$itog=$itog+$row['Price']*((isset($itemscount[$row['Message_ID']])) ? $itemscount[$row['Message_ID']] : "0" );
				$itogz=$itogz+$row['originalprice']*((isset($itemscount[$row['Message_ID']])) ? $itemscount[$row['Message_ID']] : "0" );
			}
			$html.="<tr>
				<td colspan='4' style='text-align:right;'>Итого:</td>
				<td style='text-align:right;'>".number_format($itog, 2, ',', ' ')."</td>
				<td style='text-align:right;'>".number_format($itogz, 2, ',', ' ')."</td>
				<td colspan='2'>&nbsp;</td>
			</tr>";
			$html.="</table>";
		} else {
			die(mysql_error());
		}
	
		$html.="<br><br><input type='button' value='Сохранить заказ' onclick='save();'>
		<p><a href='/netcat/modules/netshop/interface/rkorders.php'>Вернуться в список заказов</a></p>";
	}
	$html.="</form>";
	return $html;
}
function saveRKOrder($incoming) {
	$html="";
	$strorder="";
	$order_id=0;
	if ((isset($incoming['n']))&&($incoming['n']>0)) {
		$order_id=intval($incoming['n']);
	}
	$items=array();
	$items=$incoming['items'];
	$itemscount=array();
	$itemscount=$incoming['itemscount'];
	$itemsid=array();
	$itemsid=$incoming['itemsid'];
	//echo "<br>";
	//print_r($incoming['items']);
	// save order in tbl.RKOrders
	if ((isset($incoming['supplier']))&&($incoming['supplier']>0)) {
		if ($order_id>0) {
			// update
			$sql="UPDATE RKOrders SET supplier_id={$incoming['supplier']} WHERE id={$order_id}";
			if (!mysql_query($sql)) {
				die(mysql_error());
			}
			// delete old items
			$sql="DELETE FROM RKOrders_Goods WHERE rkorder_id={$order_id}";
			if (!mysql_query($sql)) {
				die(mysql_error());
			}
		} else {
			//insert
			$sql="INSERT INTO RKOrders (created,supplier_id,status) 
				VALUES ('".date("Y-m-d H:i:s")."',{$incoming['supplier']},1)";
			//echo $sql;
			if (!mysql_query($sql)) {
				die(mysql_error());
			}
			$order_id=getLastInsertID("RKOrders");
		}
		//echo $order_id."<br>";
		for($i=0;$i<count($itemscount);$i++) {
			//echo $i.":".$items[$i].":".$itemscount[$i]."<br>";//$incoming['itemscount['.$i.']']."<br>";
			if ($itemscount[$i]>0) {
				// get prices
				$sql="SELECT Message57.Price,Message57_p.Price AS originalprice FROM Message57 
					RIGHT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
					WHERE Message_ID={$itemsid[$i]}";
				$itemprice=$originalprice=0;
				if ($result=mysql_query($sql)) {
					while ($row = mysql_fetch_array($result)) {
						$itemprice=$row['Price'];
						$originalprice=$row['originalprice'];
					}
				}
				$sql="INSERT INTO RKOrders_Goods (rkorder_id,item_id,qty,itemprice,originalprice)
				VALUES ({$order_id},{$itemsid[$i]},{$itemscount[$i]},{$itemprice},{$originalprice})";
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
			}
		}
		$html.="<p>Заказ сохранен.</p>
		<p><a href='/netcat/modules/netshop/interface/rkorders.php'>Вернуться в список заказов</a></p>";
	} else {
		$html.="<p style='color:#f30000;'>Ошибка! Не выбран поставщик!</p>";
	}
	
	
	return $html;
}


// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
//print_r($incoming);
//echo "<br>";
$printfrm=1;
$html="";
$n=0;
if ((isset($incoming['n']))&&($incoming['n']!=0)) {
	$n=intval($incoming['n']);
}
switch($incoming['action']) {
	case "save":
		$html.=saveRKOrder($incoming);
		break;
	case "selsupp":case "selsteel":
		$html.=printRKOrderFrm1($n,$incoming);
		break;
	default:
		$html.=printRKOrderFrm1($n,$incoming);
		break;
}


//echo $html;
// ------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
	<title>Заказ поставщику</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script type="text/javascript">
	function selectAll(count) {
		var i=0;
		while (document.getElementById('itemsid['+i+']')) {
			document.getElementById('itemscount['+i+']').value=count;
			console.log(i);
			i=i+1;
		}
		if (count==2) {
			document.getElementById('sall1').checked=false;
		}
		if (count==1) {
			document.getElementById('sall2').checked=false;
		}
	}
	function selectSupplier() {
		document.getElementById('action').value='selsupp';
		document.getElementById('frm1').submit();
	}
	function selectSteel() {
		document.getElementById('action').value='selsteel';
		document.getElementById('frm1').submit();
	}
	function save() {
		document.getElementById('action').value='save';
		document.getElementById('frm1').submit();
	}
	</script>
</head>
<body>
	
<?php
	echo printMenu();
	$err="";
?>
<h1>Заказ поставщику</h1>
<?php 

echo $html;
?>
<br>
</body>
</html>
<?php

mysql_close($con);
?>

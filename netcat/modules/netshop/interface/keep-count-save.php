<?php
// 17.01.2014 Elen
// создание накладной 
include_once ("../../../../vars.inc.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------
function getCartCost($str,$id) {
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[4]*$itm[2];
				}
			}
		}
	}
	//echo $total;
	return $total;
}
function getCartCostOriginal($str,$id) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[3]*$itm[2];
				}
			}
		}
	}
	return $total;
}
function getCartQty($str,$id) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[3]*$itm[2];
				}
			}
		}
	}
	return $total;
}

// save ONLY PRICES items count in DB
function saveItemsPrice($str) {
	$html="";
	$tmp=explode(";",$str);
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			// $itm[0] - item id 
			// $itm[1] - qty in retail order
			// $itm[2] - приходная цена
			// $itm[3] - розничная цена
			$sql="UPDATE Message57 SET Price={$itm[3]} WHERE Message_ID={$itm[0]}";
			//echo $sql;
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
			$iprice1=getPrice1($itm[0]);
			if ($iprice1==0) {
				// новый товар, добавить цену
				$sql="INSERT INTO Message57_p (Message_ID,Price,created) VALUES ({$itm[0]},{$itm[2]},'".date("Y-m-d H:i:s")."')";
			} else {
				// обновить цену
				$sql="UPDATE Message57_p SET Price={$itm[2]}, created='".date("Y-m-d H:i:s")."' WHERE Message_ID={$itm[0]}";
			}
			//echo $sql;
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
		}
	}
	$_SESSION['items']="";
	$html.="<p><b>Цены обновлены</b></p>";
	return $html;
}
// ------------------------------------------------------------------------------------------------

include_once ("utils.php");
include_once ("utils-waybill.php");

$incoming = parse_incoming();
//print_r($incoming);
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

// создаем накаладную
$dte=date("Y-m-d H:i:s");
$strlist="";
$sql="INSERT INTO Waybills (created, name, type_id, status_id, vendor_id, vendor_date, payment_id, moneytype_id, paid, summoriginal,summ, comments,onsite,title,intro) 
					VALUES ('".$dte."','',11,1,10,'0000-00-00 00:00:00',1,1,0,0,0,'',0,'','')";
if (!mysql_query($sql)) {	
	die($sql."Ошибка: ".mysql_error());
}
$wbid=getLastInsertID("Waybills");
$sql="SELECT Message57.Message_ID,Message57.StockUnits AS qty,Message57.Price AS itemprice,Message57_p.Price AS originalprice FROM Message57 
			RIGHT JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID) 
			ORDER BY Message_ID ASC";
if ($res=mysql_query($sql)) {
	while ($row=mysql_fetch_array($res)) {
		if ($row['Message_ID']) {
			$sql="INSERT INTO Waybills_goods (waybill_id,item_id,qty,originalprice,itemprice)
			VALUES ({$wbid},{$row['Message_ID']},
				".(($row['qty']) ? $row['qty'] : 0).",{$row['originalprice']},{$row['itemprice']})";
			
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
			$strlist.="{$wbid}:{$row['Message_ID']}:".(($row['qty']) ? $row['qty'] : 0).":{$row['originalprice']}:{$row['itemprice']};";
		}
	}
}
$summ1=getCartCost($strlist,$wbid);
$summoriginal1=getCartCostOriginal($strlist,$wbid);
$qty=getCartQty($strlist,$wbid);
$sql="UPDATE Waybills SET 
	summoriginal={$summoriginal1},
	summ={$summ1}
	WHERE id=".$wbid;
if (!mysql_query($sql)) {	
	die($sql."Ошибка: ".mysql_error());
}
$html.="<h2 style='color:#00f300;'>Остатки сохранены!</h2>";
switch ($incoming['action']) {
	default: 
		$html.="<h1>Накладные остатков</h1>";
		$html.=getWaybillListHold(array('wbtype'=>11,'max'=>date("Y-m-d")));
		
		//(isset($incoming['wbtype'])) ?  "" : $incoming['wbtype']=1;
		//if (isset($_SESSION['items'])) {
		//	$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['wbtype']);
		//}
		break;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Накладные учёта.</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript">
		function showAnonim() {
			document.getElementById("addclient").style.display="none";
			document.getElementById("findclient").style.display="none";
			document.getElementById('action').value='cli-anonim';
			document.getElementById("frm").submit();
		}
		function showAddNewClient() {
			document.getElementById("addclient").style.display="block";			
			document.getElementById("findclient").style.display="none";
		}
		function submitAddClient() {
			document.getElementById('action').value='cli-add';
			document.getElementById("frm").submit();
		}
		function showFindClient() {
			document.getElementById("findclient").style.display="block";
			document.getElementById("addclient").style.display="none";
		}
		function submitFindClient() {
			document.getElementById('action').value='cli-search';
			document.getElementById("frm").submit();
		}
		function addToOrder(mesid, price1, price) {
			//alert(mesid);
			document.getElementById('action').value='item-add';			
			document.getElementById('scharticul').value=mesid;
			document.getElementById('schprice').value=price;
			document.getElementById('schitemprice').value=price1;
			document.getElementById("frm").submit();
		}
		function delItem(itemid) {
			document.getElementById('qty_'+itemid).value=0;
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function recalcCart() {
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function rewritePrices() {
			document.getElementById('action').value='cart-rewrite';
			document.getElementById('frm').submit();
		}
		function saveCart() {
			document.getElementById('action').value='cart-save';
			document.getElementById('frm').submit();
		}
		function searchItem() {
			document.getElementById('action').value='item-search';
			document.getElementById('frm').submit();
		}
		function addNewItem() {
			if (document.getElementById('addcategory').selectedIndex==0) {
				document.getElementById('err-addcategory').style.display="block";
				return false;
			} else {
				document.getElementById('err-addcategory').style.display="none";
			}
			document.getElementById('action').value='item-addnew';
			document.getElementById('frm').submit();
		}
		function changewbtype() {
		}
		function setPaidDate() {
			var tmp=new Date();
			document.getElementById('paid_date').value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
	</script>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	#addclient, #findclient {
		padding-left:50px;
	}
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	//print_r($incoming);
	echo printMenu();
	//$title=($incoming['id']) ? "Накладная ".$incoming['id'] : "Список накладных &quot;учёт&quot;";
	
	if ($showall) {
?>
<h1><?php echo $title; ?></h1>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/keep-count-waybills.php" method="post">
<input type="hidden" id="id" name="id" value="<?php echo (isset($incoming['id']) ? $incoming['id'] : "");?>">
<input type="hidden" id="action" name="action" value="">
<?php
		}
	echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</form>
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

<?php
// 17.01.2014 Elen
// создание накладной 
include_once ("../../../../vars.inc.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------


// get discount for an item
// @id = Message_ID in Message57

function printCrtPrint($str, $wbstatus=1) {
	$html="";
	$tmp=array();
	$tmp=explode(";",$str);
//	echo "<br><br>";
//	print_r($tmp);
//	echo "<br><br>";
	$total=$total1=$totalqty=0;
	$j=$m=0;
	$k=1;
	$_SESSION['items']="";
	$html.="
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td><b>#</b></td>
		<td><b>Артикул</b></td>
		<td><b>Название</b></td>
		<td><b>Кол.</b></td>
		<td><b>Цена<br>отпускная</b></td>
		<td><b>Цена<br>розничная</b></td>
		<td><b>% наценки</b></td>
		<td><b>Сумма<br>отпускная</b></td>
		<td><b>Сумма<br>розничная</b></td>
	</tr>";
	$html1="";
	$dsbl=($wbstatus==2) ? "disabled" : "";
	$m=count($tmp)-1;
	foreach($tmp as $t) {
	//while ($m>=0) {
		//$t=$tmp[$m];
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[1]!=0) {
				$result = mysql_query("SELECT * FROM Message57 WHERE Message_ID=".$itm[0]);
				while($row = mysql_fetch_array($result)) {
					$discount=getDiscount($row['Message_ID'], $row['Price']);
					$iprice1=($itm[2]) ? $itm[2] : getPrice1($row['Message_ID']); // отпускная цена
					$iprice=($itm[3]) ? $itm[3] : $row['Price']; // розничная цена
					$html1.="<tr ".(($row['Checked']==0) ? "style='background:#b0b0b0;'" : "").">
					<td><b>".($k)."</b></td>
					<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>".$row['ItemID']."</a>
					<input type='hidden' name='iid[{$j}]' id='iid[{$j}]' value='{$row['Message_ID']}'>
					</td>";
					//$image_arr = explode(":", $row['Image']);
					//$image_url = "/netcat_files/".$image_arr[3];
					//$html.="<td><img src='".$image_url."' width='300'></td>";
					$html1.="<td>{$row['Name']}</td>";
					//$html.="<td style='text-align:right;'>{$row['Price']}</td>";
					$html1.="<td>{$itm[1]}</td>";
					$html1.="<td>{$iprice1}</td>";
					$html1.="<td>{$iprice}</td>";
					$html1.="<td align='right'>".number_format(($iprice*100/$iprice1),2,'.',' ')."</td>";
					$html1.="<td style='text-align:right;width:70px;'>".($iprice1*$itm[1])."</td>";
					$html1.="<td style='text-align:right;width:70px;'>".($iprice*$itm[1])."</td>";
					$total=$total+$iprice*$itm[1];
					$total1=$total1+$iprice1*$itm[1];
					$totalqty=$totalqty+$itm[1];
					//$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
					$html1.="</tr>";
					
					$_SESSION["items"].=$row['Message_ID'].":".$itm[1].":".$iprice1.":".$iprice.";";
					$j=$j+1;
				}
				
			}
			$k=$k+1;
		}
		$m=$m-1;
	}
	//print_r($_SESSION);
	$html.=$html1;
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='3' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td></tr>";
	//$html.="<tr>
	//<td colspan='11' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать' {$dsbl}>
	//<input type='button'  onclick='saveCart();' value='Сохранить' {$dsbl}>
	//</td></tr>";
	$html.="</table>";
	return $html;
}
function getCartCost($str) {
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[1]!=0) {
				$total=$total+$itm[3]*$itm[1];
			}
		}
	}
	return $total;
}
function getCartCostOriginal($str) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[1]!=0) {
				$total=$total+$itm[2]*$itm[1];
			}
		}
	}
	return $total;
}
function getCartQty($str) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[1]!=0) {
				$total=$total+$itm[2]*$itm[1];
			}
		}
	}
	return $total;
}

// ------------------------------------------------------------------------------------------------

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

$_SESSION['items']="";
$_SESSION['wbid']="";
if ((isset($incoming['wb'])) && ($incoming['wb'])) {
	$_SESSION['wbid']=$incoming['wb'];
	$_SESSION['items']="";
	$sql="SELECT * FROM Waybills WHERE id=".$_SESSION['wbid'];
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$incoming['name']=$row['name'];
		$incoming['vendor_date']=(($row['vendor_date'])&&(date("d.m.Y", strtotime($row['vendor_date']))!="01.01.1970")) ? date("d.m.Y", strtotime($row['vendor_date'])) : "" ;
		$incoming['comments']=$row['comments'];
		$incoming['wbtype']=$row['type_id'];
		$incoming['wbstatus']=$row['status_id'];
		$incoming['wbpayment']=$row['payment_id'];
		$incoming['wbpaymenttype']=$row['moneytype_id'];
		$incoming['supplier']=$row['vendor_id'];
		$incoming['summ']=$row['summ'];
		$incoming['cash']=($row['cash']==1) ? "1" : "0";
		$incoming['paid']=($row['paid']==1) ? "1" : "0";
		$incoming['closed']=($row['closed']==1) ? "1" : "0";
	}
	$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".$_SESSION['wbid']." ORDER BY id  DESC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$_SESSION['items'].=$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
	}
}
(isset($incoming['wbstatus'])) ?  "" : $incoming['wbstatus']=1;
if (isset($_SESSION['items'])) {
	$cart=printCrtPrint($_SESSION['items'], $incoming['wbstatus']);
}

//echo $_SESSION['wbid'];
?>
<!DOCTYPE html>
<html>
<head>
	<title>Накладная <?php echo $_SESSION['wbid'];?>.</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
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
	
	$title=($_SESSION['wbid']) ? "Накладная ".$_SESSION['wbid'] : "";
?>
<h1><?php echo $title; ?></h1>
<table cellpadding='2' cellspacing='0' border='1'>
<tr><td style='text-align:right;'>Номер накладной</td><td><?php echo $incoming['name']; ?></td>
	<td style='text-align:right;'>Дата поставщика накладной</td><td><?php echo ($incoming['vendor_date']) ? $incoming['vendor_date'] : "&nbsp;" ; ?></td>
	<td colspan='2'><input type='checkbox' name='paid' id='paid' value='1' <?php echo ($incoming['paid']) ? "checked" : "";?>>Оплачено</td></tr>
<tr>
	<td style='text-align:right;'>Статус</td><td><?php echo selectWaybillStatus($incoming); ?></td>
	<td style='text-align:right;'>Поставщик</td><td><?php echo selectSupplier($incoming); ?></td>
	<td style='text-align:right;'>Тип накладной</td><td><?php echo selectWaybillType($incoming); ?></tr>
<tr></tr>
<tr><td style='text-align:right;'>Тип оплаты</td><td><?php echo selectWaybillPayment($incoming); ?></td>
	<td style='text-align:right;'>Способ оплаты</td><td><?php echo selectWaybillPaymentType($incoming); ?></td>
	<td colspan='2'><?php echo $incoming['comments'];?>&nbsp;</td></tr>

</table>
<br class="clear">
<h3>Состав накладной</h3>
<?php echo $cart; ?>


<?php
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

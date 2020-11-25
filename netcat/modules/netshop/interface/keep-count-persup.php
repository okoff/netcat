<?php
// 26.01.2015 Elen
// разделение накладной учета по поставщикам
//http://de.russian-knife.ru/netcat/modules/netshop/interface/keep-count-persup.php?action=supplier&id=586&sup=5 кустари
//http://de.russian-knife.ru/netcat/modules/netshop/interface/keep-count-persup.php?action=supplier&id=586&sup=18 жбанов
	
include_once ("../../../../vars.inc.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------


// get discount for an item
// @id = Message_ID in Message57

function printCrtHold($str,$id,$wbtype=1) {
	$html=$wbtype=$wbtypename="";
	$tmp=array();
	$tmp=explode(";",$str);
	//echo $wbtype."<br><br>";
	//print_r($tmp);
	//echo $str."<br><br>";
	$total=$total1=$totalqty=0;
	$j=$m=0;
	$k=1;
	$_SESSION['items']="";
	
	$sql="SELECT Waybills.*,Waybills_type.name AS TypeName FROM Waybills 
				INNER JOIN Waybills_type ON (Waybills.type_id=Waybills_type.id) 
				WHERE Waybills.id=".$id;
		//echo $sql;
	if ($res=mysql_query($sql)) {
		if ($row=mysql_fetch_array($res)) {
			$wbtype=$row['type_id'];
			$wbtypename=$row['TypeName'];
		}
	}
	$html.="<h1>Накладная #{$id} {$wbtypename}</h1>";
		
	$html.="
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td><b>#</b></td>
		<td><b>Артикул</b></td>
		<td><b>Название</b></td>
		<td><b>Кол.</b></td>
		<td><b>На складе</b></td>
		<td><b>Цена<br>отпускная</b></td>
		<td><b>Цена<br>розничная</b></td>
		<td><b>% наценки</b></td>
		<td><b>Сумма<br>отпускная</b></td>
		<td><b>Сумма<br>розничная</b></td>";
	if ($wbtype==2) {
		$html.="
		<td><b>Списано</b></td>
		<td><b>Оплачено</b></td>";
	} else {
		//$html.="<td><b>Удалить</b></td>";
	}
	$html.="
		
	</tr>";
	$html1="";
	$dsbl=($wbtype==2) ? "disabled" : "";
	$m=count($tmp)-1;
	foreach($tmp as $t) {
	//while ($m>=0) {
		//$t=$tmp[$m];
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[0]==$id) {
				//if ($itm[2]!=0) {
					$result = mysql_query("SELECT * FROM Message57 WHERE Message_ID=".$itm[1]);
					while($row = mysql_fetch_array($result)) {
						$discount=getDiscount($row['Message_ID'], $row['Price']);
						$iprice1=($itm[3]) ? $itm[3] : getPrice1($row['Message_ID']); // отпускная цена
						$iprice=($itm[4]) ? $itm[4] : $row['Price']; // розничная цена
						//($row['video']) ? $bk="style='background:#addfad;'" : $bk="";
						$html1.="<tr ".(($row['Checked']==0) ? "style='background:#b0b0b0;'" : "").">
						<td><b>".($m)."</b></td>
						<td {$bk}><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>".$row['ItemID']."</a>[{$row['Message_ID']}]</td>";
						//$image_arr = explode(":", $row['Image']);
						//$image_url = "/netcat_files/".$image_arr[3];
						//$html.="<td><img src='".$image_url."' width='300'></td>";
						$html1.="<td>{$row['Name']}</td>";
						//$html.="<td style='text-align:right;'>{$row['Price']}</td>";
						$html1.="<td>{$itm[2]}</td>";
						$html1.="<td style='text-align:right;'>{$row['StockUnits']}</td>";
						$html1.="<td>{$iprice1}</td>";
						$html1.="<td>{$iprice}</td>";
						$html1.="<td align='right'>".number_format(($iprice*100/$iprice1-100),2,'.',' ')."</td>";
						$html1.="<td style='text-align:right;width:70px;'>".($iprice1*$itm[2])."</td>";
						$html1.="<td style='text-align:right;width:70px;'>".($iprice*$itm[2])."</td>";
						$total=$total+$iprice*$itm[2];
						$total1=$total1+$iprice1*$itm[2];
						$totalqty=$totalqty+$itm[2];
						if ($wbtype==2) {
							$sqlq="SELECT sales,paid FROM Waybills_goods WHERE waybill_id={$id} AND item_id={$row['Message_ID']}";
							//echo $sqlq."<br>";
							if ($rq=mysql_query($sqlq)) {
								while ($rwq=mysql_fetch_array($rq)) {
									//print_r($rwq);
									//echo "<br>";
									$html1.="<td style='text-align:right;'>{$rwq['sales']}</td>";
									$html1.="<td style='text-align:right;'>{$rwq['paid']}</td>";
								}
							}
						} else {
							//$html1.="<td><a href='#' onclick='delItem({$j});'><img src='/images/icons/del.png' border='0' title='Удалить' alt='Удалить'></a></td>";		
						}
						
						
						//$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
						$html1.="</tr>";
						
						//$_SESSION["items"].=$id.":".$row['Message_ID'].":".$itm[2].":".$iprice1.":".$iprice.";";
						$j=$j+1;
					}
					
				//}
			}
			$k=$k+1;
		}
		$m=$m-1;
	}
	//print_r($_SESSION);
	/*$html.="<tr>
	<td colspan='".(($wbtype==2) ? "12" : "11")."' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать' {$dsbl}>
	</td></tr>";
	
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='3' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td><td colspan='".(($wbtype==2) ? "3" : "2")."'>&nbsp;</td></tr>";
	*/
	$html.=$html1;
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='4' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td><td  colspan='".(($wbtype==2) ? "2" : "1")."'>&nbsp;</td></tr>";
	$html.="<tr>
	<td colspan='".(($wbtype==2) ? "12" : "11")."' style='text-align:center;'>
	<input type='button' id='btnSaveCart' onclick='saveCart();' value='Провести' {$dsbl}>
	</td></tr>";
	$html.="</table>";
	return $html;
}
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

$userinfo="";
$cart="";
$search="";
$showall=1;
//echo $incoming['action'];
//print_r($incoming);

if ((isset($incoming['start'])) && ($incoming['start']==1)) {
	$_SESSION['items']="";
//	$_SESSION['wbid']="";
}

(isset($_SESSION['cid'])) ? $cid=$_SESSION['cid'] : $cid=0;
(isset($_SESSION['items'])) ? $cart=printCrtHold($_SESSION['items'],$incoming['id']) : $cart="";

switch ($incoming['action']) {
	case "hold":
		if ((isset($incoming['id'])) && ($incoming['id'])) {
			//$_SESSION['wbid']=$incoming['id'];
			$qty=array();
			$items=array();
			$str="";
			$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($incoming['id'])." ORDER BY item_id  ASC";
			//echo $sql;
			$result=mysql_query($sql);
			$n=0;
			$j=0;
			$nqty=0;
			$k=0;
			while ($row = mysql_fetch_array($result)) {
				if (($n==$row['item_id'])&&($n!=0)) {
					//echo $j."-".$row['item_id'].":".$row['qty']."-".$nqty."-".($row['qty']+$nqty)."<br>";
					$items[$j-1]=$incoming["id"].":".$row['item_id'].":".($row['qty']+$nqty).":".$row['originalprice'].":".$row['itemprice'];
				} else {
					//echo "2<br>";
					$items[$j]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'];
					$qty[$j]=$row['qty'];
					$nqty=$row['qty'];
					
				}
				//$sorting[$row['item_id']]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
				$n=$row['item_id'];
				$j=$j+1;
				$k=$k+1;
			}
			//print_r($qty);
			//print_r($items);
			for ($j=0;$j<$k;$j++) {
				if ($items[$j]) {
					$str.=$items[$j].";";
				}
			}
			//echo $str;
			$html=printCrtHold($str,$incoming['id'],$incoming['wbtype']);
			//$html=printCrtHold($newstr,$incoming['id'],$incoming['wbtype']);
		}
		break;
	case "supplier":
		if ((isset($incoming['id'])) && ($incoming['id'])) {
			$wbid=intval($incoming['id']);
		} else {
			break;
		}
		if ((isset($incoming['sup'])) && ($incoming['sup'])) {
			$sup=intval($incoming['sup']);
		} else {
			break;
		}
		//$_SESSION['wbid']=$incoming['id'];
		$qty=array();
		$items=array();
		$str="";
		$sql="SELECT Waybills_goods.*,Message57.supplier AS supplier FROM Waybills_goods 
			INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
			WHERE waybill_id=".$wbid." AND supplier=".$sup." ORDER BY item_id  ASC";
		//echo $sql."<br>";
		$result=mysql_query($sql);
		$n=0;
		$j=0;
		$nqty=0;
		$k=0;
		while ($row = mysql_fetch_array($result)) {
			if (($n==$row['item_id'])&&($n!=0)) {
				//echo $j."-".$row['item_id'].":".$row['qty']."-".$nqty."-".($row['qty']+$nqty)."<br>";
				$items[$j-1]=$incoming["id"].":".$row['item_id'].":".($row['qty']+$nqty).":".$row['originalprice'].":".$row['itemprice'];
			} else {
				//echo "2<br>";
				$items[$j]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'];
				$qty[$j]=$row['qty'];
				$nqty=$row['qty'];
				
			}
			//$sorting[$row['item_id']]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
			$n=$row['item_id'];
			$j=$j+1;
			$k=$k+1;
		}
		//print_r($qty);
		//print_r($items);
		for ($j=0;$j<$k;$j++) {
			if ($items[$j]) {
				$str.=$items[$j].";";
			}
		}
		$html=printCrtHold($str,$incoming['id'],$incoming['wbtype']);
		
		// save
		$dte=date("Y-m-d H:i:s");
		$sql="INSERT INTO Waybills (created, name, type_id, status_id, vendor_id, vendor_date, payment_id, moneytype_id, paid, summoriginal,summ, comments,onsite,title,intro) 
							VALUES ('".$dte."','',2,1,{$sup},'0000-00-00 00:00:00',3,1,0,0,0,'',0,'','')";
		if (!mysql_query($sql)) {	
			die($sql."Ошибка: ".mysql_error());
		}
		$nwbid=getLastInsertID("Waybills");
		$tmp=explode(";",$str);
		foreach($tmp as $t) {
			if ($t) {
				$tmp1=explode(":",$t);
				$sql="INSERT INTO Waybills_goods (waybill_id,item_id,qty,originalprice,itemprice)
				VALUES ({$nwbid},{$tmp1[1]},{$tmp1[2]},{$tmp1[3]},{$tmp1[4]})";
				
				if (!mysql_query($sql)) {	
					die($sql."Ошибка: ".mysql_error());
				}
			}
		}
		$summ1=getCartCost($str,$wbid);
		$summoriginal1=getCartCostOriginal($str,$wbid);
		$qty=getCartQty($str,$wbid);
		$sql="UPDATE Waybills SET 
			summoriginal={$summoriginal1},
			summ={$summ1}
			WHERE id=".$nwbid;
		if (!mysql_query($sql)) {	
			die($sql."Ошибка: ".mysql_error());
		}
		break;
	default: 
		$html="<h1>Накладные учёта</h1>";
		$html.=getWaybillListHold(array('wbtype'=>8,'max'=>date("Y-m-d")));
		
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

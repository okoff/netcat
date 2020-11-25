<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();
function printWbCartOff($id) {
	$itogn=0;
	$itog=0;
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:760px;'>
	<tr><td>Артикул</td><td>Название</td><td>Цена закуп.</td><td>Кол.</td><td>На&nbsp;складе<br>сейчас</td><td>Продано</td><td>Оплачено</td><td>Возвращено</td><td>Расход по учету</td></tr>";
	$sql="SELECT Waybills_goods.*, Message57.StockUnits,Message57.ItemID AS ItemID, Message57.Name AS Name,Waybills.created AS wb_created FROM Waybills_goods 
		INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		INNER JOIN Waybills ON (Waybills_goods.waybill_id=Waybills.id)
		WHERE waybill_id=".$id;
	//echo $sql;
	
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$return=searchInReturn($row['id'],$row['item_id'],$row['wb_created'])+searchInBrak($row['id'],$row['item_id'],$row['wb_created']);
		$rashod=searchInRashod($row['item_id']);
		if ($row['qty']>($row['sales']+$row['defect'])) {
			$res.="<tr><td width='50' style='font-size:8pt;'><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
			<td style='width:500px;font-size:8pt;'>{$row['Name']}</td>
			<td width='50' style='font-size:8pt;'>".($row['originalprice']*$row['qty'])."</td>
			<td width='50' style='font-size:8pt;text-align:center;'>{$row['qty']}</td>
			<td width='50' style='font-size:8pt;text-align:center;'>{$row['StockUnits']}</td>
			<td width='30' style='font-size:8pt;text-align:center;'><a target='_blank' href='/netcat/modules/netshop/interface/selling-preview.php?wb={$row['waybill_id']}&item={$row['item_id']}'>{$row['sales']}</a></td>
			<td width='30' style='font-size:8pt;text-align:center;'>{$row['paid']}</td>
			<td width='30' style='font-size:8pt;text-align:center;'>{$return}&nbsp;</td>
			<td width='30' style='font-size:8pt;text-align:center;'>{$rashod}&nbsp;</td>
			</tr>";
			$itogn=$itogn+($row['qty']-$row['sales']-$row['defect']);
			$itog=$itog+($row['originalprice']*($row['qty']-$row['sales']-$row['defect']));
		}
		/*$style="";
		if ($row['sales']!=0) {
			$style="background:#FFFF99;";
		//	echo $row['ItemID'].":".$row['qty']."<br>";
		}
		if ($row['paid']==$row['qty']) {
			$style="background:#FF99FF;";
		//	echo $row['ItemID'].":".$row['qty']."<br>";
		} 
	
		$return=searchInReturn($row['id'],$row['item_id'],$row['wb_created']);
		if ($return>0) {
			$style="background:#9999FF;";
		}
		$return=searchInBrak($row['id'],$row['item_id'],$row['wb_created']);
		if ($return>0) {
			$style="background:#9999FF;";
		}
		if ($row['defect']>0) {
			$return=$return+$row['defect'];
			$style="background:#9999FF;";
		}
		$rashod=searchInRashod($row['item_id']);
		if ($rashod>0) {
			$style="background:#99FFFF;";
		}
		if (!$style) {
		$res.="<tr><td width='50' style='font-size:8pt;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
			<td style='width:500px;font-size:8pt;{$style}'>{$row['Name']}</td>
			<td width='50' style='font-size:8pt;{$style}'>".($row['originalprice']*$row['qty'])."</td>
			<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['qty']}</td>
			<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['StockUnits']}</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/selling-preview.php?wb={$row['waybill_id']}&item={$row['item_id']}'>{$row['sales']}</a></td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$row['paid']}</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$return}&nbsp;</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$rashod}&nbsp;</td>
			</tr>";
			$itogn=$itogn+$row['qty'];
			$itog=$itog+($row['originalprice']*$row['qty']);
		}*/
	}
	$res.="<tr>
		<td colspan='2'><b>ИТОГО:</b></td>
		<td><b>{$itog}</b></td>
		<td style='text-align:center;'><b>{$itogn}</b></td>
		<td colspan='5'>&nbsp;</td>
	</tr>";
	$res.="</table>";
	
	$tmp=array($res,$itog,$itogn);
	return $tmp;
}
function getWaybillsRe($incoming) {
	$tmp="";
	$lastdate=array();
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
	}
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			$tmp.=" AND created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	//echo $startdate;
	$sql="SELECT * FROM Waybills 
		WHERE payment_id=3 AND status_id=2 AND type_id=2 {$tmp}
		ORDER BY id ASC";
	//echo "<br>".$sql;
	$j=0;
	
	$itog=0;
	$itogn=0;
	
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'><td>#</td><td>Дата создания</td><td>Поставщик</td><td>Товар</td></tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
		
			$sql1="SELECT created FROM Waybills_selling WHERE waybill_id={$row['id']} ORDER BY id DESC LIMIT 1";
			//echo $sql1."<br>";
			if ($result1=mysql_query($sql1)) {
				while ($row1=mysql_fetch_array($result1)) {
					$lastdate[$j]=$row1['created'];
				}
			}
			$tmp=printWbCartOff($row['id']);
			$html.="<tr>
			<td style='vertical-align:top;'><b>{$row['id']}</b></td>
			<td style='vertical-align:top;'>".date("d.m.Y",strtotime($row['created']))."</td>
			<td style='vertical-align:top;'>".getSupplier($row['vendor_id'])."</td>
			<td>".$tmp[0]."</td>
		</tr>";
			$itog=$itog+$tmp[1];
			$itogn=$itogn+$tmp[2];
			$j=$j+1;
		}
	}
	$html.="<tr>
		<td colspan='3'><b>ИТОГО:</b></td>
		<td>
			<table cellpadding='2' cellspacing='0' border='0' style='width:760px;'>
			<tr>
				<td style='width:270px;' colspan='2'>&nbsp;</td>
				<td><b>".number_format($itog,0,'.',' ')."p.</b></td>
				<td style='text-align:center;'><b>{$itogn}шт.</b></td>
				<td colspan='5' style='width:230px;'>&nbsp;</td>
			</tr>
			</table>
		</td>
	</tr>";
	$html.="</table>";
	sort($lastdate);
	//print_r($lastdate);
	$html=(($lastdate) ? "<p>Последнее списание <b>".date("d.m.Y H:i:s",strtotime($lastdate[count($lastdate)-1]))."</b></p>" : "").$html;
	return $html;
}
function findWaybillPosition($order_id,$retail_id,$item_id,$qty,$supplier) {
	//echo "findWaybillPosition {$order_id},{$retail_id},{$item_id},{$qty}<br>";
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($supplier);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			$tmp.=" AND Waybills.created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	
	
	
	$sql="SELECT Waybills_goods.* FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
		WHERE vendor_id={$supplier} AND payment_id=3 AND status_id=2 AND type_id=2 AND item_id={$item_id} AND Waybills_goods.sales<Waybills_goods.qty 
		 {$tmp} LIMIT 1";
	//if ($item_id==5576) {
		//echo $sql."<br>";
	//}
	//echo str_replace("<","&lt;",$sql)."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row['qty']>=($qty+$row['sales'])) {
				// сохраняем в историю
				//echo $qty."-".$row['sales']."-".$row['qty']."<br>";
				// эта позиция уже оплачена. Для Жбанова!
				
				$payment=0;
				if ($supplier==18) {
					$sql1="SELECT * FROM Waybills_paid WHERE item_id={$item_id} AND waybill_id={$row['waybill_id']}";
					//echo $sql1."<br>";
					if ($r1=mysql_query($sql1)) {
						while ($row1=mysql_fetch_array($r1)) {
							$sql2="SELECT * FROM Waybills_selling WHERE item_id={$item_id} AND waybill_id={$row['waybill_id']} AND payment_id=".$row1['id'];
							//echo $sql2."<br>";
							if ($r2=mysql_query($sql2)) {
								while ($row2=mysql_fetch_array($r2)) {
									$payment=$payment+1;
								}
							}
						}
					}
				}
				//echo $payment."<br>";
				if ($payment==0) {
					$sql="INSERT INTO Waybills_selling (item_id,waybill_id,order_id,retail_id,qty,created)
						VALUES ({$item_id},{$row['waybill_id']},".(($order_id) ? $order_id : "0").",".(($retail_id) ? $retail_id : "0").",{$qty},'".date("Y-m-d H:i:s")."')";
					//echo $sql."<br>";
					if (!mysql_query($sql)) {
						die($sql."<br>Error: ".mysql_error());
					}
					if ($order_id) {
						// закрываем позицию заказа
						$sql="UPDATE Netshop_OrderGoods SET sales={$qty},closed=1 WHERE Order_ID={$order_id} AND Item_ID={$item_id}";
						//echo $sql."<br>";
						if (!mysql_query($sql)) {
							die($sql."<br>Error: ".mysql_error());
						}
					}
					if ($retail_id) {
						// закрываем позицию заказа
						$sql="UPDATE Retails_goods SET sales={$qty},closed=1 WHERE retail_id={$retail_id} AND Item_ID={$item_id}";
						//echo $sql."<br>";
						if (!mysql_query($sql)) {
							die($sql."<br>Error: ".mysql_error());
						}
					}
					// закрываем позицию накладной
					$sql="UPDATE Waybills_goods SET sales=sales+{$qty} 
						WHERE id={$row['id']}";
					//echo $sql."<br>";
					if (!mysql_query($sql)) {
						die($sql."<br>Error: ".mysql_error());
					}
				}
			}
		}
	}
	return 0;
}

// запись продаж в таблицу Waybills_Goodssale 
function getWaybillsGoodsSale($incoming) {
	$arrdate=array();
	$arritem=array();
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	/*$sql="SELECT created FROM Waybills_selling ORDER BY id DESC LIMIT 1";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['created'];
		}
	}*/
	//echo $startdate."<br>";
	//$startdate="2015-05-04 00:00:00";
	$j=0;
	$str="";
	//if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
	//	$str=" AND supplier=".intval($incoming['supplier']);
	//}
	// online
	$sql="SELECT Netshop_OrderGoods.*,Message51.Created FROM Netshop_OrderGoods 
			INNER JOIN Message51 ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID)  
			WHERE Netshop_OrderGoods.closed=0 AND 
			Message51.wroff=1 AND 
			Message51.wroffdate>='".date("Y-m-d H:i:s",strtotime($startdate))."' 
			ORDER BY Netshop_OrderGoods.Order_ID ASC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row["closed"]==0) { // позиция не закрыта
				$arrdate[$j]=$row['Created'];
				$arritem[$j]=$row["Order_ID"].":0:".$row["Item_ID"].":".$row["Qty"];
				/*if ($row["sales"]!=$row["Qty"]) { // кол-во списанного != кол-во заказанного
					// нужно делать списание
					findWaybillPosition($row["Order_ID"],0,$row["Item_ID"],$row["Qty"]); 
					
				} */
				$j=$j+1;
			}
		}
	}
	// retail
	$sql="SELECT Retails_goods.*,Retails.Created FROM Retails_goods 
			INNER JOIN Retails ON (Retails.id=Retails_goods.retail_id) 
			INNER JOIN Message57 ON (Message57.Message_ID=Retails_goods.item_id) 
			WHERE Retails_goods.closed=0 AND Retails_goods.deleted=0 AND Retails.Created >= '".date("Y-m-d H:i:s",strtotime($startdate))."' {$str}
			ORDER BY Retails.id ASC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row["closed"]==0) { // позиция не закрыта
				$arrdate[$j]=$row['Created'];
				$arritem[$j]="0:{$row['retail_id']}:".$row["item_id"].":".$row["qty"];
				//echo $arrdate[$j]."||".$arritem[$j]."<br>";
				$j=$j+1;
			}
		}
	}
	asort($arrdate);
	//print_r($arritem);
	foreach ($arrdate as $key => $val) {
		$tmp=explode(":",$arritem[$key]);
		findWaybillPosition($tmp[0],$tmp[1],$tmp[2],$tmp[3],$incoming['supplier']); 
	}
//	echo $sql;
	
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
		case "save":
			getWaybillsGoodsSale($incoming);
			$html.="<p style='color:#00f300;font-weight:bold;'>Списания пересчитаны</p>";
			$html.=getWaybillsRe($incoming);
			break;
		default:
			// заполняем Waybills_goodssale
			//getWaybillsGoodsSale();
			
			$html.=getWaybillsRe($incoming);
			$html.="<form name='frm1' action='".$_SERVER["REQUEST_URI"]."' method='post'>
			<input type='hidden' name='action' value='save'>
			<input type='hidden' name='supplier' value='".((isset($incoming['supplier'])) ? $incoming['supplier'] : "" )."'>
			<input type='submit' value='Сохранить списания'>
			</form>";
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
	<h1>Списания по накладным. Остатки на складе на текущий момент</h1>
	<p>v3.1 <?php echo date("d.m.Y H:i:s",filemtime("selling.php")); ?></p>
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Поставщик</td><td><?php echo selectSupplier($incoming,1,1); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>
	<br>
	<table cellpadding='0' cellspacing='0' border='1'>
		<tr><td style='background:#FFFF99;'>&nbsp;&nbsp;&nbsp;</td><td>продано</td></tr>
		<tr><td style='background:#FF99FF;'>&nbsp;&nbsp;&nbsp;</td><td>все оплачено</td></tr>
		<tr><td style='background:#9999FF;'>&nbsp;&nbsp;&nbsp;</td><td>возврат поставщику, брак отложено</td></tr>
		<tr><td style='background:#99FFFF;'>&nbsp;&nbsp;&nbsp;</td><td>расход по учету</td></tr>
	</table>
	<br>
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

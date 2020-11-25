<?php
// 20.05.2014 Elen
// 14.06.2016 добавлены суммы по накладным

function findPayment($item_id,$waybill_id) {
	$res="";
	$sql="SELECT * FROM Waybills_paid WHERE item_id={$item_id} AND waybill_id={$waybill_id}";
	if ($result=mysql_query($sql)) {
		while ($row = mysql_fetch_array($result)) {
			$res.=$row['id']."   ";
		}
	}
	return $res;
}

function printWbCart($id) {
	$resi=array();

	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:760px;'>
	<tr><td>Артикул</td><td>Название</td><td>Цена закуп.[1шт.]</td><td>Кол.</td><td>На&nbsp;складе<br>сейчас</td><td>Продано</td><td>Оплачено</td><td>Возвращено</td><td>Расход по учету</td></tr>";
	$sql="SELECT Waybills_goods.*, Message57.StockUnits,Message57.ItemID AS ItemID, Message57.Name AS Name,Waybills.created AS wb_created FROM Waybills_goods 
		INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		INNER JOIN Waybills ON (Waybills_goods.waybill_id=Waybills.id)
		WHERE waybill_id=".$id;
	//echo $sql;
	$itog=$itogsold=$itogrest=$itogbrak=$itograshod=0;
	$result=mysql_query($sql);
	$return=$rashod=0;
	while ($row = mysql_fetch_array($result)) {
		$style="";
		if ($row['sales']!=0) {
			$style="background:#FFFF99;";
		//	echo $row['ItemID'].":".$row['qty']."<br>";
		}
		if ($row['paid']==$row['qty']) {
			$style="background:#FF99FF;";
		//	echo $row['ItemID'].":".$row['qty']."<br>";
		} 
		if($row['defect']!=0) {
			$style="background:#9999FF;";
		}
		$rashod=searchInRashod($row['item_id']);
		if($rashod>0) {
			$style="background:#99FFFF;";
		}
		$res.="<tr><td width='50' style='font-size:8pt;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
			<td style='width:500px;font-size:8pt;{$style}'>{$row['Name']}</td>
			<td width='50' style='font-size:8pt;{$style}'>".($row['originalprice']*$row['qty'])." [".$row['originalprice']."]</td>
			<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['qty']}</td>
			<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['StockUnits']}</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/selling-preview.php?wb={$row['waybill_id']}&item={$row['item_id']}'>{$row['sales']}</a></td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$row['paid']}".(($row['paid']!=0) ? " [".findPayment($row['item_id'],$id)."]" : "")."</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$row['defect']}&nbsp;".
								(($row['defectwb_id']!=0) ? "[".$row['defectwb_id']."]" : ""). "</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$rashod}&nbsp;</td>
			</tr>";
		$itog=$itog+$row['originalprice']*$row['qty'];
		$itogsold=$itogsold+$row['originalprice']*$row['sales'];
		$itogrest=$itogrest+$row['originalprice']*($row['qty']-$row['sales']-$row['defect']);
		$itogbrak=$itogbrak+$row['originalprice']*$row['defect'];
		$itograshod=$itograshod+$rashod*$row['originalprice'];
	}
	$res.="</table>
	<p>Всего по накладной:{$itog}</p>
	<p>Продано:{$itogsold}</p>
	<p>Остатки:{$itogrest}</p>
	<p>Брак-возврат:{$itogbrak}</p>
	<p>Расход по учету:{$itograshod}</p>";
	//return $res;
	$resi=array(0=>$res,
				1=>$itog,
				2=>$itogsold,
				3=>$itogrest,
				4=>$itogbrak,
				5=>$itograshod);
	return $resi;
}

function searchInRashod($item_id) {
	$sql="SELECT Startdate FROM Classificator_Supplier
		INNER JOIN Message57 ON (Message57.supplier=Classificator_Supplier.Supplier_ID)
		WHERE Message57.Message_ID=".intval($item_id);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=date("Y-m-d H:i:s",strtotime($row['Startdate']));
		}
	}
	$sql="SELECT Waybills_goods.qty AS qty,Waybills_goods.sales AS sales FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
		WHERE Waybills.type_id=10 AND Waybills.status_id=2 AND Waybills.created>'{$startdate}' AND Waybills_goods.item_id={$item_id}";
	//echo $sql."<br><br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			//$html.="<p>Последний пересчет: ".date("d.m.Y H:i:s",strtotime($row['created']))."</p>";
			$res=$row['qty']-$row['sales'];
		}
	}
	//echo $res."<br><br>";
	return $res;
}

function getLastWbSelling() {
	$res="";
	$sql="SELECT created FROM Waybills_selling ORDER BY created DESC LIMIT 1";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			//$html.="<p>Последний пересчет: ".date("d.m.Y H:i:s",strtotime($row['created']))."</p>";
			$res=$row['created'];
		}
	}
	return $res;
}

?>
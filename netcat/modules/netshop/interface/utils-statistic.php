<?php
function getPriceZ($complect) {
	$pricez=0;
	$tmp=explode(";",$complect);
	foreach($tmp as $t) {
		$tmp1=explode(":",$t);
		$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
			INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
			WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
		//echo $sql2."<br>";
		$r2=mysql_query($sql2);
		while ($row2=mysql_fetch_array($r2)) {
			$pricez=$pricez+$tmp1[1]*$row2['PriceZ'];
		}
	}
	return $pricez;
}
function getSold($sid,$year,$month) {
	$res="";
	$all=$allm=$allmz=0;
	$retail=$retailz=0;
	$netshop=$netshopz=0;
	
	$sql="SELECT Retails_goods.*,Message57_p.Price AS PriceZ,Message57.complect  FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("{$year}-{$month}-01 00:00:00")."' AND '".date("{$year}-{$month}-".cal_days_in_month(CAL_GREGORIAN, $month, $year))." 23:59:59'
			AND Retails_goods.deleted=0
			AND Message57.supplier={$sid}
			ORDER BY Retails_goods.id DESC";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	while ($row1=mysql_fetch_array($r)) {
		$retail=$retail+$row1['qty']*$row1['itemprice'];
		//$retailz=$retailz+$row1['qty']*$row1['PriceZ'];
		$pricez=0;
		if ($row1['complect']!="") {
			$pricez=getPriceZ($row1['complect']);
		} else {
			$pricez=$row1['PriceZ'];
		}
		$retailz=$retailz+$row1['qty']*$pricez;
	}
	$sql="SELECT Netshop_OrderGoods.*,Message57_p.Price AS PriceZ,Message57.complect FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
			WHERE Message51.created BETWEEN '".date("{$year}-{$month}-01 00:00:00")."' AND '".date("{$year}-{$month}-".cal_days_in_month(CAL_GREGORIAN, $month, $year))." 23:59:59'
			AND Message57.supplier={$sid}
			AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2
			ORDER BY Netshop_OrderGoods.Order_ID DESC";
	//echo $sql;
	$r1=mysql_query($sql);
	while ($row1=mysql_fetch_array($r1)) {
		$netshop=$netshop+$row1['Qty']*$row1['ItemPrice'];
		$pricez=0;
		if ($row1['complect']!="") {
			$pricez=getPriceZ($row1['complect']);
		} else {
			$pricez=$row1['PriceZ'];
		}
		$netshopz=$netshopz+$row1['Qty']*$pricez;
	}
	
	$all=mysql_num_rows($r)+mysql_num_rows($r1);
	$allm=$retail+$netshop;
	$allmz=$retailz+$netshopz;
	
	$res=$all.":".$allm.":".$allmz;
	return $res;
}

function showStatisticVendorForMonth($sid,$year,$month) {
	$res="";
	
	$tt=explode(":",getFullPriceMo($year,$month));
	$itog=$tt[0];
	$allnum=$tt[1];
	
	$sold=getSold($sid,$year,$month);
	//echo $sold;
	$sld=explode(":",$sold);
	
	$res.="	<td><p style='text-align:right;'><b>".number_format($sld[0],0,'.',' ')."</b></p></td>
			<td style='text-align:right;'>".number_format($sld[1],2,'.',' ')."</td>
			<td style='text-align:right;'>".number_format($sld[2],2,'.',' ')."</td>
			<td style='text-align:right;'>".number_format(round($sld[1]/$sld[0], 0),0,'.',' ')."</td>
			<td style='text-align:right;'>".number_format(round($sld[1]/$itog*100, 2),2,'.',' ')."</td>";
	return $res;
}
function showStatisticVendorForYear($sid,$year) {
	$res="";
	for ($i=1;$i<13;$i++) {
		$res.="<tr><td>{$incoming['month']}.{$incoming['year']}</td>";
		$res.=showStatisticVendorForMonth($sid,$year,$i);
		$res.=showResidueForVendorForMonth($sid,$year,$i);
	}
	return $res;
}

function getResidue($sid,$year,$month) {
	$res="";
	$sql="SELECT Message57.Price,Message57.StockUnits, Message57_p.Price AS PriceZ FROM Message57 
			LEFT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
			WHERE Message57.supplier={$sid} AND Message57.Checked=1 AND Message57.StockUnits<>0 AND Message57.status=2
			ORDER BY Message57.Message_ID ASC";
	$r=mysql_query($sql);
	$allm=$all=$allmz=0;
	while ($row1=mysql_fetch_array($r)) {
		$allm=$allm+$row1['StockUnits']*$row1['Price'];
		$allmz=$allmz+$row1['StockUnits']*$row1['PriceZ'];
		$all=$all+$row1['StockUnits'];
	}
	$res=$all.":".$allm.":".$allmz;
	return $res;
}
function showResidueForVendorForMonth($sid,$year,$month) {
	$res="";
	$tt=explode(":",getFullPriceMoResidue($year,$month));
	$itog=$tt[0];
	$allnum=$tt[1];
	
	$residue=getResidue($sid,$year,$month);
	$rsd=explode(":",$residue);
	
	$res.="<td style='text-align:right;'>".number_format($rsd[0],0,'.',' ')."</td>
			<td style='text-align:right;'>".number_format($rsd[1],2,'.',' ')."</td>
			<td style='text-align:right;'>".number_format($rsd[2],2,'.',' ')."</td>
			<td style='text-align:right;'>".number_format(round($rsd[1]/$itog*100, 2),2,'.',' ')."</td>
			<td style='text-align:right;'>".(($rsd[2]!=0) ? number_format(round($rsd[1]/$rsd[2]*100-100, 2),2,'.',' ') : "0.0")."</td>";
	return $res;
}

function getFullPriceMo($year, $month) {
	$res="";
	$all=0;
	$sql="SELECT * FROM Classificator_Supplier WHERE Statistic=1 ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//$res.=showStatisticForVendor($row['Supplier_ID'], $incoming['min'],$incoming['max'], $itog);
		$sql="SELECT Retails_goods.* FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("{$year}-{$month}-01 00:00:00")."' AND '".date("{$year}-{$month}-".cal_days_in_month(CAL_GREGORIAN, $month, $year))." 23:59:59'
			AND Message57.supplier={$row['Supplier_ID']}
			ORDER BY Retails_goods.id DESC";
		//echo $sql;
		$r=mysql_query($sql);
		while ($row1=mysql_fetch_array($r)) {
			$res=$res+$row1['qty']*$row1['itemprice'];
		}
		$sql="SELECT Netshop_OrderGoods.* FROM Netshop_OrderGoods 
				INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
				INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
				WHERE Message51.created BETWEEN '".date("{$year}-{$month}-01 00:00:00")."' AND '".date("{$year}-{$month}-".cal_days_in_month(CAL_GREGORIAN, $month, $year))." 23:59:59'
				AND Message57.supplier={$row['Supplier_ID']}
				ORDER BY Netshop_OrderGoods.Order_ID DESC";
		//echo $sql;
		$r1=mysql_query($sql);
		while ($row1=mysql_fetch_array($r1)) {
			$res=$res+$row1['Qty']*$row1['ItemPrice'];
		}
		$all=$all+mysql_num_rows($r)+mysql_num_rows($r1);
	}
	
	
	return $res.":".$all;
}
	
function getFullPriceMoResidue() {
	$res=$resz=0;
	$all=0;
	$sql="SELECT Message57.StockUnits,Message57.Price,Message57_p.Price AS PriceZ 
		FROM Message57 
		LEFT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
		WHERE Message57.Checked=1 AND Message57.StockUnits<>0 AND Message57.status=2 
		ORDER BY Message57.Message_ID ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$res+$row['StockUnits']*$row['Price'];
		$resz=$resz+$row['StockUnits']*$row['PriceZ'];
		$all=$all+$row['StockUnits'];
	}	
	return $res.":".$all.":".$resz;
}
?>
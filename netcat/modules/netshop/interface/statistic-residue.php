<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
	
	
function showResidueList($incoming) {
	//print_r($incoming);
	$min="2016-01-01";
	$max=date("Y-m-d");
	if (isset($incoming['min'])) {
		$min=$incoming['min'];
	}
	if (isset($incoming['max'])) {
		$max=$incoming['max'];
	}
	$sup=0;
	if (isset($incoming['supplier'])) {
		$sup=intval($incoming['supplier']);
	}
	$wb=0;
	if ((isset($incoming['wb']))&&($incoming['wb']==1)) {
		$wb=1;
	}
	$tv=0;
	if ((isset($incoming['tv']))&&($incoming['tv']==1)) {
		$tv=1;
	}

	$res="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td>#</td>
			<td>Поставщик</td>
			<td>Артикул</td>
			<td>Товар</td>
			<td>На складе</td>
			
			<td>Цена,1шт</td>
			<td>Цена закуп.,1шт</td>
			<td>Цена</td>
			<td>Цена закуп.</td>
			<td>Наценка</td>
			<td>% наценки</td>
		</tr>";
	
	$sql1="SELECT Message57.*,Classificator_Supplier.Supplier_Name,Message57_p.Price AS Pricez FROM Message57 
		LEFT JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		LEFT JOIN Classificator_Supplier ON ( Message57.supplier=Classificator_Supplier.Supplier_ID )
		WHERE Message57.Checked=1 AND Message57.StockUnits>0 AND Status=2 
			".(($sup!=0) ? "AND Message57.supplier=".$sup : "")."
			AND Message57.Created BETWEEN '2000-01-01 00:00:00' AND '".date("Y-m-d 00:00:00",strtotime($min))."'
		ORDER BY Message_ID ASC";
	//echo $sql1."<br>";
	$r1=mysql_query($sql1);
	$i=1;
	$n=0;
	$p=$z=$nc=0;
	while ($row1=mysql_fetch_array($r1)) {
		//echo $row1['Message_ID']."  ";

		$sql="SELECT Netshop_OrderGoods.* FROM Netshop_OrderGoods 
				INNER JOIN Message51 ON (Netshop_OrderGoods.Order_ID=Message51.Message_ID)
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' AND Netshop_OrderGoods.Item_ID=".$row1['Message_ID'];
		//echo $sql."<br>";
		$r=mysql_query($sql);
		
		if (mysql_num_rows($r)==0) {
			
			$sqlr="SELECT Retails_goods.* FROM Retails_goods 
					INNER JOIN Retails ON (Retails_goods.retail_id=Retails.id)
				WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' AND Retails_goods.item_id=".$row1['Message_ID'];
			//echo $sql."<br>";
			$rr=mysql_query($sqlr);
			if (mysql_num_rows($rr)==0) {
				$show=1;
				if ($wb==1) {
					$sqlwb="SELECT Waybills.*,Waybills_goods.* FROM Waybills 
						INNER JOIN  Waybills_goods ON (Waybills.id=Waybills_goods.waybill_id)
						WHERE Waybills.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' 
							AND Waybills_goods.item_id=".$row1['Message_ID'];
					//echo $sqlwb."<br>";
					$rwb=mysql_query($sqlwb);
					if (mysql_num_rows($rwb)>0) {
						$show=0;
					}
				}
				//echo $tv."<br>";
				if ($tv==1) {
					$show=0;
					$sqlwb="SELECT Waybills.*,Waybills_goods.* FROM Waybills 
						INNER JOIN  Waybills_goods ON (Waybills.id=Waybills_goods.waybill_id)
						WHERE (Waybills.type_id=1 OR Waybills.type_id=2) AND Waybills.status_id=2 AND Waybills.payment_id=1
							AND Waybills_goods.item_id=".$row1['Message_ID'];
					//echo $sqlwb."<br>";
					//AND Waybills.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' 
					$rwb=mysql_query($sqlwb);
					if (mysql_num_rows($rwb)>0) {
						$show=1;
					}
				}
				if ($show==1) {
					$res.="<tr>";
					$res.="<td>{$i}</td>"; //{$row1['Supplier_Name']}
					$res.="<td>".(($row1['Supplier_Name']!="") ? $row1['Supplier_Name'] : "--")."</td>"; //{$row1['Supplier_Name']}
					$res.="<td ".(($row1['Checked']==0) ? "style='background:#c0c0c0;'" : "")."><a href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row1['Message_ID']}' target='_blank'>{$row1['ItemID']}</a></td>";
					$res.="<td ".(($row1['Checked']==0) ? "style='background:#c0c0c0;'" : "").">{$row1['Name']}</td>";
					$res.="<td style='text-align:right;'>{$row1['StockUnits']}</td>";
					$res.="<td style='text-align:right;'>{$row1['Price']}</td>";
					$res.="<td style='text-align:right;'>{$row1['Pricez']}</td>";
					$res.="<td style='text-align:right;'>".($row1['Price']*$row1['StockUnits'])."</td>";
					$res.="<td style='text-align:right;'>".($row1['Pricez']*$row1['StockUnits'])."</td>";
					$res.="<td style='text-align:right;'>".($row1['Price']-$row1['Pricez'])."</td>";
					$res.="<td style='text-align:right;'>".number_format(((($row1['Price']/$row1['Pricez'])-1)*100),2,","," ")."</td>";
					//$res.="<td>{$row['sales']}</td>";
					//$res.="<td><b>".($row['qty']-$row['sales'])."</b></td>";
					$res.="</tr>";
					$i=$i+1;
					$n=$n+$row1['StockUnits'];
					$p=$p+$row1['Price']*$row1['StockUnits'];
					$pz=$pz+$row1['Pricez']*$row1['StockUnits'];
					$nc=$nc+($row1['Price']-$row1['Pricez'])*$row1['StockUnits'];
				}
			}
		}
		//if ($i>15) break;
	}
	
	$res.="<tr>
		<td colspan='4' style='text-align:right;'>Итого</td>
		<td style='text-align:right;'>{$n}</td>
		<td style='text-align:right;'>&nbsp;</td>
		<td style='text-align:right;'>&nbsp;</td>
		<td style='text-align:right;' nowrap>".number_format($p,0,","," ")."</td>
		<td style='text-align:right;' nowrap>".number_format($pz,0,","," ")."</td>
		<td style='text-align:right;' nowrap>".number_format($nc,0,","," ")."</td>
		<td style='text-align:right;'>&nbsp;</td>
	</tr>";
	
	$res.="</table>
	<p>Всего: <b>".($i-1)."</b> позиций</p>";
	if ((!isset($incoming['act']))||($incoming['act']!="print")) {
		$res.="<p><a target='_blank' href='/netcat/modules/netshop/interface/statistic-residue.php?act=print&min={$incoming['min']}&max={$incoming['max']}&supplier={$incoming['supplier']}&tv={$incoming['tv']}&wb={$incoming['wb']}'>Распечатать</a></p>";
	}
	return $res;
}


function showResidueListCheck($incoming) {
	//print_r($incoming);
	$min="2016-01-01";
	$max=date("Y-m-d");
	if (isset($incoming['min'])) {
		$min=$incoming['min'];
	}
	if (isset($incoming['max'])) {
		$max=$incoming['max'];
	}
	$sup=0;
	if (isset($incoming['supplier'])) {
		$sup=intval($incoming['supplier']);
	}
	$wb=0;
	if ((isset($incoming['wb']))&&($incoming['wb']==1)) {
		$wb=1;
	}
	$tv=0;
	if ((isset($incoming['tv']))&&($incoming['tv']==1)) {
		$tv=1;
	}

	$res="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td>#</td>
			<td>Поставщик</td>
			<td>Артикул</td>
			<td>Товар</td>
			<td>На складе</td>
			
			<td>Цена,1шт</td>
			<td>Цена закуп.,1шт</td>
			<td>Цена</td>
			<td>Цена закуп.</td>
			<td>Наценка</td>
			<td>% наценки</td>
		</tr>";
	
	$sql1="SELECT Message57.*,Classificator_Supplier.Supplier_Name,Message57_p.Price AS Pricez FROM Message57 
		LEFT JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		LEFT JOIN Classificator_Supplier ON ( Message57.supplier=Classificator_Supplier.Supplier_ID )
		WHERE Message57.Checked=1 AND Message57.StockUnits>0 AND Status=2 
			".(($sup!=0) ? "AND Message57.supplier=".$sup : "")."
			AND Message57.Created BETWEEN '2000-01-01 00:00:00' AND '".date("Y-m-d 00:00:00",strtotime($min))."'
		ORDER BY Message_ID ASC";
	//echo $sql1."<br>";
	$r1=mysql_query($sql1);
	$i=1;
	$n=0;
	$p=$z=$nc=0;
	while ($row1=mysql_fetch_array($r1)) {
		//echo $row1['Message_ID']."  ";

		$sql="SELECT Netshop_OrderGoods.* FROM Netshop_OrderGoods 
				INNER JOIN Message51 ON (Netshop_OrderGoods.Order_ID=Message51.Message_ID)
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' AND Netshop_OrderGoods.Item_ID=".$row1['Message_ID'];
		//echo $sql."<br>";
		$r=mysql_query($sql);
		
		if (mysql_num_rows($r)==0) {
			
			$sqlr="SELECT Retails_goods.* FROM Retails_goods 
					INNER JOIN Retails ON (Retails_goods.retail_id=Retails.id)
				WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' AND Retails_goods.item_id=".$row1['Message_ID'];
			//echo $sql."<br>";
			$rr=mysql_query($sqlr);
			if (mysql_num_rows($rr)==0) {
				$show=1;
				if ($wb==1) {
					$sqlwb="SELECT Waybills.*,Waybills_goods.* FROM Waybills 
						INNER JOIN  Waybills_goods ON (Waybills.id=Waybills_goods.waybill_id)
						WHERE Waybills.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' 
							AND Waybills_goods.item_id=".$row1['Message_ID'];
					//echo $sqlwb."<br>";
					$rwb=mysql_query($sqlwb);
					if (mysql_num_rows($rwb)>0) {
						$show=0;
					}
				}
				//echo $tv."<br>";
				if ($tv==1) {
					$show=0;
					$sqlwb="SELECT Waybills.*,Waybills_goods.* FROM Waybills 
						INNER JOIN  Waybills_goods ON (Waybills.id=Waybills_goods.waybill_id)
						WHERE (Waybills.type_id=1 OR Waybills.type_id=2) AND Waybills.status_id=2 AND Waybills.payment_id=1
							AND Waybills_goods.item_id=".$row1['Message_ID'];
					//echo $sqlwb."<br>";
					//AND Waybills.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' 
					$rwb=mysql_query($sqlwb);
					if (mysql_num_rows($rwb)>0) {
						$show=1;
					}
				}
				if ($show==1) {
					if (($row1["Subdivision_ID"]==141)||
						($row1["Subdivision_ID"]==103)||
						($row1["Subdivision_ID"]==138)||
						($row1["Subdivision_ID"]==102)||
						($row1["Subdivision_ID"]==139)||
						($row1["Subdivision_ID"]==106)||
						($row1["Subdivision_ID"]==151)||
						($row1["Subdivision_ID"]==153)||
						($row1["Subdivision_ID"]==157)||
						($row1["Subdivision_ID"]==161)||
						($row1["Subdivision_ID"]==280)||
						($row1["Subdivision_ID"]==108)) {
					
							$res.="<tr>";
							$res.="<td>{$i}</td>"; //{$row1['Supplier_Name']}
							$res.="<td>".(($row1['Supplier_Name']!="") ? $row1['Supplier_Name'] : "--")."</td>"; //{$row1['Supplier_Name']}
							$res.="<td ".(($row1['Checked']==0) ? "style='background:#c0c0c0;'" : "")."><a href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row1['Message_ID']}' target='_blank'>{$row1['ItemID']}</a></td>";
							$res.="<td ".(($row1['Checked']==0) ? "style='background:#c0c0c0;'" : "").">{$row1['Name']}</td>";
							$res.="<td style='text-align:right;'>{$row1['StockUnits']}</td>";
							$res.="<td style='text-align:right;'>{$row1['Price']}</td>";
							$res.="<td style='text-align:right;'>{$row1['Pricez']}</td>";
							$res.="<td style='text-align:right;'>".($row1['Price']*$row1['StockUnits'])."</td>";
							$res.="<td style='text-align:right;'>".($row1['Pricez']*$row1['StockUnits'])."</td>";
							$res.="<td style='text-align:right;'>".($row1['Price']-$row1['Pricez'])."</td>";
							$res.="<td style='text-align:right;'>".number_format(((($row1['Price']/$row1['Pricez'])-1)*100),2,","," ")."</td>";
							//$res.="<td>{$row['sales']}</td>";
							//$res.="<td><b>".($row['qty']-$row['sales'])."</b></td>";
							$res.="</tr>";
							$i=$i+1;
							$n=$n+$row1['StockUnits'];
							$p=$p+$row1['Price']*$row1['StockUnits'];
							$pz=$pz+$row1['Pricez']*$row1['StockUnits'];
							$nc=$nc+($row1['Price']-$row1['Pricez'])*$row1['StockUnits'];
							
							$sqlu="UPDATE Message57 SET unsaled=1 WHERE Message_ID=".$row1["Message_ID"];
							if (!mysql_query($sqlu)) {
								die(mysql_error());
							}
					}
				}
			}
		}
		//if ($i>15) break;
	}
	
	$res.="<tr>
		<td colspan='4' style='text-align:right;'>Итого</td>
		<td style='text-align:right;'>{$n}</td>
		<td style='text-align:right;'>&nbsp;</td>
		<td style='text-align:right;'>&nbsp;</td>
		<td style='text-align:right;' nowrap>".number_format($p,0,","," ")."</td>
		<td style='text-align:right;' nowrap>".number_format($pz,0,","," ")."</td>
		<td style='text-align:right;' nowrap>".number_format($nc,0,","," ")."</td>
		<td style='text-align:right;'>&nbsp;</td>
	</tr>";
	
	$res.="</table>
	<p>Всего: <b>".($i-1)."</b> позиций</p>";
	if ((!isset($incoming['act']))||($incoming['act']!="print")) {
		$res.="<p><a target='_blank' href='/netcat/modules/netshop/interface/statistic-residue.php?act=print&min={$incoming['min']}&max={$incoming['max']}&supplier={$incoming['supplier']}&tv={$incoming['tv']}&wb={$incoming['wb']}'>Распечатать</a></p>";
	}
	return $res;
}

function showResidueListDiscount($incoming) {
	//print_r($incoming);
	$min="2016-01-01";
	$max=date("Y-m-d");
	if (isset($incoming['min'])) {
		$min=$incoming['min'];
	}
	if (isset($incoming['max'])) {
		$max=$incoming['max'];
	}
	$sup=0;
	if (isset($incoming['supplier'])) {
		$sup=intval($incoming['supplier']);
	}
	$wb=0;
	if ((isset($incoming['wb']))&&($incoming['wb']==1)) {
		$wb=1;
	}
	$tv=0;
	if ((isset($incoming['tv']))&&($incoming['tv']==1)) {
		$tv=1;
	}

	$res="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td>#</td>
			<td>Поставщик</td>
			<td>Артикул</td>
			<td>Товар</td>
			<td>На складе</td>
			
			<td>Цена,1шт</td>
			<td>Цена закуп.,1шт</td>
			<td>Цена</td>
			<td>Цена закуп.</td>
			<td>Наценка</td>
			<td>% наценки</td>
		</tr>";
	
	$sql1="SELECT Message57.*,Classificator_Supplier.Supplier_Name,Message57_p.Price AS Pricez FROM Message57 
		LEFT JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		LEFT JOIN Classificator_Supplier ON ( Message57.supplier=Classificator_Supplier.Supplier_ID )
		WHERE Message57.Checked=1 AND Message57.StockUnits>0 AND Status=2 
			".(($sup!=0) ? "AND Message57.supplier=".$sup : "")."
			AND Message57.Created BETWEEN '2000-01-01 00:00:00' AND '".date("Y-m-d 00:00:00",strtotime($min))."'
		ORDER BY ItemID ASC";
	//echo $sql1."<br>";
	$r1=mysql_query($sql1);
	$i=1;
	$n=0;
	$p=$z=$nc=0;
	while ($row1=mysql_fetch_array($r1)) {
		//echo $row1['Message_ID']."  ";

		$sql="SELECT Netshop_OrderGoods.* FROM Netshop_OrderGoods 
				INNER JOIN Message51 ON (Netshop_OrderGoods.Order_ID=Message51.Message_ID)
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' AND Netshop_OrderGoods.Item_ID=".$row1['Message_ID'];
		//echo $sql."<br>";
		$r=mysql_query($sql);
		
		if (mysql_num_rows($r)==0) {
			
			$sqlr="SELECT Retails_goods.* FROM Retails_goods 
					INNER JOIN Retails ON (Retails_goods.retail_id=Retails.id)
				WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' AND Retails_goods.item_id=".$row1['Message_ID'];
			//echo $sql."<br>";
			$rr=mysql_query($sqlr);
			if (mysql_num_rows($rr)==0) {
				$show=0;
				/*if ($wb==1) {
					$sqlwb="SELECT Waybills.*,Waybills_goods.* FROM Waybills 
						INNER JOIN  Waybills_goods ON (Waybills.id=Waybills_goods.waybill_id)
						WHERE Waybills.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' 
							AND Waybills_goods.item_id=".$row1['Message_ID'];
					//echo $sqlwb."<br>";
					$rwb=mysql_query($sqlwb);
					if (mysql_num_rows($rwb)>0) {
						$show=0;
					}
				}*/
				//echo $tv."<br>";
				//if ($tv==1) {
					//$show=0;
					$sqlwb="SELECT Waybills.*,Waybills_goods.* FROM Waybills 
						INNER JOIN  Waybills_goods ON (Waybills.id=Waybills_goods.waybill_id)
						WHERE (Waybills.type_id=1 OR Waybills.type_id=2) AND Waybills.payment_id=1 AND Waybills.status_id=2
							AND Waybills_goods.item_id=".$row1['Message_ID'];
					//echo $sqlwb."<br>";
					//AND Waybills.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($min))."' AND '".date("Y-m-d 00:00:00",strtotime($max))."' 
					$rwb=mysql_query($sqlwb);
					if (mysql_num_rows($rwb)>0) {
						$show=1;
					}
				//}
				if ($show==1) {
					if (($row1["Subdivision_ID"]==141)||
						($row1["Subdivision_ID"]==103)||
						($row1["Subdivision_ID"]==138)||
						($row1["Subdivision_ID"]==102)||
						($row1["Subdivision_ID"]==139)||
						($row1["Subdivision_ID"]==106)||
						($row1["Subdivision_ID"]==151)||
						($row1["Subdivision_ID"]==153)||
						($row1["Subdivision_ID"]==157)||
						($row1["Subdivision_ID"]==161)||
						($row1["Subdivision_ID"]==280)||
						($row1["Subdivision_ID"]==108)) {
					
							$discount=getDiscount($row1['Message_ID'], $row1['Price']);
					
							$res.="<tr>";
							$res.="<td>{$i}</td>"; //{$row1['Supplier_Name']}
							$res.="<td>".(($row1['Supplier_Name']!="") ? $row1['Supplier_Name'] : "--")."</td>"; //{$row1['Supplier_Name']}
							$res.="<td ".(($row1['Checked']==0) ? "style='background:#c0c0c0;'" : "")."><a href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row1['Message_ID']}' target='_blank'>{$row1['ItemID']}</a></td>";
							$res.="<td ".(($row1['Checked']==0) ? "style='background:#c0c0c0;'" : "").">{$row1['Name']}</td>";
							$res.="<td style='text-align:right;'>{$row1['StockUnits']}</td>";
							$res.="<td style='text-align:right;'>".(($discount!=$row1['Price']) ? "<strike>".$row1['Price']."</strike> ".$discount : $row1['Price'] )."</td>";
							$res.="<td style='text-align:right;'>{$row1['Pricez']}</td>";
							$res.="<td style='text-align:right;'>".($row1['Price']*$row1['StockUnits'])."</td>";
							$res.="<td style='text-align:right;'>".($row1['Pricez']*$row1['StockUnits'])."</td>";
							$res.="<td style='text-align:right;'>".($row1['Price']-$row1['Pricez'])."</td>";
							$res.="<td style='text-align:right;'>".number_format(((($row1['Price']/$row1['Pricez'])-1)*100),2,","," ")."</td>";
							//$res.="<td>{$row['sales']}</td>";
							//$res.="<td><b>".($row['qty']-$row['sales'])."</b></td>";
							$res.="</tr>";
							$i=$i+1;
							$n=$n+$row1['StockUnits'];
							$p=$p+$row1['Price']*$row1['StockUnits'];
							$pz=$pz+$row1['Pricez']*$row1['StockUnits'];
							$nc=$nc+($row1['Price']-$row1['Pricez'])*$row1['StockUnits'];
							
							//$sqlu="UPDATE Message57 SET unsaled=1 WHERE Message_ID=".$row1["Message_ID"];
							//if (!mysql_query($sqlu)) {
							//	die(mysql_error());
							//}
					}
				}
			}
		}
		//if ($i>15) break;
	}
	
	$res.="<tr>
		<td colspan='4' style='text-align:right;'>Итого</td>
		<td style='text-align:right;'>{$n}</td>
		<td style='text-align:right;'>&nbsp;</td>
		<td style='text-align:right;'>&nbsp;</td>
		<td style='text-align:right;' nowrap>".number_format($p,0,","," ")."</td>
		<td style='text-align:right;' nowrap>".number_format($pz,0,","," ")."</td>
		<td style='text-align:right;' nowrap>".number_format($nc,0,","," ")."</td>
		<td style='text-align:right;'>&nbsp;</td>
	</tr>";
	
	$res.="</table>
	<p>Всего: <b>".($i-1)."</b> позиций</p>";
	if ((!isset($incoming['act']))||($incoming['act']!="print")) {
		$res.="<p><a target='_blank' href='/netcat/modules/netshop/interface/statistic-residue.php?act=print&min={$incoming['min']}&max={$incoming['max']}&supplier={$incoming['supplier']}&tv={$incoming['tv']}&wb={$incoming['wb']}'>Распечатать</a></p>";
	}
	return $res;
}


//=================================================================================================
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
// ------------------------------------------------------------------------------------------------
	$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/trace/';
	$DownloadDir='/netcat_files/postfiles/trace/';
//	$DownloadDirLink='/netcat_files/postfiles/';
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$orders=array();
	$show=1;
	//print_r($incoming);
	if ($incoming["action"]=="check") {
		//$html.="<h3>Только разделы 141,103,138,102,139,106,151,153,157,161,280,108</h3>";
		$html.=showResidueListCheck($incoming);
	} elseif ($incoming["action"]=="discount") {
		//$html.="<h3>Только разделы 141,103,138,102,139,106,151,153,157,161,280,108</h3>";
		$html.=showResidueListDiscount($incoming);
	} else {
		
		$html.=showResidueList($incoming);
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Статистика по розничным продажам</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script language="Javascript" type="text/javascript">
	function checkInSales() {
		this.document.getElementById("action").value="check";
		this.document.getElementById("frm1").submit();
		return true;
	}
	function checkInDiscount() {
		this.document.getElementById("action").value="discount";
		this.document.getElementById("frm1").submit();
		return true;
	}
	</script>
</head>

<body>
	<?php
		if ((!isset($incoming['act']))||($incoming['act']!="print")) {
			echo printMenu();
		}
	?>
	<h1>Статистика по непроданным товарам</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-residue.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<p>Товар поступил в магазин до первой даты.</p>
	<p>Не было продаж по товару</p>
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td>
			с <input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.01.2016" ?>" class="datepickerTimeField">		
		</td><td>
			по <input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y"); ?>" class="datepickerTimeField">		
		</td></tr>
		
		<tr>
			<td style="text-align:right;">Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;">Не было поступлений:</td>
			<td><input type="checkbox" value="1" name="wb" id="wb" <?=(((isset($incoming['wb']))&&($incoming['wb']==1)) ? "checked" : "")?>></td>
		</tr>
		<tr>
			<td style="text-align:right;">Твердый счет:</td>
			<td><input type="checkbox" value="1" name="tv" id="tv" <?=(((isset($incoming['tv']))&&($incoming['tv']==1)) ? "checked" : "")?>></td>
		</tr>
		<?php
		if ((!isset($incoming['act']))||($incoming['act']!="print")) {
		?>
		<tr><td colspan="2"><input type="submit" value="Показать">
		&nbsp;&nbsp;
		<input type="button" value="Пометить залежалый товар" onclick="checkInSales();">
		&nbsp;&nbsp;
		<input type="button" value="Пометить для распродажи" onclick="checkInDiscount();">
		</td></tr>
		<?php
		}
		?>
	</table>
	</form>
<?php echo $html; ?>
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
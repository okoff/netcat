<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();

function printWbCartSel($id) {
	$resi=array();

	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:1000px'>
	<tr><td>Артикул</td><td>Название</td><td>Цена закуп.</td><td>Кол.</td><td>На&nbsp;складе<br>сейчас</td>
		<td>Продано</td>
		<td>Оплачено</td>
		<td>Списания</td></tr>";
	$sql="SELECT Waybills_goods.*, Message57.StockUnits,Message57.ItemID AS ItemID, Message57.Name AS Name,Waybills.created AS wb_created FROM Waybills_goods 
		INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		INNER JOIN Waybills ON (Waybills_goods.waybill_id=Waybills.id)
		WHERE waybill_id=".$id;
	//echo $sql;
	$itog=$itogsold=$itogrest=$itogbrak=0; // продажи. подсчет по позициям накладной
	$insold=$isold=0; // продажи. подсчет по списаниям Waybills_selling
	$result=mysql_query($sql);
	$return=$rashod=0;
	while ($row = mysql_fetch_array($result)) {
		$tmp="";
		$sql1="SELECT * FROM Waybills_selling WHERE waybill_id={$row['waybill_id']} AND item_id={$row['item_id']}";
		if ($r1=mysql_query($sql1)) {
			while ($row1 = mysql_fetch_array($r1)) {
				$tmp.=(($tmp!="") ? "<br>" : "");
				$tmp.=date("d.m.Y H:i:s",strtotime($row1['created']))." ".
						$row1['qty']." ".
						(($row1['order_id']!=0) ? "заказ ".$row1['order_id'] : "" ).
						(($row1['retail_id']!=0) ? "розн. ".$row1['retail_id'] : "");
				$insold=$insold+$row1['qty'];
				$isold=$isold+$row1['qty']*$row['originalprice'];
			}
		}
		
		
		$res.="<tr><td width='50' style='font-size:8pt;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
			<td style='width:500px;font-size:8pt;{$style}'>{$row['Name']}</td>
			<td width='50' style='font-size:8pt;{$style}'>".($row['originalprice']*$row['qty'])."</td>
			<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['qty']}</td>
			<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['StockUnits']}</td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/selling-preview.php?wb={$row['waybill_id']}&item={$row['item_id']}'>{$row['sales']}</a></td>
			<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$row['paid']}</td>
			<td width='300' style='font-size:8pt;text-align:left;{$style}'>{$tmp}&nbsp;</td>
			</tr>";
		$itog=$itog+$row['originalprice']*$row['qty'];
		$itogsold=$itogsold+$row['originalprice']*$row['sales'];
		$itogrest=$itogrest+$row['originalprice']*($row['qty']-$row['sales']-$rashod-$return);
		$itogbrak=$itogbrak+$row['originalprice']*($rashod+$return);
		
	}
	$res.="</table>
	<p>Всего по накладной:{$itog}</p>
	<p>Продано:{$itogsold}</p>
	<p>Остатки:{$itogrest}</p>
	<!--p>Брак-возврат-расход по учету:{$itogbrak}</p-->
	<p>СПИСАНИЯ:{$insold}шт. {$isold}</p>";
	//return $res;
	$resi=array(0=>$res,
				1=>$itog,
				2=>$itogsold,
				3=>$itogrest,
				4=>$itogbrak);
	return $resi;
}

function getSelling($incoming) {
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
		ORDER BY id ASC "; //LIMIT 0,1";
	//echo "<br>".$sql;
	$j=0;
	
	//Всего по накладной:
	$itog=0;
	//Продано:
	$itogsold=0;
	//Остатки:
	$itogrest=0;
	//Брак-возврат-расход по учету:
	$itogbrak=0;
	
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
			$resi=printWbCartSel($row['id']);
			$html.="<tr>
			<td style='vertical-align:top;'><b>{$row['id']}</b></td>
			<td style='vertical-align:top;'>".date("d.m.Y",strtotime($row['created']))."</td>
			<td style='vertical-align:top;'>".getSupplier($row['vendor_id'])."</td>
			<td>".$resi[0]."</td>
		</tr>";
			$j=$j+1;
			$itog=$itog+$resi[1];
			$itogsold=$itogsold+$resi[2];
			$itogrest=$itogrest+$resi[3];
			$itogbrak=$itogbrak+$resi[4];
		}
	}
	$html.="</table>";
	sort($lastdate);
	//print_r($lastdate);
	$html=(($lastdate) ? "<p>Последнее списание <b>".date("d.m.Y H:i:s",strtotime($lastdate[count($lastdate)-1]))."</b></p>" : "").$html;
	$html.="<p>Всего по накладным:{$itog}</p>
	<p>Продано:{$itogsold}</p>
	<p>Остатки:{$itogrest}</p>
	<p>Брак-возврат-расход по учету:{$itogbrak}</p>";
	return $html;
}

// 
function getSold($incoming) {
	$html="";
	// startdate
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
	// orders
	$sql="SELECT Message51.wroffdate,Netshop_OrderGoods.*,Message57.ItemID,Message57.Name,Message57_p.Price AS PriceZ FROM Message51 
			INNER JOIN Netshop_OrderGoods ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID)
			INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
			INNER JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		WHERE Message51.wroff=1 
		AND Message57.Vendor=".intval($incoming['supplier']);  //wroffdate
	echo $sql."<br>";
	$html.="<table border='1' cellpadding='2' cellspacing='0'>
	<tr><td>#</td>
		<td>articul</td>
		<td>item</td>
		<td>qty</td>
		<td>price</td>
		<td>fullcost</td>
	</tr>";
	$itog=$itogn=0;
	if ($r=mysql_query($sql)) {
		while ($row=mysql_fetch_array($r)) {
			if(strtotime($row['wroffdate'])>strtotime($startdate)) {
				$html.="<tr><td>".$row['Order_ID']."</td>
				<td>".$row['ItemID']."</td>
				<td>".$row['Name']."</td>
				<td>".$row['Qty']."</td>
				<td>".$row['PriceZ']."</td>
				<td>".($row['Qty']*$row['PriceZ'])."</td>
				</tr>";
				$itogn=$itogn+$row['Qty'];
				$itog=$itog+$row['Qty']*$row['PriceZ'];
			}
		}
	}
	$html.="</table>";
	$html.="<p>ИТОГО: {$itogn}шт. {$itog}</p>";
	
	$sql="SELECT Retails.created,Retails_goods.*,Message57.ItemID,Message57.Name,Message57_p.Price AS PriceZ FROM Retails 
			INNER JOIN Retails_goods ON (Retails.id=Retails_goods.retail_id)
			INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
			INNER JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		WHERE Message57.Vendor=".intval($incoming['supplier']);  //wroffdate
	echo $sql."<br>";
	$html.="<table border='1' cellpadding='2' cellspacing='0'>
	<tr><td>#</td>
		<td>articul</td>
		<td>item</td>
		<td>qty</td>
		<td>price</td>
		<td>fullcost</td>
	</tr>";
	$itog1=$itogn1=0;
	if ($r=mysql_query($sql)) {
		while ($row=mysql_fetch_array($r)) {
			if(strtotime($row['created'])>strtotime($startdate)) {
				$html.="<tr><td>".$row['retail_id']."</td>
				<td>".$row['ItemID']."</td>
				<td>".$row['Name']."</td>
				<td>".$row['qty']."</td>
				<td>".$row['PriceZ']."</td>
				<td>".($row['qty']*$row['PriceZ'])."</td>
				</tr>";
				$itogn1=$itogn1+$row['qty'];
				$itog1=$itog1+$row['qty']*$row['PriceZ'];
			}
		}
	}
	$html.="</table>";
	$html.="<p>ИТОГО: {$itogn1}шт. {$itog1}<p>";
	$html.="<p><b>ИТОГО: ".($itogn1+$itogn)."шт. ".($itog+$itog1)."</b></p>";
	return $html;
}

// запись продаж в таблицу Waybills_Goodssale 
function getWaybillsGoodsSale($incoming) {
	$arrdate=array();
	$arritem=array();
	$tmp="";
	$cdte=date("Y-m-d H:i:s");
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			$tmp.=" AND Waybills.created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	$tmp.=((strlen($tmp)>0) ? " AND " : "")." vendor_id=".intval($incoming['supplier']);
	// get first waybill for selling
	$sql="SELECT * FROM Waybills 
		WHERE payment_id=3 AND status_id=2 AND type_id=2 {$tmp}
		ORDER BY id ASC"; 
	//	LIMIT 0,1"; // ограничение накладной тест
	echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$sql1="SELECT * FROM Waybills_goods WHERE waybill_id=".$row['id']. " ORDER BY id ASC";// LIMIT 0,20"; // ограничение позиции накладной тест
			$QTY=-1;
			$wbpos=0;
			echo $sql1."<br>";
			// $row1 - waybill position 
			if ($result1=mysql_query($sql1)) {
				while ($row1=mysql_fetch_array($result1)) {
					$QTY=$row1['qty']-$row1['sales']; // открытое количество товара в накладной 
					$wbpos=$row1['id']; // анализируемая позиция
					
					if ($QTY>0) {
						// search online orders
						
						echo "открытое количество товара в накладной ".$QTY."<br>";
						
						//$sql2="SELECT Message51.Message_ID,Netshop_OrderGoods.* FROM Message51 
						//INNER JOIN Netshop_OrderGoods ON (Netshop_OrderGoods.Order_ID=Message51.Message_ID)
						//WHERE Message51.wroff=1 AND (Message51.wroffdate BETWEEN '".date("Y-m-d H:i:s",strtotime($startdate))."' AND '{$cdte}') 
						//AND Netshop_OrderGoods.Item_ID={$row1['item_id']} AND Netshop_OrderGoods.Qty>Netshop_OrderGoods.sales";
						$sql2="SELECT * FROM Netshop_OrderGoods WHERE Item_ID={$row1['item_id']} AND closed=0 AND Qty>sales ";
						
						echo $sql2."<br><br>";
						$rdte="0000-00-00 00:00:00";
						if ($result2=mysql_query($sql2)) {
							while($row2=mysql_fetch_array($result2)) {
								print_r($row2);
								echo "<br>";
								$sql4="SELECT * FROM Message51 WHERE Message_ID=".$row2['Order_ID']." AND wroff=1 ";
								$rdte="0000-00-00 00:00:00";
								if ($result4=mysql_query($sql4)) {
									while($row4=mysql_fetch_array($result4)) {
										$rdte=$row4['wroffdate']; 
									}
								}
								echo "----".$sql4."<br>";
								echo $rdte." ".$QTY."<br>";
								if ($QTY>0) {
									if (strtotime($rdte)>strtotime($startdate)) {
										$order_id=$row2['Order_ID'];
										$item_id=$row1['item_id'];
										if ($row2['sales']<$row2['Qty']) {
											if ($QTY>=$row2['Qty']) {
												$sql="INSERT INTO Waybills_selling (item_id,waybill_id,order_id,retail_id,qty,created)
													VALUES ({$item_id},{$row['id']},".$order_id.",0,{$row2['Qty']},'".date("Y-m-d H:i:s")."')";
												echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
												// закрываем позицию заказа
												$sql="UPDATE Netshop_OrderGoods SET sales={$row2['Qty']},closed=1 WHERE Order_ID={$order_id} AND Item_ID={$item_id}";
												//echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
												// закрываем позицию накладной
												$sql="UPDATE Waybills_goods SET sales=sales+{$row2['Qty']} 
													WHERE id={$row1['id']}";
												//echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
												$QTY=$QTY-$row2['Qty'];
												echo "!o!!!!".$QTY."<br>";
											}
										}
									}
								}
							}
						}
					
						// search in retails
						
						echo "открытое количество товара в накладной ".$QTY."<br>";
						
						$sql3="SELECT Retails_goods.* FROM Retails_goods			
							WHERE closed=0 AND deleted=0 
								AND item_id={$row1['item_id']}
								AND qty>sales ORDER BY id ASC";
						echo $sql3."<br><br>";
						//LEFT JOIN Retails_goods ON (Retails.id=Retails_goods.item_id)
						// (Retails.created BETWEEN '".date("Y-m-d H:i:s",strtotime($startdate))."' AND '{$cdte}') 
						
						//AND Retails.created >= '".date("Y-m-d H:i:s",strtotime($startdate))."'
						if ($result3=mysql_query($sql3)) {
							while($row3=mysql_fetch_array($result3)) {
								print_r($row3);
								echo "<br>";
								$sql4="SELECT * FROM Retails WHERE id=".$row3['retail_id'];
								if ($result4=mysql_query($sql4)) {
									while($row4=mysql_fetch_array($result4)) {
										$rdte=$row4['created'];
									}
								}
								echo $rdte." ".$QTY."<br>";
								if ($QTY>0) {
									if (strtotime($rdte)>strtotime($startdate)) {
										$retail_id=$row3['retail_id'];
										$item_id=$row1['item_id'];
										if ($row3['sales']<$row3['qty']) {
											echo $row3['qty']."<br>";
											//echo ($row1['qty']-$row1['sales'])."<br>";
											if ($QTY>=$row3['qty']) {
												$sql="INSERT INTO Waybills_selling (item_id,waybill_id,order_id,retail_id,qty,created)
													VALUES ({$item_id},{$row['id']},0,".$retail_id.",{$row3['qty']},'".date("Y-m-d H:i:s")."')";
												echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
												// закрываем позицию заказа
												$sql="UPDATE Retails_goods SET sales={$row3['qty']},closed=1 WHERE retail_id={$retail_id} AND item_id={$item_id}";
												//echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
												// закрываем позицию накладной
												$sql="UPDATE Waybills_goods SET sales=sales+{$row3['qty']} 
													WHERE id={$row1['id']}";
												//echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
											}
											
											$QTY=$QTY-$row3['qty'];
											echo $QTY."!!!!<br><br>";
										}
									}
								}
							}
						}
						echo "<br>";
					}
				
					
				}
			}
			
			
					
			
		}
	}

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
		/*case "save":
			getWaybillsGoodsSale($incoming);
			$html.="<p style='color:#00f300;font-weight:bold;'>Списания пересчитаны</p>";
			$html.=getWaybillsRe($incoming);
			break;*/
		default:
			// заполняем Waybills_goodssale
			//getWaybillsGoodsSale();
			
			$html.=getSelling($incoming);
			$html.=getSold($incoming);
			
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
	<h1>Списания по накладным</h1>
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

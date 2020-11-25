<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();

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
	
	//Всего по накладной:
	$itog=0;
	//Продано:
	$itogsold=0;
	//Остатки:
	$itogrest=0;
	//Брак-возврат
	$itogbrak=0;
	// Расход по учету
	$itograshod=0;
	
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
			$resi=printWbCart($row['id']);
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
			$itograshod=$itograshod+$resi[5];
		}
	}
	$html.="</table>";
	sort($lastdate);
	//print_r($lastdate);
	$html=(($lastdate) ? "<p>Последнее списание <b>".date("d.m.Y H:i:s",strtotime($lastdate[count($lastdate)-1]))."</b></p>" : "").$html;
	$html.="<p>Всего по накладным:{$itog}</p>
	<p>Продано:{$itogsold}</p>
	<p>Остатки:{$itogrest}</p>
	<p>Брак-возврат:{$itogbrak}</p>
	<p>Расход по учету:{$itograshod}</p>";
	return $html;
}
function findWaybillPosition($order_id,$retail_id,$item_id,$qty,$supplier) {
	echo "findWaybillPosition {$order_id},{$retail_id},{$item_id},{$qty}<br>";
	
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=".intval($supplier);
	$startdate="0000-00-00 00:00:00";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			$tmp.=" AND Waybills.created BETWEEN '".$startdate."' AND '".date("Y-m-d H:i:s")."'";
		}
	}
	
	
	
	$sql="SELECT Waybills_goods.* FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
		WHERE vendor_id={$supplier} AND payment_id=3 AND status_id=2 AND type_id=2 AND item_id={$item_id} AND Waybills_goods.qty>(Waybills_goods.sales + Waybills_goods.defect)
		 {$tmp} LIMIT 1";
	//if ($item_id==5576) {
	//echo $sql."<br>";
	//}
	//echo str_replace("<","&lt;",$sql)."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			if ($row['qty']>=($qty+$row['sales'])) {
				// сохраняем в историю
				echo "<br>списываем {$row['item_id']} ".$qty."-продано ".$row['sales']."- всего в накл.".$row['qty']."<br>";
				// эта позиция уже оплачена. Для Жбанова!
				
				$payment=0;
				/*if ($supplier==18) {
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
				}*/
				//echo $payment."<br>";
				if ($payment==0) {
					$sql="INSERT INTO Waybills_selling (item_id,waybill_id,order_id,retail_id,qty,created)
						VALUES ({$item_id},{$row['waybill_id']},".(($order_id) ? $order_id : "0").",".(($retail_id) ? $retail_id : "0").",{$qty},'".date("Y-m-d H:i:s")."')";
					echo $sql."<br>";
					if (!mysql_query($sql)) {
						die($sql."<br>Error: ".mysql_error());
					}
					if ($order_id) {
						// закрываем позицию заказа
						$sql="UPDATE Netshop_OrderGoods SET sales={$qty},closed=1 WHERE Order_ID={$order_id} AND Item_ID={$item_id}";
						echo $sql."<br>";
						if (!mysql_query($sql)) {
							die($sql."<br>Error: ".mysql_error());
						}
					}
					if ($retail_id) {
						// закрываем позицию заказа
						$sql="UPDATE Retails_goods SET sales={$qty},closed=1 WHERE retail_id={$retail_id} AND Item_ID={$item_id}";
						echo $sql."<br>";
						if (!mysql_query($sql)) {
							die($sql."<br>Error: ".mysql_error());
						}
					}
					// закрываем позицию накладной
					$sql="UPDATE Waybills_goods SET sales=sales+{$qty} 
						WHERE id={$row['id']}";
					echo $sql."<br>";
					if (!mysql_query($sql)) {
						die($sql."<br>Error: ".mysql_error());
					}
					echo "<br><br>";
				}
			}
		}
	} 
	return 0;
}

// поиск продаж по поставщику.
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
	echo $startdate."<br>";
	//$startdate="2015-05-04 00:00:00";
	$j=0;
	$str="";
	//if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
	//	$str=" AND supplier=".intval($incoming['supplier']);
	//}
	// online
	/*$sql="SELECT Netshop_OrderGoods.*,Message51.Created,Message51.wroffdate FROM Netshop_OrderGoods 
			INNER JOIN Message51 ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID)  
			WHERE Netshop_OrderGoods.closed=0 AND 
			Message51.wroff=1 AND 
			Message51.wroffdate>='".date("Y-m-d H:i:s",strtotime($startdate))."' 
			ORDER BY Netshop_OrderGoods.Order_ID ASC";*/
	$sql="SELECT Netshop_OrderGoods.*,Message51.wroffdate FROM Netshop_OrderGoods 
			INNER JOIN Message51 ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID)  
			INNER JOIN Message57 ON (Message57.Message_ID=Netshop_OrderGoods.Item_ID)
			WHERE Netshop_OrderGoods.closed=0 AND 
			Message51.wroff=1  AND Message57.supplier={$incoming['supplier']}
			AND Message51.wroffdate BETWEEN '".date("Y-m-d H:i:s",strtotime($startdate))."' AND '".date("Y-m-d H:i:s")."'
			ORDER BY Netshop_OrderGoods.Order_ID ASC";
	echo $sql."<br>";
	$wrdte="0000-00-00 00:00:00";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$wrdte="0000-00-00 00:00:00";
			$wrdte=$row['wroffdate'];
			if (strtotime($wrdte)>strtotime($startdate)) {
				if ($row["closed"]==0) { // позиция не закрыта
					$arrdate[$j]=$row['wroffdate'];
					$arritem[$j]=$row["Order_ID"].":0:".$row["Item_ID"].":".$row["Qty"];
					$j=$j+1;
				}
			}
		}
	}
	// retail
	$sql="SELECT Retails_goods.*,Retails.created FROM Retails_goods 
			INNER JOIN Retails ON (Retails.id=Retails_goods.retail_id) 
			INNER JOIN Message57 ON (Message57.Message_ID=Retails_goods.item_id) 
			WHERE Retails_goods.closed=0 AND Retails_goods.deleted=0  
			AND Message57.supplier={$incoming['supplier']}
			AND Retails.created  BETWEEN '".date("Y-m-d H:i:s",strtotime($startdate))."' AND '".date("Y-m-d H:i:s")."'
			ORDER BY Retails.id ASC";
	echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$wrdte="0000-00-00 00:00:00";
			$wrdte=$row['created'];
			if (strtotime($wrdte)>strtotime($startdate)) {
				if ($row["closed"]==0) { // позиция не закрыта
					$arrdate[$j]=$row['created'];
					$arritem[$j]="0:{$row['retail_id']}:".$row["item_id"].":".$row["qty"];
					//echo $arrdate[$j]."||".$arritem[$j]."<br>";
					$j=$j+1;
				}
			}
		}
	}
	asort($arrdate);
	//print_r($arrdate);
	//echo "<br><br>";
	//print_r($arritem);
	$j=1;
	foreach ($arrdate as $key => $val) {
		$tmp=explode(":",$arritem[$key]);
		echo $j.") ";
		findWaybillPosition($tmp[0],$tmp[1],$tmp[2],$tmp[3],$incoming['supplier']); 
		$j=$j+1;
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
			$html.="
			<a href='/netcat/modules/netshop/interface/selling-brak.php?supplier={$incoming['supplier']}'>Пересчитать брак</a><br><br>
			<form name='frm1' action='".$_SERVER["REQUEST_URI"]."' method='post'>
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
	<h1>Списания по накладным</h1>
	<p>v3.2 <?php echo date("d.m.Y H:i:s",filemtime("selling.php")); ?> новый возврат/брак</p>
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

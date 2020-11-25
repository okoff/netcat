<?php
// 27.01.2017 Elen
// пересчет брака и возврата
include_once ("../../../../vars.inc.php");
session_start();


function printWbCartBrak($id) {
	$resi=array();

	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:760px;'>
	<tr><td>Артикул</td><td>Название</td><td>Цена закуп.</td><td>Кол.</td><td>На&nbsp;складе<br>сейчас</td><td>Продано</td><td>Оплачено</td><td>Возвращено</td><td>Расход по учету</td></tr>";
	
	$sql="SELECT Waybills_goods.*, Message57.StockUnits,Message57.ItemID AS ItemID, Message57.Name AS Name,Waybills.created AS wb_created FROM Waybills_goods 
		INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		INNER JOIN Waybills ON (Waybills_goods.waybill_id=Waybills.id)
		WHERE waybill_id=".$id." ORDER BY Waybills_goods.id ASC";
	//echo $sql."<br>";
	$itog=$itogsold=$itogrest=$itogbrak=0;
	if ($result=mysql_query($sql)) {
		while ($row = mysql_fetch_array($result)) {
			$item_id=$row['item_id'];
			//echo "return ".$row['item_id']."<br>";
			searchInReturn($row['id'],$item_id,$row['wb_created']);

			//echo "brak ".$item_id."<br>";
			searchInBrak($row['id'],$item_id,$row['wb_created']);

			$sql1="SELECT * FROM Waybills_goods WHERE id=".$row['id'];
			if ($result1=mysql_query($sql1)) {
				while ($row1 = mysql_fetch_array($result1)) {
					$style="";
					if ($row1['sales']!=0) {
						$style="background:#FFFF99;";
					}
					if ($row1['paid']==$row1['qty']) {
						$style="background:#FF99FF;";
					} 
					if($row1['defect']!=0) {
						$style="background:#9999FF;";
					}
					$res.="<tr><td width='50' style='font-size:8pt;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['item_id']}'>{$row['ItemID']}</a></td>
						<td style='width:500px;font-size:8pt;{$style}'>{$row['Name']}</td>
						<td width='50' style='font-size:8pt;{$style}'>".($row['originalprice']*$row['qty'])."</td>
						<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['qty']}</td>
						<td width='50' style='font-size:8pt;text-align:center;{$style}'>{$row['StockUnits']}</td>
						<td width='30' style='font-size:8pt;text-align:center;{$style}'><a target='_blank' href='/netcat/modules/netshop/interface/selling-preview.php?wb={$row['waybill_id']}&item={$row['item_id']}'>{$row['sales']}</a></td>
						<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$row1['paid']}</td>
						<td width='30' style='font-size:8pt;text-align:center;{$style}'>{$row1['defect']}&nbsp;".
								(($row1['defectwb_id']!=0) ? "[".$row1['defectwb_id']."]" : ""). "</td>
						<td width='30' style='font-size:8pt;text-align:center;{$style}'>&nbsp;</td>
						</tr>";
					$itog=$itog+$row['originalprice']*$row['qty'];
					$itogsold=$itogsold+$row['originalprice']*$row['sales'];
					$itogrest=$itogrest+$row['originalprice']*($row['qty']-$row['sales']-$row['defect']);
					$itogbrak=$itogbrak+$row['originalprice']*$row['defect'];
				}
			}
				
		}
	}
	$res.="</table>
	<p>Всего по накладной:{$itog}</p>
	<p>Продано:{$itogsold}</p>
	<p>Остатки:{$itogrest}</p>
	<p>Брак-возврат-расход по учету:{$itogbrak}</p>";
	//return $res;
	$resi=array(0=>$res,
				1=>$itog,
				2=>$itogsold,
				3=>$itogrest,
				4=>$itogbrak);
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

function searchInReturn($id,$item_id,$created) {
	$res=0;
	$sql="SELECT Startdate FROM Classificator_Supplier
		INNER JOIN Message57 ON (Message57.supplier=Classificator_Supplier.Supplier_ID)
		WHERE Message57.Message_ID=".intval($item_id);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	//echo $sql."<br>";
	// накладная возврата возврат поставщику
	$sql="SELECT Waybills_goods.id,Waybills_goods.waybill_id,Waybills_goods.qty AS qty, Waybills_goods.sales AS sales,Waybills.created FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
		WHERE Waybills.type_id=7 AND Waybills.status_id=2 AND Waybills.created BETWEEN '".$startdate."' AND '".date("Y-m-d H:i:s")."'
			AND Waybills_goods.item_id={$item_id} AND Waybills_goods.qty>Waybills_goods.sales";
	//echo $sql."<br><br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			//$res=$row['qty']-$row['sales'];
			//echo "defect5:".$row['created'].":".$created."<br>";
			if (strtotime($row['created'])>strtotime($created)) {
				//echo $id."-".$row['id']."<br>";
				$res=$row['qty']-$row['sales']; // открытые позиции в накладной возврата
				echo "открытые позиции {$item_id} в накладной возврата {$row['waybill_id']} {$row['qty']}-{$row['sales']}: ".$res."<br>";
				if ($res>0) {
					$sql1="SELECT * FROM Waybills_goods WHERE id=".$id;
					//echo $sql1."<br>";
					if ($result1=mysql_query($sql1)) {
						$row1=mysql_fetch_array($result1);
						$n=$row1['qty']-$row1['sales']-$row1['defect']; // открытые позиции в накладной реализации
						echo "открытые позиции в накладной реализации {$row1['waybill_id']}: ".$n."<br>";
					
						// если есть открытый брак и открытая позиция накладной, списываем и закрываем
						if ($n>=$res) {
							// накладная реализация
							$usql1="UPDATE Waybills_goods SET 
								defect=".($row1["defect"]+$res).",defectwb_id={$row['waybill_id']}
								WHERE id=".$id;
							if (!mysql_query($usql1)) {
								die($usql1."<br>Error: ".mysql_error());
							}
							echo "вносим брак {$res}шт. <br><br>";
							
							// накладная брака
							$usql2="UPDATE Waybills_goods SET
									sales=".($row['sales']+$res)." 
								WHERE id={$row['id']}";
							if (!mysql_query($usql2)) {
								die($usql2."<br>Error: ".mysql_error());
							}
							//echo $usql2."<br>";
						}
					}
				}
			}
		}
	}
	return $res;
}

// @id - номер искомой позиции в накладной списания
function searchInBrak($id,$item_id,$created) {
	$res=0;
	$sql="SELECT Startdate FROM Classificator_Supplier
		INNER JOIN Message57 ON (Message57.supplier=Classificator_Supplier.Supplier_ID)
		WHERE Message57.Message_ID=".intval($item_id);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
		}
	}
	//echo $startdate."<br>";
	//echo "<!--";
	// поиск товара в накладных 
	$sql="SELECT Waybills_goods.id,Waybills_goods.waybill_id,Waybills_goods.qty AS qty, Waybills_goods.sales AS sales,Waybills.created FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
		WHERE Waybills.type_id=5 AND Waybills.status_id=2 AND Waybills.created BETWEEN '".$startdate."' AND '".date("Y-m-d H:i:s")."'
			AND Waybills_goods.item_id={$item_id} AND Waybills_goods.qty>Waybills_goods.sales";
	//if ($item_id==7922) {
	//echo $sql."<br><br>";
	//}
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			//echo "defect".$row['wb_created'].":".$created."<br>";
			//if (strtotime($row['created'])>strtotime($created)) {
				//echo $id."-".$row['id']."-".$item_id."\n";
				$res=$row['qty']-$row['sales']; // открытые позиции в накладной брака 
				echo "открытые позиции в накладной брака: ".$res."<br>";
				if ($res>0) {
					$sql1="SELECT * FROM Waybills_goods WHERE id=".$id;
					//echo $sql1."<br>";
					if ($result1=mysql_query($sql1)) {
						$row1=mysql_fetch_array($result1);
						$n=$row1['qty']-$row1['sales']-$row1['defect']; // открытые позиции в накладной реализации
						echo "открытые позиции в накладной реализации: ".$n."<br>";
					
						// если есть открытый брак и открытая позиция накладной, списываем и закрываем
						if ($n>=$res) {
							// накладная реализация
							$usql1="UPDATE Waybills_goods SET 
								defect=".($row1["defect"]+$res).",defectwb_id={$row['waybill_id']}
								WHERE id=".$id;
							if (!mysql_query($usql1)) {
								die($usql1."<br>Error: ".mysql_error());
							}
							echo "вносим брак {$res}шт.<br><br>";
							
							// накладная брака
							$usql2="UPDATE Waybills_goods SET
								sales=".($row['sales']+$res)." 
								WHERE id={$row['id']}";
							if (!mysql_query($usql2)) {
								die($usql2."<br>Error: ".mysql_error());
							}
							//echo $usql2."<br>";
						}
					}
				}
			//}
		}
	}
	//echo "-->\n";
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
			$resi=printWbCartBrak($row['id']);
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

?>
	<h1>Списания по накладным. Брак-возврат.</h1>
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
	<?=$html?>
	<p><a href="/netcat/modules/netshop/interface/selling.php?supplier=<?=$incoming['supplier']?>">Просмотр списаний</a></p>
<?php	
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
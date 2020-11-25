<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();

/*2	от производителя
3	возврат рознич. продажи
4	возврат онлайн продажи
5	брак отложено
6	брак возвращено поставщику
7	возврат поставщику
8	учёт
9	приход по учёту
10	расход по учёту
11	остатки на сайте*/
function checkIncoming($id) {
	$html=$rtn="";
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=18"; //.intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			//$tmp.=" AND created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	//echo $startdate."<br>";
	$wbtype=array(	2=>"от производителя",
					3=>	"возврат рознич. продажи",
					4=>	"возврат онлайн продажи",
					5=>	"брак отложено",
					6=>	"брак возвращено поставщику",
					7=>	"возврат поставщику",
					8=>	"учёт",
					9=>	"приход по учёту",
					10=>	"расход по учёту",
					11=>	"остатки на сайте");
	$sql="SELECT Waybills_goods.*,Waybills.type_id,Waybills.created FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
	WHERE item_id=".$id." ORDER BY Waybills.id ASC ";
	//echo $sql."<br>";
	//$html.=$sql;
	$html1="<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td style='font-size:10px;'>дата создания</td>
		<td style='font-size:10px;width:30px;'>#</td>
		<td style='font-size:10px;'>тип</td>
		<td style='font-size:10px;'>кол.</td>
		<td style='font-size:10px;'>продажи</td>
		<td style='font-size:10px;'>брак</td>
		<td style='font-size:10px;'># брака</td>
		<td style='font-size:10px;'>оплачено</td>
		<td style='font-size:10px;'>списания</td>
	</tr>";

	if ($result=mysql_query($sql)) {	
		
		while ($row=mysql_fetch_array($result)) {
			if (strtotime($row['created'])>strtotime($startdate)) {
				if ($row['type_id']==2) {
					$tmp="";
					if ($row['sales']>0) {
						$strord=getStrOrders($id);
						$strret=getStrRetails($id);
						$tmp=checkSelling($row['waybill_id'],$id,$strord,$strret);
					}
					$html.="<tr>
					<td style='font-size:10px;'>".date("d.m.Y",strtotime($row['created']))."</td>
					<td style='font-size:10px;'>".$row['waybill_id']."</td>
					<td style='font-size:10px;'>".$wbtype[$row['type_id']]."</td>
					<td style='font-size:10px;'>".$row['qty']."</td>
					<td style='font-size:10px;'>".$row['sales']."</td>
					<td style='font-size:10px;'>".$row['defect']."</td>
					<td style='font-size:10px;'>".$row['defectwb_id']."</td>
					<td style='font-size:10px;'><b>".$row['paid']."</b></td>
					<td style='font-size:10px;'>".$tmp."</td>";
					$html.="</tr>";

				}
			}
			
		}
	}
	if ($html!="") {
		$rtn=$html1.$html."</table>";
	}
	return $rtn;
}

function checkSelling($wbid,$id,$strord,$strret) {
	$html="";
	
	$sql="SELECT Waybills_selling.*,Waybills_paid.transfer_id FROM Waybills_selling 
			LEFT JOIN Waybills_paid ON (Waybills_paid.id=Waybills_selling.payment_id)
			WHERE Waybills_selling.waybill_id={$wbid} AND Waybills_selling.item_id={$id} 
		ORDER BY Waybills_selling.id ASC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$style="";
			if (($row['order_id']!=0)&&(substr_count($strord,$row['order_id'])>1)) {
				$style="style='background:#ff9999;'";
			}
			if (($row['retail_id']!=0)&&(substr_count($strret,"".$row['retail_id'])>1)) {
				$style="style='background:#ff9999;'";
			}
			$html.="<p {$style}> ".date("d.m.Y",strtotime($row['created']))." ".$row['qty']."шт. ".$row['order_id']."/".$row['retail_id']."  
				оплата <a target='_blank' href='/netcat/modules/netshop/interface/selling-transfer.php?n=".$row['transfer_id']."'>".$row['transfer_id']."</a> (".$row['payment_id'].")</p>";
			
		}
	}
	
	return $html;
}

function getStrOrders($id) {
	$html="";
	$strord=";";
	$strret=";";
	
	$sql="SELECT * FROM Waybills_selling WHERE item_id={$id} ORDER BY id ASC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$strord.=(($row['order_id']!=0) ? $row['order_id'].";" : "");
			$strret.=(($row['retail_id']!=0) ? $row['retail_id'].";" : "");
		}
	}
	
	return $strord;
}

function getStrRetails($id) {
	$html="";
	$strord=";";
	$strret=";";
	
	$sql="SELECT * FROM Waybills_selling WHERE item_id={$id} ORDER BY id ASC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$strord.=(($row['order_id']!=0) ? $row['order_id'].";" : "");
			$strret.=(($row['retail_id']!=0) ? $row['retail_id'].";" : "");
		}
	}
	
	return $strret;
}
function getFullIncoming($id) {
	$ret=array();
	$ret[0]=0;
	$ret[1]=0;
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=18"; //.intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			//$tmp.=" AND created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	$sql="SELECT Waybills_goods.*,Waybills.type_id,Waybills.created FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
	WHERE Waybills_goods.item_id=".$id." AND type_id=2 AND status_id=2 ORDER BY Waybills.id ASC ";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {	
		
		while ($row=mysql_fetch_array($result)) {
			if (strtotime($row['created'])>strtotime($startdate)) {
				$ret[0]=$ret[0]+$row['qty'];
				$ret[1]=$ret[1]+$row['sales']+$row['defect'];
			}
		}
	}
	return $ret;
}
// поиск в расходе по учету
function getItemInRashod($id) {
	$ret=0;
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=18"; //.intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			//$tmp.=" AND created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	$sql="SELECT Waybills_goods.*,Waybills.type_id,Waybills.created FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
	WHERE Waybills_goods.item_id=".$id." AND type_id=10 AND status_id=2 ORDER BY Waybills.id ASC ";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {	
		
		while ($row=mysql_fetch_array($result)) {
			if (strtotime($row['created'])>strtotime($startdate)) {
				$ret=$ret+$row['qty'];
			}
		}
	}
	return $ret;
}
// поиск в приходе по учету
function getItemInPrihod($id) {
	$ret=0;
	$sql="SELECT Startdate FROM Classificator_Supplier WHERE Supplier_ID=18"; //.intval($incoming['supplier']);
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$startdate=$row['Startdate'];
			//$tmp.=" AND created>'".date("Y-m-d H:i:s", strtotime($startdate))."'";
		}
	}
	$sql="SELECT Waybills_goods.*,Waybills.type_id,Waybills.created FROM Waybills_goods 
		INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id)
	WHERE Waybills_goods.item_id=".$id." AND type_id=9 AND status_id=2 AND not Waybills.id=591 ORDER BY Waybills.id ASC ";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {	
		
		while ($row=mysql_fetch_array($result)) {
			if (strtotime($row['created'])>strtotime($startdate)) {
				$ret=$ret+$row['qty'];
			}
		}
	}
	return $ret;
}
function getItems($incoming) {
	$html="";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>#</td>
	<td>Артикул</td>
	<td>Название</td>
	<td>На складе</td>
	<td>Пришло всего</td>
	<td>Списано всего</td>
	<td>Списано учет</td>
	<td>Приход учет</td>
	<td>Цена</td>
	<td>-</td>
	</tr>";
	$itogon=$itogo=0;
	$sql="SELECT Message57.*,Message57_p.Price AS PriceZ FROM Message57 
		LEFT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
	WHERE Vendor=".intval($incoming['supplier'])." ORDER BY Message57.Message_ID ASC ";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$itg=getFullIncoming($row['Message_ID']);
			$uch=getItemInRashod($row['Message_ID']);
			$uchinc=getItemInPrihod($row['Message_ID']);
			$style="";
			if (($itg[0]-$itg[1]-$uch+$uchinc)!=$row['StockUnits']) {
				$style="background:#ff9999;";
			}
			$html.="<tr><td>".$row['Message_ID']."</td>";
			$html.="<td><b><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id=".$row['Message_ID']."'>".$row['ItemID']."</a></b></td>";
			$html.="<td style='width:300px;'>".$row['Name']."</td>";
			$html.="<td style='{$style}'><b>".$row['StockUnits']."</b></td>";
			$html.="<td style='{$style}'><b>".$itg[0]."</b></td>";
			$html.="<td style='{$style}'><b>".$itg[1]."</b></td>";
			$html.="<td style='{$style}'><b>".$uch."</b></td>";
			$html.="<td style='{$style}'><b>".$uchinc."</b></td>";
			$html.="<td><b>".$row['PriceZ']."</b></td>";
			$itogon=$itogon+$row['StockUnits'];
			$itogo=$itogo+$row['StockUnits']*$row['PriceZ'];
			//if ($row['StockUnits']>0) {
				$html.="<td style='font-size:10px;'>".checkIncoming($row['Message_ID'])."</td>";
				
			//} else {
			//	$html.="<td>&nbsp;</td>";
			//}
			$html.="</tr>";
		}
	}
	$html.="</table>";
	$html.="<p>Всего {$itogon} на {$itogo}</p>";
	return $html;
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
			
			$html.=getItems($incoming);
			//$html.=getSold($incoming);
			
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

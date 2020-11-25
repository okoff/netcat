<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
	
function showListFiles() {
	$res="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Netshop_PostHistoryFiles ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<tr><td>".$row["id"]."</td>
		<td>".date("d.m.Y", strtotime($row["created"]))."</td>
		<td>{$row['name']}</td>
		<td>".(($row['checked']==1) ? "<a href='/netcat/modules/netshop/interface/order-post-history.php?action=read&fid={$row['id']}'>результат</a>" : "<a href='/netcat/modules/netshop/interface/order-post-history.php?action=process&fid={$row['id']}&f={$row['name']}'>начать обработку файла</a>")."</td>
		</tr>";
	}
	$res.="</table>";
	return $res;
}	

function showStatisticForVendor($sid,$min,$max, $itog) {
	$res="";
	$all=0;	
	$numstr="";
	$sql="SELECT Retails_goods.*,Message57_p.Price AS PriceZ,Message57.complect  FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Retails_goods.deleted=0
			AND Message57.supplier={$sid}
			ORDER BY Retails_goods.id DESC";
	//echo $sql;
	$r=mysql_query($sql);
	$retail=$retailz=0;
	while ($row1=mysql_fetch_array($r)) {
		$pricez=0;
		if ($row1['complect']!="") {
			$tmp=explode(";",$row1['complect']);
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
		} else {
			$pricez=$row1['PriceZ'];
		}
		$numstr.=$row1['retail_id']."p ";
		$retail=$retail+$row1['qty']*$row1['itemprice'];
		$retailz=$retailz+$row1['qty']*$pricez;
		$all=$all+1;
	}
	$sql="SELECT Netshop_OrderGoods.*,Message57_p.Price AS PriceZ,Message57.complect FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Message57.supplier={$sid} AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2
			ORDER BY Netshop_OrderGoods.Order_ID DESC";
	//echo $sql;
	$r1=mysql_query($sql);
	$netshop=$netshopz=0;
	while ($row1=mysql_fetch_array($r1)) {
		if ($row1['complect']!="") {
			$tmp=explode(";",$row1['complect']);
			foreach($tmp as $t) {
				$tmp1=explode(":",$t);
				$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
					INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
					WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
				$r2=mysql_query($sql2);
				while ($row2=mysql_fetch_array($r2)) {
					$netshopz=$netshopz+$tmp1[1]*$row2['PriceZ'];
				}
			}
			$netshop=$netshop+$row1['Qty']*$row1['ItemPrice'];
		} else {
			$netshop=$netshop+$row1['Qty']*$row1['ItemPrice'];
			$netshopz=$netshopz+$row1['Qty']*$row1['PriceZ'];
		}
		$numstr.=$row1['Order_ID']."i ";
		$all=$all+1;
	}
	
	//$all=mysql_num_rows($r)+mysql_num_rows($r1);
	$allm=$retail+$netshop;
	$allmz=$retailz+$netshopz;
	$res.="<td style='text-align:right;'>".number_format($all,0,'.','')." </td>
			<td style='text-align:right;'>".number_format($allm,2,'.','')."</td>
			<td style='text-align:right;'>".number_format($allmz,2,'.','')."</td>
			<td style='text-align:right;'>".number_format(($allm-$allmz),2,'.','')."</td>
			<td style='text-align:right;'>".number_format(round($allm/$all, 0),0,'.','')."</td>
			<td style='text-align:right;'>".number_format(round($allm/$itog*100, 2),2,'.','')."</td>";
	return $res;
}
function showResidueForVendor($sid,$itog) {
	$res="";
	$sql="SELECT Message57.Price,Message57.StockUnits, Message57_p.Price AS PriceZ,Message57.complect FROM Message57 
			LEFT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
			WHERE Message57.supplier={$sid} AND Message57.Checked=1 AND Message57.StockUnits<>0 AND Message57.status=2
			ORDER BY Message57.Message_ID ASC";
	$r=mysql_query($sql);
	$allm=$all=$allmz=0;
	while ($row1=mysql_fetch_array($r)) {
		if ($row1['complect']=="") {
			$allm=$allm+$row1['StockUnits']*$row1['Price'];
			$allmz=$allmz+$row1['StockUnits']*$row1['PriceZ'];
			$all=$all+$row1['StockUnits'];
		}
	}
	$res.="<td style='text-align:right;'>".number_format($all,0,'.','')."</td>
			<td style='text-align:right;'>".number_format($allm,2,'.','')."</td>
			<td style='text-align:right;'>".number_format($allmz,2,'.','')."</td>
			<td style='text-align:right;'>".number_format(round($allm/$itog*100, 2),2,'.','')."</td>
			<td style='text-align:right;'>".(($allmz!=0) ? number_format(round($allm/$allmz*100-100, 2),2,'.','') : "0.0")."</td>";
	return $res;
}

function getFullPrice($min, $max) {
	$res=0;
	$resz=0;
	$all=0;
	$sql="SELECT * FROM Classificator_Supplier WHERE Statistic=1 ORDER BY Supplier_Name ASC";
	//$sql="SELECT * FROM Classificator_Supplier ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//$res.=showStatisticForVendor($row['Supplier_ID'], $incoming['min'],$incoming['max'], $itog);
		$sql="SELECT Retails_goods.*,Message57_p.Price AS PriceZ,Message57.complect FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			INNER JOIN Message57_p ON ( Retails_goods.item_id = Message57_p.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Message57.supplier={$row['Supplier_ID']}
			ORDER BY Retails_goods.id DESC";
		//echo $sql;
		$r=mysql_query($sql);
		while ($row1=mysql_fetch_array($r)) {
			/*if ($row1['complect']!="") {
				//echo $row1['complect'];
				
				$tmp=explode(";",$row1['complect']);
				foreach($tmp as $t) {
					$tmp1=explode(":",$t);
					$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
						INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
						WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
					//echo $sql2."<br>";
					$r2=mysql_query($sql2);
					while ($row2=mysql_fetch_array($r2)) {
						$resz=$resz+$tmp1[1]*$row2['PriceZ'];
					}
				}
				//$pricez=$pricez+$row1['Qty']*$row1['ItemPrice'];
				//$fullpricez=$fullpricez+$row1['Qty']*$row1['ItemPrice'];
			} else {
				$resz=$resz+$row1['qty']*$row1['PriceZ'];
			}
			$res=$res+$row1['qty']*$row1['itemprice'];
			$resz=$resz+$row1['qty']*$row1['PriceZ'];*/
			$pricez=0;
			if ($row1['complect']!="") {
				$tmp=explode(";",$row1['complect']);
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
			} else {
				$pricez=$row1['PriceZ'];
			}
			$numstr.=$row1['retail_id']."p ";
			$retail=$retail+$row1['qty']*$row1['itemprice'];
			$retailz=$retailz+$row1['qty']*$pricez;
			$all=$all+1;
		}
		$sql="SELECT Netshop_OrderGoods.*,Message57_p.Price AS PriceZ FROM Netshop_OrderGoods 
				INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
				INNER JOIN Message57_p ON ( Netshop_OrderGoods.Item_ID = Message57_p.Message_ID )
				INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
				WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
				AND Message57.supplier={$row['Supplier_ID']} AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2
				ORDER BY Netshop_OrderGoods.Order_ID DESC";
		//echo $sql;
		$r1=mysql_query($sql);
		while ($row1=mysql_fetch_array($r1)) {
			/*if ($row1['complect']!="") {
				$tmp=explode(";",$row1['complect']);
				foreach($tmp as $t) {
					$tmp1=explode(":",$t);
					$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
						INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
						WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
					$r2=mysql_query($sql2);
					while ($row2=mysql_fetch_array($r2)) {
						$resz=$resz+$tmp1[1]*$row2['PriceZ'];
					}
				}
				$res=$res+$row1['Qty']*$row1['ItemPrice'];
			} else {
				$res=$res+$row1['Qty']*$row1['ItemPrice'];
				$resz=$resz+$row1['Qty']*$row1['PriceZ'];
			}
			//$res=$res+$row1['Qty']*$row1['ItemPrice'];
			//$resz=$resz+$row1['Qty']*$row1['PriceZ'];*/
			if ($row1['complect']!="") {
				$tmp=explode(";",$row1['complect']);
				foreach($tmp as $t) {
					$tmp1=explode(":",$t);
					$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
						INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
						WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
					$r2=mysql_query($sql2);
					while ($row2=mysql_fetch_array($r2)) {
						$netshopz=$netshopz+$tmp1[1]*$row2['PriceZ'];
					}
				}
				$netshop=$netshop+$row1['Qty']*$row1['ItemPrice'];
			} else {
				$netshop=$netshop+$row1['Qty']*$row1['ItemPrice'];
				$netshopz=$netshopz+$row1['Qty']*$row1['PriceZ'];
			}
			$numstr.=$row1['Order_ID']."i ";
			$all=$all+1;
		}
		//$all=$all+mysql_num_rows($r)+mysql_num_rows($r1);
	}
	
	$allm=$retail+$netshop;
	$allmz=$retailz+$netshopz;
	return $all.":".$allm.":".$allmz;
}
	
function getFullPriceResidue() {
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
	
function showStatisticList($incoming) {
	$res="";
	$res.="<table cellpadding='2' cellspacing='0' border='1'  id='myTable' class='tablesorter'>
	<thead> 
	<tr><th><b>Поставщик</b></th>
		<th><b>Кол. проданных<br>штук</b></th>
		<th><b>Сумма<br>реализации</b></th>
		<th><b>Сумма<br>реализации закуп.</b></th>
		<th><b>Реализация-закупка</b></th>
		<th><b>Сред. стоимость<br>покупки</b></th>
		<th><b>% от итого</b></th>
		<th><b>Остаток</b></th>
		<th><b>Сумма остатка</b></th>
		<th><b>Сумма остатка<br>закуп.</b></th>
		<th><b>% от итого</b></th>
		<th><b>% наценки</b></th></tr>
	</thead>
	<tbody>";
	$tt=explode(":",getFullPrice($incoming['min'],$incoming['max']));
	$itog=$tt[1];
	$itogz=$tt[2];
	$allnum=$tt[0];
	
	$tk=explode(":",getFullPriceResidue());
	//print_r($tk);
	$itogk=$tk[0];
	$allnumk=$tk[1];
	$itogkz=$tk[2];
//	echo $itog;
	$sql="SELECT * FROM Classificator_Supplier WHERE Statistic=1 ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="<tr><td><b>{$row['Supplier_Name']}</b></td>";
		$res.=showStatisticForVendor($row['Supplier_ID'], $incoming['min'],$incoming['max'], $itog);
		$res.=showResidueForVendor($row['Supplier_ID'], $itogk);
		$res.="</tr>";
	}
	$res.="<tbody> 
		<tr style='font-weight:bold;'><td>Итого</td>
			<td style='text-align:right;'>".number_format($allnum,0,'.',' ')."</td>
			<td style='text-align:right;'>".number_format($itog, 2, '.', ' ')."</td>
			<td style='text-align:right;'>".number_format($itogz, 2, '.', ' ')."</td>
			<td style='text-align:right;'>".number_format($itog-$itogz, 2, '.', ' ')."</td>
			<td style='text-align:right;'>".number_format(round($itog/$allnum,2),0,'.',' ')."</td><td>&nbsp;</td>
			<td style='text-align:right;'>".number_format($allnumk,0,'.',' ')."</td>
			<td style='text-align:right;'>".number_format($itogk,2,'.',' ')."</td>
			<td style='text-align:right;'>".number_format($itogkz,2,'.',' ')."</td><td>&nbsp;</td>
			<td style='text-align:right;'>".number_format($itogk*100/$itogkz-100,2,'.',' ')."</td>
			
			</tr>";
	$res.="
	</table>";
	return $res;
}
function printTblStat($order,$tbl) {
	$html="";
	
	return $html;
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
	switch ($incoming['action']) {
		case "filter":
			$html.=showStatisticList($incoming);
			break;
		default:
			//$html.=showListFiles();
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Статистика по поставщикам</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<link rel="stylesheet" href="style.css" type="text/css" media="print, projection, screen" />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery.tablesorter.js"></script> 
</head>

<body>
<script type="text/javascript">
$(document).ready(function() 
    { 
        $("#myTable").tablesorter(); 
    } 
); 
</script>
	<?php
		echo printMenu();
	?>
	<h1>Статистика по поставщикам</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-vendor.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td colspan="2">
		<table cellpadding="0" cellspacing="5" border="0"><tr>
				<td>с</td>
				<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.".date("m.Y") ?>" class="datepickerTimeField"></td>
				<td>по</td>
				<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</tr></table>
		</td></tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
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
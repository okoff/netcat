<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function showStatisticForVendor($sid,$min,$max, $incoming=array()) {
	$res="";
	$items=array();
	$data=array();
	$j=0;
	$fullprice=$fullpricez=0;
	$where="";
	if ($incoming['steel']!="") {
		$where=" AND Message57.steel=".intval($incoming["steel"]);
	}
	if ($incoming['category']!="") {
		$tmp=explode(":",$incoming["category"]);
		$where.=" AND Message57.Subdivision_ID={$tmp[0]} AND Message57.Sub_Class_ID={$tmp[1]}";
	}
	$res="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'><td>#</td><td>Артикул</td><td>#</td><td>#</td><td>Дата</td><td>Название</td><td>Цена закупки</td><td>Цена розничная</td></tr>";
	$sql="SELECT Retails_goods.*,Message57_p.Price AS PriceZ,Retails.created AS RetCreated,Message57.ItemID,Message57.Name,Message57.Price,Message57.Subdivision_ID,Message57.Sub_Class_ID,Message57.complect  FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Retails_goods.deleted=0
			AND Message57.supplier={$sid} {$where}
			ORDER BY Retails_goods.id ASC";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	$retail=$retailz=0;
	while ($row1=mysql_fetch_array($r)) {
		$items[$j]=$row1['ItemID'];
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
		//$data[$row1['ItemID']]=
		$data[$j]="<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row1['Subdivision_ID']}&cc={$row1['Sub_Class_ID']}&message={$row1['item_id']}'>{$row1['ItemID']}</a></td>
			<td>розн.</td>
			<td><a href='/netcat/modules/netshop/interface/retail-edit.php?id={$row1['retail_id']}&start=1' target='_blank'>{$row1['retail_id']}</a></td>
			<td>".date("d.m.Y H:i:s",strtotime($row1['RetCreated']))."</td>
			<td>{$row1['Name']}</td>
			<td style='text-align:right;'>{$pricez}</td>
			<td style='text-align:right;'>{$row1['Price']}</td></tr>";
		$fullprice=$fullprice+$row1['Price']*$row1['qty'];
		$fullpricez=$fullpricez+$pricez*$row1['qty'];
		$j=$j+1;
	}
	/*$sql="SELECT Netshop_OrderGoods.*, Message51.created AS OrdCreated,Message57.ItemID,Message57.Name,Message57.Price,Message57_p.Price AS PriceZ,Message57.Subdivision_ID,Message57.Sub_Class_ID FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Message57.supplier={$sid}
			ORDER BY Netshop_OrderGoods.Order_ID ASC";*/
	$sql="SELECT Netshop_OrderGoods.*, Message51.created AS OrdCreated,Message57_p.Price AS PriceZ,Message57.ItemID,Message57.Name,Message57.Price,Message57.Subdivision_ID,Message57.Sub_Class_ID,Message57.complect 
			FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Netshop_OrderGoods.Item_ID = Message57_p.Message_ID )
			INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Message57.supplier={$sid} AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2 {$where}
			ORDER BY Netshop_OrderGoods.Order_ID ASC";
	//echo $sql."<br>";
	$r1=mysql_query($sql);
	$netshop=$netshopz=0;
	while ($row1=mysql_fetch_array($r1)) {
		$items[$j]=$row1['ItemID'];
		//$data[$row1['ItemID']]=
		$pricez=0;
		if ($row1['complect']!="") {
			$tmp=explode(";",$row1['complect']);
				foreach($tmp as $t) {
					$tmp1=explode(":",$t);
					$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
						INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
						WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
					$r2=mysql_query($sql2);
					while ($row2=mysql_fetch_array($r2)) {
						$pricez=$pricez+$tmp1[1]*$row2['PriceZ'];
					}
				}
		} else {
			$pricez=$row1['PriceZ'];
		}
		$data[$j]="<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row1['Subdivision_ID']}&cc={$row1['Sub_Class_ID']}&message={$row1['item_id']}'>{$row1['ItemID']}</a></td>
			<td>онлайн</td>
			<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row1['Order_ID']}' target='_blank'>{$row1['Order_ID']}</a><br>
			".getOrderStatusName($row1['Order_ID'])."</td>
			<td>".date("d.m.Y H:i:s",strtotime($row1['OrdCreated']))."</td>
			<td>{$row1['Name']}</td>
			<td style='text-align:right;'>{$pricez}</td>
			<td style='text-align:right;'>{$row1['Price']}</td></tr>";
		$fullprice=$fullprice+$row1['Price']*$row1['Qty'];
		$fullpricez=$fullpricez+$row1['Qty']*$pricez;
		$j=$j+1;
	}
	//echo $j."<br>";
	$solditms.="</table>";
	//print_r($items);
	//echo "<br><br>";
	asort($items);
	//print_r($items);
	$i=0;
	foreach ($items as $key => $val) {
		//echo "$key = $val\n";
		$i=$i+1;
		$res.="<tr><td>{$i}</td>".$data[$key];
	}

	/*for ($i=0;$i<count($items);$i++) {
		$res.="<tr><td>{$i}</td>".$data[$items[$i]];
	}*/
	$res.="<tr style='font-weight:bold;'><td colspan='6' style='text-align:right;'>Итого:</td><td style='text-align:right;'>{$fullpricez}</td><td style='text-align:right;'>{$fullprice}</td></tr>";
	$res.="</table>";
	$all=mysql_num_rows($r)+mysql_num_rows($r1);
	$allm=$retail+$netshop;
	$allmz=$retailz+$netshopz;

	return $res;
}
function showStatisticForVendorClnt($sid,$min,$max, $incoming=array()) {
	$res="";
	$items=array();
	$data=array();
	$j=0;
	$fullprice=$fullpricez=0;
	$where="";
	if ($incoming['steel']!="") {
		$where=" AND Message57.steel=".intval($incoming["steel"]);
	}
	if ($incoming['category']!="") {
		$tmp=explode(":",$incoming["category"]);
		$where.=" AND Message57.Subdivision_ID={$tmp[0]} AND Message57.Sub_Class_ID={$tmp[1]}";
	}
	$res="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'><td>#</td><td>Артикул</td><td>#</td><td>#</td><td>Дата</td><td>Название</td><td>ФИО</td><td>Телефон</td><td>email</td></tr>";
	
	$sql="SELECT Retails_goods.*,Message57_p.Price AS PriceZ,Retails.created AS RetCreated,Message57.ItemID,Message57.Name,Message57.Price,Message57.Subdivision_ID,Message57.Sub_Class_ID  FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Message57.supplier={$sid} {$where}
			ORDER BY Retails_goods.id ASC";
	//echo $sql;
	$r=mysql_query($sql);
	$retail=$retailz=0;
	while ($row1=mysql_fetch_array($r)) {
		$items[$j]=$row1['ItemID'];
		//$data[$row1['ItemID']]=
		$data[$j]="<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row1['Subdivision_ID']}&cc={$row1['Sub_Class_ID']}&message={$row1['item_id']}'>{$row1['ItemID']}</a></td>
			<td>розн.</td>
			<td><a href='/netcat/modules/netshop/interface/retail-edit.php?id={$row1['retail_id']}&start=1' target='_blank'>{$row1['retail_id']}</a></td>
			<td>".date("d.m.Y H:i:s",strtotime($row1['RetCreated']))."</td>
			<td>{$row1['Name']}</td>
			<td>-</td>
			<td>-</td>
			<td>-</td></tr>";
		$fullprice=$fullprice+$row1['Price'];
		$fullpricez=$fullpricez+$row1['PriceZ'];
		$j=$j+1;
	}
	$sql="SELECT Netshop_OrderGoods.*, Message51.created AS OrdCreated,Message51.User_ID,Message51.ContactName,Message51.Phone,Message51.Email,Message57_p.Price AS PriceZ,Message57.ItemID,Message57.Name,Message57.Price,Message57.Subdivision_ID,Message57.Sub_Class_ID 
			FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
			INNER JOIN Message57_p ON ( Netshop_OrderGoods.Item_ID = Message57_p.Message_ID )
			INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND Message57.supplier={$sid} AND NOT Message51.Status=2  AND Message51.Town LIKE '%москва%' AND NOT Message51.DeliveryMethod=2 {$where}
			ORDER BY Netshop_OrderGoods.Order_ID ASC";
	//echo $j."-";
	$r1=mysql_query($sql);
	$netshop=$netshopz=0;
	while ($row1=mysql_fetch_array($r1)) {
		$items[$j]=$row1['ItemID'];
		//$data[$row1['ItemID']]=
		$data[$j]="<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row1['Subdivision_ID']}&cc={$row1['Sub_Class_ID']}&message={$row1['item_id']}'>{$row1['ItemID']}</a></td>
			<td>онлайн</td>
			<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row1['Order_ID']}' target='_blank'>{$row1['Order_ID']}</a><br>
			".getOrderStatusName($row1['Order_ID'])."</td>
			<td>".date("d.m.Y H:i:s",strtotime($row1['OrdCreated']))."</td>
			<td>{$row1['Name']}</td>
			<td>{$row1['ContactName']}</td>
			<td>{$row1['Phone']}</td>
			<td>{$row1['Email']}</td>
		</tr>";
		$fullprice=$fullprice+$row1['Price'];
		$fullpricez=$fullpricez+$row1['PriceZ'];
		$j=$j+1;
	}
	//echo $j."<br>";
	$solditms.="</table>";
	//print_r($items);
	//echo "<br><br>";
	asort($items);
	//print_r($items);
	$i=0;
	foreach ($items as $key => $val) {
		//echo "$key = $val\n";
		$i=$i+1;
		$res.="<tr><td>{$i}</td>".$data[$key];
	}
	$res.="</table>";
	$all=mysql_num_rows($r)+mysql_num_rows($r1);
	$allm=$retail+$netshop;
	$allmz=$retailz+$netshopz;

	return $res;
}
function showStatisticForVendorShort($sid,$min,$max, $incoming=array()) {
	$res="";
	$items=array();
	$data=array();
	$num=array();
	$rest=array();
	$price=$pricez=array();
	$j=0;
	$ntn=$ntprice=$ntpricez=0; // netshop
	$rtn=$rtprice=$rtpicez=0;  // retails
	$where="";
	if ($incoming['sid']!="") {
		$where.=" AND Message57.supplier=".$sid;
	}
	if ($incoming['steel']!="") {
		$where.=" AND Message57.steel=".intval($incoming["steel"]);
	}
	if ($incoming['category']!="") {
		$tmp=explode(":",$incoming["category"]);
		$where.=" AND Message57.Subdivision_ID={$tmp[0]} AND Message57.Sub_Class_ID={$tmp[1]}";
	}
	$res="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'><td>#</td><td>Артикул</td><td>Название</td><td>На складе</td><td>Кол.</td><td>Цена закупки</td><td>Цена розничная</td></tr>";
	$sql="SELECT Retails_goods.*,Message57_p.Price AS PriceZ,Retails.created AS RetCreated,Message57.ItemID,Message57.Name,Message57.StockUnits,Message57.Price,Message57.Subdivision_ID,Message57.Sub_Class_ID,Message57.complect  FROM Retails_goods 
			INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
			LEFT JOIN Message57_p ON ( Message57_p.Message_ID = Message57.Message_ID )
			INNER JOIN Retails ON ( Retails.id = Retails_goods.retail_id )
			WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			{$where}
			ORDER BY Retails_goods.id ASC";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	while ($row1=mysql_fetch_array($r)) {
		$new=1;
		$pricezrub=0;
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
					$pricezrub=$pricezrub+$tmp1[1]*$row2['PriceZ'];
				}
			}
		} else {
			$pricezrub=$row1['PriceZ'];
		}
		for ($i=0;$i<count($items);$i++) {
			if ($items[$i]==$row1['ItemID']) {
				$num[$i]=$num[$i]+$row1['qty'];
				$rest[$i]=$row1['StockUnits'];
				//$price[$i]=$price[$i]+$row1['ItemPrice'];
				$price[$i]=$row1['itemprice'];
				//$pricez[$i]=$pricez[$i]+$row1['PriceZ'];
				$pricez[$i]=$pricezrub;
				$new=0;
			}
		}
		if ($new) {
			$items[$j]=$row1['ItemID'];
			$data[$j]=$row1['Name'];
			$num[$j]=$row1['qty'];
			$price[$j]=$row1['Price'];
			$pricez[$j]=$pricezrub;
			$rest[$j]=$row1['StockUnits'];
			$j=$j+1;
		}
		$rtprice=$rtprice+$row1['Price']*$row1['qty'];
		$rtpricez=$rtpricez+$pricezrub*$row1['qty'];
		$rtn=$rtn+$row1['qty'];		
	}
	$sql="SELECT Netshop_OrderGoods.*, Message51.created AS OrdCreated,Message57.ItemID AS ART,Message57_p.Price AS PriceZ,Message57.Name,Message57.StockUnits,Message57.Price,Message57.Subdivision_ID,Message57.Sub_Class_ID,Message57.complect 
			FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON ( Netshop_OrderGoods.Item_ID = Message57.Message_ID )
			INNER JOIN Message57_p ON ( Netshop_OrderGoods.Item_ID = Message57_p.Message_ID )
			INNER JOIN Message51 ON ( Message51.Message_ID = Netshop_OrderGoods.Order_ID )
			WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($min))."' AND '".date("Y-m-d 23:59:59", strtotime($max))."'
			AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2 {$where}
			ORDER BY Netshop_OrderGoods.Order_ID ASC";
	//echo $sql."<br>";
	//print_r($items);
	//echo "<br>j={$j}<br>";
	$r1=mysql_query($sql);
	while ($row1=mysql_fetch_array($r1)) {
		$new=1;
		$pricezrub=0;
		if ($row1['complect']!="") {
			$tmp=explode(";",$row1['complect']);
				foreach($tmp as $t) {
					$tmp1=explode(":",$t);
					$sql2="SELECT Message57_p.Price AS PriceZ FROM Message57_p 
						INNER JOIN Message57 ON ( Message57_p.Message_ID = Message57.Message_ID )
						WHERE Message57.ItemID LIKE '{$tmp1[0]}'";
					$r2=mysql_query($sql2);
					while ($row2=mysql_fetch_array($r2)) {
						$pricezrub=$pricezrub+$tmp1[1]*$row2['PriceZ'];
					}
				}
		} else {
			$pricezrub=$row1['PriceZ'];
		}
		for ($i=0;$i<count($items);$i++) {
			if ($items[$i]==$row1['ART']) {
				$num[$i]=$num[$i]+$row1['Qty'];
				//$price[$i]=$price[$i]+$row1['ItemPrice'];
				$price[$i]=$row1['ItemPrice'];
				//$pricez[$i]=$pricez[$i]+$row1['PriceZ'];
				$pricez[$i]=$pricezrub;
				$rest[$i]=$row1['StockUnits'];
				$new=0;
				break;
			}
		}
		if ($new) {
			$items[$j]=$row1['ART'];
			$data[$j]=$row1['Name'];
			$num[$j]=$row1['Qty'];
			$price[$j]=$row1['ItemPrice'];
			$pricez[$j]=$pricezrub;
			$rest[$j]=$row1['StockUnits'];
			$j=$j+1;
		}
		$ntprice=$ntprice+$row1['ItemPrice']*$row1['Qty'];
		$ntpricez=$ntpricez+$pricezrub*$row1['Qty'];
		$ntn=$ntn+$row1['Qty'];
	}

	//print_r($items);
	asort($items);
	$i=0;
	foreach ($items as $key => $val) {
		//echo "$key = $val\n";
		$i=$i+1;
		$res.="<tr><td>{$i}</td><td>{$val}</td><td>{$data[$key]}</td><td>{$rest[$key]}</td><td>{$num[$key]}</td><td>{$pricez[$key]}</td><td>{$price[$key]}</td></tr>";
	}
	$res.="<tr><td colspan='4' style='text-align:right;'>Итого заказы</td><td>{$ntn}</td><td>{$ntpricez}</td><td>{$ntprice}</td></tr>";
	$res.="<tr><td colspan='4' style='text-align:right;'>Итого розница</td><td>{$rtn}</td><td>{$rtpricez}</td><td>{$rtprice}</td></tr>";
	$res.="<tr><td colspan='4' style='text-align:right;'><b>ИТОГО</b></td><td><b>".($ntn+$rtn)."</b></td><td><b>".($ntpricez+$rtpricez)."</b></td><td><b>".($ntprice+$rtprice)."</b></td></tr>";
	$res.="</table>";
	//$all=mysql_num_rows($r)+mysql_num_rows($r1);
	//$allm=$retail+$netshop;
	//$allmz=$retailz+$netshopz;


	
	return $res;
}

function showStatisticList($incoming) {
	$res="";
	//if (is_numeric($incoming['supplier'])) {
		//if (isset($incoming['short'])) {
			$res.=showStatisticForVendorShort($incoming['supplier'], $incoming['min'],$incoming['max'], $incoming);
		//} else {
		//	if (isset($incoming['clnt'])) {
		//		$res.=showStatisticForVendorClnt($incoming['supplier'], $incoming['min'],$incoming['max'], $incoming);
		//	} else {
		//		$res.=showStatisticForVendor($incoming['supplier'], $incoming['min'],$incoming['max'], $incoming);
		//	}
		//}
	//}
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
	<title>Статистика по проданным товарам</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>

<body>
	<?php
		echo printMenu();
	?>
	<h1>Статистика по проданным товарам</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-vendor3.php" method="post">
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
		<tr>
			<td style="text-align:right;">Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;">Сталь:</td>
			<td><?php echo selectSteel($incoming); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;">Категория:</td>
			<td><?php echo selectCategory4Supplier($incoming); ?></td>
		</tr>
		<tr><td colspan="3"><input type="submit" value="Показать"></td></tr>
	</table>
	</form><br>
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
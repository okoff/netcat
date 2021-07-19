<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");
include_once ("utils-waybill.php");
	
/*function showListFiles() {
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
}*/		
function showItemList($incoming) {
	$res="";
	//print_r($incoming);
	if ((isset($incoming['item_id'])) && ($incoming['item_id'])) {
		$where = "ItemID LIKE '%".$incoming['item_id']."%'";
	}
	if ((isset($incoming['item_sku'])) && ($incoming['item_sku'])) {
		$where.=($where) ? " AND " : "";
		$where.="Message57.Message_ID LIKE '%".ltrim(substr($incoming['item_sku'],2),'0')."%'";
	}
	if ((isset($incoming['item_name'])) && ($incoming['item_name'])) {
		$where.=($where) ? " AND " : "";
		$where.="Name LIKE '%".$incoming['item_name']."%'";
	}
	if ((isset($incoming['supplier'])) && ($incoming['supplier'])) {
		$where.=($where) ? " AND " : "";
		$where.="supplier=".$incoming['supplier'];
	}
	$sql="SELECT Message57.*, Message57_p.Price AS PriceOtp FROM Message57 
		LEFT JOIN Message57_p ON (Message57.Message_ID=Message57_p.Message_ID)
	".($where ? " WHERE " :" " ).$where." ORDER BY ItemID ASC";
	$result=mysql_query($sql);
//	echo $sql;
	$res.="<table cellpadding='2' cellspacing='0' border='1'><tr>
	<td><b>Артикул</b></td>
	<td><b>SKU</b></td>
	<td><b>Списания</b></td>
	<td><b>Название</b></td><td><b>Кол.</b></td>
	<td><b>Поставщик</b></td>
	<td><b>Модель</b></td>
	<td><b>Цена</b></td>
	<td><b>Цена отпускная</b></td><td><b>% наценки</b></td>
	<td><b>Статус</b></td>
	<td><b>Изменить отпускную цену</b></td>
	</tr>";
	$status="";
	while ($row = mysql_fetch_array($result)) {	
		switch ($row['status']) {
			case 2: $status="на складе"; break;
			case 3: $status="нет"; break;
			case 4: $status="под заказ"; break;
			default:break;
		}
		$discount=getDiscount($row['Message_ID'], $row['Price']);
		$res.="<tr>
	<td>{$row['ItemID']}</td>
	<td>".sprintf('%d%05d', 57, $row['Message_ID'])."</td>
	<td style='text-align:center;'><a target='_blank' href='/netcat/modules/netshop/interface/statistic-item.php?action=history&id={$row['Message_ID']}'>&gt;&gt;&gt;</a></td>
	<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>{$row['Name']}</a></td><td>{$row['StockUnits']}</td>
	<td>".printVendor($row['supplier'])."</td>
	<td>".printModel($row['model'])."</td>
	<td>".(($discount!=$row['Price']) ? "<strike>".$row['Price']."</strike> ".$discount : $row['Price'] )."</td>
	<td>".(($row['PriceOtp']) ? $row['PriceOtp'] : "<a target='_blank' href='/netcat/modules/netshop/interface/item-addprice.php?itm={$row['Message_ID']}'>добавить</a>")."</td>
	<td>".(($row['PriceOtp']) ? number_format(($row['Price']*100/$row['PriceOtp'] - 100), 2) : "&nbsp;")."</td>
	<td>{$status}</td>
	<td style='text-align:center;'><a href='/netcat/modules/netshop/interface/item-addprice.php?itm={$row['Message_ID']}'>&gt;&gt;&gt;</a></td>
	</tr>";		
	}
	$res.="</table>";
	return $res;
}	
function showItemHistory($id) {
	$res="";
	$sql="SELECT * FROM Message57 WHERE Message_ID={$id}";
	$result=mysql_query($sql);
	$status="";
	$sumcell=0;
	$ncell=0;
	$sumintro=0;
	$nintro=0;
	while ($row = mysql_fetch_array($result)) {	
		switch ($row['status']) {
			case 2: $status="на складе"; break;
			case 3: $status="нет"; break;
			case 4: $status="под заказ"; break;
			default:break;
		}
		$discount=getDiscount($row['Message_ID'], $row['Price']);
		$res.="<h2>[{$row['ItemID']}] {$row['Name']}</h2>
		<p><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>карточка товара</a></p>
		<p>Статус: <b>{$status}</b>. На складе: <b>{$row['StockUnits']}</b></p>
		<p>Цена: <b>".(($discount!=$row['Price']) ? "<strike>".$row['Price']."</strike> ".$discount : $row['Price'] )."</b></p>";		
	}
	$res.="<table cellpadding='0' cellspacing='2' border='0'>
		<tr><td style='vertical-align:top;'>";
	/*$sql="SELECT * FROM Writeoff WHERE item_id={$id} ORDER BY id DESC";
	if ($result=mysql_query($sql)) {
		$res.="<h3>Списание</h3>";
		$res.="<table cellpadding='3' cellspacing='0' border='1'>";
		while ($row = mysql_fetch_array($result)) {	
			if ($row['order_id']!=0)
			$res.="<tr><td>{$row['order_id']}</td><td>{$row['retail_id']}</td></tr>";
		}
		$res.="</table>";
	}*/
	$sql="SELECT Netshop_OrderGoods.*,Message51.Message_ID,Message51.Status,Message51.Created AS ordCreated,Message51.User_ID,Message51.ContactName,Message51.wroff,Message51.wroffdate,Classificator_ShopOrderStatus.ShopOrderStatus_Name FROM Netshop_OrderGoods 
			INNER JOIN Message51 ON Message51.Message_ID=Netshop_OrderGoods.Order_ID
			INNER JOIN Classificator_ShopOrderStatus ON Classificator_ShopOrderStatus.ShopOrderStatus_ID=Message51.Status
			WHERE Item_ID={$id}
			AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2
			ORDER BY Order_ID DESC";
	//echo $sql;
	if ($result=mysql_query($sql)) {
		$res.="<h3>Списания. Интернет-магазин</h3>";
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b>#</b></td><td><b>Статус</b></td><td><b>Кол.</b></td><td><b>Клиент</b></td><td><b>Цена</b></td>
			<td><b>Списать с релизации</b></td>
			</tr>";
		while ($row = mysql_fetch_array($result)) {
			($row['Status']==2) ? $style=" style='color:#a0a0a0;'" : $style="";
			$res.="<tr><td {$style}>".date("d.m.Y", strtotime($row['ordCreated']))."</td>
				<td {$style}><a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Order_ID']}'>{$row['Order_ID']}</a></td>
				<td {$style}>{$row['ShopOrderStatus_Name']}</td>
				<td {$style}>{$row['Qty']}</td>
				<td {$style}><a target='_blank' href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$row['User_ID']}'>{$row['ContactName']}</a></td>
				<td {$style}>{$row['ItemPrice']}</td>
				<td {$style}>".(($row['wroff']) ? "<img src='/images/icons/ok.png'> ".date("d.m.Y",strtotime($row['wroffdate'])) : "")."</td>
				</tr>";
			$ncell=$ncell+$row['Qty'];
			$sumcell=$sumcell+$row['ItemPrice']*$row['Qty'];
		}
		$res.="</table>";
	}
	$sql="SELECT * FROM Retails_goods 
			INNER JOIN Retails ON Retails.id=Retails_goods.retail_id
			WHERE item_id={$id} AND Retails_goods.deleted=0 ORDER BY Retails.id DESC";
	//echo $sql;
	if ($result=mysql_query($sql)) {
		$res.="<h3>Розничные продажи</h3>";
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b>#</b></td><td><b>Кол.</b></td><td><b>Клиент</b></td><td><b>Цена</b></td></tr>";
		while ($row = mysql_fetch_array($result)) {	
			$res.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/retail-edit.php?id={$row['retail_id']}&start=1'>{$row['retail_id']}</a></td>
				<td>{$row['qty']}</td>
				<td>".(($row['user_id']==-1) ? "аноним" : "")."<a target='_blank' href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$row['User_ID']}'>{$row['ContactName']}</a></td>
				<td>{$row['itemprice']}</td>
				</tr>";
			$ncell=$ncell+$row['qty'];
			$sumcell=$sumcell+$row['itemprice']*$row['qty'];
		}
		$res.="</table>";
	}
	// возврат поставщику
	$sql="SELECT * FROM Waybills 
				INNER JOIN Waybills_goods ON (Waybills_goods.waybill_id=Waybills.id)
			WHERE item_id={$id} AND Waybills.status_id=2 AND Waybills.type_id=7 ORDER BY Waybills.id DESC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		$res.="<h3>Возврат постащику</h3>";
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b># накладной</b></td><td><b>Кол.</b></td><td><b>Цена</b></td></tr>";
		while ($row = mysql_fetch_array($result)) {	
			$res.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>{$row['waybill_id']}</a></td>
				<td>{$row['qty']}</td>
				<td>{$row['itemprice']}</td>
				</tr>";
		}
		$res.="</table>";
	}
	// брак отложено
	$sql="SELECT * FROM Waybills 
				INNER JOIN Waybills_goods ON (Waybills_goods.waybill_id=Waybills.id)
			WHERE item_id={$id} AND Waybills.status_id=2 AND Waybills.type_id=5 ORDER BY Waybills.id DESC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		$res.="<h3>Брак отложено</h3>";
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b># накладной</b></td><td><b>Кол.</b></td><td><b>Цена</b></td></tr>";
		while ($row = mysql_fetch_array($result)) {	
			$res.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>{$row['waybill_id']}</a></td>
				<td>{$row['qty']}</td>
				<td>{$row['itemprice']}</td>
				</tr>";
		}
		$res.="</table>";
	}
	// расход по учету
	$sql="SELECT * FROM Waybills 
				INNER JOIN Waybills_goods ON (Waybills_goods.waybill_id=Waybills.id)
			WHERE item_id={$id} AND Waybills.status_id=2 AND Waybills.type_id=10 ORDER BY Waybills.id DESC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		$res.="<h3>Расход по учёту</h3>";
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b># накладной</b></td><td><b>Кол.</b></td><td><b>Цена</b></td></tr>";
		while ($row = mysql_fetch_array($result)) {	
			$res.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>{$row['waybill_id']}</a></td>
				<td>{$row['qty']}</td>
				<td>{$row['itemprice']}</td>
				</tr>";
		}
		$res.="</table>";
	}
	$res.="<p><b>Итого продажи: ".$ncell." шт. на ".$sumcell."</b></p>";
	
	
	$res.="</td><td style='vertical-align:top;'>";
	$res.="<h3>Приход</h3>";
	$sql="SELECT Waybills_goods.*,Waybills.created FROM Waybills_goods INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id) WHERE item_id=".$id." AND Waybills.status_id=2 AND (Waybills.type_id=2 OR Waybills.type_id=3)";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b># накладной</b></td><td><b>Кол.</b></td><td><b>Цена отпускная</b></td><td><b>Цена</b></td></tr>";
		while ($row = mysql_fetch_array($result)) {	
			$res.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>{$row['waybill_id']}</a></td>
				<td>{$row['qty']}</td>
				<td>{$row['originalprice']}</td>
				<td>{$row['itemprice']}</td>
				</tr>";
			$nintro=$nintro+$row['qty'];
			$sumintro=$sumintro+$row['itemprice']*$row['qty'];
		}
		$res.="</table>";
		
	}
	
	$res.="<h3>Приход по учету</h3>";
	$sql="SELECT Waybills_goods.*,Waybills.created FROM Waybills_goods INNER JOIN Waybills ON (Waybills.id=Waybills_goods.waybill_id) WHERE item_id=".$id." AND Waybills.status_id=2 AND Waybills.type_id=9";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		$res.="<table cellpadding='3' cellspacing='0' border='1'>
			<tr><td><b>Дата</b></td><td><b># накладной</b></td><td><b>Кол.</b></td><td><b>Цена</b></td></tr>";
		while ($row = mysql_fetch_array($result)) {	
			$res.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td>
				<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>{$row['waybill_id']}</a></td>
				<td>{$row['qty']}</td>
				<td>{$row['itemprice']}</td>
				</tr>";
			$nintro=$nintro+$row['qty'];
			$sumintro=$sumintro+$row['itemprice']*$row['qty'];
		}
		$res.="</table>";
	}
	$res.="<p><b>Итого приход: ".$nintro."шт. на ".$sumintro."</b></p>";
	$res.="</td></tr></table>";
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
		case "history":
			$show=0;
			$html.=showItemHistory($incoming['id']);
			break;
		case "filter":
			$show=1;
			$html.=showItemList($incoming);
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
	<title>Статистика по товарам</title>
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
	<h1>Статистика по товарам</h1>
	<?php
	if ($show==1) {
	?>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-item.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr>
			<td style="text-align:right;">Артикул:</td>
			<td><input type="text" value="<?php echo (isset($incoming['item_id']) ? $incoming['item_id'] : "" ); ?>" name="item_id" id="item_id"></td>
		</tr>
		<tr>
			<td style="text-align:right;">SKU:</td>
			<td><input type="text" value="<?php echo (isset($incoming['item_sku']) ? $incoming['item_sku'] : "" ); ?>" name="item_sku" id="item_sku"></td>
		</tr>
		<tr>
			<td style="text-align:right;">Название:</td>
			<td><input type="text" value="<?php echo (isset($incoming['item_name']) ? $incoming['item_name'] : "" ); ?>" name="item_name" id="item_name"></td>
		</tr>
		<tr>
			<td style="text-align:right;">Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td>
		</tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>
<?php 
}
echo $html; 
?>
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
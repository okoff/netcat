<?php
// 24.10.2020 OPE
// статистика по лидам в списке заказов
include_once ("../../../../vars.inc.php");
session_start();
// ------------------------------------------------------------------------------------------------
function selectOrderType($sel="") {
	$res="";
	$res="<select name='type' id='type'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_Ordertypes
		ORDER BY Ordertypes_Priority ASC";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['Ordertypes_ID']}' ".(($row['Ordertypes_ID']==$sel) ? "selected" : "").">{$row['Ordertypes_Name']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function selectOrderStatus($sel="") {
	$res="";
	$res="<select name='status' id='status'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_ShopOrderStatus
		ORDER BY ShopOrderStatus_Priority ASC";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['ShopOrderStatus_ID']}' ".(($row['ShopOrderStatus_ID']==$sel) ? "selected" : "").">{$row['ShopOrderStatus_Name']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function selectDelivery($sel="") {
	$res="";
	$res="<select name='delivery' id='delivery'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Message56
		ORDER BY Priority ASC";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['Message_ID']}' ".(($row['Message_ID']==$sel) ? "selected" : "").">{$row['Name']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function selectCourier($sel="") {
	$res="";
	$res="<select name='courier' id='courier'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_Courier
		ORDER BY Courier_Name ASC";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['Courier_ID']}' ".(($row['Courier_ID']==$sel) ? "selected" : "").">{$row['Courier_Name']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function selectPayment($sel="") {
	$res="";
	$res="<select name='payment' id='payment'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Message55 WHERE Checked=1
		ORDER BY Priority ASC";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['Message_ID']}' ".(($row['Message_ID']==$sel) ? "selected" : "").">{$row['Name']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function printPostHistory($oid) {
	$res="";
	$sql="SELECT * FROM Netshop_PostHistory WHERE Order_ID={$oid}
		ORDER BY id ASC";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.=date("d.m.Y", strtotime($row['created']))." ".$row['status']." ".$row['address']."<br>";
		}
	}
	return $res;
}
function getStatusDate($id,$status) {
	$res="";
	$sql="SELECT * FROM Netshop_OrderHistory WHERE Order_ID={$id} ORDER BY id desc LIMIT 1";
	//echo "<!--".$sql."-->";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			if ($row["orderstatus_id"]!=$status) {
				break;
			}
			$res.=date("d.m.Y H:i:s", strtotime($row['created']))."<br>";
		}
	}
	return $res;
}

// ------------------------------------------------------------------------------------------------

function printOrder($row) {
	$html="
	<td style='vertical-align:top;{$stylecom}'>".date("d.m.Y", strtotime($row['Created']))."</td>
	<td style='vertical-align:top;'>".str_replace(";","<BR>",$row['utm'])."</td>
	<td style='vertical-align:top;'>".
	((strlen($row['href'])>24) 
		? "<div class='tooltip'>".substr($row['href'],0,59)."<span class='tooltiptext'>".$row['href']."</span></div>"
		: $row['href']
	)."</td>
	<td style='vertical-align:top;'>".$row['Daily']."</td>
	</tr>";
	return $html;
}

function getOrderList($incoming) {
	$html="";
	$where="";
	$closed=0;
	if ((isset($incoming['type'])) && ($incoming['type']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.Type=".$incoming['type'];
		$closed=1;
	}
	if ((isset($incoming['status'])) && ($incoming['status']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.Status=".$incoming['status'];
		$closed=1;
	}
	if ((isset($incoming['delivery'])) && ($incoming['delivery']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.DeliveryMethod=".$incoming['delivery'];
		$closed=1;
	}
	if ((isset($incoming['payment'])) && ($incoming['payment']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.PaymentMethod=".$incoming['payment'];
		$closed=1;
	}
	if ((isset($incoming['paid'])) && ($incoming['paid']==1)) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.paid=1";
		$closed=1;
	}
	if ((isset($incoming['unpaid'])) && ($incoming['unpaid']==1)) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.paid=0";
		$closed=1;
	}
	if ((isset($incoming['contactname'])) && ($incoming['contactname']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.ContactName  LIKE '%".$incoming['contactname']."%'";
	}
	if ((isset($incoming['courier'])) && ($incoming['courier']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.courier=".intval($incoming['courier']);
	}
	if ((isset($incoming['phone'])) && ($incoming['phone']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" (Message51.Phone LIKE '%{$incoming['phone']}%' OR Message51.mphone LIKE '%{$incoming['phone']}%')";
	}
	if ((isset($incoming['min'])) && ($incoming['min']!="")&&(isset($incoming['max'])) && ($incoming['max']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.Created BETWEEN '".date("Y-m-d H:i:s",strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59",strtotime($incoming['max']))."'";
	}
	
	if ((isset($incoming['mesid'])) && ($incoming['mesid']!="")) {
		//(strlen($where)>3) ? $where.=" AND " : "";
		$where=" Message51.Message_ID=".intval($incoming['mesid']);
	}
	if ((isset($incoming['barcode'])) && ($incoming['barcode']!="")) {
		//(strlen($where)>3) ? $where.=" AND " : "";
		$where=" Message51.barcode=".intval($incoming['barcode']);
	}
	
	($closed) ? $where=" NOT  Message51.closed=1 AND ".$where : "";
	($where!="") ? $where=" WHERE ".$where : $where=" WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
	//echo $where."<br>";
	$sql="
	SELECT 
		DATE(Message51.Created) Created,
		IFNULL(Message51.utm,'') utm,
		IFNULL(Message51.href,'') href,
		COUNT( * ) Daily
	FROM Message51
		".$where."
	GROUP BY 
		DATE(Message51.Created), 
		IFNULL(Message51.utm,''), 
		IFNULL(Message51.href,'')
	ORDER BY 
		DATE(Message51.Created) DESC,
		Message51.utm, 
		Message51.href";
	if ($incoming['start']!=1) {
		$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'>
		<td>Дата</td>
		<td>UTM</td>
		<td>HREF</td>
		<td>Лидов</td>
	</tr>";
		if ($result=mysql_query($sql)) {
			$html="<p>Всего лидов: <b>".mysql_num_rows($result)."</b></p>".$html;
			while($row = mysql_fetch_array($result)) {
				$html.=printOrder($row);
			}
		}
		$html.="</table>";
	}
	return $html;
}
// ------------------------------------------------------------------------------------------------

// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
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
//print_r($incoming);
switch ($incoming['action']) {
	
	default:
		$html=getOrderList($incoming);
		break;
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Статистика по лидам заказов</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	.tooltip {
	  position: relative;
	  display: inline-block;
	  border-bottom: 1px dotted black;
	}
	.tooltip .tooltiptext {
	  visibility: hidden;
	  width: 480px;
	  background-color: black;
	  color: #fff;
	  text-align: left;
	  border-radius: 6px;
	  padding: 5px 0;
 
	  /* Position the tooltip */
	  position: absolute;
	  left: 0;
	  z-index: 1;
	}
	.tooltip:hover .tooltiptext {
	  visibility: visible;
	}
</style>
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
?>
	<h1>Статистика по лидам заказов</h1>
	<form action="/netcat/modules/netshop/interface/order-utm-stat.php" method="post" name="frm1" id="frm1">
	<fieldset style='border:0;'>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td align='right'>Тип заказа:</td>
			<td><?php echo selectOrderType(((isset($incoming['type'])) ? $incoming['type'] : "")); ?></td>
			<td align='right'>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='right'>Статус заказа:</td>
			<td><?php echo selectOrderStatus(((isset($incoming['status'])) ? $incoming['status'] : "")); ?></td>
			<td align='right'>Заказ оплачен:
			<td><input type='checkbox' value='1' id='paid' name='paid' <?php echo (((isset($incoming['paid'])) && ($incoming['paid']==1)) ? "checked" : "");  ?>></td>
		</tr>
		<tr>
			<td align='right'>Способ доставки:</td>
			<td><?php echo selectDelivery(((isset($incoming['delivery'])) ? $incoming['delivery'] : "")); ?></td>
			<td align='right'>Заказ не оплачен:
			<td><input type='checkbox' value='1' id='unpaid' name='unpaid' <?php echo (((isset($incoming['unpaid'])) && ($incoming['unpaid']==1)) ? "checked" : "");  ?>></td>
		</tr>
		<tr>
			<td align='right'>Способ оплаты:</td>
			<td><?php echo selectPayment(((isset($incoming['payment'])) ? $incoming['payment'] : "")); ?></td>
			<td align='right'>№ заказа:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['mesid'])) ? $incoming['mesid'] : "");  ?>' name='mesid' id='mesid'></td>
		</tr><tr>
			<td align='right'>Заказчик:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['contactname'])) ? $incoming['contactname'] : "");  ?>' name='contactname' id='contactname'></td>
			<td align='right'>Курьер:</td>
			<td><?php echo selectCourier(((isset($incoming['courier'])) ? $incoming['courier'] : "")); ?></td>
		</tr><tr>
			<td align='right'>Телефон:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['phone'])) ? $incoming['phone'] : "");  ?>' name='phone' id='phone'></td>
			<td align='right'>Баркод:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['barcode'])) ? $incoming['barcode'] : "");  ?>' name='barcode' id='barcode'></td>
		</tr>
		<tr>
			<td colspan="4">
				<table cellpadding="0" cellspacing="5" border="0"><tr>
					<td>с</td>
					<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : date("d.m.Y",mktime(0, 0, 0, date("m")-2, date("d"), date("Y"))); ?>" class="datepickerTimeField"></td>
					<td>по</td>
					<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
				</tr></table>
			</td>
		</tr>
		<tr>
		<td colspan='4'><input type='submit' value='Найти'></td>
		</tr>
	</table>
	</fieldset>
	</form>
	<?php echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
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
<?php
	mysql_close($con);
?>

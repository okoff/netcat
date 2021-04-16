<?php
// 12.11.2013 Elen
// 14.10.2020 OPE отображение utm, href
// 12.12.2020 OPE фильтр по utm, href
// поиск и просмотр списка заказов
include_once ("../../../../vars.inc.php");
session_start();
// ------------------------------------------------------------------------------------------------
function selectOrderDomain($sel="") {
	$res="";
	$res="<select name='domain' id='domain'>
		<option value=''>---</option>";
	$sql="
SELECT
    LEFT(
        RIGHT(
            `href` ,
            length(`href`) - (position('//' IN `href`) + 1)
        ) ,
        position(
            '/' IN RIGHT(
                `href` ,
                length(`href`) - (position('//' IN `href`) + 1)
            )
        ) - 1
    ) AS domain,
	COUNT(*) refs
FROM
	Message51
WHERE
	LENGTH(IFNULL(href,''))>0 AND
	POSITION('knife.ru' IN href)=0
GROUP BY
	domain
ORDER BY
	refs DESC,
	domain";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['domain']}' ".(($row['domain']==$sel) ? "selected" : "").">{$row['domain']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function selectOrderCampaign($sel="") {
	$res="";
	$res="<select name='campaign' id='campaign'>
		<option value=''>---</option>";
	$sql="
SELECT DISTINCT
    LEFT(
        RIGHT(
            `utm` ,
            length(`utm`) - (position('utm_campaign=' IN `utm`) + 12)
        ) ,
        position(
            ';' IN RIGHT(
                `utm` ,
                length(`utm`) - (position('utm_campaign=' IN `utm`) + 12)
            )
        ) - 1
    ) AS campaign
FROM
	Message51
WHERE
	LENGTH(IFNULL(utm,''))>0
ORDER BY
	campaign";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['campaign']}' ".(($row['campaign']==$sel) ? "selected" : "").">{$row['campaign']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
function selectOrderSource($sel="") {
	$res="";
	$res="<select name='source' id='source'>
		<option value=''>---</option>";
	$sql="
SELECT DISTINCT
    LEFT(
        RIGHT(
            `utm` ,
            length(`utm`) - (position('utm_source=' IN `utm`) + 10)
        ) ,
        position(
            ';' IN RIGHT(
                `utm` ,
                length(`utm`) - (position('utm_source=' IN `utm`) + 10)
            )
        ) - 1
    ) AS source
FROM
	Message51
WHERE
	LENGTH(IFNULL(utm,''))>0
ORDER BY
	source";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$res.="<option value='{$row['source']}' ".(($row['source']==$sel) ? "selected" : "").">{$row['source']}</option>";
		}
	}
	$res.="</select>";
	return $res;
}
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
	$html="";
	
	//print_r($row);
	//echo "<br><br>";
	($row['Comments']!='') ? $stylecom=" background:#ffff66;" : $stylecom="";
	($row['paid']==1) ? $style=" background:#B2FFB4;" : $style="";
	$html.="<tr>
	<td style='vertical-align:top;{$style}'><input type='checkbox' id='ob{$row['Message_ID']}' name='ob' value='{$row['Message_ID']}'>
		<input type='hidden' value='".getOrderCost($row['Message_ID'])."' id='obcost{$row['Message_ID']}' name='obcost{$row['Message_ID']}'>
		<input type='hidden' value='".$row['DeliveryCost']."' id='obdlv{$row['Message_ID']}' name='obdlv{$row['Message_ID']}'>
		<input type='hidden' value='".(($row['couriercost']) ? $row['couriercost'] : 0)."' id='obcur{$row['Message_ID']}' name='obcur{$row['Message_ID']}'>
	</td>
	<td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></b></td>
	<td style='vertical-align:top;{$stylecom}'>".date("d.m.Y H:i:s", strtotime($row['Created']))."</td>
	<td style='vertical-align:top;'>".$row['OrderType']."</td>
	<td style='vertical-align:top;'>{$row['OrderStatus']}<br><div style='font-size:8pt;color:#505050;'>".getStatusDate($row['Message_ID'],$row['Status'])."
	".(($row['paid']==1) ? "оплачен" : "")."
	".(($row['Status']==9) ? printPostHistory($row['Message_ID']) : "")."</div>
	</td>
	<td style='vertical-align:top;'><b>{$row['ContactName']}</b><br>{$row['Email']}<br>{$row['Phone']}<br>{$row['Address']}
	</td>
	<td style='vertical-align:top;'>{$row['Delivery']}<br><br><b>".getCourier($row['courier']).
	(($row['DeliveryMethod']==9) ? (($row['cdek_modeid']==3) ? "склад-дверь" : (($row['cdek_modeid']==4) ? "склад-склад" : "")."") : "")."</b></td>
	<td style='vertical-align:top;'>{$row['Payment']}</td>
	<td style='vertical-align:top;'>{$row['barcode']}</td>
	<td style='vertical-align:top;'>".(($row['senddate']!=null) ? date("d.m.Y", strtotime($row['senddate'])) : "&nbsp;")."</td>
	<td style='vertical-align:top;'><div style='font-size:8pt;'>".printCart($row['Message_ID'])."</div></td>
	<td style='vertical-align:top;'>".getOrderCost($row['Message_ID'])."</td>
	<td style='vertical-align:top;'>".$row['DeliveryCost']."</td>
	<td style='vertical-align:top;'>".$row['couriercost']."</td>
	<td style='vertical-align:top;'>".str_replace(";","<BR>",$row['utm'])."</td>
	<td style='vertical-align:top;'>".
	((strlen($row['href'])>24) 
		? "<div class='tooltip'>".substr($row['href'],0,23)."<span class='tooltiptext'>".$row['href']."</span></div>"
		: $row['href']
	)."</td>
	</tr>";
	
	return $html;
}

function getOrderList($incoming) {
	$html="";
	$where="";
	$closed=0;
	
	if ((isset($incoming['domain'])) && ($incoming['domain']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.href LIKE '%//".$incoming['domain']."/%'";
		$closed=1;
	}
	if ((isset($incoming['campaign'])) && ($incoming['campaign']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.utm LIKE '%utm_campaign=".$incoming['campaign'].";%'";
		$closed=1;
	}
	if ((isset($incoming['source'])) && ($incoming['source']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.utm LIKE '%utm_source=".$incoming['source'].";%'";
		$closed=1;
	}
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
	$sql="SELECT Message51.*,Message56.Name AS Delivery,Message55.Name AS Payment, Classificator_ShopOrderStatus.ShopOrderStatus_Name AS OrderStatus,
			Classificator_Ordertypes.Ordertypes_Name AS OrderType 
		FROM Message51
		LEFT JOIN Classificator_ShopOrderStatus ON (Classificator_ShopOrderStatus.ShopOrderStatus_ID=Message51.Status)
		LEFT JOIN Classificator_Ordertypes ON (Classificator_Ordertypes.Ordertypes_ID=Message51.Type)
		LEFT JOIN Message56 ON (Message56.Message_ID=Message51.DeliveryMethod)
		LEFT JOIN Message55 ON (Message55.Message_ID=Message51.PaymentMethod) ".$where."
		ORDER BY Message_ID DESC ";
	//echo "<!--".$sql."-->";
	//echo $sql;
	if ($incoming['start']!=1) {
		$html.="<table cellpadding='' cellspacing='1' border='0'>";
		$html.="<tr><td style='text-align:right'>Всего выбрано заказов:</td><td><input type='text' value='' id='rclnum' name='rclnum' disabled></td></tr>";
		$html.="<tr><td style='text-align:right'>Суммарная стоимость заказов:</td><td><input type='text' value='' id='rclcost' name='rclcost' disabled></td></tr>";
		$html.="<tr><td style='text-align:right'>Суммарная стоимость доставки:</td><td><input type='text' value='' id='rcldlv' name='rcldlv' disabled></td></tr>";
		$html.="<tr><td style='text-align:right'>Оплата курьеру:</td><td><input type='text' value='' id='rclcur' name='rclcur' disabled></td></tr>";
		$html.="<tr><td style='text-align:right'>ИТОГО:</td><td><input type='text' value='' id='rclitog' name='rclitog' disabled></td></tr>";
		$html.="<tr><td colspan='2'><input type='button' value='Пересчитать' name='recalc' id='recalc' onclick='reCalc();'></td></tr></table>";
		$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'><td><input type='checkbox' id='all' name='all' onclick='checkAll();'></td>
		<td>#</td><td>Дата</td>
		<td>Тип</td>
		<td>Статус</td>
		<td>ФИО</td><td>Способ доставки</td>
		<td>Способ оплаты</td>
		<td>Баркод</td>
		<td>Дата отправления</td>
		<td>Состав заказа</td>
		<td>Сумма заказа</td>
		<td>Сумма доставки</td>
		<td>Оплата курьеру</td>
		<td>UTM</td>
		<td>HREF</td>
	</tr>";
		if ($result=mysql_query($sql)) {
			$html="<p>Всего заказов: <b>".mysql_num_rows($result)."</b></p>".$html;
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
	<title>Заказы</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script>
function reCalc() {
	var cost=0;
	var dlv=0;
	var cur=0;
	var itog=0;
	var num=0;
	var j=1;
	var boxes = document.getElementsByTagName('input');
	for (i=0; i<boxes.length; i++)  {
		if (boxes[i].type == 'checkbox')   {
			if (boxes[i].name!="all") {
				if (boxes[i].checked) {
					//alert(document.getElementById('obdlv'+boxes[i].value).value);
					cost=cost+parseFloat(document.getElementById('obcost'+boxes[i].value).value);
					dlv=dlv+parseFloat(document.getElementById('obdlv'+boxes[i].value).value);
					cur=cur+parseFloat(document.getElementById('obcur'+boxes[i].value).value);
					num=num+1;
					itog=itog+parseFloat(document.getElementById('obcost'+boxes[i].value).value)+parseFloat(document.getElementById('obdlv'+boxes[i].value).value);
					//return true;
				}
				j=j+1;
			}
		}	
	}
	document.getElementById("rclcost").value=cost;
	document.getElementById("rcldlv").value=dlv;
	document.getElementById("rclnum").value=num;
	document.getElementById("rclitog").value=itog;
	document.getElementById("rclcur").value=cur;
	return false;
}
function checkAll() {
	var boxes = document.getElementsByTagName('input');
	if (document.getElementById("all").checked) {
		
		for (i=0; i<boxes.length; i++)  {
			if (boxes[i].type == 'checkbox')   {
				if ((boxes[i].name!="paid")&&(boxes[i].name!="unpaid")) {
					boxes[i].checked=true;
				}
			}	
		}
	}
	return true;
}
	</script>
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
	<h1>Заказы</h1>
	<form action="/netcat/modules/netshop/interface/order-list.php" method="post" name="frm1" id="frm1">
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
			<td><?php echo selectOrderStatus(((isset($incoming['status'])) ? $incoming['status'] : "5")); ?></td>
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
		</tr><tr>
			<td align='right'>Реклама:</td>
			<td colspan="3">
				<table cellpadding="0" cellspacing="5" border="0"><tr>
					<td align='right'>Кампания:</td>
					<td><?php echo selectOrderCampaign(((isset($incoming['campaign'])) ? $incoming['campaign'] : "")); ?></td>
					<td align='right'>Источник:</td>
					<td><?php echo selectOrderSource(((isset($incoming['source'])) ? $incoming['source'] : "")); ?></td>
					<td align='right'>Домен:</td>
					<td><?php echo selectOrderDomain(((isset($incoming['domain'])) ? $incoming['domain'] : "")); ?></td>
				</tr></table>
			</td>					
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

<?php
// 12.11.2013 Elen
// поиск и просмотр списка заказов
include_once ("../../../../vars.inc.php");
session_start();
// ------------------------------------------------------------------------------------------------
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
	($row['paid']==1) ? $style=" background:#B2FFB4;" : $style="";
	$html.="<tr>
	<td style='vertical-align:top;'><input type='checkbox' id='ob{$row['Message_ID']}' name='ob' value='{$row['Message_ID']}'>
		<input type='hidden' value='".getOrderCost($row['Message_ID'])."' id='obcost{$row['Message_ID']}' name='obcost{$row['Message_ID']}'>
		<input type='hidden' value='".$row['DeliveryCost']."' id='obdlv{$row['Message_ID']}' name='obdlv{$row['Message_ID']}'>
		<input type='hidden' value='".(($row['couriercost']) ? $row['couriercost'] : 0)."' id='obcur{$row['Message_ID']}' name='obcur{$row['Message_ID']}'>
	</td>
	<td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></b></td>
	<td style='vertical-align:top;'>".date("d.m.Y H:i:s", strtotime($row['Created']))."</td>
	<td style='vertical-align:top;'>{$row['OrderStatus']}<br><div style='font-size:8pt;color:#505050;'>".getStatusDate($row['Message_ID'],$row['Status'])."
	".(($row['paid']==1) ? "оплачен" : "")."
	".(($row['Status']==9) ? printPostHistory($row['Message_ID']) : "")."</div>
	</td>
	<td style='vertical-align:top;'><b>{$row['ContactName']}</b><br>{$row['Email']}<br>{$row['Phone']}<br>{$row['Address']}
	</td>
	<td style='vertical-align:top;'>{$row['Delivery']}<br><br><b>".getCourier($row['courier'])."</b></td>
	<td style='vertical-align:top;'>{$row['Payment']}</td>
	<!--td style='vertical-align:top;'>{$row['barcode']}</td>
	<td style='vertical-align:top;'>".(($row['senddate']!=null) ? date("d.m.Y", strtotime($row['senddate'])) : "&nbsp;")."</td-->
	<td style='vertical-align:top;'><div style='font-size:8pt;'>".printCart($row['Message_ID'])."</div></td>
	<td style='vertical-align:top;'>".getOrderCost($row['Message_ID'])."</td>
	<td style='vertical-align:top;'>".$row['DeliveryCost']."</td>
	<td style='vertical-align:top;'>".$row['couriercost']."</td>
	<td style='vertical-align:top;'>".date("d.m.Y H:i:s",strtotime($row['statuschanged']))."</td>
	</tr>";
	
	return $html;
}

function getOrderList($incoming) {
	$html="";
	$where="";
	
	if ((isset($incoming['min'])) && ($incoming['min']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Netshop_OrderHistory.created BETWEEN '".date("Y-m-d 00:00:00",strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59",strtotime($incoming['min']))."'";
	}
	
	($where!="") ? $where=" WHERE ".$where : $where=" WHERE Netshop_OrderHistory.created BETWEEN '".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:59:59")."'";
	//echo $where."<br>";
	/*$sql="SELECT Message51.*,Message56.Name AS Delivery,Message55.Name AS Payment, Classificator_ShopOrderStatus.ShopOrderStatus_Name AS OrderStatus,Netshop_OrderHistory.created AS statuschanged
		FROM Message51
		INNER JOIN Classificator_ShopOrderStatus ON (Classificator_ShopOrderStatus.ShopOrderStatus_ID=Message51.Status)
		INNER JOIN Message56 ON (Message56.Message_ID=Message51.DeliveryMethod)
		INNER JOIN Message55 ON (Message55.Message_ID=Message51.PaymentMethod) 
		INNER JOIN Netshop_OrderHistory ON (Netshop_OrderHistory.Order_ID=Message51.Message_ID)
		".$where."
		ORDER BY Message_ID DESC";*/
	$sql="SELECT * FROM Netshop_OrderHistory
		".$where."
		ORDER BY Order_ID ASC , id DESC";
	//echo "<!--".$sql."-->";
	//echo $sql."<br>";
	$ordst=array();
	
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			if ($row['orderstatus_id']==null) {
				//echo "!";
				$ordst[5].=$row['Order_ID'].";";
			}
			for ($j=1;$j<16;$j++) {
				if ($row['orderstatus_id']==$j) {
					$ordst[$j].=$row['Order_ID'].";";
				}
			}
		}
	}
	//echo "<br>";
	//print_r($ordst);
	$html.="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Classificator_ShopOrderStatus WHERE Checked=1 ORDER BY ShopOrderStatus_Priority";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$html.="<tr><td>{$row['ShopOrderStatus_Name']}</td><td>";
			$html1="";
			$k=0;
			$tmp=explode(";",$ordst[$row['ShopOrderStatus_ID']]);
			$u=0;
			for ($i=0;$i<count($tmp);$i++) {
				if ($tmp[$i]) {
					if ($u!=$tmp[$i]) {
						$html1.="<a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$tmp[$i]}'>{$tmp[$i]}</a>; ";
						$k=$k+1;
					}
				}
				$u=$tmp[$i];
			}
			$html.="<b>{$k}</b></td><td>{$html1}</td></tr>";
		}
	}
	$html.="</table>";
	
	
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
	</style>
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
?>
	<h1>Статистика по заказам</h1>
	<form action="/netcat/modules/netshop/interface/statistic-order.php" method="post" name="frm1" id="frm1">
	<fieldset style='border:0;'>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<!--td align='right'>Статус заказа изменен на:</td>
			<td><?php echo selectOrderStatus(((isset($incoming['status'])) ? $incoming['status'] : "5")); ?></td-->
			<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</tr>
		
		<tr>
		<td><input type='submit' value='Найти'></td>
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

<?php
// 16.09.2013 Elen
// orderstatus=8 - подготовлен к отправке на почту
// orderstatus=9 - передан на почту (пришел первый ответ от почты)
include_once ("../../../../vars.inc.php");
session_start();

function getDeliveryMethod($id) {
	$res="";
	$sql="SELECT Name FROM Message56 WHERE Message_ID=".$id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['Name'];
	}
	return $res;
}
function getPaymentMethod($id) {
	$res="";
	$sql="SELECT Name FROM Message55 WHERE Message_ID=".$id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['Name'];
	}
	return $res;
}
function tracksendings($SessionId,$incoming,$ikn,$order_id) {
	// tracksendings
$State=array();
$State[101]="Магазин зарегистрировал заказ в PickPoint";
$State[102]="Магазин подготовил заказ для передачи в компанию PickPoint";
$State[103]="Магазин самостоятельно разводит заказы по постаматам и пунктам выдачи";
$State[104]="Отправитель скомплектовал заказ для передачи в PickPoint";
$State[105]="Отправление обработано на сортировочном центре PickPoint";
$State[106]="Заказ находится в сортировочном центре PickPoint (в Москве или регионах)";
$State[107]="Заказ отправлен в город доставки (магистральная перевозка)";
$State[108]="Заказ передан курьеру для доставки в постамат";
$State[109]="Заказ в постамате или пункте выдачи";
$State[110]="Возврат принят в постамат.";
$State[111]="Заказ получен покупателем";
$State[112]="Срок хранения заказа в постамате истек";
$State[113]="Отправление возвращено в магазин";
$State[114]="Покупатель отказался от заказа";
$State[115]="Отправление сформированно для возврата в магазин";
$State[116]="Отправление передано на возврата в магазин";
$State[117]="Возврат передан в логистическую компанию для доставки на Сортировочный центр PickPoint";
$State[123]="Сгруппированы отправления";

	$html="";
	$sending="{\n";
	$sending.="\"SessionId\":\"{$SessionId}\",\n";
	$sql="SELECT * FROM Message51 WHERE Message_ID={$order_id} ORDER BY Message_ID ASC";
	//echo $sql."<br>";
	$html.="<h3>Заказ #{$order_id}</h3>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$invoice=$row['pickpoint_invoice'];
			$sending.="\"InvoiceNumber\":\"".$invoice."\"\n";
		}
	} 
	$sending.="}";
	//echo $sending."<br><br>";
	$lgn=sendReq("tracksending",$sending);
	//echo $lng;
	//echo "<br>Answer:<br>".$lgn."<br><br>";
	//$tmp=json_decode($lgn);
	$tmp=explode("},{",$lgn);
	//print_r($tmp);
	$html.="<h3>Баркод отправления: {$invoice}</h3>";
	foreach ($tmp as $name => $value) {
		$value=str_replace("[{","",$value);
		$value=str_replace("}]","",$value);
		$value=str_replace("\"","",$value);
		//$html.=$name."-&gt;".$value."<br>";
		$tmp1=explode(",",$value);
		foreach ($tmp1 as $n => $v) {
			$v1=explode(":",$v);
			if ($v1[0]=="ChangeDT") {
				$html.=$v1[1].":".$v1[2]."<br>";
			}
			if ($v1[0]=="State") {
				$html.=$v1[1]." ".$State[$v1[1]]."<br>";
			}
			if ($v1[0]=="StateMessage") {
				$html.=$v1[1]."<br><br>";
			}
		}
	}
	
	return $html;
}

function getOrderList($SessionId) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=16 подготовлен к отправке
	// status=17 передан в РР
	$sql="SELECT * FROM Message51 WHERE (Status=17) ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";
	$i=0;
	if ($result=mysql_query($sql)) {
		$html.="<p>Количество заказов: ".((!empty($orders)) ? count($orders) : mysql_num_rows($result))."</p>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>".((empty($orders)) ? "<td>все<br><input type='checkbox' onclick='checkAll();' id='chkall'></td><td>Дата создания</td>" : "")."
		<td>№ заказа</td>
		<td>ФИО</td>
		<td>Описание отправления</td>
		<td>Адрес доставки</td>
		".((!empty($orders)) ? "<td>сумма</td><td>Телефон</td>" : 
			"<td>Телефон<br>
				Моб. тел.</td>
			<td>Сумма заказа</td>
			<td>Стоимость доставки</td>
			<td>ИТОГО</td>
			<td>Способ оплаты</td>
			<td>статус отправления</td>")."</tr>";
		while($row = mysql_fetch_array($result)) {
			$st=($row['paid']==1) ? "" : "style='background:#ff9999;'";
			//print_r($row);
			
			$sending="{\n";
			$sending.="\"SessionId\":\"{$SessionId}\",\n";
			$invoice=$row['pickpoint_invoice'];
			$sending.="\"InvoiceNumber\":\"".$invoice."\"\n";
			$sending.="}";
			//echo $sending."<br><br>";
			$lgn=sendReq("tracksending",$sending);
			$tmp=explode("},{",$lgn);
			//print_r($tmp);
			$htmlh="<p>Баркод: {$invoice}</p>";
			$html1="";
			foreach ($tmp as $name => $value) {
				$value=str_replace("[{","",$value);
				$value=str_replace("}]","",$value);
				$value=str_replace("\"","",$value);
				//$html.=$name."-&gt;".$value."<br>";
				$tmp1=explode(",",$value);
				
				foreach ($tmp1 as $n => $v) {
					$v1=explode(":",$v);
					if ($v1[0]=="ChangeDT") {
						$html1=$v1[1].":".$v1[2]."<br>";
					}
					if ($v1[0]=="State") {
						$html1.=$v1[1]." ".$State[$v1[1]]."<br>";
					}
					if ($v1[0]=="StateMessage") {
						$html1.=$v1[1]."<br><br>";
					}
					
					if ($v1[1]==111) {
						$htmlh.="<p style='color:#00f300;'><b>Заказ выполнен!</b></p>";
						$sql="UPDATE Message51 SET closed=1,status=4 WHERE Message_ID=".$row['Message_ID'];
						//echo $sql."<br>";
						if (!mysql_query($sql)) {
							die(mysql_error());
						}
					}
					if (($v1[1]==112)||($v1[1]==114)) {
						$htmlh.="<p style='color:#f30000;'><b>Невостребовано!!!!</b></p>";
					}
				}
			}
	
			$html.="<tr {$st}>
			".((empty($orders)) ? "<td {$st}><input type='checkbox' name='oid[{$i}]' id='oid-{$row['Message_ID']}' value='{$row['Message_ID']}'></td>" : "")."
			".((empty($orders)) ? "<td>{$row['Created']}</td>" : "")."
			<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
			<td>{$row['ContactName']}</td>
			<td>".getOrderItems($row['Message_ID'],0)."</td>
			<td>{$row['pickpoint_address']}"."</td>
			".((empty($orders)) ? "<td style='white-text:nowrap;'>{$row['Phone']}<br>".(($row['mphone']!="+7") ? $row['mphone'] : "")."</td>
								<td>".getOrderCost($row['Message_ID'])."</td><td>{$row['DeliveryCost']}</td>
								<td>".(getOrderCost($row['Message_ID'])+$row['DeliveryCost'])."</td>
								<td>".getPaymentMethod($row['PaymentMethod'])."</td>
								<td>{$htmlh}{$html1}
								<p><b><a target='_blank' href='/netcat/modules/netshop/interface/pickpoint-history.php?oid={$row['Message_ID']}'>&gt;&gt;&gt;</a></b></p>
								</td>
			" : "<td>{$row['summinsurance']}</td>
			<td>".(($row['Phone']!="") ? $row['Phone'] : $row['mphone'])."</td>").
			"</tr>";
			$i=$i+1;
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	

	$html.="</table><br>";

	return $html;
}
// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	include_once ("pickpoint-api.php");
	/*//$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
	$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pickpointfiles/upload/';
	$UploadDirNarv=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pickpointfiles/narv/';
	//$UploadDir='/netcat_files/postfiles/upload/';
	//$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
	$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pickpointfiles/';
	$DownloadDirLink='/netcat_files/pickpointfiles/';
	
	if ((isset($incoming['id'])) && ($incoming['id']!="narv")) {
		//$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
		$uploadfile = $UploadDir . $incoming['id'].".csv";
		$uploadfilenarv = $UploadDirNarv . $incoming['id'].".csv";
	}*/
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	
	$show=1;
	$SessionId="";
	$loginstr = '{"Login":"russianknife", "Password":"zxcvb123"}';
	$lgn=sendReq("login",$loginstr);
	if (($lgn['ErrorMessage']=="null")&&($lgn['SessionId'])){
		$SessionId=$lgn['SessionId'];
		$_SESSION['PP_session']=$SessionId;
	} else {
		die("Error login in API!");
	}
	
	switch ($incoming['action']) {
		default:
			$html="<h1>Заказы в статусе &quot;отправлен в PickPoint&quot;</h1><br clear='all'>".getOrderList($SessionId);
			break;
	}
	
	//echo $where;
//print_r($_SESSION);
if ($show) {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Работа с PickPoint</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script>
function checkAll() {
	var boxes = document.getElementsByTagName('input');
	var chk=false;
	if (document.getElementById("chkall").checked) {
		chk=true;
	}
	for (i=0; i<boxes.length; i++)  {
		if (boxes[i].type == 'checkbox')   {
			boxes[i].checked=chk;
		}	
	}

}
function clickEdit() {
	var len=0;
	var j=1;
	var boxes = document.getElementsByTagName('input');
	if (document.getElementById("action").value=="create") {
		for (i=0; i<boxes.length; i++)  {
			if (boxes[i].type == 'checkbox')   {
				if (boxes[i].checked) {
					//alert(j);
					return true;
				}
				j=j+1;
			}	
		}
		document.getElementById("err").style.display="block";
		return false;
	} else {
		return true;
	}
	
}
	</script>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>
<body>
<?php
//print_r($_SESSION);
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php
}
mysql_close($con);
?>

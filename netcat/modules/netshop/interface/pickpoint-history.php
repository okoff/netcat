<?php
// 23.03.2016 Elen
// работа с PickPoint отправлениями
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("pickpoint-api.php");

$ikn="9990394012";


function getstatus($SessionId,$incoming,$ikn) {
	// getInvoicesChangeState
	$html="";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n
	\"DateFrom\":\"\",
	\"DateTo\":\"\",
	\"State\":101
	}";
	echo $sending;
	$lgn=sendReq("getInvoicesChangeState",$sending);
	//echo $lng;
	echo "<br>Answer:<br>".$lgn."<br>";
	
	
	return $html;
}

function printArray($value) {
	$html=""; //printArray";
	foreach($value as $n=>$v) {
		if(is_array($v)){
			$html.=printArray($v);
		} else {
			$html.="&nbsp;&nbsp;&nbsp;".$n."-&gt;".$v."<br>";
		}
	}
	return $html;
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
$SessionId="";
//echo $SessionId." ".$_SESSION['PP_session']."<br>";
//if ($_SESSION['PP_session']!="") {
//	$SessionId=$_SESSION['PP_session'];
//} else {
	// login. start session. get session id
	//$loginstr = '{"Login":"apitest", "Password":"apitest"}';
	$loginstr = '{"Login":"russianknife", "Password":"zxcvb123"}';
	$lgn=sendReq("login",$loginstr);
	if (($lgn['ErrorMessage']=="null")&&($lgn['SessionId'])){
		$SessionId=$lgn['SessionId'];
		$_SESSION['PP_session']=$SessionId;
	} else {
		die("Error login in API!");
	}
//}
if ((isset($incoming['oid']))&&($incoming['oid']!="")) {
	$order_id=intval($incoming['oid']);
} else {
	die("Не указан номер заказа");
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Работа с PickPoint</title>
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
//print_r($_SESSION);
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
?>	
<h3>Информация по отправлению PickPoint</h3>
<?php
	echo tracksendings($SessionId,$incoming,$ikn,$order_id);
?>
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

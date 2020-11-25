<?php
// 23.03.2016 Elen
// работа с PickPoint отправлениями
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-mysqli.php");
include_once ("utils-template.php");
include_once ("cdek-api.php");

/*
ИМ отправляет POST запрос на адрес:
? для документа «Список заказов на доставку» http://int.cdek.ru/new_orders.php ? https://integration.cdek.ru/new_orders.php
? для документа «Прозвон получателя» http://int.cdek.ru/new_schedule.php ?
? для документа «Вызов курьера» http://int.cdek.ru/call_courier.php ?
? для документа «Список заказов на удаление» http://int.cdek.ru/delete_orders.php .
? для документа «Печатная форма квитанции к заказу» http://int.cdek.ru/orders_print.php .
с заполненной переменной $_POST['xml_request'], в которой передается содержимое XML фaйла.
*/
 

// функции создания запросов
function createsending($con,$incoming,$secure_account,$secure_password) {
	//print_r($incoming);
	$url="";
	$html="";
	$html.="<h3>Регистрация отправлений</h3>";
	$itemscreated="";
	$id=0;
	$dom = "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; // Создаём XML-документ версии 1.0 с кодировкой utf-8
	$dte1=date("Y-m-d H:i:s");
	$dte=date("Y-m-d")."T".date("H:i:s");
	$secure = md5($dte.'&'.$secure_password);
	// получаем номер отправки из БД
	$fid=intval($incoming['f']);
	$itcre="";
	$query="SELECT * FROM Netshop_CDEKFiles WHERE id=".$fid;
	$result=db_query($con,$query);
	while ($row = mysqli_fetch_array($result)) {
		$id=$row['id'];
		$strorders=$row['ppfile'];
		$itcre=$row['itemscreated'];
	}
	
	$orders=explode(";",$strorders);
	
	$ordercount=count($orders)-1;
	
	
	$dom.="<DeliveryRequest Number=\"{$id}\" Date=\"{$dte}\" Account=\"{$secure_account}\" Secure=\"{$secure}\" OrderCount=\"{$ordercount}\">";
	foreach ($orders as $o) {
		if ($o!="") {
			$query="SELECT * FROM Message51 WHERE Message_ID=".intval($o);
			$result1=db_query($con,$query);
			while ($row1 = mysqli_fetch_array($result1)) {
				//$dom.="<Order Number=\"{$o}\" DeliveryRecipientCost=\"{$row1['DeliveryCost']}\" SendCityPostCode=\"111033\" RecCityPostCode=\"".$row1['PostIndex']."\" RecipientName=\"".convstr($row1['ContactName'])."\" Phone=\"{$row1['Phone']}\" Comment=\"".convstr(htmlspecialchars($row1['Comments']))."\" TariffTypeCode=\"137\" RecientCurrency=\"RUB\" ItemsCurrency=\"RUB\" >";
				//$dom.="<Order Number=\"{$o}\" DeliveryRecipientCost=\"0\" SendCityPostCode=\"111033\" RecCityPostCode=\"".$row1['PostIndex']."\" RecipientName=\"".convstr($row1['ContactName'])."\" Phone=\"{$row1['Phone']}\" Comment=\"".convstr(htmlspecialchars($row1['Comments']))."\" TariffTypeCode=\"137\" RecientCurrency=\"RUB\" ItemsCurrency=\"RUB\" DeliveryRecipientVATRate=\"БЕЗ НДС\" DeliveryRecipientVATSum=\"0\" >";
				$cdek_pvz = ((strlen($row1['cdek_pvz'])>1) ? " PvzCode=\"".$row1['cdek_pvz']."\" TariffTypeCode=\"136\" " : " TariffTypeCode=\"137\"");
				if ($row1['paid']==1) {
					$dom.="<Order Number=\"{$o}\" DeliveryRecipientCost=\"0\" SendCityPostCode=\"111033\" RecCityPostCode=\"".$row1['PostIndex']."\" RecCityCode=\"".$row1['cdek_cityid']."\" RecipientName=\"".convstr($row1['ContactName'])."\" Phone=\"{$row1['Phone']}\" Comment=\"".convstr(htmlspecialchars($row1['Comments']))."\"  RecientCurrency=\"RUB\" ItemsCurrency=\"RUB\" DeliveryRecipientVATRate=\"VATX\" DeliveryRecipientVATSum=\"0\" ".$cdek_pvz." >";
				} else {
					$dom.="<Order Number=\"{$o}\" DeliveryRecipientCost=\"{$row1['DeliveryCost']}\" SendCityPostCode=\"111033\" RecCityPostCode=\"".$row1['PostIndex']."\" RecipientName=\"".convstr($row1['ContactName'])."\" Phone=\"{$row1['Phone']}\" Comment=\"".convstr(htmlspecialchars($row1['Comments']))."\" RecientCurrency=\"RUB\" ItemsCurrency=\"RUB\" DeliveryRecipientVATRate=\"VATX\" DeliveryRecipientVATSum=\"0\" ".$cdek_pvz." >";
				}
				$dom.="<Address Street=\"".convstr($row1['Street'])."\" House=\"".convstr($row1['House'])."\" Flat=\"".convstr($row1['Flat'])."\" />";
				$dom.="<Package Number=\"1\" BarCode=\"101\" Weight=\"".$row1['weight']."\" >";
				// состав заказа
				$query2="SELECT Netshop_OrderGoods.*,Message57.ItemID,Message57.Name,Message57.weight,Message57.fullweight FROM Netshop_OrderGoods 
					INNER JOIN Message57 on (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
				WHERE Order_ID={$o}";
				//echo $query2;
				$result2=db_query($con,$query2);
				while ($row2=mysqli_fetch_array($result2)) {
					//echo convstr($row2['Name'])."<br>".mb_detect_encoding($row2['Name'])."<br>";
					$itemart=convstr($row2['ItemID']);
					$name=str_ireplace("Нож", "Сувенир", convstr($row2['Name']));
					$name=str_replace("Ножи", "Сувениры", $name);
					$name=str_replace("нож", "сувенир", $name);
					$name=str_replace("ножи", "сувениры", $name);
					$name=str_replace("Куябрик", "Сувенир", $name);
					$name=str_replace("куябрик", "сувенир", $name);
					$name=str_replace("ножей", "сувениров", $name);
					$name=str_replace("метательных", "", $name);
					$name=str_replace("Метательный", "", $name);
					$name=str_replace("\"", "", $name);
					$name=str_replace("&", "", $name);
					$name=str_replace("<", "", $name);
					$name=str_replace(">", "", $name);
					//echo $name."<br>";
					//$name=convstrw($name);
					//$dom.="<Item WareKey=\"".$itemart."\" Cost=\"".$row2['ItemPrice']."\" Payment=\"0\" Weight=\"".((($row2['fullweight']!="")&&($row2['fullweight']!=null)&&($row2['fullweight']!=0)) ? $row2['fullweight'] : $row2['weight'])."\" Amount=\"".$row2['Qty']."\" Comment=\"".$name."\" />";
					if ($row1['paid']==1) {
						$dom.="<Item WareKey=\"".$itemart."\" Cost=\"".$row2['ItemPrice']."\" Payment=\"0\" Weight=\"".((($row2['fullweight']!="")&&($row2['fullweight']!=null)&&($row2['fullweight']!=0)) ? $row2['fullweight'] : $row2['weight'])."\" Amount=\"".$row2['Qty']."\" Comment=\"".$name."\" />";
					} else {
						$dom.="<Item WareKey=\"".$itemart."\" Cost=\"".$row2['ItemPrice']."\" Payment=\"".$row2['ItemPrice']."\" Weight=\"".((($row2['fullweight']!="")&&($row2['fullweight']!=null)&&($row2['fullweight']!=0)) ? $row2['fullweight'] : $row2['weight'])."\" Amount=\"".$row2['Qty']."\" Comment=\"".$name."\" />";
					}
				}
				$dom.="</Package>";
				//$dom.="<Schedule><Attempt ID=\"1\" Date=\"2016-12-12\" TimeBeg=\"09:00:00\" TimeEnd =\"20:00:00\" RecipientName=\"".htmlspecialchars($row1['ContactName'])."\" /></Schedule>";
				$dom.="</Order>";

			}
		}
	}
	$dom.="</DeliveryRequest>";
	
	echo $dom."<br>";
	//echo mb_detect_encoding($dom)."<br><br>";
	$resreq=array();
	$resreq=post_request("https://integration.cdek.ru/new_orders.php",$dom,"");

	//print_r($resreq);
	
	if ($resreq['status']=="ok") {
		//$html.=htmlspecialchars($resreq['content']);
		$xml=$resreq['content'];
		$p = xml_parser_create();
		xml_parse_into_struct($p, $xml, $vals, $index);
		xml_parser_free($p);
		//echo "Index array\n";
		//print_r($index);
		//echo "\nМассив Vals\n";
		//print_r($vals);
		//< xml version="1.0" encoding="UTF-8" ><response><Order Number="33759" DispatchNumber="1034918768"/><Order Msg="Добавлено заказов 1"/></response>
		foreach ($vals as $v) {
			if ($v['tag']=="ORDER") {
				if ($v['attributes']['ERRORCODE']!="") {
					$html.=$v['attributes']['NUMBER']."<br>";
					$html.=$v['attributes']['ERRORCODE']."<br>";
					$html.=$v['attributes']['MSG']."<br>";
				}
				if ($v['attributes']['DISPATCHNUMBER']!="") {
					$html.="<p>Заказ {$v['attributes']['NUMBER']} зарегистрирован.</p>";
					$sql="UPDATE Message51 SET cdek_barcode='{$v['attributes']['DISPATCHNUMBER']}' 
						WHERE Message_ID=".intval($v['attributes']['NUMBER']);
					//echo $sql."<br>";
					if (db_query($con,$sql)) {
						$html.="Заказ ".intval($v['attributes']['NUMBER'])." обработан. <br>";
					} 
					$itemscreated.=intval($v['attributes']['NUMBER']).";";
				}
			}
			$html.="<br>";
			
			
		}
		
	} else {
		$html="<p>Ошибка</p>".$resreq['error'];
	}
	
	$sql="UPDATE Netshop_CDEKFiles SET registered='".date("Y-m-d H:i:s",strtotime($dte1))."',
		itemscreated='".$itcre.$itemscreated."'
		WHERE id=".$fid;
	if (db_query($con,$sql)) {
		$html.="<b>Список заказов обработан.</b><br>";
	} 
	
	$html.="<p><a href='/netcat/modules/netshop/interface/order-cdek.php?action=view'>Вернуться в список отправлений</a></p>";
	return $html; //$sending;
}

/*
< ?xml version="1.0" encoding="UTF­8" ? >
<OrdersPrint Date="2011­09­15"
Account="123" Secure="123" OrderCount="3" CopyCount="2">
<Order Number="634686069092845559" Date="2012­03­29" />
<Order DispatchNumber="2894484" />
<Order Number="634686069092845560" Date="2012­03­29" />
</OrdersPrint>
*/
function createlabels($con,$incoming,$secure_account,$secure_password) {
	// print from
	$html="";
	$html.="<h3>Печать квитанций</h3>";
	$UploadDir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/cdekfiles/labels/";
	//print_r($incoming);
	$url="";
	$html="";
	$itemscreated="";
	$id=0;
	$dom = "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; // Создаём XML-документ версии 1.0 с кодировкой utf-8
	$dte1=date("Y-m-d H:i:s");
	$dte=date("Y-m-d");//."T".date("H:i:s");
	$secure = md5($dte.'&'.$secure_password);
	// получаем номер отправки из БД
	$fid=intval($incoming['f']);
	$query="SELECT * FROM Netshop_CDEKFiles WHERE id=".$fid;
	$result=db_query($con,$query);
	while ($row = mysqli_fetch_array($result)) {
		$id=$row['id'];
		$strorders=$row['itemscreated'];
	}
	//echo $strorders."<br>";
	$orders=explode(";",$strorders);
	
	$ordercount=count($orders)-1;

	$dom.="<OrdersPrint Date=\"{$dte}\" Account=\"{$secure_account}\" Secure=\"{$secure}\" OrderCount=\"{$ordercount}\" CopyCount=\"1\">";
	foreach ($orders as $o) {
		if ($o!="") {
			$query="SELECT Message_ID,cdek_barcode FROM Message51 WHERE Message_ID=".intval($o);
			$result1=db_query($con,$query);
			while ($row1 = mysqli_fetch_array($result1)) {
				$dom.="<Order DispatchNumber=\"".$row1['cdek_barcode']."\" />";
				$html.="<p>Заказ #{$row1['Message_ID']} <a target='_blank' href='http://api.cdek.ru/orderPrint/?orders={$row1['cdek_barcode']}'>печать</a></p>";
			}
		}
	}
	$dom.="</OrdersPrint>";
	
	//echo htmlspecialchars($dom)."<br>";
	//$resreq=array();
	//$resreq=post_request_pdf("http://int.cdek.ru/orders_print.php",$dom,$fid,$UploadDir);

	/*if ($resreq==0) {
		$html.="<p>Ошибка! Заказ не найден</p>";
	} else {
		$file=$fid.".pdf";
		$html.="<p><a target='_blank' href='/netcat_files/cdekfiles/labels/".$file."'>Печать квитанции</a></p>";
	}*/
	/*if (strpos($resreq,"?xml")>0) {
		// error
		$xml=$resreq;
		$p = xml_parser_create();
		xml_parse_into_struct($p, $xml, $vals, $index);
		xml_parser_free($p);
		foreach ($vals as $v) {
			$html.="<p>".htmlspecialchars($v['Msg'])."</p>";
		}
	} else {*/
		
		//$file=$fid.".pdf";
		//file_put_contents($UploadDir.$file, $resreq);		
	//}
	
	
	$html.="<p><a href='/netcat/modules/netshop/interface/order-cdek.php?action=view'>Вернуться в список отправлений</a></p>";
	return $html; //$sending;
	
}

function getstatus($SessionId,$incoming,$ikn) {
	// getInvoicesChangeState
	$html="";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n
	\"DateFrom\":\"\",
	\"DateTo\":\"\",
	\"State\":101
	}";
	//echo $sending;
	$lgn=sendReq("getInvoicesChangeState",$sending);
	//echo $lng;
	//echo "<br>Answer:<br>".$lgn."<br>";
	
	
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



function tracksendings($con,$incoming,$secure_account,$secure_password) {
	//$html.="<h3>Трассировка отправлений</h3>";
	//print_r($incoming);
	if ((isset($incoming["orderid"]))&&($incoming["orderid"]!="")) {
		// запрос по 1 отправлению
		// get dispatch number (barcode cdek)
		$query="SELECT * FROM Message51 WHERE Message_ID=".intval(trim($incoming["orderid"]));
		//echo "<br>".$query;
		$result=db_query($con,$query);
		$dnumber="";
		while ($row = mysqli_fetch_array($result)) {
			$dnumber=$row['cdek_barcode'];
		}
		//echo "<br>".$dnumber;
		$dom = "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; // Создаём XML-документ версии 1.0 с кодировкой utf-8
		$dte1=date("Y-m-d H:i:s");
		$dte=date("Y-m-d");//."T".date("H:i:s");
		$secure = md5($dte.'&'.$secure_password);
		$lastmonth = mktime(0, 0, 0, date("m"), date("d")-10,   date("Y"));
		$dom.="<StatusReport Date=\"".$dte."\" Account=\"".$secure_account."\" Secure=\"".$secure."\" ShowHistory=\"1\" ShowReturnOrder=\"1\" ShowReturnOrderHistory=\"1\">
		<Order DispatchNumber=\"".$dnumber."\" />
	</StatusReport>";
		//echo htmlspecialchars($dom)."<br>";
		
		$lgn=post_request_trass("http://int.cdek.ru/status_report_h.php",$dom,"");
	} else {
	
	
		// tracksendings
		$dom = "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; // Создаём XML-документ версии 1.0 с кодировкой utf-8
		$dte1=date("Y-m-d H:i:s");
		$dte=date("Y-m-d H:i:s"); //$incoming['max'];
		
		$secure = md5($dte.'&'.$secure_password);
		$lastmonth = mktime(0, 0, 0, date("m"), date("d")-10,   date("Y"));
		$dom.="<StatusReport Date=\"".$dte."\" Account=\"".$secure_account."\" Secure=\"".$secure."\" ShowHistory=\"1\">
		<ChangePeriod DateFirst=\"".date("Y-m-d",$lastmonth)."\" DateLast=\"".date("Y-m-d",strtotime($dte))."\" />
	</StatusReport>";
		//echo htmlspecialchars($dom)."<br>";
		
		$lgn=post_request_trass("http://int.cdek.ru/status_report_h.php",$dom,"");
	}
		//print_r($lgn);
		
	if ($lgn['status']=="ok") {
		//echo htmlspecialchars($lgn['content']);
		$res=explode("\n\r",$lgn['content']);
		//print_r($res);
		//echo strpos($res[1],"x");
		$xmltext=substr($res[1], (strpos($res[1],"<?xml")));
		$xmltext=substr($xmltext, 0, (strpos($xmltext,"</StatusReport>")+15));
		//echo "<br><br>";
		//echo htmlspecialchars($xmltext);
		//echo "<br><br>";
		
	
		$tx=new SimpleXMLElement($xmltext);

		if (is_object($tx)) {
			//echo $tx->child;
			//$tmp=simplexml_load_string ($res);
			//var_dump($tx);
			$html="<table cellpadding='2' cellspacing='0' border='1'>";
			$html.="<tr>
				<td>Номер заказа</td>
				<td>Получатель</td>
				<td>Дата изменения статуса</td>
				<td>Статус</td>
			</tr>";
			foreach($tx as $order){
				//print_r($order);
				//print_r($order->State);
				//echo "<br><br>";
				$query="SELECT Message51.*,Classificator_ShopOrderStatus.*,Message55.Name AS DeliveryName FROM Message51 
					INNER JOIN Classificator_ShopOrderStatus ON ( Classificator_ShopOrderStatus.`ShopOrderStatus_ID` = Message51.`Status` ) 
					INNER JOIN Message55 ON ( Message55.`Message_ID` = Message51.`PaymentMethod` ) 
				WHERE Message51.Message_ID=".intval(trim($order['Number']));
				//echo "<br>".$query;
				$result=db_query($con,$query);
				$status="";
				$paid=0;
				
				while ($row = mysqli_fetch_array($result)) {
					$status=convstr($row['ShopOrderStatus_Name']);
					$paid=$row['paid'];
					$delivery=convstr($row['DeliveryName']);
				}
				$style=(($paid==0) ? "background:#ffcccc;" : "");
				$html.="<tr>";
				$html.="<td style='".$style."'><a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message=".$order['Number']."'>".$order['Number']."</a><br>".
				"<b>".$status."</b><br>".
				"<i>".$delivery."</i><br>".
				(($paid==1) ? "оплачен" : "")."
				</td>";
				$html.="<td>".$order['RecipientName']."</td>";
				$html.="<td>".date("d.m.Y",strtotime($order->Status['Date']))."</td>";
				$html.="<td><!--".$order->Status['Code']." -->".$order->Status['Description']."<br>
				<div style='font-size:11px;'>";
				foreach($order->Status->State as $state) { 
					//var_dump($state);
					//echo "<br><br>";
					$html.=date("d.m.Y H:i:s",strtotime($state['Date']))." ".$state['Description']."<br>";
				}
				$html.="</div></td>";
				$html.="</tr>";
			}
			$html.="</table>";
		} else {
			$orders=explode("</Order>",$xmltext);
			print_r($orders);
		}
		
	}
	
	return $html;
}
function callcourier($con,$incoming,$secure_account,$secure_password) {
	// courier
	$html="";//<h3>Вызов курьера</h3>";
	$dom="<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	$dte1=date("Y-m-d H:i:s");
	$dte=date("Y-m-d");//."T".date("H:i:s");
	$secure = md5($dte.'&'.$secure_password);
	
	$fid=intval($incoming['f']);
	
	$dom.="<CallCourier Date=\"".$dte."\" Account=\"".$secure_account."\" Secure=\"".$secure."\" CallCount=\"1\">";
	
	$sql="SELECT * FROM Netshop_CDEKFiles WHERE id={$fid}";
	$result=db_query($con,$sql);
	while($row = mysqli_fetch_array($result)) {
		$dom.="<Call Date =\"".date("Ymd", strtotime($row['collection_date']))."\" 
TimeBeg =\"".date("h:i",$row['collection_timestart'])."\" 
TimeEnd=\"17:00\" 
SendCityCode =\"44\" 
SenderName =\"".htmlspecialchars_decode(iconv("cp1251","UTF-8",$row['collection_fio']))."\" 
Weight =\"".$row['weight']."\" 
Comment =\"".htmlspecialchars_decode(iconv("cp1251","UTF-8",$row['comment']))."\" 
SendPhone =\"84952255492\">
<Address Street=\"Таможенный проезд\" House =\"6 стр. 9\" Flat =\"212\" />
</Call>";
	}
$dom.="</CallCourier>";

	echo htmlspecialchars($dom)."<br>";
	
	$lgn=post_request("http://int.cdek.ru/call_courier.php",$dom,"");
	
	print_r($lgn);

	if ($lgn['status']=="ok") {
		$html.=$lgn['content'];
	}

	$html.="<p><a href='/netcat/modules/netshop/interface/order-cdek.php?action=view'>Вернуться в список отправлений</a></p>"; 
	return $html;
}


isLogged();

// work
$secure_account="966b523203ee0dd09485f1af91c40bf3";
$secure_password="a082f619378a85387c0cedc22dd1fd6b";

// test
//$secure_account="3ef9222e09776c3973bef6517f6a66fc";
//$secure_password="2f39c3d7b05422ab5635488525957d45";
	
$incoming = parse_incoming();
$con=db_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);

echo printHeader("СДЭК");
echo printTopMenu($con);

$show=1;

switch ($incoming['action']) {
	case "listpvz":
		$res=get_request("http://int.cdek.ru/pvzlist.php","type=ALL");
		echo $res['xml'];
		break;
	case "register":
		// регистрация отправлений
		$html=createsending($con,$incoming,$secure_account,$secure_password);
		break;
	case "getlabel":
		// формирование этикеток
		$html=createlabels($con,$incoming,$secure_account,$secure_password);
		break;
	case "getstatus":
		// формирование этикеток
		$html=getstatus($SessionId,$incoming,$ikn);
		break;
	case "tracksendings":
		// трассировка отправлений
		//print_r($incoming);
		$html.="<h3>Трассировка всех отправлений</h3>";
		$html.=tracksendings($con,$incoming,$secure_account,$secure_password);
		break;
	case "courier":
		// вызов курьера
		$html.="<h3>Вызов курьера</h3>";
		$html.=callcourier($con,$incoming,$secure_account,$secure_password);
		break; 
	default:
		break;
}

if ($show) {
	echo $html; 
}

db_close($con);
printFooter();
?>

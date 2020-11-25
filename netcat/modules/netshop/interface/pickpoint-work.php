<?php
// 23.03.2016 Elen
// работа с PickPoint отправлениями
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("pickpoint-api.php");

$ikn="9990394012";

// функции создания запросов
function createsending($SessionId,$incoming,$ikn) {
	$html="";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n";
	$sending.="\"Sendings\":[\n";
	$fid=intval($incoming['f']); // id отправки
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id=".$fid;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$orders=explode(";",$row["ppfile"]);
			$n=count($orders);
			$i=0;
			foreach($orders as $o) {
				if ($o!="") {
					$description="Заказ ".intval($o);
					$sql1="SELECT * FROM Message51 WHERE Message_ID=".intval($o);
					if ($result1=mysql_query($sql1)) {
						while($row1=mysql_fetch_array($result1)) {
							$sending.="{\n";
							$sending.="\"EDTN\":\"{$o}\",\n";
							$sending.="\"IKN\":\"{$ikn}\",\n";
							$sending.="\"Invoice\":{\n";
							$sending.="\"SenderCode\":\"{$o}\",\n";
							$sending.="\"BarCode\":\"\",\n";
							$sending.="\"GCBarCode\":\"\",\n";
							//$sending.="\"Description\":\"".iconv("WINDOWS-1251","UTF-8", $description)."\",\n"; //getOrderItems($o)
							$sending.="\"Description\":\"".$description."\",\n"; //getOrderItems($o)
							//$sending.="\"RecipientName\":\"".iconv("WINDOWS-1251","UTF-8", $row1['ContactName'])."\",\n";
							$sending.="\"RecipientName\":\"".$row1['ContactName']."\",";
							$sending.="\"PostamatNumber\":\"{$row1['pickpoint_id']}\",\n";
							$sending.="\"MobilePhone\":\"".((($row1['mphone'])&&($row1['mphone']!="+7")) ? $row1['mphone'] : $row1['Phone'])."\",\n";
							$sending.="\"Email\":\"{$row1['Email']}\",\n";
							$sending.="\"PostageType\":10001,\n"; // стандарт
							$sending.="\"GettingType\":101,\n"; // вызов курьера
							$sending.="\"PayType\":1,\n"; //всегда
							//$sending.="\"Sum\":\"".getOrderCost($o)."\",\n";
							//$sending.="\"InsuareValue\":\"{$row1['summinsurance']}\",\n";
							$sending.="\"Sum\":\"0\",\n";
							$sending.="\"InsuareValue\":\"".getOrderCost($o)."\",\n";
							$sending.="\"Width\":\"10\",\n"; //10*36*40 XS
							$sending.="\"Height\":\"36\",\n";
							$sending.="\"Depth\":\"40\",\n";
							//$sending.="\"Depth\":\"40\",\n";
							$sending.=SenderCity()."\n";
							$sending.=ClientReturnAddress()."\n";
							$sending.=UnclaimedReturnAddress()."\n";
							//$sending.=",".SubEncloses($o)."\n";
							
							
							$sending.="}\n";
							$sending.="}";
						}
					}
				}
				$i=$i+1;
				$sending.=($i<($n-1)) ? ",\n" : "\n";
			}
		}
	}
	$sending.="]\n";
	$sending.="}\n";
	//echo $sending."<br>";
	$lgn=sendReq("createsending",$sending); //!!!!!!!!!!!!!!
	//$lgn=sendReq("CreateShipment",$sending);
	//echo "<!-- Answer:<br>".$lgn."-->";
	
	//$lgn="{\"CreatedSendings\":[{\"EDTN\":\"39985\",\"InvoiceNumber\":\"15939384300\",\"Barcode\":\"202031512624\",\"Places\":[{\"BarCode\":\"202031512624\",\"GCBarCode\":\"\"}],\"SenderCode\":\"39985\"},{\"EDTN\":\"40001\",\"InvoiceNumber\":\"15939384299\",\"Barcode\":\"202031512617\",\"Places\":[{\"BarCode\":\"202031512617\",\"GCBarCode\":\"\"}],\"SenderCode\":\"40001\"}],\"RejectedSendings\":[]}";
	//echo "<!-- Answer:<br>".$lgn."-->";
	
/*	$headers='Content-type: text/plain; UTF-8'."\r\n".
'From: Интернет-магазин Русские ножи <admin@russian-knife.ru>'."\r\n";
	mail("elena@best-hosting.ru", "PP answer", htmlspecialchars($sending."<br>".$lng), $headers);*/
	// разбор ответа
	$tmp=explode("RejectedSendings",$lgn);
	
	$html.="<h2>Регистрация отправлений. Ответ PickPoint</h2>";
	$itemscreated=$itemsrejected="";
	//print_r($tmp);
	//echo "<br><br>";
	// $tmp[0] =>  {"CreatedSendings":[],"
	// $tmp[1] =>  ":[{"Error":"Возможно неверный формат телефона.","ErrorCode":20,"EDTN":"29303","ErrorMessage":"Возможно неверный формат телефона."} 
	// обработка полученного кода
	//“InvoiceNumber”:	”<Номер отправления присвоенный PickPoint (20 символов)>”,
	//“Barcode”:	”< Штрих код от PickPoint (50 символов, геренируется, если не было во входящем запросе)>”
	//  {"CreatedSendings":[{"EDTN":"29309","InvoiceNumber":"15931269890","Barcode":"201539123851","Places":null},{}]
	if (strlen($tmp[0])>1) {
		//$str=strstr($tmp[0], '[');
		//$str=strstr($str, ']', true); 
		//$str=substr($str,2);
		//$str=substr($str,0,-1);
		$str=$tmp[0];
		$str=str_replace("CreatedSendings","",$str);
		$str=str_replace("\"\":","",$str);
		//echo $str."\n\n\n\n";
		$cre=explode("},{",$str);
		//print_r($cre);
		//echo "<br>";
		//echo count($cre)."<br>";
		for ($j=0;$j<count($cre);$j++) {
			if ($cre[$j]!="") {
				$order_id=0;
				$invoice="";
				$barcode="";
				//echo $j."-".$cre[$j]."<br>"; 
				$str=$cre[$j];
				$str=str_replace("[","",$str);
				$str=str_replace("]","",$str);
				$str=str_replace("{","",$str);
				$str=str_replace("}","",$str);
				$str=str_replace("\"","",$str);
				$m=explode(",",$str);
				//print_r($m);
				//echo "<br>";
				foreach($m as $m1) {
					$m2=explode(":",$m1);
					switch (strtolower($m2[0])) {
						case "edtn":
							$order_id=$m2[1];
							break;
						case "invoicenumber":
							$invoice=$m2[1];
							break;
						case "barcode":
							$barcode=$m2[1];
							break;
						default:break;
					}
				}
				$sql="UPDATE Message51 SET pickpoint_invoice='{$invoice}',
					pickpoint_barcode='{$barcode}'
				WHERE Message_ID=".intval($order_id);
				//echo $sql."<br>";
				if (mysql_query($sql)) {
					$html.="Заказ ".$order_id." обработан. <br>";
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
				if ($order_id!=0) {
					$itemscreated.=$order_id.";";
				}
				
			}	
		}
	}
	
	// обработка отказа
	if (strlen($tmp[1])>1) {
		$rej=explode("{",$tmp[1]);
		//print_r($rej);
		// начиная с 1 получаем запись ошибки для каждого отправления
		for ($j=1;$j<=count($rej);$j++) {
			$str=substr($rej[$j],0,strlen($rej[$j])-1);
			$item=explode(",",$str);
			//print_r($item);
			$order_id=0;
			$html.="<p>";
			foreach ($item as $it) {
				$m=explode(":",$it);
				//print_r($m);
				$m[0]=str_replace("\"","",$m[0]);
				$m[1]=str_replace("\"","",$m[1]);
				$m[1]=str_replace("}","",$m[1]);
				
				switch (strtolower($m[0])) {
					case "edtn":
						$order_id=$m[1];
						$html.="<b><a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$m[1]}'>".$m[1]."</a></b> ";
						break;
					case "errorcode":
						$html.="[".$m[1]."] ";
						break;
					case "errormessage":
						$html.=$m[1]." <b style='color:#f30000;'>отправление не зарегистрировано</b>";
						break;
				}
				
			}
			if ($order_id!=0) {
				$itemsrejected.=$order_id.";";
			}
		}
	}
	$sql="UPDATE Netshop_PickpointFiles SET registered='".date("Y-m-d H:i:s")."',
		itemscreated='{$itemscreated}',
		itemsrejected='{$itemsrejected}' 
		WHERE id=".$fid;
	if (mysql_query($sql)) {
		$html.="<b>Список заказов обработан.</b><br>";
	} else {
		die($sql."Ошибка: ".mysql_error());
	}
	$html.="<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
	return $html; //$sending;
}

function createlabels($SessionId,$incoming,$ikn) {
	// makelabel
	$html="";
	$html.="<h3>Получение этикеток</h3>";
	$UploadDir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/pickpointfiles/labels/";
	
	$fid=intval($incoming['f']); // id отправки
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id=".$fid;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$orders=explode(";",$row["itemscreated"]);
			foreach($orders as $o) {
				if ($o!="") {
					// получаем этикетки
					$sql="SELECT pickpoint_invoice,pickpoint_barcode 
							FROM Message51 WHERE Message_ID=".$o;
					if ($result=mysql_query($sql)) {
						while($row = mysql_fetch_array($result)) {
							$invoice=$row['pickpoint_invoice'];
						}
					}
					$sending="{";
					$sending.="\"SessionId\":\"{$SessionId}\",\n";
					$sending.="\"Invoices\":[\n";
					$sending.="\"".$invoice."\"";
					$sending.="]\n
					}";
					//echo $sending;
					$lgn=sendReq("makelabel",$sending);
					//echo $lng;
					//echo "<br>Answer:<br>".$lgn."<br>";
					if (stripos($lgn, "error")!=false) {
						$html.="Ошибка ".$lgn."<br>";
					} else {	
						$file=$invoice.".pdf";
						//$current = file_get_contents($UploadDir.$file);
						//$current.=$lgn;
						file_put_contents($UploadDir.$file, $lgn);
						$sql="UPDATE Message51 SET pickpoint_label=1 WHERE Message_ID=".$o;
						if (mysql_query($sql)) {
							$html.="Этикетка для заказа {$o} получена.<br>";
						} else {
							die($sql."Ошибка: ".mysql_error());
						}
					}
				}
			}
		}
	}
	// сохраняем признак, что этикетки получены
	$sql="UPDATE Netshop_PickpointFiles SET getlabel='".date("Y-m-d H:i:s")."'
			WHERE id=".$fid;
	if (mysql_query($sql)) {
		$html.="<p><b>Все этикетки получены.</b></p>
		<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
	} else {
		die($sql."Ошибка: ".mysql_error());
	}		
	return $html;
}
function getreestrnumber($SessionId,$incoming,$ikn) {
	// getreestrnumber
	$html="";
	$fid=intval($incoming['f']); // id отправки
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id=".$fid;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$orders=explode(";",$row["itemscreated"]);
			foreach($orders as $o) {
				if ($o!="") {
					// получаем этикетки
					$sql="SELECT pickpoint_invoice,pickpoint_barcode 
							FROM Message51 WHERE Message_ID=".$o;
					if ($result=mysql_query($sql)) {
						while($row = mysql_fetch_array($result)) {
							$invoice=$row['pickpoint_invoice'];
							$sending="{";
							$sending.="\"SessionId\":\"{$SessionId}\",\n";
							$sending.="\"InvoiceNumber\":";
							$sending.="\"".$invoice."\"";
							$sending.="}";
							//echo $sending;
							$lgn=sendReq("getreestrnumber",$sending);
							//echo $lgn;
							$str=substr($lgn,0,-1);
							$str=substr($str,1);
							$str=str_replace("\"","",$str);
							$tmp=explode("Number",$str);
							//print_r($tmp);
							$n=substr($tmp[1],1);
							//echo $n;
							if ($n!="null") {
								$sql="UPDATE Message51 SET pickpoint_reestr={$n} WHERE Message_ID=".$o;
								if (mysql_query($sql)) {
									$html.="<p>Заказ {$o} - номер реестра {$n}</p>";
								} else {
									die($sql."Ошибка: ".mysql_error());
								}
							}
						}
					}
					if ($n!="null") {
						$file=$invoice.".pdf";
						$current = file_get_contents($UploadDir.$file);
						$current.=$lgn;
						file_put_contents($UploadDir.$file, $current);
						$sql="UPDATE Message51 SET pickpoint_label=1 WHERE Message_ID=".$o;
						if (!mysql_query($sql)) {
							die($sql."Ошибка: ".mysql_error());
						}
					}
				}
			}
		}
	}
	// сохраняем номер реестра
	$sql="UPDATE Netshop_PickpointFiles SET reestr='".$n."'
			WHERE id=".$fid;
	if (mysql_query($sql)) {
		$html.="<p><b>Номер получен и сохранен.</b></p>
		<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
	} else {
		die($sql."Ошибка: ".mysql_error());
	}	
	return $html;
}

function createreestr($SessionId,$incoming,$ikn) {
	// makereestr
	$html="<h3>Сформировать реестр отправлений</h3>";
	$UploadDir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/pickpointfiles/reestr/";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n";
	$sending.="\"CityName\":\"Москва\",\n";
	$sending.="\"RegionName\":\"Москва\",\n";
	$sending.="\"Invoices\":[\n";
	$fid=intval($incoming['f']); // id отправки
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id=".$fid;
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$orders=explode(";",$row["itemscreated"]);
			for($j=0;$j<count($orders);$j++){
				//echo $orders[$j]."<br>";
				if($orders[$j]!=""){
					// получаем номера отправлений
					$sql1="SELECT pickpoint_invoice,pickpoint_barcode 
								FROM Message51 WHERE Message_ID=".$orders[$j];
					if ($result1=mysql_query($sql1)) {
						while($row1 = mysql_fetch_array($result1)) {
							$invoice=$row1['pickpoint_invoice'];
							$sending.="\"".$invoice."\"";
							
						}
					}
					$sending.=($j<(count($orders)-2)) ? "," : "";
				}
			}
		}
	} 
	$sending.="]\n}";
	//echo $sending."<br>"; 
	//$sending=iconv("WINDOWS-1251","UTF-8", $sending);
	$lgn=sendReq("makereestr",$sending);
	//echo "<br>Answer:<br>".$lgn."<br>".stripos($lgn, "error");
	if (stripos($lgn, "rror")!=false) {
		$html.="Ошибка ".$lgn."
		<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
	} else {	
		$file=$fid.".pdf";
		//$current = file_get_contents($UploadDir.$file);
		//$current.=$lgn;
		file_put_contents($UploadDir.$file, $lgn);
		// сохраняем признак, что реестр получен
		$sql="UPDATE Netshop_PickpointFiles SET getreestr='".date("Y-m-d H:i:s")."'
				WHERE id=".$fid;
		if (mysql_query($sql)) {
			$html.="<p><b>Реестр отправлений получен.</b></p>
			<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
		} else {
			die($sql."Ошибка: ".mysql_error());
		}	
	}	
	return $html;
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
function tracksendings($SessionId,$incoming,$ikn) {
	// tracksendings
	$html="";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n
	\"Invoices\":[";
	$sql="SELECT * FROM Message51 WHERE NOT pickpoint_invoice='' ORDER BY Message_ID ASC";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$invoice=$row['pickpoint_invoice'];
			$sending.="\"".$invoice."\",";
		}
	} 
	$sending.="]}";
	//echo $sending."<br><br>";
	$lgn=sendReq("tracksendings",$sending);
	//echo $lng;
	//echo "<br>Answer:<br>".$lgn."<br><br>";
	$tmp=json_decode($lgn);
	//print_r($tmp);
	foreach ($tmp as $name => $value) {
		
		if ($name=="Invoices") {
			foreach ($value as $v) {
				$varr = json_decode(json_encode($v),true);
				//print_r($varr);
				foreach($varr as $n=>$v) {
					if (is_array($v)) {
						$html.=printArray($v);
					} else {
						$html.=$n."-&gt;".$v."<br>";
					}
				}
				$html.="<br><br>";
			}
		} else {
			//$html.=$name."-&gt;".$value."<br>";
		}
	}
	
	return $html;
}
function callcourier($SessionId,$incoming,$ikn) {
	// courier
	$html="";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n";
	$sending.="\"IKN\":\"{$ikn}\",\n";
	//“SenderCode“:	“<Код вызова курьера отправителя, НЕ обязательное поле>”,
	$sending.="\"City\":\"Москва\",\n";
	$sending.="\"City_id\":992,\n";
	//“City_owner_id“:	<owner_id город>,
	$sending.="\"Address\":\"Таможенный проезд, д.6, стр.9, офис 212\",\n";
	$fid=intval($incoming['f']); // id отправки
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id={$fid}";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$sending.="\"FIO\":\"".$row['collection_fio']."\",\n";
			$sending.="\"Phone\":\"84952255492\",\n";
			$sending.="\"Date\":\"".date("Y.m.d", strtotime($row['collection_date']))."\",\n";
			$sending.="\"TimeStart\":".$row['collection_timestart'].",\n"; //количество минут от 00:00
			$sending.="\"TimeEnd\":".$row['collection_timeend'].",\n"; //количество минут от 00:00
			$items=explode(";",$row['itemscreated']);
			$sending.="\"Number\":".count($items).",\n"; 
			$sending.="\"Weight\":".$row['weight'].",\n"; 
			$sending.="\"Comment\":\"".$row['comment']."\"\n"; 
		}
	} 
	$sending.="}";
	//$sending=iconv("WINDOWS-1251","UTF-8", $sending);
	//echo $sending."<br><br>";
	$lgn=sendReq("courier",$sending);
	//echo $lng;
	//echo "<br>Answer:<br>".$lgn."<br><br>";
	$lgn=str_replace("\"","",$lgn);
	$lgn=str_replace("{","",$lgn);
	$lgn=str_replace("}","",$lgn);
	$tmp=explode(",",$lgn);
	foreach ($tmp as $t) {
		$pp=explode(":",$t);
		//if (($pp[0]=="CourierRequestRegistred")&&($pp[1]!=null)) {
			//$html.="<p>Ошибка:".$pp[1]."</p>";
		//}
		if ($pp[0]=="CourierRequestRegistred"){
			if ($pp[1]!="true") {
				$html.="<p>Курьер не вызван :(</p>";
			}
		}
		if (($pp[0]=="ErrorMessage")&&($pp[1]!="null")) {
			$html.="<p>Ошибка:".$pp[1]."</p>";
		}
		if (($pp[0]=="OrderNumber")&&($pp[1]!="null")&&($pp[1]!="")) {
			$html.="<p>Номер заказа:".$pp[1]."</p>";
			$sql="UPDATE Netshop_PickpointFiles SET courier_order='".$pp[1]."'
					WHERE id=".$fid;
			//echo $sql."<br>";
			if (mysql_query($sql)) {
				// change order status 
				// 17 передан в PickPoint
				foreach ($items as $i) { 
					if ($i!="") {
						$sql="UPDATE Message51 SET Status=17 WHERE Message_ID=".$i;
						if (!mysql_query($sql)) {
							die($sql."<br>Ошибка: ".mysql_error());
						} 
						//echo $sql."<br>";
						$sql="INSERT INTO Netshop_OrderHistory (Order_ID,created,orderstatus_id,comments)
						VALUES ({$i},'".date("Y-m-d H:i:s")."',17,'заказ передан курьеру PickPoint')";
						//echo $sql."<br>";
						if (!mysql_query($sql)) {
							die($sql."<br>Ошибка: ".mysql_error());
						} 
					}
				}
				$html.="<p><b>Курьер вызван.</b></p>";
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
		}
	}

	$html.="<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
	return $html;
}
function cancelcourier($SessionId,$incoming,$ikn) {
	// couriercancel
	$html="";
	$sending="{";
	$sending.="\"SessionId\":\"{$SessionId}\",\n";
	$fid=intval($incoming['f']); // id отправки
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id={$fid}";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$sending.="\"OrderNumber\":\"".$row['courier_order']."\"\n";
		}
	} 
	$sending.="}";
	//$sending=iconv("WINDOWS-1251","UTF-8", $sending);
	//echo $sending."<br><br>";
	$lgn=sendReq("couriercancel",$sending);
	//echo $lng;
	//echo "<br>Answer:<br>".$lgn."<br><br>";
	$lgn=str_replace("\"","",$lgn);
	$lgn=str_replace("{","",$lgn);
	$lgn=str_replace("}","",$lgn);
	$tmp=explode(",",$lgn);
	foreach ($tmp as $t) {
		$pp=explode(":",$t);
		if (($pp[0]=="Error")&&($pp[1]!="")) {
			$html.="<p>Ошибка:".$pp[1]."</p>";
		}
		if (($pp[0]=="Canceled")&&($pp[1]=="true")) {
			
			$sql="UPDATE Netshop_PickpointFiles SET courier_order='".$row['courier_order']."'
					WHERE id=".$fid;
			//echo $sql."<br>";
			if (mysql_query($sql)) {
				$html.="<p>Вызов курьера по этому отправлению отменен.</p>";
			}
		}
	}
	$html.="<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
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
if ($_SESSION['PP_session']!="") {
	$SessionId=$_SESSION['PP_session'];
} else {
	// login. start session. get session id
	$loginstr = '{"Login":"russianknife", "Password":"zxcvb123"}';
	$lgn=sendReq("login",$loginstr);
	if (($lgn['ErrorMessage']=="null")&&($lgn['SessionId'])){
		$SessionId=$lgn['SessionId'];
		$_SESSION['PP_session']=$SessionId;
	} else {
		die("Error login in API!");
	}
}
//echo $SessionId."<br>";
switch ($incoming['action']) {
	case "register":
		// регистрация отправлений
		$html=createsending($SessionId,$incoming,$ikn);
		break;
	case "getlabel":
		// формирование этикеток
		$html=createlabels($SessionId,$incoming,$ikn);
		break;
	case "getreestr":
		// формирование этикеток
		$html=createreestr($SessionId,$incoming,$ikn);
		break;
	case "getstatus":
		// формирование этикеток
		$html=getstatus($SessionId,$incoming,$ikn);
		break;
	case "tracksendings":
		// трассировка отправлений
		$html.="<h3>Трассировка всех отправлений</h3>";
		$html.=tracksendings($SessionId,$incoming,$ikn);
		break;
	case "getreestrnumber":
		// получить реестр по номеру отправления
		$html.="<h3>Получить реестр по номеру отправления</h3>";
		$html.=getreestrnumber($SessionId,$incoming,$ikn);
		break;
	case "courier":
		// вызов курьера
		$html.="<h3>Вызов курьера</h3>";
		$html.=callcourier($SessionId,$incoming,$ikn);
		break; 
	case "couriercancel":
		// вызов курьера
		$html.="<h3>Отмена вызова курьера</h3>";
		$html.=cancelcourier($SessionId,$incoming,$ikn);
		break; 
	case "logout":
		// logout
		$loginstr = '{"SessionId":"'.$SessionId.'"}';
		$lgn=sendReq("logout",$loginstr);
		$_SESSION['PP_session']="";
		//$html.=$lgn;
		$html.="<p>Сессия завершена</p>
		<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
		break; 
	default:
		break;
}

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

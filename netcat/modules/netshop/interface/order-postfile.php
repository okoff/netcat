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

function getOrderListCSV($orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=($o!="") ? " Message_ID=".$o." OR" : "";
	}
	// status=8 подготовлен к отправке
	//$sql="SELECT * FROM Message51 WHERE Status=8 ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";
	$sql="SELECT * FROM Message51 WHERE ".(($where!="") ? " (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";
	//echo $sql;
	$html.="№ заказа;ФИО;адрес;сумма;НП;№ накладной;Телефон\n";

	$i=0;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			//print_r($row);
			$html.=$row['Message_ID'].";{$row['ContactName']};{$row['Address']}".
			/*(($row['Street']!="") ? "ул.".$row['Street'].", " : "").(($row['House']!="") ? "д.".$row['House'].", " : "").
			(($row['Flat']!="") ? "кв.".$row['Flat'] : "")*/";".$row['summinsurance'].";".$row['summnp'].";1 класс;{$row['mphone']}\n";
			$i=$i+1;
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	
	return $html;
}
function getOrderList($orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=8 подготовлен к отправке
	$sql="SELECT * FROM Message51 WHERE (Status=8) ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";
	$i=0;
	if ($result=mysql_query($sql)) {
		$html.="<p>Количество заказов: ".((!empty($orders)) ? count($orders) : mysql_num_rows($result))."</p>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>".((empty($orders)) ? "<td>все<br><input type='checkbox' onclick='checkAll();' id='chkall'></td><td>Дата создания</td>" : "")."<td>№ заказа</td>
	<td>ФИО</td><td>адрес</td>".((!empty($orders)) ? "<td>сумма</td><td>НП</td><td>№ накладной</td><td>Телефон</td>" : "<td>Телефон</td><td>Моб. тел.</td><td>Сумма заказа</td><td>Стоимость доставки</td><td>ИТОГО</td><td>Страховая сумма</td><td>Сумма НП</td><td>Способ доставки</td><td>Способ оплаты</td>")."</tr>";
		while($row = mysql_fetch_array($result)) {
			//print_r($row);
			$st="";
			if ($row['DeliveryMethod']!=3) {
				$st=" style='background:#ffa0a0;'";
			}
			if ($row['PaymentMethod']!=7) {
				$st=" style='background:#ffa0a0;'";
			}
			if ($row['summnp']==0) {
				$st=" style='background:#ffa0a0;'";
			}
			($row['summinsurance']!=(getOrderCost($row['Message_ID'])+$row['DeliveryCost'])) ? $st=" style='background:#ffa0a0;'" : "";
			($row['ContactName']=="") ? $st=" style='background:#ffa0a0;'" : "";
			//($row['PostIndex']=="") ? $st=" style='background:#ffa0a0;'" : "";
			$html.="<tr {$st}>
			".((empty($orders)) ? "<td {$st}><input type='checkbox' name='oid[{$i}]' id='oid-{$row['Message_ID']}' value='{$row['Message_ID']}'></td>" : "")."
			".((empty($orders)) ? "<td>{$row['Created']}</td>" : "")."
			<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
			<td>{$row['ContactName']}</td>
			<td>{$row['Address']}".
			/*<td>{$row['PostIndex']}, ".(($row['Region']!="") ? $row['Region']."," : "").(($row['Town']!="") ? $row['Town'].", " : "").
			(($row['Street']!="") ? "ул.".$row['Street'].", " : "").(($row['House']!="") ? "д.".$row['House'].", " : "").
			(($row['Flat']!="") ? "кв.".$row['Flat'] : "")*/"</td>
			".((empty($orders)) ? "<td>{$row['Phone']}</td><td>".(($row['mphone']!="+7") ? $row['mphone'] : "")."</td>
								<td>".getOrderCost($row['Message_ID'])."</td><td>{$row['DeliveryCost']}</td>
								<td>".(getOrderCost($row['Message_ID'])+$row['DeliveryCost'])."</td>
								<td>{$row['summinsurance']}</td>
								<td>{$row['summnp']}</td><td>".getDeliveryMethod($row['DeliveryMethod'])."</td>
								<td>".getPaymentMethod($row['PaymentMethod'])."</td>
			" : "<td>{$row['summinsurance']}</td><td>{$row['summnp']}</td>
			<td>1 класс</td>
			<td>{$row['mphone']}</td>").
			"</tr>";
			$i=$i+1;
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	

	$html.="</table><br>";

	return $html;
}
function getOrderListForm($orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=8 подготовлен к отправке
	$sql="SELECT * FROM Message51 WHERE (Status=8) ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";

	$html.="<span id='err' style='display:none;color:#f30000;'>Нужно выбрать заказ!</span>
	<form method='post' name='frm1' id='frm1' action='/netcat/modules/netshop/interface/order-postfile.php'  onsubmit='return clickEdit();'>
	<fieldset style='border:0;'>";

	$i=0;
	$html.=getOrderList($orders);
	

	//$html.="</table><br>";

	if ($where=="") { 
		$html.="<input type='hidden' id='action' name='action' value='create'>
		<input type='submit' value='Создать файл' id='btn_submit'>";
	} else {
		$i=0;
		foreach ($orders as $o) {
			$html.="<input type='hidden' name='oid[{$i}]' id='oid-{$o}' value='{$o}'>";
			$i=$i+1;
		}
		$html.="<input type='hidden' id='action' name='action' value='save'>
		<input type='submit' value='Сохранить файл' id='btn_submit'>";
	}
	$html.="</fieldset>
	</form>";
	
	return $html;

}
function getPostFileList() {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=8 подготовлен к отправке
	$sql="SELECT * FROM Netshop_PostFiles ORDER BY ID DESC";

	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>#</td><td>Дата создания</td><td>&nbsp;</td><td>Заказы в файле</td><td>Кол. заказов</td><td>Страховая сумма</td><td>Сумма НП</td><td>Доставка</td>
		<td>Ответ с почты</td><td>Заказы в ответе</td><td>Кол. заказов</td><td>Страховая сумма</td><td>Сумма НП</td><td>Доставка</td>
		<td>Отличия</td></tr>";

	$i=0;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$orders=explode(";", $row['postfile']);
			$reqstr="";
			foreach($orders as $o) {
				if ($o!="") {
					$reqstr.="<a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$o}' target='_blank'>{$o}</a>; ";
				}
			}
			$ansstr="";
			if ($row['answer']!="") {
				$aorders=explode(";",$row['answer']);
				foreach($aorders as $o) {
					if ($o!="") {
						$ansstr="<a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$o}' target='_blank'>{$o}</a>; ".$ansstr;
					}
				}
			}
			//print_r($row);
			if (($row['reqinsurance']!=$row['ansinsurance']) || ($row['reqnp']!=$row['ansnp'])) {
				// check all orders
			}
			$html.="<tr>
			<td>{$row['id']}</td>
			<td>".date("d.m.Y",strtotime($row['created']))."</td>
			<td><a href='/netcat/modules/netshop/interface/order-postfile.php?action=save&oid={$row['id']}'><img src='/images/buttons/download.png' border='0' alt='Скачать файл' title='Скачать файл'></a></td>
			<td style='width:400px;'>{$reqstr}</td>
			<td>{$row['reqcount']}</td>
			<td>{$row['reqinsurance']}</td>
			<td>{$row['reqnp']}</td>
			<td>{$row['reqdeliverycost']}</td>
			<td>".(($row['answered']!="0000-00-00 00:00:00") ? date("d.m.Y", strtotime($row['answered'])) : "<a href='/netcat/modules/netshop/interface/order-postfile.php?action=upload&id={$row['id']}'><img src='/images/buttons/upload.png' border='0' alt='Загрузить файл' title='Загрузить файл' style='display:block;margin:0 auto;'></a>")."
			<a href='/netcat/modules/netshop/interface/order-postfile.php?action=upload&id={$row['id']}'><img src='/images/buttons/upload.png' border='0' alt='Загрузить файл' title='Загрузить файл' style='display:block;margin:0 auto;'></a>
			</td>
			<td style='width:400px;'>{$ansstr}</td>
			<td>".($row['anscount'] ? $row['anscount'] : "&nbsp;")."</td>
			<td>".($row['ansinsurance'] ? $row['ansinsurance'] : "&nbsp;")."</td>
			<td>".($row['ansnp'] ? $row['ansnp'] : "&nbsp;")."</td>
			<td>".($row['ansdeliverycost'] ? $row['ansdeliverycost'] : "&nbsp;")."</td>
			<td><a href='/netcat/modules/netshop/interface/order-postfile.php?action=difference&oid={$row['id']}'>&gt;&gt;&gt;</a></td>
			</tr>";
			$i=$i+1;
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	

	$html.="</table><br>";

	return $html;
}
function saveCSVFile($dir, $dirlink, $orders) {
	$html=$text=$where="";
	$reqinsurance=$reqnp=$reqcount=$reqdeliverycost=0;
	//$text=getOrderListDB($orders);
	foreach ($orders as $o) {
		$text.=$o.";";
		$where.=" Message_ID=".$o." OR";
	}
	// get reqinsurense, reqnp, reqcount
	$sql="SELECT summinsurance, summnp, DeliveryCost FROM Message51 WHERE ".substr($where, 0, -3);
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$reqinsurance=$reqinsurance+$row['summinsurance'];
			$reqnp=$reqnp+$row['summnp'];
			$reqdeliverycost=$reqdeliverycost+$row['DeliveryCost'];
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	$reqcount=count($orders);
	//echo $reqinsurance." ".$reqnp." ".$reqdeliverycost;
	// save postfile in DB
	$sql="INSERT INTO Netshop_PostFiles (created, postfile, reqinsurance, reqnp, reqdeliverycost, reqcount) 
				VALUES ('".date("Y-m-d H:i:s")."','".addslashes($text)."',{$reqinsurance}, {$reqnp}, {$reqdeliverycost},{$reqcount})";
	//echo $sql;
	if (mysql_query($sql)) {
		$html.= "Файл для почты сохранен.<br>";
	} else {
		die("Ошибка: " . mysql_error());
	}
	
	$id=0;
	$sql="SELECT id FROM Netshop_PostFiles ORDER BY id DESC LIMIT 0,1";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$id=$row['id'];
		}
	} else {
		echo "Ошибка: " . mysql_error();
	}
	// create txt file and download it
	$filename = "russian-knife-{$id}.csv";
	$somecontent = getOrderListCSV($orders);
	if (!$handle = fopen($dir.$filename, 'w')) {
		echo "Не могу открыть файл";
	}
	fwrite($handle, $somecontent);
	fclose($handle);
	
	echo $dir.$filename;
	//$dir = '/netcat/modules/netshop/interface/';
	$html.="<a target='_blank' href='{$dirlink}{$filename}'><b>Скачать файл</b></a>";
	$html.="<br><br><a href='/netcat/modules/netshop/interface/order-postfile.php?action=view'><b>Список файлов для почты</b></a>";

	return $html;
}
function readCSVFile($dir, $dirlink, $id) {
	$somecontent="";
	$filename = "russian-knife-{$id}.csv";
	if (!file_exists($dir.$filename)) {
		$sql="SELECT * FROM Netshop_PostFiles WHERE id=".$id;
		if ($result=mysql_query($sql)) {
			while($row = mysql_fetch_array($result)) {
				$somecontent = getOrderListCSV(explode(";",$row['postfile']));
			}
		} else {
			echo "Ошибка: " . mysql_error();
		}
		if (!$handle = fopen($dir.$filename, 'w')) {
			echo "Не могу открыть файл ".$dir.$filename;
		}
		fwrite($handle, $somecontent);
		fclose($handle);
	}
	$html.="<a target='_blank' href='{$dirlink}{$filename}'><b>Скачать файл</b></a>";
	$html.="<br><br><a href='/netcat/modules/netshop/interface/order-postfile.php?action=view'><b>Список файлов для почты</b></a>";
	return $html;
}
function printUploadFrm($id) {
	$html="<form enctype='multipart/form-data' action='/netcat/modules/netshop/interface/order-postfile.php' method='post' name='frm1' id='frm1'>
		<input type='hidden' name='action' value='upload'>
		<input type='hidden' name='id' value='{$id}'>
		<input type='file' name='fname'>
		<input type='submit' value='Загрузить'>
	</form>";
	return $html;
}
function readTxtFile($fname) {
	if (file_exists($fname)) {
		$fp = fopen($fname, "r");
		while (!feof($fp)) { 
			$str.=fread($fp, 1024); 
		} 
		fclose($fp);
		
	} else {
		return 0;
	}	
	return $str;	
}

function processResponse($text, $id) {
	$strs=explode("\n", $text);
	$ar_exists=array(); // уже загруженный ответ
	$html=$orders="";
	$ansinsurance=$ansnp=$anscount=$ansdeliverycost=0;
	//$anscount=count($strs)-2;
	mysql_set_charset("cp1251", $con);
	
	// read answer if exists
	$sql="SELECT * FROM Netshop_PostFiles WHERE id={$id}";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		if ($row = mysql_fetch_array($result)) {
			$orders1=$row['answer'];
		}
	}
	if ($orders1) {
		$ar_exists=explode(";",$orders1);
		if (is_array($ar_exists)) {
			for($j=0;$j<count($ar_exists);$j++) {
				if ($ar_exists[$j]) {
					$anscount=$anscount+1; // количество заказов в ответе, если уже есть
				}
			}
		}
	}
	foreach ($strs as $str) {
		$tmp=explode(";", $str);
		//echo $tmp[0]."<br>";
		if (($tmp[0])&&(is_numeric($tmp[0]))) {
			$add=1;
			if (is_array($ar_exists)) {
				for($j=0;$j<count($ar_exists);$j++) {
					if ($ar_exists[$j]==$tmp[0]) {
						$add=0;
						break;
					}
				}
			}
			//echo $add."<br>";
			if ($add) {
				//echo $tmp[8]."-".$tmp[9];
				$anscount=$anscount+1; // количество заказов в ответе
				$insurance=str_replace(chr(44),".",str_replace(chr(160),"",substr($tmp[8],0,-2)));
				$insurance=floatval(str_replace(" ","",iconv("windows-1251","utf-8",$insurance)));
				$ansinsurance=$ansinsurance+$insurance;
				
				$np=str_replace(chr(44),".",str_replace(chr(160),"",substr($tmp[9],0,-2)));
				$np=str_replace(chr(32),'',$np);
				/*$dlina=strlen($np);
				$i=0;
				while ($i<$dlina) {
					echo $np."-".$np{$i}."-".ord($np{$i}).'<br/>';
					$i++;
				}*/
				$np=floatval(str_replace(" ","",iconv("windows-1251","utf-8",$np)));
				
				$ansnp=$ansnp+$np;
				$delcost=str_replace(chr(44),".",str_replace(chr(160),"",substr($tmp[11],0,-2)));
				$delcost=floatval(str_replace(" ","",iconv("windows-1251","utf-8",$delcost)));
				$ansdeliverycost=$ansdeliverycost+$delcost;
				//$ansnp=$ansnp+floatval(str_replace(chr(44),".",str_replace(chr(160),"",substr($tmp[9],0,-2))));
				//$ansdeliverycost=$ansdeliverycost+floatval(str_replace(chr(44),".",str_replace(chr(160),"",substr($tmp[11],0,-2))));
				$sql="UPDATE Message51 SET weight=".str_replace(",",".",$tmp[10]).", 
							barcode='".iconv("utf-8","cp1251",str_replace(" ", "", $tmp[6]))."',
							senddate='".date("Y-m-d",strtotime($tmp[7]))."', 
							sendprice=".$delcost.", 
							sendinsurance=".$insurance.", 
							sendnp=".$np.", 
							sendtype='".iconv("windows-1251","utf-8",$tmp[4])."', Status=9 WHERE Message_ID={$tmp[0]}";
				//echo $sql."<br>";
				if (mysql_query($sql)) {
					$html.="Заказ ".$tmp[0]." обработан. <br>";
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
				$sql1="SELECT Email, ContactName FROM Message51 WHERE Message_ID=".$tmp[0];
				if ($result=mysql_query($sql1)) {
					while($row = mysql_fetch_array($result)) {
						sendmail($tmp[0], $row['Email'], $row['ContactName'], str_replace(" ", "", $tmp[6]));
						sendMail($tmp[0], "yuraklinok@yandex.ru", $row['ContactName'], str_replace(" ", "", $tmp[6]));
						sendMail($tmp[0], "elena@best-hosting.ru", $row['ContactName'], str_replace(" ", "", $tmp[6]));
					}
				}
				
				$orders.=$tmp[0].";";
				//echo $orders."<br>";
					//break;
			}
		}
	}
	$sql="SELECT * FROM Netshop_PostFiles WHERE id={$id}";
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		if ($row = mysql_fetch_array($result)) {
			$orders=$row['answer'].$orders;
			$ansinsurance=$row['ansinsurance']+$ansinsurance;
			$ansnp=$row['ansnp']+$ansnp;
			$ansdeliverycost=$row['ansdeliverycost']+$ansdeliverycost;
		}
	}
	$sql="UPDATE Netshop_PostFiles SET answered='".date("Y-m-d H:i:s")."', answer='{$orders}',anscount={$anscount},ansinsurance={$ansinsurance},ansnp={$ansnp}, ansdeliverycost={$ansdeliverycost} WHERE id={$id}";
	//echo $sql."<br>";
	if (mysql_query($sql)) {
		$html.="<h3>Файл ответа обработан</h3>";
	} else {
		die("Ошибка: " . mysql_error());
	}
	mysql_set_charset("utf8", $con);
	return $html;
}

function getDiffInPostFile($id) {
	$req=$ans=array();
	$html="";
	$sql="SELECT * FROM Netshop_PostFiles WHERE id=".$id;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			
			$req=explode(";", $row['postfile']);
			$ans=explode(";", $row['answer']);
			$dfr=array();
			
			$html.="<h2>Анализ файла для почты #{$id}.</h2>
			<p>Дата создания:{$row['created']}</p> 
			<p>Ответ: {$row['answered']} </p>";
			if (count($req)!=count($ans)) {
				$html.="<p>Количество заказов в файлах не совпадает. На почту: ".(count($req)-1).". С почты: ".(count($ans)-1).".</p>";
				if (count($req)>count($ans)) {
					for ($i=0;$i<count($req);$i++) {
						$d=false;
						for ($j=0;$j<count($ans); $j++) {
							if ($req[$i]==$ans[$j]) {
								$d=true;
								break;
							}
						}
						if (!($d)) {
							//$html.="<p>{$req[$i]}</p>";
							$dfr[count($dfr)]=$req[$i];
						}
					}
				} else {
					for ($i=0;$i<count($ans);$i++) {
						$d=false;
						for ($j=0;$j<count($req); $j++) {
							if ($ans[$i]==$req[$j]) {
								$d=true;
								break;
							}
						}
						if (!($d)) {
							//$html.="<p>{$ans[$i]}</p>";
							$dfr[count($dfr)]=$ans[$i];
						}
					}
				}
			}
			//print_r($dfr);
			$html.="<table cellspacing='0' cellpadding='2' border='1'>
			<tr><td>#</td><td>Страх. сумма</td><td>Страх. сумма (почта)</td><td>Cумма НП</td><td>Сумма НП (почта)</td><td>Доставка</td>
			<td>Доставка (почта)</td>
			<td>Полная стоимость заказа</td></tr>";
	
			$i=$j=0;
			$delrk=$delpost=$insrk=$inspost=$nprk=$nppost=$itog=0;
			while ($i<count($req)) {
				$j=0;
				while ($j<count($ans)) {
					if ($req[$i]==$ans[$j]) {
						// get all summs from order 
						$sql="SELECT * FROM Message51 WHERE Message_ID=".$req[$i];
						if ($result1=mysql_query($sql)) {
							while($row1 = mysql_fetch_array($result1)) {
								($row1['summinsurance']!=$row1['sendinsurance']) ? $style=" style='background:#ffa0a0;'" : $style="";
								($row1['summnp']!=$row1['sendnp']) ? $style1=" style='background:#ffa0a0;'" : $style1="";
								($row1['DeliveryCost']!=$row1['sendprice']) ? $style2=" style='background:#ffa0a0;'" : $style2="";
								$ordercost=getOrderCost($req[$i]);
								$html.="<tr>
								<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row1['Message_ID']}' target='_blank'>{$row1['Message_ID']}</a></td>
								<td {$style}>{$row1['summinsurance']}</td>
								<td {$style}>{$row1['sendinsurance']}</td>
								<td {$style1}>{$row1['summnp']}</td>
								<td {$style1}>{$row1['sendnp']}</td>
								<td {$style2}>{$row1['DeliveryCost']}</td>
								<td {$style2}>{$row1['sendprice']}</td>
								<td>".($ordercost + $row1['DeliveryCost'])."</td>
								</tr>";
								//echo "<br><br>";
								$delrk=$delrk+$row1['DeliveryCost'];
								$delpost=$delpost+$row1['sendprice'];
								$insrk=$insrk+$row1['summinsurance'];
								$inspost=$inspost+$row1['sendinsurance'];
								$nprk=$nprk+$row1['summnp'];
								$nppost=$nppost+$row1['sendnp'];
								$itog=$itog+$ordercost + $row1['DeliveryCost'];
							}
						}
						break;
					} 
					
					$j=$j+1;
				}
				$i=$i+1;
			}
			$html.="<tr><td colspan='7'>&nbsp;</td></tr>";
			for ($i=0;$i<count($dfr);$i++) {
				$sql="SELECT * FROM Message51 WHERE Message_ID=".$dfr[$i];
				if ($result1=mysql_query($sql)) {
					while($row1 = mysql_fetch_array($result1)) {
						($row1['summinsurance']!=$row1['sendinsurance']) ? $style=" style='background:#ffa0a0;'" : $style="";
						($row1['summnp']!=$row1['sendnp']) ? $style1=" style='background:#ffa0a0;'" : $style1="";
						($row1['DeliveryCost']!=$row1['sendprice']) ? $style2=" style='background:#ffa0a0;'" : $style2="";
						
						$html.="<tr>
						<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row1['Message_ID']}' target='_blank'>{$row1['Message_ID']}</a></td>
						<td {$style}>{$row1['summinsurance']}</td>
						<td {$style}>{$row1['sendinsurance']}</td>
						<td {$style1}>{$row1['summnp']}</td>
						<td {$style1}>{$row1['sendnp']}</td>
						<td {$style2}>{$row1['DeliveryCost']}</td>
						<td {$style2}>{$row1['sendprice']}</td>
						</tr>";
						//echo "<br><br>";
						$delrk=$delrk+$row1['DeliveryCost'];
						$delpost=$delpost+$row1['sendprice'];
						$insrk=$insrk+$row1['summinsurance'];
						$inspost=$inspost+$row1['sendinsurance'];
						$nprk=$nprk+$row1['summnp'];
						$nppost=$nppost+$row1['sendnp'];
					}
				}
			}			
		}
	}
	
	$html.="<tr><td><b>ИТОГО</b></td><td><b>{$insrk}</b></td><td><b>{$inspost}</b></td>
	<td><b>{$nprk}</b></td><td><b>{$nppost}</b></td>
	<td><b>{$delrk}</b></td><td><b>{$delpost}</b></td><td><b>{$itog}</b></td></tr>";
	$html.="</table><br>";
	return $html;
}
function getNarvFileList($dir) {
	$html="";
	//echo $dir."<br>";
	/*$files=getFileList($dir);
	$html.="<ul>";
	foreach ($files as $file) {
		$html.="<li><a href='/netcat/modules/netshop/interface/order-postfile.php?action=narv&f={$file['name']}'>".$file['name']."</a></li>";
	}
	$html.="</ul>";*/
	$html.="<form enctype='multipart/form-data' action='/netcat/modules/netshop/interface/order-postfile.php' method='post' name='frm1' id='frm1'>
		<input type='hidden' name='action' value='upload'>
		<input type='hidden' name='id' value='narv'>
		<input type='file' name='fname'>
		<input type='submit' value='Загрузить'>
	</form><br clear='both'>";
	$barcode="";
	$sql="SELECT * FROM Netshop_PostFilesNarv ORDER BY id DESC";
	$html.="<table cellpadding='3' cellspacing='0' border='1'>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$barcode="";
			$arbarcode=explode(";", $row['barcodes']);
			foreach($arbarcode as $ab) {
				$barcode.=$ab."; ";
			}
			$html.="<tr>
			<td><a href='/netcat/modules/netshop/interface/order-postfile.php?action=narv&id={$row['id']}'>{$row['id']}</a></td>
			<td><a href='/netcat/modules/netshop/interface/order-postfile.php?action=narv&id={$row['id']}'>".date("d.m.Y", strtotime($row['created']))."</a></td>
			<td>{$row['fname']}</td>
			<td>{$barcode}</td>
			</tr>";
		}
	}
	$html.="</table><br><br>";
	
	return $html;
}
function printNarvById($id) {
	$html="";
	$sql="SELECT * FROM Netshop_PostFilesNarv WHERE id=".$id;
	if ($result=mysql_query($sql)) {
		$row = mysql_fetch_array($result);
		$html.="<h2>{$row['id']}. {$row['fname']}</h2>";
		$html.=printNarv($row);
		return $html;
	}
	
	return $html;
}
function printNarv($row) {
	$html="";
	//print_r($row);
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>#</td><td>Баркод</td><td>Сумма НП</td><td>Оплачено</td><td>Дата оплаты</td><td>Дата отправки</td><td>Дата получения</td></tr>";
	$barcodes=explode(";", $row['barcodes']);
	foreach ($barcodes as $barcode) {
		if (strlen($barcode)>3) {
			$sql="SELECT * FROM Message51 WHERE barcode LIKE '".$barcode."' LIMIT 1";
			if ($result=mysql_query($sql)) {
				$row = mysql_fetch_array($result);
				$html.="<tr>
				<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
				<td>{$barcode}</td>
				<td>{$row['summnp']}</td>
				<td>{$row['paysum']}</td>
				<td>".((isset($row['paydate'])) ? date("d.m.Y",strtotime($row['paydate'])) : "&nbsp;")."</td>
				<td>".((isset($row['senddate'])) ? date("d.m.Y",strtotime($row['senddate'])) : "&nbsp;")."</td>
				<td>".((isset($row['acceptdate'])) ? date("d.m.Y",strtotime($row['acceptdate'])) : "&nbsp;")."</td>";
				
				$html.="</tr>";
			}
		}
	}
	
	return $html;
}
function processNarv($fname) {
	$html="";
	//echo $fname;
	
	$sql="SELECT * FROM Netshop_PostFilesNarv WHERE fname LIKE '{$fname}' LIMIT 1";
	if ($result=mysql_query($sql)) {
		$row = mysql_fetch_array($result);
		if (!empty($row)) {
			$html = printNarv($row);
			return $html;
		}
	}
	// save data in DB
	$text=readTxtFile($_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/narv/'.$fname);
	//echo $text."<br>";
	$strbarcode="";
	$strs=explode("\n", $text);
	foreach ($strs as $str) {
		$tmp=explode(";", $str);
		if (strlen($tmp[0])>3) {
			$barcode=((strstr($tmp[0],"=")) ? substr($tmp[0],4) : $tmp[0]);
			$sql="SELECT * FROM Message51 WHERE barcode LIKE '{$barcode}' LIMIT 1";
			$html.=$barcode."<br>";
			if ($result=mysql_query($sql)) {
				while($row = mysql_fetch_array($result)) {
					//print_r($row);
					$paysum=str_replace(",",".",$tmp[2]);
					//$paysum=str_replace(chr(44),".",str_replace(chr(160),"",substr($tmp[2],0,-2)));
					//$paysum=floatval(str_replace(" ","",iconv("windows-1251","utf-8",$paysum)));
	
					$sql="UPDATE Message51 SET acceptdate='".date("Y-m-d",strtotime($tmp[1]))."', 
								paydate='".date("Y-m-d",strtotime($tmp[5]))."',
								paysum=".$paysum.", Status=4, paid=1,
								wroff=1,wroffdate='".date("Y-m-d",strtotime($tmp[1]))."' WHERE Message_ID={$row['Message_ID']}";
					//echo $sql."<br>";
					if (mysql_query($sql)) {
						$html.= " Заказ {$row['Message_ID']} обновлен.<br>";
					} else {
						die("Ошибка: " . mysql_error());
					}
	//echo $sql."<br>";"
				}
			}
			$strbarcode.=$barcode.";";
		}
	}
	$sql="INSERT INTO Netshop_PostFilesNarv (fname, barcodes, created) VALUES
		('{$fname}','{$strbarcode}','".date("Y-m-d H:i:s")."')";
	if (mysql_query($sql)) {
		$html.= "Файл отчета об оплате сохранен и обработан.<br>";
		
	} else {
		die("Ошибка: " . mysql_error());
	}
	$sql="SELECT * FROM Netshop_PostFilesNarv ORDER By id DESC LIMIT 1";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$html.=printNarv($row);
		}
	}
		//}
	
	return $html;
}
// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	//$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
	$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
	$UploadDirNarv=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/narv/';
	//$UploadDir='/netcat_files/postfiles/upload/';
	//$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
	$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
	$DownloadDirLink='/netcat_files/postfiles/';
	$incoming = parse_incoming();
	if ((isset($incoming['id'])) && ($incoming['id']!="narv")) {
		//$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
		$uploadfile = $UploadDir . $incoming['id'].".csv";
		$uploadfilenarv = $UploadDirNarv . $incoming['id'].".csv";
	}
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
		case "create":
			$orders=$incoming['oid'];
			$html=getOrderListForm($orders);
			break;
		case "save":
			//$show=0;
			$orders=$incoming["oid"];
			if ($incoming['request_method']=="post") {
				$html=saveCSVFile($DownloadDir,$DownloadDirLink,$orders);
			} else {
				//print_r($incoming);
				$html=readCSVFile($DownloadDir,$DownloadDirLink,$orders);
			}
			//header("Location:/netcat/modules/netshop/interface/order-postfile.php?action=view");
			break;
		case "upload":
			if ($incoming['request_method']=="get") {
				$html=printUploadFrm($incoming['id']);
				if (file_exists($uploadfile)) {
					$html.="<p>Файл загружен. <a href='/netcat/modules/netshop/interface/order-postfile.php?action=process&id={$incoming['id']}'>Начать обработку файла.</a></p>";
				}
			} else {
				if (file_exists($uploadfile)) {
					unlink($uploadfile);
				}
					//echo $uploadfile;
				if ((isset($incoming['id'])) && ($incoming['id']!="narv")) {
					if (move_uploaded_file($_FILES['fname']['tmp_name'], $uploadfile)) {
						//$html.="<p>Файл корректен и был успешно загружен.</p>";
						$html.="<p>Файл загружен. <a href='/netcat/modules/netshop/interface/order-postfile.php?action=process&id={$incoming['id']}'>Начать обработку файла.</a></p>";
					
					} else {
						$html.="<p>Файл не загружен!</p>";
					}
				} else {
					if (move_uploaded_file($_FILES['fname']['tmp_name'], $UploadDirNarv.$_FILES['fname']['name'])) {
						//$html.="<p>Файл корректен и был успешно загружен.</p>";
						$html.="<p>Файл загружен. <a href='/netcat/modules/netshop/interface/order-postfile.php?action=narv&f={$_FILES['fname']['name']}'>Начать обработку файла.</a></p>";
					
					} else {
						$html.="<p>Файл не загружен!</p>";
					}
				}
				/*	//print_r($_FILES);
				} else {
					// delete old file
					
					$html="<p>Файл загружен. <a href='/netcat/modules/netshop/interface/order-postfile.php?action=process&id={$incoming['id']}'>Начать обработку файла.</a></p>";
					//header("Location:/netcat/modules/netshop/interface/order-postfile.php?action=process&id={$incoming['id']}");
				}*/
			}
			break;
		case "process":
			if (isset($incoming['id'])) { 
				$resptext=readTxtFile($uploadfile);
				$html=processResponse($resptext,$incoming['id']);
			} else {
				$html="Не указан файл, для которого пришел ответ";
			}
			break;
		case "view":
			$html="<h1>Файлы для почты</h1>
			<p><a href='/netcat/modules/netshop/interface/statistic-postfiles.php'>отчет по файлам для почты</a></p>
			<br clear='both'>";
			//$html.="	<p><a href=\"/netcat/modules/netshop/interface/statistic-post.php\">Статистика по почте</a></p>";
			$html.=getPostFileList();
			break;
		case "difference":
			if (isset($incoming['oid'])) {
				$html=getDiffInPostFile($incoming['oid']);
			}
			break;
		case "narv":
			$html.="<h1>Отчеты об оплате</h1>";
			if (isset($incoming['id'])) {
				if (intval($incoming['id'])) {
					$html=printNarvById($incoming['id']);
				}
			} elseif (isset($incoming['f'])) {
				$html=processNarv($incoming['f']);
			} else {
				
				//$html.=getNarvFileList($UploadDirNarv);
				$html.=getNarvFileList("../../../../netcat_files/postfiles/narv/");
			}
			break;
		default:
			$html="<h1>Заказы в статусе &quot;подготовлен к отправке на почту&quot;</h1><br clear='all'>".getOrderListForm($orders);
			break;
	}
	
	//echo $where;
//print_r($_SESSION);
if ($show) {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Работа с почтой</title>
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

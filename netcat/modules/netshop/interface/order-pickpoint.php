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
function getOrderList($orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=16 подготовлен к отправке
	$sql="SELECT * FROM Message51 WHERE (Status=16) ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";
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
			"<td>Телефон</td><td>Моб. тел.</td><td>Сумма заказа</td><td>Стоимость доставки</td><td>ИТОГО</td><td>Страховая сумма</td><td>Способ доставки</td><td>Способ оплаты</td>")."</tr>";
		while($row = mysql_fetch_array($result)) {
			$st=($row['paid']==1) ? "style='background:#ccffcc;'" : "style='background:#ff9999;'";
			//print_r($row);

			$html.="<tr {$st}>
			".((empty($orders)) ? "<td {$st}><input type='checkbox' name='oid[{$i}]' id='oid-{$row['Message_ID']}' value='{$row['Message_ID']}'></td>" : "")."
			".((empty($orders)) ? "<td>{$row['Created']}</td>" : "")."
			<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
			<td>{$row['ContactName']}</td>
			<td>".getOrderItems($row['Message_ID'],0)."</td>
			<td>{$row['pickpoint_address']}"."</td>
			".((empty($orders)) ? "<td>{$row['Phone']}</td><td>".(($row['mphone']!="+7") ? $row['mphone'] : "")."</td>
								<td>".getOrderCost($row['Message_ID'])."</td><td>{$row['DeliveryCost']}</td>
								<td>".(getOrderCost($row['Message_ID'])+$row['DeliveryCost'])."</td>
								<td>{$row['summinsurance']}</td>
								<td>".getDeliveryMethod($row['DeliveryMethod'])."</td>
								<td>".getPaymentMethod($row['PaymentMethod'])."</td>
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
function getOrderListForm($orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=16 подготовлен к отправке
	$sql="SELECT * FROM Message51 WHERE (Status=16) ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";

	$html.="<span id='err' style='display:none;color:#f30000;'>Нужно выбрать заказ!</span>
	<form method='post' name='frm1' id='frm1' action='/netcat/modules/netshop/interface/order-pickpoint.php'  onsubmit='return clickEdit();'>
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
function getPPFileList() {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=8 подготовлен к отправке
	$sql="SELECT * FROM Netshop_PickpointFiles ORDER BY ID DESC LIMIT 0,100";
	$html.="<p><b>Внимание!</b> Вызов курьера должен быть в интервале с 9 до 18. Для Московской области вызов курьера на текущий день должен создаваться 
	до 10:30. Для остальных регионов вызов на текущий день должен быть создан до 14:00. Интервал вызова должен быть не менее 4 часов.</p>";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>#</td><td>Дата создания</td>
		<td width='20%'>Действия</td>
		<td>Заказы в файле</td>
		<td>Кол. заказов</td>
		<td>Зарегистрированные заказы</td>
		<td>Отклоненные заказы</td>
		<td>Номер реестра, данные для курьера</td>
		</tr>";

	$i=0;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$orders=explode(";", $row['ppfile']);
			$reqstr="";
			foreach($orders as $o) {
				if ($o!="") {
					$reqstr.="<a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$o}' target='_blank'>{$o}</a>; ";
				}
			}
			$n=count($orders)-1;
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
			$straction="";
			//if ($row['registered']=="0000-00-00 00:00:00") {
				$straction.="1) <a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=register&f={$row['id']}'>зарегистрировать отправления</a><br>";
			//} 
			//if (($row['itemscreated']!="")&&($row['itemsrejected']=="")&&($row['getlabel']=="0000-00-00 00:00:00")) {
				$straction.="2) <a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=getlabel&f={$row['id']}'>сформировать этикетки</a>
				<a target='_blank' href='/netcat/modules/netshop/interface/pickpoint-labels.php?f={$row['id']}'><img src='/images/icons/print.png' alt='печать этикеток' title='печать этикеток'></a><br>";
			//}
			//if (($row['itemscreated']!="")&&($row['itemsrejected']=="")&&($row['getlabel']!="0000-00-00 00:00:00")) {
				$straction.="3) <a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=getreestr&f={$row['id']}'>сформировать реестр</a>
				<a target='_blank' href='/netcat_files/pickpointfiles/reestr/{$row['id']}.pdf'><img src='/images/icons/print.png' alt='печать реестра' title='печать реестра'></a><br>";
				$straction.="4) <a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=getreestrnumber&f={$row['id']}'>получить номер реестра</a>
				<br>";
				$straction.="5) <a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=settimeweight&f={$row['id']}'>указать время сбора и суммарный вес</a><br>";
				$straction.="6) <a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=courier&f={$row['id']}'>вызвать курьера</a><br>";
				$straction.="<br><br>7) <a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=couriercancel&f={$row['id']}'>отменить вызов курьера</a><br>";
			//}
			$html.="<tr>
			<td>{$row['id']}</td>
			<td>".date("d.m.Y H:i:s",strtotime($row['created']))."
				".(($row['registered']!="0000-00-00 00:00:00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y H:i:s",strtotime($row['registered']))." отправления зарегистрированы</p>" : "")."
				".(($row['getlabel']!="0000-00-00 00:00:00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y H:i:s",strtotime($row['getlabel']))." сформированы этикетки</p>" : "")."
				".(($row['getreestr']!="0000-00-00 00:00:00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y H:i:s",strtotime($row['getreestr']))." сформирован реестр</p>" : "")."
				".(($row['courier_order']!="") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y",strtotime($row['collection_date']))." вызов курьера. №{$row['courier_order']}</p>" : "")."
			</td>
			<td>{$straction}</td>
			<td>{$reqstr}</td>
			<td>{$n}</td>
			<td>{$row['itemscreated']}</td>
			<td>{$row['itemsrejected']}</td>
			<td>".(($row['reestr']!="") ? "<b>".$row['reestr']."</b><br>" : "&nbsp;")."
			".(($row['collection_date']!="0000-00-00") ? "Дата отправки: ".date("d.m.Y",strtotime($row['collection_date']))."<br>" : "")."
			".(($row['collection_timestart']!=0) ? "Время отправки с: ".$row['collection_timestart']."(".($row['collection_timestart']/60).")<br>" : "")."
			".(($row['collection_timeend']!=0) ? "Время отправки до: ".$row['collection_timeend']."(".($row['collection_timeend']/60).")<br>" : "")."
			".(($row['weight']!=0) ? "Примерный вес: ".$row['weight']."кг.<br>" : "")."
			".(($row['comment']!="") ? "Комментарий: ".$row['comment']."<br>" : "")."
			</td>
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
	//$text=getOrderListDB($orders);
	foreach ($orders as $o) {
		$text.=$o.";";
		$where.=" Message_ID=".$o." OR";
	}
	$reqcount=count($orders);
	//echo $reqinsurance." ".$reqnp." ".$reqdeliverycost;
	// save postfile in DB
	$sql="INSERT INTO Netshop_PickpointFiles (created, ppfile) 
				VALUES ('".date("Y-m-d H:i:s")."','".addslashes($text)."')";
	//echo $sql;
	if (mysql_query($sql)) {
		$html.= "Файл для PickPoint сохранен.<br>";
	} else {
		die("Ошибка: " . mysql_error());
	}
	$html.="<br><br><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'><b>Список файлов для PickPoint</b></a>";

	return $html;
}

function frmSetTimeWeight($incoming) {
	$html="";
	//print_r($incoming);
	$fid=intval($incoming['f']);
	$collection_date=date("d.m.Y");
	$collection_timestart=660;
	$collection_timeend=1080;
	$collection_fio="";
	$weight=0;
	$comment="";
	$sql="SELECT * FROM Netshop_PickpointFiles WHERE id=".$fid;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$collection_date=(($row['collection_date']!="0000-00-00") ? date("d.m.Y",strtotime($row['collection_date'])) : date("d.m.Y"));
			$collection_timestart=(($row['collection_timestart']!=0) ? $row['collection_timestart'] : $collection_timestart);
			$collection_timeend=(($row['collection_timeend']!=0) ? $row['collection_timeend'] : $collection_timeend);
			$collection_fio=(($row['collection_fio']!="") ? $row['collection_fio'] : $collection_fio);
			$weight=$row['weight'];
			$comment=$row['comment'];
		}
	}
	$html.="<h3>Указать дату, время и вес отправлений по списку {$incoming['f']}</h3>
	<form name='frm1' id='frm1' method='post' action='/netcat/modules/netshop/interface/order-pickpoint.php'>
	<input type='hidden' name='action' id='action' value='savetimeweight'>
	<input type='hidden' name='f' id='f' value='{$fid}'>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>Дата сбора</td>
		<td><input type='text' name='collection_date' id='collection_date' value='".$collection_date."'></td></tr>
	<tr><td>Ожидаемое время сбора с 
		<p style='margin:0;padding:0;font-size:10px;'>(количество минут от 00:00. 12:00 = 720)</p></td>
		<td><input type='text' name='collection_timestart' id='collection_timestart' value='{$collection_timestart}'></td></tr>
	<tr><td>Ожидаемое время сбора по 
		<p style='margin:0;padding:0;font-size:10px;'>(количество минут от 00:00.  18:00 = 1080)</p></td>
		<td><input type='text' name='collection_timeend' id='collection_timeend' value='{$collection_timeend}'></td></tr>
	<tr><td>Общий вес, кг. – примерное значение</td>
		<td><input type='text' name='weight' id='weight' value='{$weight}'></td></tr>
	<tr><td>ФИО контактного лица</td>
		<td><input type='text' name='collection_fio' id='collection_fio' value='{$collection_fio}' size='70'></td></tr>
	<tr><td>Комментарий для курьера</td>
		<td><textarea name='comment' id='comment' cols='60'>{$comment}</textarea></td></tr>
	<!--tr><td>Внутр. комментарий</td><td></td></tr-->
	
	</table>
	<p><input type='submit' value='Сохранить'></p>
	</form>";
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
	$where="";
	$orders=array();
	$show=1;
	//echo "<!--";
	//print_r($incoming);
	//echo "-->";
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
			//header("Location:/netcat/modules/netshop/interface/order-pickpoint.php?action=view");
			break;
		case "view":
			$html="<h1>Файлы для PickPoint</h1>";
			//$html.="<p><a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=getstatus'>Посмотреть статусы отправлений</a></p>";
			$html.="<p><a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=tracksendings'>Отследить отправления</a></p>";
			$html.="<p><a href='/netcat/modules/netshop/interface/pickpoint-work.php?action=logout'>завершить сессию</a></p><br clear='both'>";
			//$html.="	<p><a href=\"/netcat/modules/netshop/interface/statistic-post.php\">Статистика по почте</a></p>";
			$html.=getPPFileList();
			break;
		case "settimeweight":
			$html.=frmSetTimeWeight($incoming);
			break;
		case "savetimeweight":
			//print_r($incoming);
			//echo "<br>";
			$html.="<h3>Указать дату, время и вес отправлений по списку {$incoming['f']}</h3>";
			$sql="UPDATE Netshop_PickpointFiles SET
				collection_date='".date("Y-m-d",strtotime($incoming['collection_date']))."',
				collection_timestart=".intval($incoming['collection_timestart']).",
				collection_timeend=".intval($incoming['collection_timeend']).",
				weight=".$incoming['weight'].",
				collection_fio='".quot_smart($incoming['collection_fio'])."',
				comment='".quot_smart($incoming['comment'])."'
				WHERE id=".intval($incoming['f']);
			//echo $sql."<br>";
			if (mysql_query($sql)) {
				$html.="<p><b>Данные сохранены.</b></p>
				<p><a href='/netcat/modules/netshop/interface/order-pickpoint.php?action=view'>Вернуться в список отправлений</a></p>";
			} else {
				die($sql."Ошибка: ".mysql_error());
			}	
			break;
		default:
			$html="<h1>Заказы в статусе &quot;подготовлен к отправке в PickPoint&quot;</h1><br clear='all'>".getOrderListForm($orders);
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

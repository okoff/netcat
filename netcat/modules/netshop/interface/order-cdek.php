<?php
// 11.11.2016 Elen
// orderstatus=18 - подготовлен к отправке в сдэк
// orderstatus=19 - передан в сдэк 
include_once ("../../../../vars.inc.php");
include_once ("utils.php");
include_once ("utils-mysqli.php");
include_once ("utils-template.php");
include_once ("cdek-api.php");
session_start();

function getDeliveryMethod($con,$id) {
	$res="";
	$sql="SELECT Name FROM Message56 WHERE Message_ID=".$id;
	$result=db_query($con,$sql);
	while ($row = mysqli_fetch_array($result)) {
		$res=convstr($row['Name']);
	}
	return $res;
}
function getPaymentMethod($con,$id) {
	$res="";
	$sql="SELECT Name FROM Message55 WHERE Message_ID=".$id;
	$result=mysql_query($con,$sql);
	while ($row = mysqli_fetch_array($result)) {
		$res=convstr($row['Name']);
	}
	return $res;
}

function getOrderItems($con,$order_id,$conv=1) {
	$res="";
	$sql="SELECT Message57.ItemID,Message57.Name,Netshop_OrderGoods.Qty FROM Message57 
		INNER JOIN Netshop_OrderGoods ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		WHERE Netshop_OrderGoods.Order_ID=".$order_id;
	$result=db_query($con,$sql);
	while ($row = mysqli_fetch_array($result)) {
		$res.="[".convstr($row['ItemID'])."] ".convstr($row['Name'])." {$row['Qty']}шт.;";
		
	}
	if ($conv==1) {
		//$res=iconv("WINDOWS-1251","UTF-8", $res);
	}
	return $res;
}
 
function getOrderList($con,$orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	// status=18 подготовлен к отправке
	$status=18;
	$sql="SELECT * FROM Message51 WHERE Status={$status} ".(($where!="") ? " AND (".substr($where, 0, -3).")" : "")." ORDER BY Message_ID DESC";
	$i=0;
	$result=db_query($con,$sql);
	$html.="<p>Количество заказов: ".((!empty($orders)) ? count($orders) : mysql_num_rows($result))."</p>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>".((empty($orders)) ? "<td>все<br><input type='checkbox' onclick='checkAll();' id='chkall'></td><td>Дата создания</td>" : "")."
		<td>№ заказа</td>
		<td>ФИО</td>
		<td>Описание отправления</td>
		<td>Адрес доставки</td>
		".((!empty($orders)) ? "<td>сумма</td><td>Телефон</td>" : 
			"<td>Телефон</td><td>Моб. тел.</td><td>Сумма заказа</td><td>Стоимость доставки</td><td>ИТОГО</td><td>Страховая сумма</td><td>Способ доставки</td><td>Способ оплаты</td>")."</tr>";
	while($row = mysqli_fetch_array($result)) {
		$st=($row['paid']==1) ? "style='background:#ccffcc;'" : "style='background:#ff9999;'";
		//print_r($row);

		$html.="<tr {$st}>
		".((empty($orders)) ? "<td {$st}><input type='checkbox' name='oid[{$i}]' id='oid-{$row['Message_ID']}' value='{$row['Message_ID']}'></td>" : "")."
		".((empty($orders)) ? "<td>{$row['Created']}</td>" : "")."
		<td><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
		<td>".convstr($row['ContactName'])."</td>
		<td>".getOrderItems($con,$row['Message_ID'],0)."</td>
		<td>".convstr($row['Address'])."</td>
		".((empty($orders)) ? "<td>{$row['Phone']}</td><td>".(($row['mphone']!="+7") ? $row['mphone'] : "")."</td>
							<td>".getOrderCost($row['Message_ID'])."</td><td>{$row['DeliveryCost']}</td>
							<td>".(getOrderCost($row['Message_ID'])+$row['DeliveryCost'])."</td>
							<td>{$row['summinsurance']}</td>
							<td>".getDeliveryMethod($con,$row['DeliveryMethod'])."
							".(($row['cdek_modeid']==4) ? "<b>склад-склад</b>" : "")
							.(($row['cdek_modeid']==3) ? "<b>склад-дверь</b>" : "")."</td>
							<td>".getPaymentMethod($con,$row['PaymentMethod'])."</td>
		" : "<td>{$row['summinsurance']}</td>
		<td>".(($row['Phone']!="") ? $row['Phone'] : $row['mphone'])."</td>").
		"</tr>";
		$i=$i+1;
	}
	 
	

	$html.="</table><br>";

	return $html;
} 

function getOrderListForm($con,$orders=array()) {
	$html="";
	$where="";
	foreach ($orders as $o) {
		$where.=" Message_ID=".$o." OR";
	}
	
	$html.="
	<script>
function checkAll() {
	var boxes = document.getElementsByTagName('input');
	var chk=false;
	if (document.getElementById(\"chkall\").checked) {
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
	if (document.getElementById(\"action\").value==\"create\") {
		for (i=0; i<boxes.length; i++)  {
			if (boxes[i].type == 'checkbox')   {
				if (boxes[i].checked) {
					//alert(j);
					return true;
				}
				j=j+1;
			}	
		}
		document.getElementById(\"err\").style.display=\"block\";
		return false;
	} else {
		return true;
	}
	
}
	</script>
	
	<span id='err' style='display:none;color:#f30000;'>Нужно выбрать заказ!</span>
	<form method='post' name='frm1' id='frm1' action='/netcat/modules/netshop/interface/order-cdek.php'  onsubmit='return clickEdit();'>
	<fieldset style='border:0;'>";

	$i=0;
	$html.=getOrderList($con,$orders);
	

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


function getCDEKFileList($con) {
	$html="";
	$where="";

	// status=18 подготовлен к отправке
	$sql="SELECT * FROM Netshop_CDEKFiles ORDER BY ID DESC";

	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>#</td><td>Дата создания</td>
		<td width='20%'>Действия</td>
		<td>Заказы в файле</td>
		<td>Кол. заказов</td>
		<td>Зарегистрированные заказы</td>
		<td>Отклоненные заказы</td>
		</tr>";

	$i=0;
	$result=db_query($con,$sql);
	
	while($row = mysqli_fetch_array($result)) {
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
		$straction.="1) <a href='/netcat/modules/netshop/interface/cdek-work.php?action=register&f={$row['id']}'>зарегистрировать отправления</a><br>";
		$straction.="2) <a href='/netcat/modules/netshop/interface/cdek-work.php?action=getlabel&f={$row['id']}'>печать квитанций</a><br>";
		$straction.="3) <a href='/netcat/modules/netshop/interface/order-cdek.php?action=settimeweight&f={$row['id']}'>указать время сбора и суммарный вес</a><br>";
		$straction.="4) <a href='/netcat/modules/netshop/interface/cdek-work.php?action=courier&f={$row['id']}'>вызов курьера</a><br>";
		
		$html.="<tr>
		<td>{$row['id']}</td>
		<td>".date("d.m.Y H:i:s",strtotime($row['created']))."
			".(($row['registered']!="0000-00-00 00:00:00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y H:i:s",strtotime($row['registered']))." отправления зарегистрированы</p>" : "")."
			".(($row['getlabel']!="0000-00-00 00:00:00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y H:i:s",strtotime($row['getlabel']))." сформированы этикетки</p>" : "")."
			".(($row['getreestr']!="0000-00-00 00:00:00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y H:i:s",strtotime($row['getreestr']))." сформирован реестр</p>" : "")."
			".(($row['collection_date']!="0000-00-00") ? "<p style='font-size:11px;margin:0;padding:0;color:#606060;'>".date("d.m.Y",strtotime($row['collection_date']))." вызов курьера.</p>" : "")."
		</td>
		<td>{$straction}</td>
		<td>{$reqstr}</td>
		<td>{$n}</td>
		<td>{$row['itemscreated']}</td>
		<td>{$row['itemsrejected']}</td>
		</tr>";
		$i=$i+1;
	}

	

	$html.="</table><br>";

	return $html;
}

function saveCSVFile($con, $orders) {
	$html=$text=$where="";
	//$text=getOrderListDB($orders);
	foreach ($orders as $o) {
		$text.=$o.";";
		$where.=" Message_ID=".$o." OR";
	}
	$reqcount=count($orders);
	//echo $reqinsurance." ".$reqnp." ".$reqdeliverycost;
	// save postfile in DB
	$sql="INSERT INTO Netshop_CDEKFiles (created, ppfile) 
				VALUES ('".date("Y-m-d H:i:s")."','".addslashes($text)."')";
	//echo $sql;
	if (db_query($con,$sql)) {
		$html.= "Файл для СДЭК сохранен.<br>";
	} 
	$html.="<br><br><a href='/netcat/modules/netshop/interface/order-cdek.php?action=view'><b>Список файлов для СДЭК</b></a>";

	return $html;
}
function frmSetTimeWeight($con,$incoming) {
	$html="";
	//print_r($incoming);
	$fid=intval($incoming['f']);
	$collection_date=date("d.m.Y");
	$collection_timestart="12:00";
	$collection_timeend="17:00";
	$collection_fio="";
	$weight=0;
	$comment="";
	$sql="SELECT * FROM Netshop_CDEKFiles WHERE id=".$fid;
	$result=db_query($con,$sql);
	while($row = mysqli_fetch_array($result)) {
			$collection_date=(($row['collection_date']!="0000-00-00") ? date("d.m.Y",strtotime($row['collection_date'])) : date("d.m.Y"));
			$collection_timestart=(($row['collection_timestart']!=0) ? date("H:i",$row['collection_timestart']) : $collection_timestart);
			$collection_timeend=(($row['collection_timeend']!=0) ? date("H:i",$row['collection_timeend']) : $collection_timeend);
			$collection_fio=(($row['collection_fio']!="") ? htmlspecialchars_decode(iconv("cp1251","UTF-8",$row['collection_fio'])) : $collection_fio);
			$weight=$row['weight'];
			$comment=htmlspecialchars_decode(iconv("cp1251","UTF-8",$row['comment']));
		}

	$html.="<h3>Указать дату, время и вес отправлений по списку {$incoming['f']}</h3>
	<form name='frm1' id='frm1' method='post' action='/netcat/modules/netshop/interface/order-cdek.php'>
	<input type='hidden' name='action' id='action' value='savetimeweight'>
	<input type='hidden' name='f' id='f' value='{$fid}'>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>Дата сбора</td>
		<td><input type='text' name='collection_date' id='collection_date' value='".$collection_date."'></td></tr>
	<tr><td>Ожидаемое время сбора с 
		<p style='margin:0;padding:0;font-size:10px;'>12:00</p></td>
		<td><input type='text' name='collection_timestart' id='collection_timestart' value='{$collection_timestart}'></td></tr>
	<tr><td>Ожидаемое время сбора по 
		<p style='margin:0;padding:0;font-size:10px;color:#f30000;'>17:00!!!</p></td>
		<td><input type='text' name='collection_timeend' id='collection_timeend' value='{$collection_timeend}'></td></tr>
	<tr><td>Общий вес, ГРАММЫ</td>
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

isLogged();

$incoming = parse_incoming();
//print_r($incoming);
//echo "<br>";
$con=db_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);

echo printHeader("СДЭК");
echo printTopMenu($con);


$where="";
$orders=array(); 
$show=1;

switch ($incoming['action']) {
	case "create":
		$orders=$incoming['oid'];
		$html=getOrderListForm($con,$orders);
		break;
	case "save":
		//$show=0;
		$orders=$incoming["oid"];
		if ($incoming['request_method']=="post") {
			$html=saveCSVFile($con,$orders);
		} else {
			//print_r($incoming);
			$html=readCSVFile($DownloadDir,$DownloadDirLink,$orders);
		}
		//header("Location:/netcat/modules/netshop/interface/order-pickpoint.php?action=view");
		break;
	case "view":
		$html="<h1>Файлы для СДЭК</h1>";
		$html.="<p><a href='/netcat/modules/netshop/interface/cdek-work.php?action=listpvz'>Список ПВЗ</a></p>";
		$html.="<p><a href='/netcat/modules/netshop/interface/cdek-work.php?action=tracksendings'>Отследить отправления</a></p>";
		$html.=getCDEKFileList($con);
		break;
	case "settimeweight":
		$html.=frmSetTimeWeight($con,$incoming);
		break;
	case "savetimeweight":
		//print_r($incoming);
		//echo "<br>";
		//echo mb_detect_encoding($incoming['collection_fio'])."<br>";
		$html.="<h3>Указать дату, время и вес отправлений по списку {$incoming['f']}</h3>";
		$sql="UPDATE Netshop_CDEKFiles SET
			collection_date='".date("Y-m-d",strtotime($incoming['collection_date']))."',
			collection_timestart=".intval(strtotime($incoming['collection_timestart'])).",
			collection_timeend=".intval(strtotime($incoming['collection_timeend'])).",
			weight=".$incoming['weight'].",
			collection_fio='".iconv("UTF-8","cp1251",htmlspecialchars($incoming['collection_fio']))."',
			comment='".iconv("UTF-8","cp1251",htmlspecialchars($incoming['comment']))."'
			WHERE id=".intval($incoming['f']);
		//echo $sql."<br>";
		if (mysqli_query($con,$sql)) {
			$html.="<p><b>Данные сохранены.</b></p>
			<p><a href='/netcat/modules/netshop/interface/order-cdek.php?action=view'>Вернуться в список отправлений</a></p>";
		} else {
			die($sql."Ошибка: ".mysql_error());
		}	
		break;
	default:
		$html="<h1>Заказы в статусе &quot;подготовлен к отправке в СДЭК&quot;</h1><br clear='all'>".getOrderListForm($con,$orders);
		break;
}
	
	//echo $where;
//print_r($_SESSION);
if ($show) {

	echo $html;



}

db_close($con);
printFooter();
?>

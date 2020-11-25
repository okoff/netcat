<?php
function printVendor($incoming) {
	$res="";
	$sql="SELECT * FROM Classificator_Supplier WHERE Supplier_ID={$incoming} ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['Supplier_Name'];
	}
	return $res;
}
function printModel($incoming) {
	$res="";
	$sql="SELECT * FROM Classificator_model WHERE model_ID={$incoming} ORDER BY model_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['model_Name'];
	}
	return $res;
}

function selectWaybillType($incoming,$startsta=1) {
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$dsbl=($startsta==2) ? "disabled" : "";
	$res="<select id='wbtype' name='wbtype' {$dsbl}>
		<option value=''>---</option>";
	$sql="SELECT * FROM Waybills_type ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['id']}' ".(($incoming['wbtype']==$row['id']) ? "selected" : "").">{$row['name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function printWaybillType($incoming) {
	$res="";
	$sql="SELECT * FROM Waybills_type WHERE id={$incoming} ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['name'];
	}
	return $res;
}

function selectWaybillStatus($incoming,$startsta=1) {
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$dsbl=($startsta==2) ? "disabled" : "";
	$res="<select id='wbstatus' name='wbstatus' {$dsbl} onchange='changeWBStatus();'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Waybills_status ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['id']}' ".(($incoming['wbstatus']==$row['id']) ? "selected" : "").">{$row['name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function printWaybillStatus($incoming) {
	$res="";
	$sql="SELECT * FROM Waybills_status WHERE id={$incoming} ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['name'];
	}
	return $res;
}

function selectWaybillPayment($incoming,$startsta=1) {
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$dsbl=($startsta==2) ? "disabled" : "";
	$res="<select id='wbpayment' name='wbpayment' {$dsbl}>
		<option value=''>---</option>";
	$sql="SELECT * FROM Waybills_payment ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['id']}' ".(($incoming['wbpayment']==$row['id']) ? "selected" : "").">{$row['name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function printWaybillPayment($incoming) {
	$res="";
	$sql="SELECT * FROM Waybills_payment WHERE id={$incoming} ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['name'];
	}
	return $res;
}

function selectWaybillPaymentType($incoming,$startsta=1) {
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$dsbl=($startsta==2) ? "disabled" : "";
	$res="<select id='wbpaymenttype' name='wbpaymenttype' {$dsbl}>
		<option value=''>---</option>
		<option value='1' ".(($incoming['wbpaymenttype']==1) ? "selected" : "").">наличный</option>
		<option value='2' ".(($incoming['wbpaymenttype']==2) ? "selected" : "").">на карту</option>
		<option value='3' ".(($incoming['wbpaymenttype']==3) ? "selected" : "").">безналичный</option>
		";
	/*$sql="SELECT * FROM Message55 WHERE Checked=1 ORDER BY Message_ID ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['Message_ID']}' ".(($incoming['wbpaymenttype']==$row['Message_ID']) ? "selected" : "").">{$row['Name']}</option>";
	}*/
	$res.="</select>";
	return $res;
}
function printWaybillPaymentType($incoming) {
	$waybillPaymentType[1]="наличный";
	$waybillPaymentType[2]="на карту";
	$waybillPaymentType[3]="безналичный";
	return $waybillPaymentType[$incoming];
}

function printWaybillCart($id) {
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:400px;'>";
	$sql="SELECT Waybills_goods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Waybills_goods 
		INNER JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		WHERE Waybills_goods.deleted=0 AND waybill_id=".$id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="<tr><td width='70'>{$row['ItemID']}</td>
		<td>{$row['Name']}</td><td width='20'>{$row['qty']}</td><td width='70'>".($row['itemprice']*$row['qty'])."</td></tr>";
	}
	$res.="</table>";
	return $res;
}

function printWaybill($row) {
	$html="";
	($row['paid']==1) ? $style="background:#B2FFB4;" : $style="";
	($row['status_id']==2) ? $style="background:#a0a0a0;" : $style="";
	//$html.="<tr><td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['id']}' target='_blank'>{$row['id']}</a></b></td>
	$html.="<tr style='{$style}'><td><b>{$row['id']}</b></td>
	<td><b>{$row['name']}</b></td>
	<td>".date("d.m.Y", strtotime($row['created']))."</td>
	<td>".((($row['vendor_date'])&&($row['vendor_date']!="0000-00-00")&&(date("Y-m-d",strtotime($row['vendor_date']))!="0000-00-00")&&(date("Y-m-d",strtotime($row['vendor_date']))!="1970-01-01")) ? date("d.m.Y",strtotime($row['vendor_date'])) : "&nbsp;")."</td>
	<td align='center'>".(($row['paid']==1) ? "<img src='/images/icons/ok.png'" : "-")."</td>
	<td>".((($row['paid_date'])&&((date("d.m.Y",strtotime($row['paid_date']))!="01.01.1970")and(date("d.m.Y",strtotime($row['paid_date']))!="30.11.-0001"))) ? date("d.m.Y",strtotime($row['paid_date'])) : "&nbsp;")."</td>
	<td>".printVendor($row['vendor_id'])."</td>
	<td>".printWaybillType($row['type_id'])."</td>
	<td>".printWaybillStatus($row['status_id']).(($row['defectaway']==1) ? " <img src='/images/icons/ok.png'>" : "")."</td>
	<td>".printWaybillPayment($row['payment_id'])."</td>
	<td>".printWaybillPaymentType($row['moneytype_id'])."</td>
	<td>{$row['summoriginal']}</td>
	<!--td style='vertical-align:top;'>".printWaybillCart($row['id'])."</td-->
	<!--td style='vertical-align:top;'>".(($row['status_id']==1) ? "<a href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['id']}&start=1'>&gt;&gt;&gt;</a>" : "-")."</td-->
	<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['id']}&start=1'>&gt;&gt;&gt;</a></td>
	<td>".(($row['onsite']==1) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
	<td><a href='/netcat/modules/netshop/interface/waybills-editonsite.php?id={$row['id']}'><img src='/images/icons/edit.png' alt='Изменить описание на сайте' title='Изменить описание на сайте' style='display:block;margin:0 auto;'></a></td>
	<td><a href='/netcat/modules/netshop/interface/waybills-editpayment.php?id={$row['id']}'><img src='/images/icons/money.png' alt='Изменить данные об оплате' title='Изменить данные об оплате' style='display:block;margin:0 auto;'></a></a></td>
	</tr>";
	
	return $html;
}

function printWaybillHold($row) {
	$html="";
	($row['status_id']==2) ? $style="background:#a0a0a0;" : $style="";
	//$html.="<tr><td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['id']}' target='_blank'>{$row['id']}</a></b></td>
	$html.="<tr style='{$style}'><td><b>{$row['waybill_id']}</b></td>
	<td><a href='#' onclick='javascript:if(confirm(\"Удалить накладную #{$row['waybill_id']} из списка?\")){window.location=\"/netcat/modules/netshop/interface/keep-count-waybills.php?action=wbdel&n={$row['id']}&kc={$row['keepcount_id']}\";}'><img src='/images/icons/del.png' alt='Удалить накладную #{$row['waybill_id']} из списка' style='display:block;margin:0 auto;border:0;'></a></td>
	<td><b>{$row['name']}</b></td>
	<td>".date("d.m.Y", strtotime($row['created']))."</td>
	<td>".printWaybillType($row['type_id'])."</td>
	<td>".printWaybillStatus($row['status_id'])."</td>
	<td>{$row['summoriginal']}</td>
	<td><a href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['waybill_id']}&start=1'>&gt;&gt;&gt;</a></td>
	<td><a href='/netcat/modules/netshop/interface/keep-count-waybills.php?id={$row['waybill_id']}&action=hold'>провести</a></td>
	</tr>";
	
	return $html;
}
function getWaybillList($incoming) {
	$where="";
	//print_r($incoming);
	
	if ((isset($incoming['supplier'])) && ($incoming['supplier']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" vendor_id=".$incoming['supplier'];
	}
	if ((isset($incoming['wbtype'])) && ($incoming['wbtype']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" type_id=".$incoming['wbtype'];
	}
	if ((isset($incoming['wbstatus'])) && ($incoming['wbstatus']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" status_id=".$incoming['wbstatus'];
	}
	if ((isset($incoming['wbpayment'])) && ($incoming['wbpayment']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" payment_id=".$incoming['wbpayment'];
	}
	if ((isset($incoming['wbpaymenttype'])) && ($incoming['wbpaymenttype']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" moneytype_id=".$incoming['wbpaymenttype'];
	}
	// в общей выборке не показываем учет, расход по учету, приход по учету и остатки на сайте
	$sql="SELECT * FROM Waybills
		WHERE created BETWEEN '".date("Y-m-d",strtotime($incoming['min']))." 00:00:00' AND '".date("Y-m-d",strtotime($incoming['max']))." 23:59:59'
		".(($where) ? " AND ".$where : " AND (NOT type_id=8) AND (NOT type_id=9) AND (NOT type_id=10)  AND (NOT type_id=11) ")." ORDER BY id DESC";
	//echo $sql;
	$html="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'><td>#</td><td>Номер</td><td>Дата создания</td><td>Дата поставщика</td>
	<td>Оплачено</td><td>Дата оплаты</td>
	<td>Поставщик</td><td>Тип накладной</td><td>Статус</td><td>Тип оплаты</td><td>Способ оплаты</td>
	<td>Сумма</td><td>Изменить</td><td colspan='2'>сайт</td><td>оплата</td></tr>";
	
	if ($result=mysql_query($sql)) {
		$html="<p>Всего накладных: <b>".mysql_num_rows($result)."</b></p>".$html;
		$itog=0;
		while($row = mysql_fetch_array($result)) {
			//$html.=printWaybill($row);
			($row['paid']==1) ? $style="background:#B2FFB4;" : $style="";
			($row['status_id']==2) ? $style="background:#a0a0a0;" : $style="";
			//$html.="<tr><td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['id']}' target='_blank'>{$row['id']}</a></b></td>
			$html.="<tr style='{$style}'><td><b>{$row['id']}</b></td>
			<td><b>{$row['name']}</b></td>
			<td>".date("d.m.Y", strtotime($row['created']))."</td>
			<td>".((($row['vendor_date'])&&($row['vendor_date']!="0000-00-00")&&(date("Y-m-d",strtotime($row['vendor_date']))!="0000-00-00")&&(date("Y-m-d",strtotime($row['vendor_date']))!="1970-01-01")) ? date("d.m.Y",strtotime($row['vendor_date'])) : "&nbsp;")."</td>
			<td align='center'>".(($row['paid']==1) ? "<img src='/images/icons/ok.png'" : "-")."</td>
			<td>".((($row['paid_date'])&&((date("d.m.Y",strtotime($row['paid_date']))!="01.01.1970")and(date("d.m.Y",strtotime($row['paid_date']))!="30.11.-0001"))) ? date("d.m.Y",strtotime($row['paid_date'])) : "&nbsp;")."</td>
			<td>".printVendor($row['vendor_id'])."</td>
			<td>".printWaybillType($row['type_id'])."</td>
			<td>".printWaybillStatus($row['status_id']).(($row['defectaway']==1) ? " <img src='/images/icons/ok.png'>" : "")."</td>
			<td>".printWaybillPayment($row['payment_id'])."</td>
			<td>".printWaybillPaymentType($row['moneytype_id'])."</td>
			<td>{$row['summoriginal']}</td>
			<!--td style='vertical-align:top;'>".printWaybillCart($row['id'])."</td-->
			<!--td style='vertical-align:top;'>".(($row['status_id']==1) ? "<a href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['id']}&start=1'>&gt;&gt;&gt;</a>" : "-")."</td-->
			<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row['id']}&start=1'>&gt;&gt;&gt;</a></td>
			<td>".(($row['onsite']==1) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td><a href='/netcat/modules/netshop/interface/waybills-editonsite.php?id={$row['id']}'><img src='/images/icons/edit.png' alt='Изменить описание на сайте' title='Изменить описание на сайте' style='display:block;margin:0 auto;'></a></td>
			<td><a href='/netcat/modules/netshop/interface/waybills-editpayment.php?id={$row['id']}'><img src='/images/icons/money.png' alt='Изменить данные об оплате' title='Изменить данные об оплате' style='display:block;margin:0 auto;'></a></a></td>
			</tr>";
			$itog=$itog+$row['summoriginal'];
		}
	}
	$html.="</table>";
	$html.="<p>Всего: {$itog}</p>";
	//}
	return $html;
}

function getWaybillListHold($incoming) {
	$where="";
	if ((isset($incoming['kc']))&&(is_numeric($incoming['kc']))) {
		$sql="SELECT * FROM Waybills
		INNER JOIN Keepcount_WB ON (Waybills.id=Keepcount_WB.waybill_id)
		WHERE Keepcount_WB.keepcount_id=".intval($incoming['kc'])." AND (Waybills.type_id=8 OR Waybills.type_id=9 OR Waybills.type_id=10 OR Waybills.type_id=11) 
		ORDER BY Waybills.id DESC";
	} else {
		$sql="SELECT * FROM Waybills
			WHERE created BETWEEN '".date("Y-m-d",strtotime($incoming['min']))." 00:00:00' AND '".date("Y-m-d",strtotime($incoming['max']))." 23:59:59'
			AND  (type_id=8 OR type_id=9 OR type_id=10 OR type_id=11) ORDER BY id DESC";
	}
	//echo $sql;

	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>#</td>
			<td>Удалить из учета</td>
			<td>Номер</td>
			<td>Дата создания</td>
			<td>Тип накладной</td>
			<td>Статус</td>
			<td>Сумма</td>
			<td>Изменить</td>
			<td>&nbsp;</td>
			</tr>";
	
	
	if ($result=mysql_query($sql)) {
		$html="<p>Всего накладных: <b>".mysql_num_rows($result)."</b></p>".$html;
		while($row = mysql_fetch_array($result)) {
			//if ($row['type_id']==8) {
				$html.=printWaybillHold($row);
			//} else {
			//	$html.=printWaybill($row);
			//}
		}
	}
	$html.="</table>";
	//}
	return $html;
}
function getNewArticul() {
	$art=0;
	$sql="SELECT articul FROM ItemSettings";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$art=(int)$row['articul'];
		}
	}
	return $art;
}
function getPrice1($id) {
	$r=0;
	$sql="SELECT * FROM Message57_p WHERE Message_ID={$id} ORDER BY created DESC LIMIT 1";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$r=$row['Price'];
		}
	}
	return $r;
}
function getItemCount($id) {
	$r=0;
	$sql="SELECT StockUnits FROM Message57 WHERE Message_ID={$id}";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$r=$row['StockUnits'];
		}
	}
	return $r;
}

 
?>

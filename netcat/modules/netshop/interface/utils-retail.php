<?php
function printRetailCart($id) {
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:500px;'>";
	$sql="SELECT Retails_goods.*, Message57.ItemID AS ItemID, Message57.Name AS Name,Message57.supplier  FROM Retails_goods 
		INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
		WHERE Retails_goods.deleted=0 AND retail_id=".$id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="<tr>
			<td width='70' style='font-size:8pt;'>{$row['ItemID']}</td>
			<td width='100' style='font-size:8pt;'>".getSupplier($row['supplier'])."&nbsp;</td>
			<td style='font-size:8pt;'>{$row['Name']}</td>
			<td width='50' style='font-size:8pt;'>{$row['qty']}</td>
			<td width='70' style='font-size:8pt;'>".($row['itemprice']*$row['qty'])."</td></tr>";
	}
	$res.="</table>";
	return $res;
}
function printRetail($row, $j=1) {
	$html="";
	($row['paid']==1) ? $style=" background:#B2FFB4;" : $style="";
	//$html.="<tr><td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['id']}' target='_blank'>{$row['id']}</a></b></td>
	$html.="<tr><td style='vertical-align:top;{$style}'><b>{$j}</b> [{$row['id']}]</td>
	<td style='vertical-align:top;'>".date("d.m.Y", strtotime($row['created']))."<br>
	".(($row['paid']==1) ? "оплачен" : "")."
	</td>
	<td style='vertical-align:top;'>".(($row['fixed']==1) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
	<td style='vertical-align:top;'>".(($row['user_id']!=-1) ? printUserInfo($row['user_id']) : "аноним")."</b>
	</td>
	<td style='vertical-align:top;'>".printRetailCart($row['id'])."</td>
	<td style='vertical-align:top;'>{$row['summ']}</td>
	<td style='vertical-align:top;'>{$row['summ1']}</td>
	<td style='vertical-align:top;'>{$row['discount']}</td>
	<td style='vertical-align:top;'><p><a href='/netcat/modules/netshop/interface/order-printcheck.php?rid={$row['id']}' target='_blank'>печать</a></p></td>
	</tr>";
	
	return $html;
}

function getRetailList($incoming) {
	
	$where="";
	/*
	if ((isset($incoming['status'])) && ($incoming['status']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.Status=".$incoming['status'];
	}
	if ((isset($incoming['delivery'])) && ($incoming['delivery']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.DeliveryMethod=".$incoming['delivery'];
	}
	if ((isset($incoming['payment'])) && ($incoming['payment']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.PaymentMethod=".$incoming['payment'];
	}
	if ((isset($incoming['paid'])) && ($incoming['paid']==1)) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Retails.paid=1";
	}
	if ((isset($incoming['unpaid'])) && ($incoming['unpaid']==1)) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Retails.paid=0";
	}
	if ((isset($incoming['mesid'])) && ($incoming['mesid']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.Message_ID=".intval($incoming['mesid']);
	}
	if ((isset($incoming['barcode'])) && ($incoming['barcode']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.barcode=".intval($incoming['barcode']);
	}
	if ((isset($incoming['contactname'])) && ($incoming['contactname']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.ContactName  LIKE '%".$incoming['contactname']."%'";
	} 
	($where!="") ? $where=" WHERE ".$where : ""; */
	$sql="SELECT * FROM Retails
		WHERE created BETWEEN '".date("Y-m-d 00:00:00")."' AND '".date("Y-m-d 23:23:59")."'
		ORDER BY id DESC LIMIT 100";
	//echo "<br>".$sql;
	//if ($incoming['start']!=1) {
	$html="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td><b>#</b></td>
		<td><b>Дата</b></td>
		<td><b>Касса</b></td>
		<td><b>ФИО</b></td>
		<td><b>Состав заказа</b></td>
		<td><b>Итог</b></td>
		<td><b>Итог (нал.)</b></td>
		<td><b>Скидка</b></td>
		<td><b>Товарный чек</b></td>
		</tr>";
	
	if ($result=mysql_query($sql)) {
		$html="<p>Всего заказов: <b>".mysql_num_rows($result)."</b></p>".$html;
		$j=mysql_num_rows($result);
		while($row = mysql_fetch_array($result)) {
			$html.=printRetail($row,$j);
			$j=$j-1;
		}
	}
	$html.="</table>";
	//}
	return $html;
}

?>

<?php
// Работа с доменами
   
$reqid=date("YmdHsi").".9789@nic.ru";
$login="4540/NIC-REG/adm";
$password="YnfThR1kO";

// Доступ RRP
$rrplogin = "besthosting";
$rrppassword = "Rup0R12";

function getOrderIDByDomain($domain) {
	$order=$str="";
	//$sql="SELECT OrderID,ServiceID FROM Realization 
	$sql="select Orders.OrderID,Realization.ServiceID,Services.SvcType from Realization 
INNER JOIN Orders ON (Orders.OrderID=Realization.OrderID)
INNER JOIN RealizationAttributes ON (RealizationAttributes.RealizationID=Realization.ID) 
INNER JOIN Services ON (Services.ServiceID=Realization.ServiceID)
where Orders.IsDeleted=0 AND (Services.SvcType=13 OR Services.SvcType=35) AND RealizationAttributes.AttributeID=1 AND RealizationAttributes.Value LIKE '".strtolower($domain)."'
ORDER BY Orders.OrderID ASC";
	//echo $sql."<br>";
	if ($res=mssql_query($sql)) {
		while ($row=mssql_fetch_array($res)) {
			$str.=$row['OrderID'].";";
		}
	}
	$tmp=explode(";",$str);
	for ($i=0;$i<count($tmp);$i++) {
		if ($i==0) {
			$order.=$tmp[$i];
		} else {
			if ($tmp[$i]!=$tmp[$i-1]) {
				$order.=";".$tmp[$i];
			}
		}
	}
	//echo $order."<br>";
	return $order;
}

function getDomainByRealID($realid) {
	$domain="";
	$sql="SELECT Value FROM RealizationAttributes WHERE RealizationID={$realid} AND AttributeID=1";
	if ($res=mssql_query($sql)) {
		while ($row=mssql_fetch_array($res)) {
			$domain=$row['Value'];
			break;
		}
	}
	return $domain;
}
function getOrderIDByRealID($realid) {
	$order_id="";
	$sql="SELECT OrderID FROM Realization WHERE ID={$realid}";
	if ($res=mssql_query($sql)) {
		while ($row=mssql_fetch_array($res)) {
			$order_id=$row['OrderID'];
			break;
		}
	}
	return $order_id;
}
function getSvcIdByZone($zone) {
	$srvid="";
	switch ($zone){
		case "ru":
			$srvid=51;
			break;
		case "su":
			$srvid=542;
			break;
		case "xn--p1ai": //.рф
			$srvid=432;
			break;
		case "moscow": // .москва
			$srvid=586;
			break;
		case "xn--80adxhks": // .москва
			$srvid=585;
			break;
		case "net":
			$srvid=579;
			break;
		case "com":
			$srvid=56;
			break;
		case "org":
			$srvid=577;
			break;
		case "biz":
			$srvid=580;
			break;
		case "at":
			$srvid=67;
			break;
		case "de":
			$srvid=218;
			break;
		case "bz":
			$srvid=65;
			break;
		case "tv":
			$srvid=59;
			break;
		case "ms":
			$srvid=88;
			break;
		case "cn":
			$srvid=121;
			break;
		case "co.uk":
			$srvid=87;
			break;
	}
	return $srvid;
}
function getRealIDByDomain($domain,$order_id) {
	$real="";
	$realid=0;
	//echo $domain."<br>";
	$tmp=explode(".",$domain);
	if (count($tmp)>2) {
		$zone=strtolower($tmp[1].".".$tmp[2]);
	} else {
		$zone=strtolower($tmp[1]);
	}
	$srvid=0;
	//echo $zone."<br>";
	if (!$zone) {
		return "<p style='color:#f30000;'>Ошибка! Не указана зона!</p>";
	}
	$srvid=getSvcIdByZone($zone);
		
	$sql="select Orders.OrderID,Realization.ServiceID,Realization.ID AS RealID from Realization 
INNER JOIN Orders ON (Orders.OrderID=Realization.OrderID)
INNER JOIN RealizationAttributes ON (RealizationAttributes.RealizationID=Realization.ID) 
where Orders.OrderID={$order_id} AND Orders.IsDeleted=0 AND Realization.ServiceID={$srvid} AND RealizationAttributes.AttributeID=1 AND RealizationAttributes.Value LIKE '".strtolower($domain)."'";
	//echo $sql."<br>";
	
	if ($res=mssql_query($sql)) {
		while ($row=mssql_fetch_array($res)) {
			$realid=$row['RealID'];
			//echo $realid."<br>";
			$real="<p><a target='_blank' href='/domains/edit/{$realid}/'>выставить счет по заказу #{$order_id}</a></p>";
			$sql1="SELECT TOP 1 Bills.Datetime, Bills.Status,Bills.Num FROM Bills 
				INNER JOIN billsitems ON (Bills.Num=billsitems.BillNum) 
				WHERE billsitems.RealizationID={$realid} ORDER BY Datetime DESC";
			//echo $sql."<br>";
			if ($res1=mssql_query($sql1)) {
				while ($row1=mssql_fetch_array($res1)) {
					if ($row1['Status']==0) {
						$real="<p>счет #{$row1['Num']} выставлен по заказу #{$order_id} ".date("d.m.Y",strtotime($row1['Datetime'])).", ожидается оплата</p>";
					} else {
						if (date("Y",strtotime($row1['Datetime']))!=date("Y")) {
							$real="<p><a target='_blank' href='/domains/edit/{$realid}/'>выставить счет по заказу #{$order_id}</a></p>";
						} else {
							$real="<p style='color:#00f000;'>счет #{$row1['Num']} от выставлен по заказу #{$order_id} ".date("d.m.Y",strtotime($row1['Datetime'])).", оплачен</p>";
						}
					}
				}
			}	
		}
	} 
	if (!$realid) {
		$real.="<p style='color:#f30000;'>В заказе #{$order_id} нет услуги продления для домена! <a href='/domains/addsvc/{$order_id}/{$domain}/'>Создать</a></p>";
	}
	mssql_free_result($sql);
	//echo $res."<br>";
	return $real;
}

function billDomainBody($order_id,$realid) {
	$k=0;
	$period=12;
	$sql1="SELECT Realization.*,Services.Price,Services.SvcType FROM Realization 
	INNER JOIN Services ON (Realization.ServiceID=Services.ServiceID)
	WHERE Realization.OrderID={$order_id} AND Realization.ID={$realid}";
	//echo $sql1;
	if ($res1=mssql_query($sql1)) {
		while ($row1=mssql_fetch_array($res1)) {
			$billsitems[$k]=$realid.";".$row1['Price'].";".$row1['ServiceID'].";".$period;
			$k=$k+1;
		}
	}
	return $billsitems;
}

function billForDomain($realid,$incoming) {
	$domain="";
	$order_id=0;
	$domain=getDomainByRealID($realid);
	$order_id=getOrderIDByRealID($realid);
	
	$html="<h1>".DOMAIN_EDIT." ".$domain."</h1>";
	$html.="";
	$html.="<p>Заказ #<a target='_blank' href='https://www.best-hosting.ru/siteadmin/orders/index.aspx?cmd=view&id={$order_id}'>{$order_id}</a></p>";
	// выбор услуг для выставления счета
	$billsitems=billDomainBody($order_id,$realid);
	$buhnum=getLastBuhNumber()+1;
	//echo $sql."<br>";
	$amount=0;
	$num=getLastBillNumber($order_id);
	//print_r($billsitems);
	$tmpstr="<table cellpadding='1' cellspacing='0' border='1'>
		<tr><td><b>Услуга</b></td><td><b>Цена,у.е.</b></td><td><b>Цена,руб.</b></td><td><b>Период</b></td></tr>";
	for($j=0;$j<count($billsitems);$j++) {
		$tmp=explode(";",$billsitems[$j]); 
		//$sql="INSERT INTO billsitems (BillNum,RealizationID,Cost) VALUES ({$num},{$tmp[0]},{$tmp[1]})";
		//echo $sql."<br>";
		$tmpstr.="<tr><td>".iconv("windows-1251", "UTF-8", getServiceNameById($tmp[2])).". Домен {$domain}</td><td>".$tmp[1]."</td><td>".$tmp[1]*DOLLAR."</td><td>".$tmp[3]."</td></tr>";
		$amount=$amount+$tmp[1];
	}
	$tmpstr.="</table>";
	
	$uid=getUserId($order_id);
	//echo $uid."<br>";
	$auto=md5($uid+$num);
	$linkbill="http://www.best-hosting.ru/finanses/getbill.asp?type=1&bill={$num}&auto=".$auto;
	$html.="<div style='width:500px;float:left;'>
	<p>Сумма счета: {$amount}$ <b>".($amount*DOLLAR)."</b>руб.</p>
	<p><b>Детализация счета:</b><br> {$tmpstr}</p>
	<p><a target='_blank' href='/domains/sendbill/{$realid}/'>Отправить счет</a></p>
	
	<br><br><a target='_blank' href='{$linkbill}'>Просмотр счета</a></div>";
	
	/*$sql="SELECT TOP 10 Bills.*,billsitems.RealizationID FROM Bills 
	INNER JOIN billsitems ON (Bills.Num=billsitems.BillNum)
	WHERE OrderID={$order_id} ORDER BY Num DESC";*/
	$sql="SELECT TOP 10 Bills.* FROM Bills 
	WHERE OrderID={$order_id} ORDER BY Num DESC";
	$html.="<div style='width:500px;float:left;margin-left:30px;'>";
	$html.="<p><b>Последние счета</b></p>";
	$html.="<table cellpadding='0' cellspacing='0' border='0'>
		<tr><td style='background:#ccffcc;width:15px;'>&nbsp;</td>
			<td>счет на домен {$domain} (<span style='color:#f30000;'>ожидается</span>)</td></tr>
		</table><br><br>";
	$html.="<table cellpadding='1' cellspacing='0' border='1'>";
	$html.="<tr><td><b>#</b></td><td><b>Дата выписки</b></td><td><b>Сумма, $</b></td><td><b>Сумма, руб</b></td></tr>";
	if ($res=mssql_query($sql)) {
		while ($row=mssql_fetch_array($res)) {
			$style=" style='";
			$sql1="SELECT * FROM billsitems WHERE BillNum={$row['Num']} AND RealizationID={$realid}";
			if ($res1=mssql_query($sql1)) {
				while ($row1=mssql_fetch_array($res1)) {
					if ($row1['RealizationID']==$realid) {
						$style.="background:#ccffcc;";
					}
				}
			}
			
			if ($row['Status']==5) {
				$style.="background:#c0c0c0;";
			}
			if ($row['Status']==0) {
				$style.="color:#f30000;";
			}
			
			$style.="'";
			$html.="<tr><td {$style}><a target='_blank' href='https://www.best-hosting.ru/siteadmin/printbill/index.aspx?cmd=org&id={$row['Num']}&details=on&stamp=off'>{$row['Num']}</a>
				<td {$style}><a target='_blank' href='https://www.best-hosting.ru/siteadmin/printbill/index.aspx?cmd=org&id={$row['Num']}&details=on&stamp=off'>".date("d.m.Y",strtotime($row['Datetime']))."</a></td>
				<td {$style}>{$row['Amount']}</td><td {$style}>".($row['Amount']*DOLLAR)."</td></tr>";
		}
	}
	$html.="</table></div>";
	return $html;
}

function sendBill($incoming) {
	$realid=$incoming['n'];
	$billsitems=array();
	$ServicesRu=$Services="";
	$domain="";
	$order_id=0;
	$domain=getDomainByRealID($realid);
	$order_id=getOrderIDByRealID($realid);
	
	$html.="<p>Заказ #".$order_id."</p><br>";
	// выбор услуг для выставления счета
	$billsitems=billDomainBody($order_id,$realid);
	for($j=0;$j<count($billsitems);$j++) {
		$tmp=explode(";",$billsitems[$j]); 
		$amount=$amount+$tmp[1];
	}
	$buhnum=getLastBuhNumber()+1;
	$sql="INSERT INTO Bills  (Datetime,OrderID,Amount,Status,LimitDate,PayDate,BuhNum) VALUES
		('".date("Y-m-d H:i:s")."',{$order_id},{$amount},0,'".date("Y-m-d H:i:s",mktime(0, 0, 0, date("m")  , date("d")+14, date("Y")))."',null,{$buhnum})";
	//echo $sql."<br>";
	if (!mssql_query($sql)) {
		die("Error: ".$sql);
	}
	$num=getLastBillNumber($order_id);
	//print_r($billsitems);
	$tmpstr="<table cellpadding='1' cellspacing='0' border='1'>";
	for($j=0;$j<count($billsitems);$j++) {
		$tmp=explode(";",$billsitems[$j]); 
		$sql="INSERT INTO billsitems (BillNum,RealizationID,Cost) VALUES ({$num},{$tmp[0]},{$tmp[1]})";
		//echo $sql."<br>";
		if (!mssql_query($sql)) {
			die("Error: ".$sql);
		}
		$nm=explode(";",getServiceNameById($tmp[2]));
		$tmpstr.="<tr><td>".iconv("windows-1251", "UTF-8", $nm[1]).", {$domain}</td><td>".$tmp[1]."</td></tr>";
		$ServicesRu.=iconv("windows-1251", "UTF-8", $nm[1]).", {$domain}:  ".$tmp[1]*DOLLAR."руб.\n";
		$Services.=$nm[0].", {$domain}:  ".$tmp[1]."\n";
	}
	
	$tmpstr.="</table>";
	$uid=getUserId($order_id);
	//echo $uid."<br>";
	$auto=md5($uid+$num);
	$linkbill="http://www.best-hosting.ru/finanses/getbill.asp?type=1&bill={$num}&auto=".$auto;
	$linkbilleng="http://best-hosting.net/finanses/paybill.asp?billnum={$num}";
	$html.="<p>Детализация счета:<br> {$tmpstr}</p>
	<!--p><a target='_blank' href='{$linkbill}'>Ссылка на счет</a></p-->
	";
	
	$uname=getUserName($uid);
	$email=getUserEmail($uid);
	//echo md5("123");
	// отправляем уведомление
	$tomorrow_timestamp = strtotime("+ 14 day");
	$subject="Bill for order #<$OrderID$>. Счет на оплату по заказу #<$OrderID$>";
	$body="------------- russian --------------- 
Здравствуйте, ".iconv("windows-1251", "UTF-8", $uname)."! 

Сообщаем Вам, что для продления услуг хостинга сайта, 
Вам нужно ДО ".date("d.m.Y", $tomorrow_timestamp)." оплатить счет N{$num} на сумму ".($amount*DOLLAR)."руб., и сообщить об оплате, выслав скан платежки банка или квитанции сбербанка на факс (495)788-9484, либо на e-mail: billing@best-hosting.ru . 

Детализация счета: 
{$ServicesRu} 

Распечатать счет и оплатить услуги Вы можете в разделе Для клиентов - Оплата услуг: http://www.best-hosting.ru/client/account.asp 
либо по ссылке: {$linkbill}

Если у вас есть вопросы/замечания, пишите на billing@best-hosting.ru 
или звоните по телефону (495) 788-9484 , с 10 до 19 часов Московское время. 

С уважением, 
финансовая служба 
".SITERU."
E-Mail: ".EMAIL_BILLING."
Тел.: (495) 788-9484.

------------- English --------------- 
Dear ".iconv("windows-1251", "UTF-8", $uname)."! 

We need to inform you that insufficient money on your account (hosting service). 

Bill details: 
{$Services} 

For prolongation of the hosting service, you need till ".date("d.m.Y", $tomorrow_timestamp)." to pay the bill #{$num} here:
{$linkbilleng}\n
If you have any questions - please feel free to ask us any time.\n

Regards, 
Best Hosting team, 
".SITEENG."
".SITERU."
E-Mail: ".EMAIL_BILLING."
Phone: +7 495 788-9484(9:00-22:00 GMT+3 time)";

	$to=$email;
	$subject=str_replace("<$OrderID$>", $order_id, $subject);
	$body=str_replace("<$OrderID$>", $order_id, $body);
	sendMail($to,$subject,$body);
	
	$to="gnomon@best-hosting.ru";
	$subject=str_replace("<$OrderID$>", $order_id, $subject);
	$body=str_replace("<$OrderID$>", $order_id, $body);
	sendMail($to,$subject,$body);

	$to="elena@best-hosting.ru";
	$subject=str_replace("<$OrderID$>", $order_id, $subject);
	$body=str_replace("<$OrderID$>", $order_id, $body);
	sendMail($to,$subject,$body);
	
	// сохранить уведомление в базу
	$notif_id=getLastId("Notifications_Bill");
	$sql="INSERT INTO Notifications_Bill VALUES
		({$num},'".date("Y-m-d H:i:s")."','{$email}','','".iconv("UTF-8", "windows-1251", $subject)."','".iconv("UTF-8", "windows-1251", $body)."')";
	//echo $sql."<br>";
	if (!mssql_query($sql)) {
		die("Error: ".$sql);
	}
	$html.="<p style='color:#f30000;'>Счет отправлен</p>";
	return $html;
}
function printNICanswer($answer) {
	$res="";
	$res1="";
	//$res.="<p><b>Ответ</b></p>";
	$res1.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td><b>Заказ</b></td>
		<td><b>Домен</b></td>
		<td><b>дата, по которую оплачена услуга</b></td>
		<td>&nbsp;</td></tr>";
	$service=explode("\n\n",$answer);
	$all=0;
	$oid="";
	foreach ($service as $srv) {
		$domain="";
		$expdate="";
		$payedtill="";
		$suspenddate="";
		$tmp=explode("\n",$srv);
		//print_r($tmp);
		//echo "<br><br>";
		foreach ($tmp as $a) {
			//echo $a."<br>";
			$tmp1=explode(":",$a);
			switch($tmp1[0]) {
				case "services-found":
					$all=$tmp1[1];
					break;
				case "domain":
					$domain=$tmp1[1];
					break;
				case "payed-till":
					$payedtill=$tmp1[1];
					break;
				default:break;
			}
		}
		if ($domain) {
			$olink="";
			$real="";
			$o=getOrderIDByDomain($domain);
			$oid=explode(";",$o);
			for($i=0;$i<count($oid);$i++) {
				if ($oid[$i]) {
					$olink.="<p><a target='_blank' href='https://www.best-hosting.ru/siteadmin/orders/index.aspx?cmd=view&id={$oid[$i]}'>{$oid[$i]}</a></p>";
					$real.=getRealIDByDomain($domain,$oid[$i]);
				}
			}
			
			$res1.="<tr><td>{$olink}</td>
				<td>{$domain}</td><td>{$payedtill}</td><!--td>{$srv}</td-->
				<td>{$real}</td></tr>";
		}
	}
	$res1.="</table>";
	$res.="<p>Найдено: {$all}</p>".$res1;
	return $res;
}
function printRRPanswer($answer) {
	$res="";
	$res1="";
	$domains=array();
	$zones=array();
	$dates=array();
	$j=$k=$l=0;
	echo $answer."<br>";
	//$res.="<p><b>Ответ</b></p>";
	$res1.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td><b>Заказ</b></td>
		<td><b>Домен</b></td>
		<td><b>дата, по которую оплачена услуга</b></td>
		<td>&nbsp;</td></tr>";
	$items=explode("\n",$answer);
	foreach($items as $item) {
		$i=explode("=",$item);
		if (stripos($i[0],"[domain]")) {
			$domains[$j]=$i[1];
			$j=$j+1;
		}
		if (stripos($i[0],"[zone]")) {
			$zones[$k]=$i[1];
			$k=$k+1;
		}
		//if (stripos($i[0],"[expirationdate]")) {
		if (stripos($i[0],strtolower("[RENEWALDATE]"))) {
			$dates[$l]=$i[1];
			$l=$l+1;
		}
	}
	for ($n=0;$n<count($domains);$n++) {
		$real=$olink="";
		$o=getOrderIDByDomain(trim($domains[$n]));
		$oid=explode(";",$o);
		for($i=0;$i<count($oid);$i++) {
			if ($oid[$i]) {
				$olink.="<p><a target='_blank' href='https://www.best-hosting.ru/siteadmin/orders/index.aspx?cmd=view&id={$oid[$i]}'>{$oid[$i]}</a></p>";
				$real.=getRealIDByDomain(trim($domains[$n]),$oid[$i]);
			}
		}
		$res1.="<tr><td>{$olink}</td>
				<td>{$domains[$n]}</td><td>{$dates[$n]}</td><!--td>{$srv}</td-->
				<td>{$real}</td></tr>";
	}

	$res1.="</table>";
	$res.="<p>Найдено: ".count($domains)."</p>".$res1;
	return $res;
}

//print_r($incoming);
$html="";
switch($incoming['code']) {
	case "nic":
		$enddate=date("d.m.Y",mktime(0, 0, 0, date("m")+2, date("d"), date("Y")+1));
		$startdate=date("d.m.Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		//echo $enddate."<br>";
		$html.="<h1>".NIC_LIST."</h1>";
		$html.="<p>{$enddate}</p>";
		$temp="lang:ru\nlogin:{$login}\npassword:{$password}\nrequest:service\noperation:search\nrequest-id={$reqid}\r\n
[service]\nservice:domain\nstate:0\ndomain:\nre-registration-flag:1\nperiod-start-date:{$startdate}\nperiod-end-date:{$enddate}\nservices-limit:500\nservices-first:1";
		$post_data = array(
			'SimpleRequest' => $temp
		);
		//echo $temp."<br>";
		$result1=post_request('https://www.nic.ru/dns/dealer', $post_data);
		if (stripos($result1['header'],"200 OK")) {
			//$answer1=parse_answer($result1['content']);
			$html.=printNICanswer($result1['content']);
		}
		break;
	case "rrp":
		$enddate=date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+63, date("Y")));
		$startdate=date("d.m.Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		//echo $enddate."<br>";
		$html.="<h1>".RRP_LIST."</h1>";
		$html.="<p>Домены, которые закончатся до {$enddate}</p>";
		//$temp="s_login={$rrplogin}&s_pw={$rrppassword}ote&command=QueryDomainExpireList&days=10";
		//$temp="s_login={$rrplogin}&s_pw={$rrppassword}&command=CheckDomain&domain=example.org";
		$temp="s_login={$rrplogin}&s_pw={$rrppassword}&command=QueryDomainRenewList&days=63&wide=1&limit=1000";
		$post_data = $temp;
		 
		// Send a request 
		//$result = get_request('https://api-ote.rrpproxy.net/api/call', $post_data);
		$result = get_request('https://api.rrpproxy.net/api/call', $post_data);
	 
		//print_r($result);
		if (stripos($result['content']," = 200")) {
			//$answer1=parse_answer($result1['content']);
			$html.=printRRPanswer($result['content']);
		}
		break;
	case "edit":
		if (is_numeric($incoming['n'])) {
			$id=intval($incoming['n']);
			//$html.="<h1>".DOMAIN_EDIT."</h1>";
			$html.=billForDomain($id,$incoming);
		} else {
			$html.="<p class='error'>".ERR_ORDER."</p>";
		}
		break;
	case "sendbill":
		$html.="<h1>Отправка счета</h1>";
		$html.=sendBill($incoming);
		break;
	case "addsvc":
		$order_id=$incoming['n'];
		$domain=$incoming['d'];
		//echo $domain." ".$order_id."<br>";
		
		// получить id услуги, которую нужно добавить в заказ
		$tmp=explode(".",$domain);
		$zone=strtolower($tmp[1]);
		//echo $zone."<br>";
		$srvid=0;
		if (!$zone) {
			return "<p style='color:#f30000;'>Ошибка! Не указана зона!</p>";
		}
		$srvid=getSvcIdByZone($zone);
		//echo $srvid."<br>";
		$price=getSrvCostById($srvid);
		// добавить услугу в заказ
		$dte=date("Y-m-d");
		$sql="INSERT INTO Realization (OrderID,ServiceID,Quantity,StartDate,ExpDate,Discount,Status,ServiceParams,Cost,Period,SetupFee)
			VALUES ({$order_id},{$srvid},1,'{$dte}','{$dte}',0,0,'',{$price},12,0)";
		//echo $sql."<br>";
		if (!mssql_query($sql)) {
			die("Error: ".$sql);
		}
		$sql1="SELECT TOP 1 * FROM Realization WHERE OrderID={$order_id} AND ServiceID={$srvid} ORDER BY ID DESC";
		if ($res1=mssql_query($sql1)) {
			while ($row1=mssql_fetch_array($res1)) {
				$realid.=$row1['ID'];
			}
		}
		//echo $realid."<br>";
		$sql2="INSERT INTO RealizationAttributes (RealizationID,AttributeID,Value)
			VALUES ({$realid},1,'".strtolower($domain)."')"; 
		if (!mssql_query($sql2)) {
			die("Error: ".$sql2);
		}
		$sql1="SELECT TOP 1 * FROM RealizationAttributes WHERE RealizationID={$realid} AND AttributeID=1 AND Value='{$domain}'";
		if ($res1=mssql_query($sql1)) {
			while ($row1=mssql_fetch_array($res1)) {
				$raid.=$row1['ID'];
			}
		}
		$sql2="UPDATE RealizationAttributes SET ActiveID={$raid} WHERE ID={$raid}";
		if (!mssql_query($sql2)) {
			die("Error: ".$sql2);
		}
		$html.="<p>Услуга продления создана. <a href='/domains/edit/{$realid}'>Выставить счет</a></p>";

		break;
	default:
		break;
}


?>
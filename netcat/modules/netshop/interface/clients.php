<?php
// 12.11.2013 Elen
// поиск и просмотр списка заказов
include_once ("../../../../vars.inc.php");

session_start();

// ------------------------------------------------------------------------------------------------
function printClientInfo($cid) {
	$html="";
	$sql="SELECT * FROM User WHERE User.User_ID={$cid}";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$html.="<h2>{$row['Login']}</h2><p>{$row['Email']}</p>
		<p>Дата регистрации: ".date("d.m.Y", strtotime($row['Created']))."</p>
		<p>Дисконтная карта: {$row['discountcard']} 
		".(($row['discountcard']=="") ? "[<a href='#' onclick='showFrmDsc();' style='cursor:pointer;'>добавить</a>]" : "")."</p>
		<div id='frmdsc' style='display:none;'>
			<form id='frm1' name='frm1' method='post' action='/netcat/modules/netshop/interface/clients.php'>
				<input type='hidden' name='cid' value='{$cid}'>
				<input type='hidden' name='action' value='setdiscount'>
				Номер дисконтной карты: <input type='text' name='dsccard' value='{$row['discountcard']}'>
				<input type='submit' value='Сохранить'>
			</form>
			<br>
		</div>
		<p>Телефон: {$row['phone']} 
		".(($row['phone']=="") ? "[<a href='#' onclick='showFrmPhone();' style='cursor:pointer;'>добавить</a>]" : "")."</p>
		<div id='frmphone' style='display:none;'>
			<form id='frm1' name='frm1' method='post' action='/netcat/modules/netshop/interface/clients.php'>
				<input type='hidden' name='cid' value='{$cid}'>
				<input type='hidden' name='action' value='setphone'>
				Телефон: <input type='text' name='phone' value='{$row['phone']}'>
				<input type='submit' value='Сохранить'>
			</form>
			<br>
		</div>
		{$row['comment']}<br>";
		//$res.="<tr><td width='70'>{$row['ItemID']}</td>
		//<td>{$row['Name']}</td><td width='50'>{$row['Qty']}</td><td width='100'>".($row['ItemPrice']*$row['Qty'])."</td></tr>";
	}
	$sql="SELECT * FROM User_codetails WHERE user_id={$cid} ORDER BY name ASC";
	if ($rs=mysql_query($sql)) {
		$html.="<h3>Компании пользователя [<a href='/netcat/modules/netshop/interface/client-codetails.php?cid={$cid}'>добавить</a>]</h3>";
		$html.="<ul'>";
		while ($row1 = mysql_fetch_array($rs)) {
			$html.="<li><a href='/netcat/modules/netshop/interface/client-codetails.php?cid={$cid}'>{$row1['name']}</a></li>";
		}
		$html.="</ul>";
	}

	$html.="<h3>Комментарии</h3><table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM User_comments WHERE user_id={$cid} ORDER BY id DESC"; //SELECT * FROM User_comments WHERE user_id=7535 ORDER BY id DESC
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$html.="<tr><td>".date("d.m.Y", strtotime($row['created']))."</td><td>{$row['comment']}</td></tr>";
	}
	$html.="</table>
	[<a href='/netcat/modules/netshop/interface/client-edit.php?cid={$cid}'>Добавить комментарий</a>]<br>";
	$html.="<h3>Заказы</h3>";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT Message51.*, Classificator_ShopOrderStatus.ShopOrderStatus_Name AS OrderStatus FROM Message51
			INNER JOIN Classificator_ShopOrderStatus ON (Classificator_ShopOrderStatus.ShopOrderStatus_ID=Message51.Status)
			WHERE Message51.User_ID={$cid} ORDER BY Message51.Message_ID DESC";
	//echo $sql;
	$result=mysql_query($sql);
	$tmp="";
	$onum=0;
	while ($row = mysql_fetch_array($result)) {
		$html.="<tr><td style='padding:2px;'><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
			<td style='padding:2px;'>".date("d.m.Y", strtotime($row['Created']))."</td>
			<td style='padding:2px;'>{$row['OrderStatus']}</td>
			<td>".printCart($row['Message_ID'])."</td>
			</tr>";
		$onum=$onum+1;
	}
	$html.="</table>";
	$html.="<p>Всего заказов: {$onum}</p><table cellpadding='2' cellspacing='0' border='1'>".$tmp;
	
	// розничные продажи
	$html.="<h3>Розничные продажи</h3>";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Retails
			WHERE Retails.user_id={$cid} ORDER BY id DESC";
	//echo $sql;
	$result=mysql_query($sql);
	$tmp="";
	$onum=0;
	while ($row = mysql_fetch_array($result)) {
		//$tmp.="<tr><td style='padding:2px;'><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a></td>
		$html.="<tr><td style='padding:2px;'><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['id']}' target='_blank'>{$row['id']}</a></td>
			<td style='padding:2px;'>".date("d.m.Y", strtotime($row['created']))."</td>
			<td>".printRetailCart($row['id'])."</td>
			</tr>";
		$onum=$onum+1;
	}
	$html.="</table>";
	$html.="<p>Всего заказов: {$onum}</p><table cellpadding='2' cellspacing='0' border='1'>".$tmp;
	return $html;
}
/*function printCart($oid) {
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:500px;'>";
	$sql="SELECT Netshop_OrderGoods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Netshop_OrderGoods 
		INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		WHERE Order_ID=".$oid;
	//echo $sql;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="<tr><td width='70'>{$row['ItemID']}</td>
		<td>{$row['Name']}</td><td width='50'>{$row['Qty']}</td><td width='100'>".($row['ItemPrice']*$row['Qty'])."</td></tr>";
	}
	$res.="</table>";
	return $res;
}*/
function printClient($row) {
	$html="";
	
	$html.="<tr>
	<td style='vertical-align:top;'><b><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$row['User_ID']}' target='_blank'>{$row['User_ID']}</a></b></td>
	<td style='vertical-align:top;'>{$row['Login']}</td>
	<td style='vertical-align:top;'>{$row['Email']}</td>
	<td style='vertical-align:top;'>{$row['phone']}</td>
	<td style='vertical-align:top;'>".date("d.m.Y", strtotime($row['Created']))."</td>
	<td style='vertical-align:top;'><a href='/netcat/modules/netshop/interface/clients-requests.php?cid={$row['User_ID']}'>
		<img src='/images/icons/wblist.png' alt='просморт заявок' title='просморт заявок' style='display:block;margin:0 auto;'></a></td>
	<td style='vertical-align:top;'><a href='/netcat/modules/netshop/interface/clients-requests.php?cid={$row['User_ID']}&action=add'>
		<img src='/images/icons/plus.png' alt='создать новую заявку' title='создать новую заявку' style='display:block;margin:0 auto;'></a></td>
	</tr>\n";
	
	return $html;
}

function getClientList($incoming) {
	
	$where="";
	
	if ((isset($incoming['login'])) && ($incoming['login']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Login LIKE '".$incoming['login']."%'";
	}
	if ((isset($incoming['email'])) && ($incoming['email']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Email LIKE '%".$incoming['email']."%'";
	}
	if ((isset($incoming['discountcard'])) && ($incoming['discountcard']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" discountcard LIKE '%".$incoming['discountcard']."%'";
	}
	if ((isset($incoming['phone'])) && ($incoming['phone']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" phone LIKE '%".$incoming['phone']."%'";
	}
/*	if ((isset($incoming['paid'])) && ($incoming['paid']==1)) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.paid=1";
	}
	if ((isset($incoming['mesid'])) && ($incoming['mesid']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.Message_ID=".intval($incoming['mesid']);
	}
	if ((isset($incoming['barcode'])) && ($incoming['barcode']!="")) {
		(strlen($where)>3) ? $where.=" AND " : "";
		$where.=" Message51.barcode=".intval($incoming['barcode']);
	}*/
	($where!="") ? $where=" WHERE ".$where : "";
	$sql="SELECT *
		FROM User ".$where."
		ORDER BY Login ASC, Email ASC";
	//echo "<br>".$sql;
	if ($incoming['start']!=1) {
		$html="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td rowspan='2'><b>#</b></td>
		<td rowspan='2'><b>ФИО</b></td>
		<td rowspan='2'><b>Email</b></td>
		<td rowspan='2'><b>Телефон</b></td>
		<td rowspan='2'><b>Дата регистрации</b></td>
		<td colspan='2'><b>Заявки по клиенту</b></td>
	</tr>
	<tr>
		<td><b>Просмотр</b></td>
		<td><b>Создать</b></td>
	</tr>";
		if ($result=mysql_query($sql)) {
			$html="<p>Всего клиентов: <b>".mysql_num_rows($result)."</b></p>".$html;
			while($row = mysql_fetch_array($result)) {
				$html.=printClient($row);
			}
		}
		$html.="</table>";
	}
	return $html;
}
// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	include_once ("utils-retail.php");
	//$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
	//$UploadDirNarv=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/narv/';
	//$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
	//$DownloadDirLink='/netcat_files/postfiles/';
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$show=1;
	//print_r($incoming);
	switch ($incoming['action']) {
		case "setdiscount":
			if ((isset($incoming['cid'])) && ($incoming['request_method']=="post")) {
				$sql="UPDATE User SET discountcard='{$incoming['dsccard']}' WHERE User_ID={$incoming['cid']}";
				if (mysql_query($sql)) {
					$show=0;
					$html="<p>Информация о клиенте обновлена. <a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}'>Вернуться в карточку клиента</a>.</p>";
					header("Location:/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}");
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
			}
			break;
		case "setphone":
			if ((isset($incoming['cid'])) && ($incoming['request_method']=="post")) {
				$sql="UPDATE User SET phone='{$incoming['phone']}' WHERE User_ID={$incoming['cid']}";
				if (mysql_query($sql)) {
					$show=0;
					$html="<p>Информация о клиенте обновлена. <a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}'>Вернуться в карточку клиента</a>.</p>";
					header("Location:/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}");
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
			}
			break;
		case "history":
			$show=0;
			$html=printClientInfo($incoming['cid']);
			break;
		default:
			if (!isset($incoming['start'])) {
				$html=getClientList($incoming);
			}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Клиенты</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script type='text/javascript'>
	function showFrmDsc() {
		var dst = document.getElementById("frmdsc")
        dst.style.display = (dst.style.display=='none' ? '' : 'none');
	}
	function showFrmPhone() {
		var dst = document.getElementById("frmphone")
        dst.style.display = (dst.style.display=='none' ? '' : 'none');
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
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	if ($show) {
?>
	<h1>Клиенты</h1>
	<form action="/netcat/modules/netshop/interface/clients.php" method="post" name="frm1" id="frm1">
	<fieldset style='border:0;'>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td align='right'>Фамилия:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['login'])) ? $incoming['login'] : "");  ?>' name='login' id='login'></td>
			<td align='right'>e-mail:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['email'])) ? $incoming['email'] : "");  ?>' name='email' id='email'></td>
		</tr>
		<tr>
			<td align='right'>Дисконт. карта:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['discountcard'])) ? $incoming['discountcard'] : "");  ?>' name='discountcard' id='discountcard'></td>
			<td align='right'>Телефон:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['phone'])) ? $incoming['phone'] : "");  ?>' name='phone' id='phone'></td>
		</tr>
		<tr>
		<td colspan='2'><input type='submit' value='Найти'></td>
		<td colspan='2' style='text-align:right;'>[<a href='/netcat/modules/netshop/interface/client-addnew.php'>Добавить нового клиента</a>]</td>
		</tr>
	</table>
	
	</fieldset>
	</form>
	<?php 
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

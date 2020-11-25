<?php
// 24.12.2013 Elen
// поиск и просмотр списка розничных продаж (магазин)
include_once ("../../../../vars.inc.php");
include_once ("utils.php");
session_start();
function printRetailCart($id) {
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:500px;'>";
	$sql="SELECT Retails_goods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Retails_goods 
		INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
		WHERE retail_id=".$id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="<tr><td width='70'>{$row['ItemID']}</td>
		<td>{$row['Name']}</td><td width='50'>{$row['qty']}</td><td width='100'>".($row['itemprice']*$row['qty'])."</td></tr>";
	}
	$res.="</table>";
	return $res;
}
function printRetail($row) {
	$html="";
	($row['paid']==1) ? $style=" background:#B2FFB4;" : $style="";
	//$html.="<tr><td style='vertical-align:top;{$style}'><b><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['id']}' target='_blank'>{$row['id']}</a></b></td>
	$html.="<tr><td style='vertical-align:top;{$style}'><b>{$row['id']}</b></td>
	<td style='vertical-align:top;'>".date("d.m.Y", strtotime($row['created']))."<br>
	".(($row['paid']==1) ? "оплачен" : "")."
	</td>
	<td style='vertical-align:top;'>".(($row['user_id']!=-1) ? printUserInfo($row['user_id']) : "аноним")."</b>
	</td>
	<td style='vertical-align:top;'>".printRetailCart($row['id'])."</td>
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
	<tr><td>#</td><td>Дата</td><td>ФИО</td><td>Состав заказа</td></tr>";
	
	if ($result=mysql_query($sql)) {
		$html="<p>Всего заказов: <b>".mysql_num_rows($result)."</b></p>".$html;
		while($row = mysql_fetch_array($result)) {
			$html.=printRetail($row);
		}
	}
	$html.="</table>";
	//}
	return $html;
}
// ------------------------------------------------------------------------------------------------
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
// ------------------------------------------------------------------------------------------------
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
	//print_r($incoming);
	if (!isset($incoming['action'])) {
		$html=getRetailList($incoming);
	} else {
		switch ($incoming['action']) {
			default:
				$html=getRetailList($incoming);
				break;
		}
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Розничные продажи</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script>
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
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
?>
	<h1>Розничные продажи [<a href='/netcat/modules/netshop/interface/retail-addnew.php'>новая</a>]</h1>
	<h2><?php echo date("d.m.Y"); ?></h2>
	<form action="/netcat/modules/netshop/interface/retail-list.php" method="post" name="frm1" id="frm1">
	<fieldset style='border:0;'>
	<!--table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td align='right'>Заказ оплачен:
			<td><input type='checkbox' value='1' id='paid' name='paid' <?php echo (((isset($incoming['paid'])) && ($incoming['paid']==1)) ? "checked" : "");  ?>></td>
		</tr>
		<tr>
			<td align='right'>Заказ не оплачен:
			<td><input type='checkbox' value='1' id='unpaid' name='unpaid' <?php echo (((isset($incoming['unpaid'])) && ($incoming['unpaid']==1)) ? "checked" : "");  ?>></td>
		</tr>
		
		<tr>
		<td colspan='2'><input type='submit' value='Найти'></td>
		</tr>
	</table-->
	<!--tr>
			<td align='right'>№ заказа:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['mesid'])) ? $incoming['mesid'] : "");  ?>' name='mesid' id='mesid'></td>
		</tr><tr>
			<td align='right'>Заказчик:</td>
			<td><input type='text' value='<?php echo ((isset($incoming['contactname'])) ? $incoming['contactname'] : "");  ?>' name='contactname' id='contactname'></td>
		</tr-->
	</fieldset>
	</form>
	<?php echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

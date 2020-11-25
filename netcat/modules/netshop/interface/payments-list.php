<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function savePayment($incoming) {
	$res="";
	/*$sql="INSERT INTO Payments (supplier_id,summa,created)
	VALUES ({$incoming['supplier']},{$incoming['summa']},'".date("Y-m-d 00:00:00",strtotime($incoming['created']))."') ";
	if (!mysql_query($sql)) {
		die($sql." Error: ".mysql_error());
	}*/
	return $res;
}
function showPaymentsList($incoming=array()) {
	$html="";
	/*$sql="SELECT Payments.*,Classificator_Supplier.Supplier_Name FROM Payments 
		INNER JOIN Classificator_Supplier ON (Classificator_Supplier.Supplier_ID=Payments.supplier_id)
		WHERE Payments.supplier_id=".intval($incoming['supplier']);*/
	$sql="SELECT * FROM Payments 
		WHERE supplier_id=".intval($incoming['supplier'])." ORDER BY created DESC";
	//echo $sql."<br>";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
		<td><b>Дата</b></td>
		<!--td><b>Сумма продаж</b></td>
		<td><b>Сумма остатка</b></td-->
		<td><b>Сумма оплаты</b></td>
		<td><b>б/н</b></td>
		<td>&nbsp;</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			//print_r($row);
			$html.="<tr><td>".date("d.m.Y",strtotime($row['created']))."</td>";
			/*$sql1="SELECT * FROM Stats_Goods WHERE supplier_id=".intval($incoming['supplier'])." AND created LIKE '%".date("Y-m",strtotime($row['created']))."%'";
			if ($result1=mysql_query($sql1)) {
				while($row1 = mysql_fetch_array($result1)) {
					$html.="<td>{$row1['sold_pricez']}</td>";
					$html.="<td>{$row1['residue_pricez']}</td>";
				}
			} else {
				$html.="<td>&nbsp;</td><td>&nbsp;</td>";
			}*/
			$html.="<td>".$row['summa']."</td>";
			$html.="<td>".(($row['bnal']) ? "<img src='/images/icons/ok.png'>" : "&nbsp;")."</td>";
			$html.="<td><a href='/netcat/modules/netshop/interface/payments.php?n={$row['id']}'><img src='/images/icons/edit.png'></a></td>";
			$html.="</tr>";
		}
	} else {
		die(mysql_error());
	}
	$html.="</table>\n";
	return $html;
}
//=================================================================================================
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
	$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/trace/';
	$DownloadDir='/netcat_files/postfiles/trace/';
//	$DownloadDirLink='/netcat_files/postfiles/';
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
	//switch ($incoming['action']) {
		//case "show":
			$html.=showPaymentsList($incoming);
			//break;
		//default:
			
			//break;
	//}
	
	
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Оплата поставщику.</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<link rel="stylesheet" href="style.css" type="text/css" media="print, projection, screen" />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery.tablesorter.js"></script> 
</head>

<body>
<script type="text/javascript">
$(document).ready(function() 
    { 
        $("#myTable").tablesorter(); 
    } 
); 
</script>
	<?php
		echo printMenu();
	?>
	<h1>Оплата поставщику.</h1>
	<p><a href="/netcat/modules/netshop/interface/payments.php<? echo ((isset($incoming['supplier'])) ? "?supplier=".$incoming['supplier'] : ""); ?>"><b>Добавить оплату</b></a></p>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/payments-list.php" method="post">
	<input type="hidden" name="action" id="action" value="show">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td>Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td></tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>

<?php 
if ($show) {
?>
	
<?php
}
 echo $html; ?>
<link type="text/css" href="/css/latest.css" rel="Stylesheet" />
<script type="text/javascript" src="/js/ui.datepicker.js"></script>
<script>
$(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 0// отключаем эффект появления
});

</script>

</body>
</html>
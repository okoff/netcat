<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function savePaymentNew($incoming) {
	$res="";
	$sql="INSERT INTO Payments (supplier_id,summa,bnal,created)
	VALUES ({$incoming['supplier']},{$incoming['summa']},".((isset($incoming['bnal'])) ? "1" : "0").",'".date("Y-m-d 00:00:00",strtotime($incoming['created']))."') ";
	if (!mysql_query($sql)) {
		die($sql." Error: ".mysql_error());
	}
	return $res;
}
function savePayment($incoming) {
	$res="";
	$sql="UPDATE Payments SET 
						supplier_id=".intval($incoming['supplier']).",
						summa={$incoming['summa']},
						bnal=".((isset($incoming['bnal'])) ? "1" : "0").",
						created='".date("Y-m-d 00:00:00",strtotime($incoming['created']))."'
						WHERE id=".intval($incoming['n']);
	//echo $sql;
	//VALUES ({$incoming['supplier']},{$incoming['summa']},'".date("Y-m-d 00:00:00",strtotime($incoming['created']))."') ";
	if (!mysql_query($sql)) {
		die($sql." Error: ".mysql_error());
	}
	return $res;
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
	$action="";
	$n=$incoming['n'];
	//$created=$summa="";
	//print_r($incoming);
	switch ($incoming['action']) {
		case "add":
			$html.=savePaymentNew($incoming);
			$html.="<p>Данные сохранены.</p>
			<p><a href='/netcat/modules/netshop/interface/payments.php?supplier={$incoming['supplier']}'>Добавить новую оплату.</a>
			<p><a href='/netcat/modules/netshop/interface/payments-list.php?supplier={$incoming['supplier']}'>Перейти в просмотр оплат.</a>";
			$show=0;
			break;
		case "edit":
			$html.=savePayment($incoming);
			$html.="<p>Данные сохранены.</p>
			<p><a href='/netcat/modules/netshop/interface/payments.php?supplier={$incoming['supplier']}'>Добавить новую оплату.</a>
			<p><a href='/netcat/modules/netshop/interface/payments-list.php?supplier={$incoming['supplier']}'>Перейти в просмотр оплат.</a>";
			$show=0;
			break;
		default:
			//$html.=showListFiles();
			$action="add";
			if ((isset($incoming['n']))&&($incoming['n']>0)) {
				$sql="SELECT * FROM Payments WHERE id=".intval($incoming['n']);
				if ($result=mysql_query($sql)) {
					if($row = mysql_fetch_array($result)) {
						$incoming['supplier']=$row['supplier_id'];
						$incoming['created']=$row['created'];
						$incoming['summa']=$row['summa'];
						$incoming['bnal']=$row['bnal'];
					}
				}
				$action="edit";
			}
			break;
	}
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
<?php 
if ($show) {
	//print_r($incoming);
?>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/payments.php" method="post">
	<input type="hidden" name="action" id="action" value="<?php echo $action; ?>">
	<input type="hidden" name="n" id="n" value="<?php echo $n; ?>">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td>Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td></tr>
		<tr><td>Дата оплаты:</td>
			<td><input name="created" type="text" value="<?php echo isset($incoming['created']) ? date("d.m.Y", strtotime($incoming['created'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</td></tr>
		<tr><td>Сумма:</td>
			<td><input name="summa" type="text" value="<?php echo (isset($incoming['summa']) ? $incoming['summa'] : ""); ?>"></td>
		</td></tr>
		<tr><td>б/нал:</td>
			<td><input name="bnal" value="1" type="checkbox" <?php echo (((isset($incoming['bnal']))&&($incoming['bnal']==1)) ? "checked" : ""); ?>></td>
		</td></tr>
		<tr><td colspan="2"><input type="submit" value="Сохранить"></td></tr>
	</table>
	</form>
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
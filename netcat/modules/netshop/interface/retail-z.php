<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");
	
function showListFiles() {
	$res="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Netshop_PostHistoryFiles ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<tr><td>".$row["id"]."</td>
		<td>".date("d.m.Y", strtotime($row["created"]))."</td>
		<td>{$row['name']}</td>
		<td>".(($row['checked']==1) ? "<a href='/netcat/modules/netshop/interface/order-post-history.php?action=read&fid={$row['id']}'>результат</a>" : "<a href='/netcat/modules/netshop/interface/order-post-history.php?action=process&fid={$row['id']}&f={$row['name']}'>начать обработку файла</a>")."</td>
		</tr>";
	}
	$res.="</table>";
	return $res;
}		
function showRetailZList($incoming) {
	$res="";
	//print_r($incoming);
	$strw="";
	$dte=date("d.m.Y");
	//$dte="19.05.2015";
	$sql="SELECT * FROM Retails 
			WHERE created BETWEEN '".date("Y-m-d 00:00:00", strtotime($dte))."' AND '".date("Y-m-d 23:59:59", strtotime($dte))."' 
			ORDER BY id DESC";
			
	$result=mysql_query($sql);
	//echo $sql;
	$res.="<h3>".date("d.m.Y",strtotime($dte))."</h3>";
	/*$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td><b>#</b></td><td><b>Дата</b></td><td><b>Оплачено</b></td><td><b>Наличные</b></td><td><b>К.</b></td>
		<td><b>Закрыт</b></td><td><b>Сумма</b></td><td><b>Состав заказа</b></td>
		</tr>";*/
	$allnum=0;//mysql_num_rows($result);
	$j=0; //mysql_num_rows($result);
	$jbeznal=$jnal=0;
	$itog=$beznal=$nal=0;
	$show=1;
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>#</td><td>сумма без нал.</td><td>сумма нал. чеков</td><td>Общая сумма</td></tr>";
	while ($row = mysql_fetch_array($result)) {
		// безнал, касса, наличные с безналом 
		if (($row['cash']==0)||($row['fixed']==1)) {
			
			/*$res.="<tr><td><a href='/netcat/modules/netshop/interface/retail-edit.php?id={$row['id']}&start=1'><b>{$j}</b> [{$row['id']}]</a></td>
			<td>".date("d.m.Y ", strtotime($row['created']))."</td>
			<td>".(($row['paid']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td>".(($row['cash']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td>".(($row['fixed']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td>".(($row['closed']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td style='text-align:right;'>".($row['summ']-$row['summ1'])."</td>
			<td>".printRetailCart($row['id'])."</td>
			<!--td>".($row['edited'] ? date("d.m.Y", strtotime($row['edited'])) : "&nbsp;")."</td>
			<td>".($row['order_id'] ? "<a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['order_id']}'>{$row['order_id']}</a>" : "&nbsp;")."</td-->
			</tr>";*/
			(!$row['cash']) ? $beznal=$beznal+$row['summ']-$row['summ1'] : $nal=$nal+$row['summ'];
			(!$row['cash']) ? $jbeznal=$jbeznal+1 : $jnal=$jnal+1;
			$res.="<tr><td>{$j} <!--{$row['cash']} {$row['summ']} {$row['summ1']}--></td>
				<td>".((!$row['cash']) ? ($row['summ']-$row['summ1']) : "-")."</td>
				<td>".((($row['cash']!=0)||($row['summ1']!=0)) ? (($row['summ1']!=0) ? $row['summ1'] : $row['summ']) : "-")."</td>
				<td>".($row['summ'])."</td></tr>"; //-$row['summ1']
			//$itog=$itog+$row['summ']-$row['summ1'];
			//$itog1=$itog1+$row['summ1'];
			$j=$j+1;
		}
	}
	/*$res.="<tr><td colspan='6'><b>Итого:</b></td>
		<td style='text-align:right;'><b>".str_replace(","," ",number_format($itog))."</b></td>
		<td>&nbsp;</td></tr>";*/
	
	$res.="<tr><td>&nbsp;</td><td><b>{$beznal}</b></td><td><b>{$nal}</b></td><td><b>".($beznal+$nal)."</b></td></tr>";
	$res.="</table>";
	$res.="<p>Всего заказов: {$j}; по безналу {$jbeznal}</p>";
	return $res;
}
//=================================================================================================
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
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
	$html.=showRetailZList($incoming);

?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Розничные продажи</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>

<body>
	<?php
		echo printMenu();
	?>
	<h1>Розничные продажи. Z-отчет</h1>
	
<?php echo $html; ?>

</body>
</html>
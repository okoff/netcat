<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function showCost($incoming=array()) {
	$html="";
	$ar_dlv=array();
	$ar_dlvcost=array();
	$ar_dlvonline=array();
	$ar_dlvcostonline=array();
	$ar_dlvorderscost=array();
	$ar_pmt=array();
	for ($i=0;$i<10;$i++) {
		$ar_dlv[$i]=0;
		$ar_dlvcost[$i]=0;
		$ar_dlvorderscost[$i]=0;
	}
	for ($i=0;$i<15;$i++) {
		$ar_pmt[$i]=0;
	}
	//$dte=strtotime("2016-01-01");
	
	$dte=((isset($incoming['min']))&&($incoming['min']!="")) ? strtotime($incoming['min']) : strtotime("2016-01-01");
	$dte1=((isset($incoming['max']))&&($incoming['max']!="")) ? strtotime($incoming['max']) : strtotime("Y-m-d");
	$line="";
	$jdte=0;
	while ($dte<$dte1) {
	//while ($jdte<3) {
		$dtenext = mktime(0, 0, 0, date("m",$dte)+1, date("01"),   date("Y",$dte));
		$sql="SELECT Message_ID,DeliveryMethod,DeliveryCost,PaymentMethod FROM Message51 WHERE
			Created BETWEEN '".date("Y-m-d 00:00:00",$dte)."' AND '".date("Y-m-d 00:00:00",$dtenext)."'
			ORDER BY Message_ID ASC";
		//echo $sql."<br>";
		$ordersum=0;
		$num=$num0=0;
		$fullcost=0;
		$num1=$num2=$num3=0;
		$fullcost1=$fullcost2=$fullcost3=0;
		$html1="";
		$online=0;
		$onlinesum=0;
		for ($i=0;$i<10;$i++) {
			$ar_dlv[$i]=0;
			$ar_dlvcost[$i]=0;
			$ar_dlvorderscost[$i]=0;
		}
		for ($i=0;$i<15;$i++) {
			$ar_pmt[$i]=0;
		}
		if ($result=mysql_query($sql)) {
			while($row=mysql_fetch_array($result)) {
				$ar_dlv[$row['DeliveryMethod']]=$ar_dlv[$row['DeliveryMethod']]+1;
				$ar_dlvcost[$row['DeliveryMethod']]=$ar_dlvcost[$row['DeliveryMethod']] + $row['DeliveryCost'];
				
				$ar_pmt[$row['PaymentMethod']]=$ar_pmt[$row['PaymentMethod']]+1;
				
				
				$ordersum=0;
				$sql1="SELECT * FROM Netshop_OrderGoods WHERE Order_ID=".$row['Message_ID'];
				if ($result1=mysql_query($sql1)) {
					while($row1=mysql_fetch_array($result1)) {
						$ordersum=$ordersum+$row1['ItemPrice']*$row1['Qty'];
					}
				}
				$ar_dlvorderscost[$row['DeliveryMethod']]=$ar_dlvorderscost[$row['DeliveryMethod']] + $ordersum;
				if ($row['PaymentMethod']==1) {
					$ar_dlvonline[$row['DeliveryMethod']]=$ar_dlvonline[$row['DeliveryMethod']]+1;
					$ar_dlvcostonline[$row['DeliveryMethod']]=$ar_dlvcostonline[$row['DeliveryMethod']]+$ordersum;
				}
				
			}
		}
		//print_r($ar_dlv);
		//echo "<br><br>";
		//print_r($ar_pmt);
		$j=0;
		/*$html.="<h3>Способ оплаты</h3>";
		$html.="<table cellpadding='2' cellspacing='0' border='1'>
			<tr><td>Способ оплаты</td><td>Кол. заказов</td></tr>";
		$sql="SELECT * FROM Message55 WHERE Checked=1 ORDER BY Message_ID ASC";
		if ($result=mysql_query($sql)) {
			while($row=mysql_fetch_array($result)) {
				$j=$row['Message_ID'];
				$html.="<tr><td>".$row['Name']."</td><td>".$ar_pmt[$j]."</td></tr>";
			}
		}
		$html.="</table>";*/
		/*$html.="<br><br><h3>Способ доставки</h3>";
		$html.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td>Способ доставки</td>
			<td>Кол. заказов</td>
			<td>Заказы, руб.</td>
			<td>Доставка, руб.</td>
			<td>Заказы c оплатой онлайн</td>
			<td>Стоимость онлайн, руб.</td>
		</tr>";*/
		$sql="SELECT * FROM Message55  WHERE Checked=1 ORDER BY Message_ID ASC";
		$tblhdr="['Месяц',";	
		$line.="['".date("m.Y",$dte)."',"; 
		if ($result=mysql_query($sql)) {
			while($row=mysql_fetch_array($result)) {
				$j=$row['Message_ID'];
				$tblhdr.="'".$row['Name']."',";
				/*$html.="<tr><td>".$row['Name']."</td>
					<td style='text-align:right;'>".$ar_dlv[$j]."</td>
					<td style='text-align:right;'>".$ar_dlvorderscost[$j]."</td>
					<td style='text-align:right;'>".$ar_dlvcost[$j]."</td>
					<td style='text-align:right;'>".$ar_dlvonline[$j]."</td>
					<td style='text-align:right;'>".$ar_dlvcostonline[$j]."</td>
					</tr>";*/
				$line.=$ar_pmt[$j].","; // ]";
			}
		}
		$tblhdr.="]";
		$line.="],\n";
		//$html.="</table>";
		
		$dte=$dtenext;
		//$jdte=$jdte+1;
	}
	
	$html.="<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
    <script type=\"text/javascript\">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ".$tblhdr.",
          ".$line."
        ]);

        var options = {
          title: 'Способы оплаты',
          curveType: 'function',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>
	<div id=\"curve_chart\" style=\"width: 900px; height: 500px\"></div>";
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
	switch ($incoming['action']) {
		case "show":
			$html.=showCost($incoming);
			break;
		default:
			$html.=showCost($incoming);
			$incoming['month']=date("m");
			$incoming['year']=date("Y");
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Стоимость доставки почтой.</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<link rel="stylesheet" href="style.css" type="text/css" media="print, projection, screen" />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>

<body>

	<?php
		echo printMenu();
	?>
	<h1>Статистика по способам доставки и оплаты.</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-dpmethods-gra1.php" method="post">
	<input type="hidden" name="action" id="action" value="show">
		<table cellpadding="0" cellspacing="5" border="0"><tr>
				<td>с</td>
				<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
				<td>по</td>
				<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</tr>
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
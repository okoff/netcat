<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
include_once ("utils-statistic.php");

	
function showStatisticListFull($incoming,$pr) {
	$pr=str_replace("[","",$pr);
	$prarr=explode("],",$pr);
	//print_r($prarr);
	$i=0;
	$dte=array();
	$wb=array();
	$wbr=array();
	$payments=array();
	foreach ($prarr as $tmp) {
		$data=explode(",",$tmp);
		$dte[$i]=str_replace("'","",$data[0]);
		$wb[$i]=$data[1];
		$payments[$i]=$data[2];
		$wbr[$i]=$data[3];
		$i=$i+1;
	}
	//print_r($dte);
	$res="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold'>
			<td rowspan='2' style='text-align:center;'>Дата</td>
			<td colspan='3' style='text-align:center;'>Продано</td>
			<td colspan='3' style='text-align:center;'>На складе</td>
			<td rowspan='2' style='text-align:center;'>Накладные прихода</td>
			<td rowspan='2' style='text-align:center;'>Оплаты</td>
			<td rowspan='2' style='text-align:center;'>Возвраты</td>
		</tr>
		<tr style='font-weight:bold'>
			<td>Кол.</td>
			<td>Сумма</td>
			<td>Сумма закуп.</td>
			<td>Кол.</td>
			<td>Сумма</td>
			<td>Сумма закуп.</td>
		</tr>";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']}
		AND created BETWEEN '2015-01-01 00:00:00' AND '".date("Y-m-d H:i:s")."'
	ORDER BY created ASC";
	$sold_count=0;
	$sold_price=0;
	$sold_pricez=0;
	$iwb=0;
	$ipayments=0;
	$iwbr=0;
		//echo $sql."<br>";
	$rs=mysql_query($sql);
	while ($row=mysql_fetch_array($rs)) {
		$res.="<tr><td>".date("m.Y", strtotime($row['created']))."</td>
			<td style='text-align:right;' nowrap>".number_format($row['sold_count'],0,","," ")."</td>
			<td style='text-align:right;' nowrap>".number_format($row['sold_price'],0,","," ")."</td>			
			<td style='text-align:right;' nowrap>".number_format($row['sold_pricez'],0,","," ")."</td>			
			<td style='text-align:right;' nowrap>".number_format($row['residue_count'],0,","," ")."</td>
			<td style='text-align:right;' nowrap>".number_format($row['residue_price'],0,","," ")."</td>
			<td style='text-align:right;' nowrap>".number_format($row['residue_pricez'],0,","," ")."</td>";
		$restmp="";
		for ($j=1;$j<(count($dte));$j++) {
			//echo date("m.Y", strtotime($row['created']))."|".$dte[$j]."<br>";
			if (date("m.Y", strtotime($row['created']))==$dte[$j]) {
				$restmp.="<td style='text-align:right;'>".number_format($wb[$j],0,","," ")."</td>";
				$restmp.="<td style='text-align:right;'>".number_format($payments[$j],0,","," ")."</td>";
				$restmp.="<td style='text-align:right;'>".number_format($wbr[$j],0,","," ")."</td>";
				$iwb=$iwb+$wb[$j];
				$ipayments=$ipayments+$payments[$j];
				$iwbr=$iwbr+$wbr[$j];
			}
		}
		$res.=($restmp!="") ? $restmp : "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		$res.="</tr>";
		$sold_count=$sold_count+$row['sold_count'];
		$sold_price=$sold_price+$row['sold_price'];
		$sold_pricez=$sold_pricez+$row['sold_pricez'];
		
	}
	$res.="<tr><td>---</td>
			<td style='text-align:right;' nowrap><b>".number_format($sold_count,0,","," ")."</b></td>
			<td style='text-align:right;' nowrap><b>".number_format($sold_price,0,","," ")."</b></td>			
			<td style='text-align:right;' nowrap><b>".number_format($sold_pricez,0,","," ")."</b></td>			
			<td style='text-align:right;' nowrap>---</td>
			<td style='text-align:right;' nowrap>---</td>
			<td style='text-align:right;' nowrap>---</td>
			<td style='text-align:right;'><b>".number_format($iwb,0,","," ")."</b></td>
			<td style='text-align:right;'><b>".number_format($ipayments,0,","," ")."</b></td>
			<td style='text-align:right;'><b>".number_format($iwbr,0,","," ")."</b></td>
			</tr>";
	$res.="</table>";
	//$res="['дата','накладные','оплаты','возвраты'],";
	
//	echo $res;
	return $res;
}
function graphCount($incoming) {
	$res="";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']} ORDER BY created ASC";
	$res="['дата','продано','остаток на складе'],";
	$rs=mysql_query($sql);
	while ($row=mysql_fetch_array($rs)) {
		
		$res.="['".date("m.Y", strtotime($row['created']))."', {$row['sold_count']}, {$row['residue_count']}],\n";
		
	}

	//echo $res;
	return $res;
}
function graphPrice($incoming) {
	$res="";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']} ORDER BY created ASC";
	$res="['дата','сумма реализации','сумма реализации закуп.','оплаты'],";
	$rs=mysql_query($sql);
	while ($row=mysql_fetch_array($rs)) {
		$sql1="SELECT * FROM Payments 
			WHERE supplier_id={$incoming['supplier']} 
				AND created BETWEEN '".date("Y-m-01", strtotime($row['created']))."' AND '".date("Y-m-t", strtotime($row['created']))."'
			ORDER BY created ASC";
		//echo $sql1."<br>";
		$payments=0;
		$rs1=mysql_query($sql1);
		while ($row1=mysql_fetch_array($rs1)) {
			$payments=$payments+$row1['summa'];
		}
		$res.="['".date("m.Y", strtotime($row['created']))."', {$row['sold_price']}, {$row['sold_pricez']}, {$payments}],";
		
	}
	//echo $res;
	return $res;
}
function graphResidue($incoming) {
	$res="";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']} ORDER BY created ASC";
	$res="['дата','сумма остатка','сумма остатка закуп.'],";
	$rs=mysql_query($sql);
	while ($row=mysql_fetch_array($rs)) {
		$res.="['".date("m.Y", strtotime($row['created']))."', {$row['residue_price']}, {$row['residue_pricez']}],";
		
	}
	//echo $res;
	return $res;
}
function graphPayments($incoming) {
	$res="";
	$dte="01.01.2013";
	$now=date("d.m.Y");
	$res="['дата','накладные','оплаты','возвраты'],";
	//echo strtotime($dte)."<br>";
	//echo strtotime($now)."<br>";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']} ORDER BY created ASC LIMIT 1";
	$rs=mysql_query($sql);
	while ($row=mysql_fetch_array($rs)) {
		$dte=date("d.m.Y",strtotime($row['created']));
	}
	while (strtotime($dte)<strtotime($now)) {
		$sql1="SELECT * FROM Payments 
			WHERE supplier_id={$incoming['supplier']} 
				AND created BETWEEN '".date("Y-m-01", strtotime($dte))."' AND '".date("Y-m-t", strtotime($dte))."'
			ORDER BY created ASC";
		//echo $sql1."<br>";
		$payments=0;
		$rs1=mysql_query($sql1);
		while ($row1=mysql_fetch_array($rs1)) {
			$payments=$payments+$row1['summa'];
		}
		
		$sql2="SELECT * FROM Waybills 
			WHERE vendor_id={$incoming['supplier']} AND type_id=2 AND status_id=2 
				AND created BETWEEN '".date("Y-m-01", strtotime($dte))."' AND '".date("Y-m-t", strtotime($dte))."'
			ORDER BY created ASC";
		//echo $sql2."<br>";
		$wb=0;
		$rs2=mysql_query($sql2);
		while ($row2=mysql_fetch_array($rs2)) {
			$wb=$wb+$row2['summoriginal'];
		}
		
		$sql3="SELECT * FROM Waybills 
			WHERE vendor_id={$incoming['supplier']} AND (type_id=7 OR type_id=5) AND status_id=2 
				AND created BETWEEN '".date("Y-m-01", strtotime($dte))."' AND '".date("Y-m-t", strtotime($dte))."'
			ORDER BY created ASC";
		//echo $sql2."<br>";
		$wbr=0;
		$rs3=mysql_query($sql3);
		while ($row3=mysql_fetch_array($rs3)) {
			$wbr=$wbr+$row3['summoriginal'];
		}
		
		$dte=date(("d.m.Y"),strtotime("+1 month",strtotime($dte)));
		$res.="['".date("m.Y", strtotime($dte))."', {$wb}, {$payments}, {$wbr}],";
	}

	return $res;
}
function graphFull($incoming) {
	$res="";
	$dte="01.01.2013";
	$now=date("d.m.Y");
	$res="['дата','накладные','оплаты','возвраты','реализация','остатки'],";
	//echo strtotime($dte)."<br>";
	//echo strtotime($now)."<br>";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']} ORDER BY created ASC LIMIT 1";
	$rs=mysql_query($sql);
	while ($row=mysql_fetch_array($rs)) {
		$dte=date("d.m.Y",strtotime($row['created']));
	}
	while (strtotime($dte)<strtotime($now)) {
		$sql1="SELECT * FROM Payments 
			WHERE supplier_id={$incoming['supplier']} 
				AND created BETWEEN '".date("Y-m-01", strtotime($dte))."' AND '".date("Y-m-t", strtotime($dte))."'
			ORDER BY created ASC";
		//echo $sql1."<br>";
		$payments=0;
		$rs1=mysql_query($sql1);
		while ($row1=mysql_fetch_array($rs1)) {
			$payments=$payments+$row1['summa'];
		}
		
		$sql2="SELECT * FROM Waybills 
			WHERE vendor_id={$incoming['supplier']} AND type_id=2 AND status_id=2 
				AND created BETWEEN '".date("Y-m-01 00:00:00", strtotime($dte))."' AND '".date("Y-m-t 23:59:59", strtotime($dte))."'
			ORDER BY created ASC";
		//echo $sql2."<br>";
		$wb=0;
		$rs2=mysql_query($sql2);
		while ($row2=mysql_fetch_array($rs2)) {
			$wb=$wb+$row2['summoriginal'];
		}
		
		$sql3="SELECT * FROM Waybills 
			WHERE vendor_id={$incoming['supplier']} AND (type_id=7 OR type_id=5) AND status_id=2 
				AND created BETWEEN '".date("Y-m-01 00:00:00", strtotime($dte))."' AND '".date("Y-m-t 23:59:59", strtotime($dte))."'
			ORDER BY created ASC";
		//echo $sql2."<br>";
		$wbr=0;
		$rs3=mysql_query($sql3);
		while ($row3=mysql_fetch_array($rs3)) {
			$wbr=$wbr+$row3['summoriginal'];
		}
		// реализация, остатки 
		$real=0;
		$sql4="SELECT * FROM Stats_Goods 
			WHERE supplier_id={$incoming['supplier']} AND 
				created BETWEEN '".date("Y-m-01 00:00:00", strtotime($dte))."' AND '".date("Y-m-t 23:59:59", strtotime($dte))."' 
			ORDER BY created ASC";
		//echo $sql4."<br>";
		//$res="['дата','сумма реализации','сумма реализации закуп.','оплаты'],";
		$rs4=mysql_query($sql4);
		while ($row4=mysql_fetch_array($rs4)) {
			$real=$row4['sold_pricez'];
			//$res.="['".date("m.Y", strtotime($row['created']))."', {$row['sold_price']}, {$row['sold_pricez']}, {$payments}],";
			// остатки 
			$rest=$row4['residue_pricez'];
			
		}
		
		
		$res.="['".date("m.Y", strtotime($dte))."', {$wb}, {$payments}, {$wbr},{$real},{$rest}],";
		$dte=date(("d.m.Y"),strtotime("+1 month",strtotime($dte)));
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
	//print_r($incoming);
	switch ($incoming['action']) {
		
		case "filter":
			$html.=$incoming['supplier'];
			$gra=graphCount($incoming);
			$gra1=graphPrice($incoming);
			$gra2=graphResidue($incoming);
			$gra3=graphPayments($incoming);
			$gra4=graphFull($incoming);
			
			$html.="<table cellpadding='0' cellspacing='0' border='0'>
	<tr><td style='vertical-align:top;'>

	<script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          {$gra}
        ]);
		var data1 = google.visualization.arrayToDataTable([
          {$gra1}
        ]);
		var data2 = google.visualization.arrayToDataTable([
          {$gra2}
        ]);
		var data3 = google.visualization.arrayToDataTable([
          {$gra3}
        ]);
		var data4 = google.visualization.arrayToDataTable([
          {$gra4}
        ]);

        var options = {
          title: 'Количество продано, остатки на складе'
        };
		var options1 = {
          title: 'Сумма реализации, оплаты'
        };
		var options2 = {
          title: 'Сумма остатков'
        };
		var options3 = {
          title: 'Накладные и оплаты'
        };
		var options4 = {
          title: 'Сводный график'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
		
        var chart1 = new google.visualization.LineChart(document.getElementById('chart1_div'));
        chart1.draw(data1, options1);
		
        var chart2 = new google.visualization.LineChart(document.getElementById('chart2_div'));
        chart2.draw(data2, options2);
		
		var chart3 = new google.visualization.LineChart(document.getElementById('chart3_div'));
        chart3.draw(data3, options3);
		
		var chart4 = new google.visualization.LineChart(document.getElementById('chart4_div'));
        chart4.draw(data4, options4);
      }
    </script>
    <div id=\"chart_div\" style=\"width: 900px; height: 500px;\"></div>
    <div id=\"chart1_div\" style=\"width: 900px; height: 500px;\"></div>
    <div id=\"chart2_div\" style=\"width: 900px; height: 500px;\"></div>
    <div id=\"chart3_div\" style=\"width: 900px; height: 500px;\"></div>
    <div id=\"chart4_div\" style=\"width: 900px; height: 500px;\"></div>";
				$html.="<td><td style='vertical-align:top;'>";
				$html.=showStatisticListFull($incoming,$gra3);
				$html.="</td></tr></table>";
			break;
		default:
			//$html.=showListFiles();
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Статистика по поставщикам</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<!--script language="Javascript" type="text/javascript" src="/js/jquery.js"></script-->
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>

</head>

<body>
	<?php
		echo printMenu();
	?>
	<h1>Статистика по поставщику. Сводная.</h1>
	<p><a href="/netcat/modules/netshop/interface/statistic-vendor4.php">Общая по всем поставщикам за весь период</a></p>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-vendor2.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr>
			<td style="text-align:right;">Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td>
		</tr>
		<tr><td colspan="3"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>
<?php echo $html; ?>
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
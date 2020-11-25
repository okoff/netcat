<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
include_once ("utils-statistic.php");

/*	
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
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$incoming['supplier']} ORDER BY created ASC";
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
			}
		}
		$res.=($restmp!="") ? $restmp : "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		$res.="</tr>";
		
	}
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
}*/
function graphFull() {
	$res="";
	$dte="01.01.2013";
	$now=date("d.m.Y");
	$res="['дата','накладные','оплаты','возвраты','реализация','остатки'],";
	//echo strtotime($dte)."<br>";
	//echo strtotime($now)."<br>";
	
	while (strtotime($dte)<strtotime($now)) {
		$wb=$payments=$wbr=$real=$rest=0;
		$sqlw="SELECT * FROM Classificator_Supplier ORDER BY Supplier_ID ASC";
		$rsw=mysql_query($sqlw);
		while ($roww=mysql_fetch_array($rsw)) {
			//$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$roww['Supplier_ID']} ORDER BY created ASC LIMIT 1";
			//$rs=mysql_query($sql);
			//while ($row=mysql_fetch_array($rs)) {
			//	$dte=date("d.m.Y",strtotime($row['created']));
			//}
		
			$sql1="SELECT * FROM Payments 
				WHERE supplier_id={$roww['Supplier_ID']} 
					AND created BETWEEN '".date("Y-m-01", strtotime($dte))."' AND '".date("Y-m-t", strtotime($dte))."'
				ORDER BY created ASC";
			//echo $sql1."<br>";
			
			$rs1=mysql_query($sql1);
			while ($row1=mysql_fetch_array($rs1)) {
				$payments=$payments+$row1['summa'];
			}
			
			$sql2="SELECT * FROM Waybills 
				WHERE vendor_id={$roww['Supplier_ID']} AND type_id=2 AND status_id=2 
					AND created BETWEEN '".date("Y-m-01 00:00:00", strtotime($dte))."' AND '".date("Y-m-t 23:59:59", strtotime($dte))."'
				ORDER BY created ASC";
			//echo $sql2."<br>";
			$rs2=mysql_query($sql2);
			while ($row2=mysql_fetch_array($rs2)) {
				$wb=$wb+$row2['summoriginal'];
			}
			
			$sql3="SELECT * FROM Waybills 
				WHERE vendor_id={$roww['Supplier_ID']} AND (type_id=7 OR type_id=5) AND status_id=2 
					AND created BETWEEN '".date("Y-m-01 00:00:00", strtotime($dte))."' AND '".date("Y-m-t 23:59:59", strtotime($dte))."'
				ORDER BY created ASC";
			//echo $sql2."<br>";
			$rs3=mysql_query($sql3);
			while ($row3=mysql_fetch_array($rs3)) {
				$wbr=$wbr+$row3['summoriginal'];
			}
			
			// реализация, остатки 
			$sql4="SELECT * FROM Stats_Goods 
				WHERE supplier_id={$roww['Supplier_ID']} AND 
					created BETWEEN '".date("Y-m-01 00:00:00", strtotime($dte))."' AND '".date("Y-m-t 23:59:59", strtotime($dte))."' 
				ORDER BY created ASC";
			//echo $sql4."<br>";
			//$res="['дата','сумма реализации','сумма реализации закуп.','оплаты'],";
			$rs4=mysql_query($sql4);
			while ($row4=mysql_fetch_array($rs4)) {
				$real=$real+$row4['sold_pricez'];
				//$res.="['".date("m.Y", strtotime($row['created']))."', {$row['sold_price']}, {$row['sold_pricez']}, {$payments}],";
				// остатки 
				$rest=$rest+$row4['residue_pricez'];
				
			}
			
			
			
			
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
	
	$html="";
			

			$gra=graphFull();
			
			$html.="

	<script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
		var data = google.visualization.arrayToDataTable([
          {$gra}
        ]);


		var options = {
          title: 'Сводный график'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
		

      }
    </script>
    <div id=\"chart_div\" style=\"width: 1200px; height: 900px;\"></div>";
				

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
	<h1>Статистика по всем поставщикам за весь период</h1>
	<br clear='both'>
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
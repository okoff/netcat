<?php
// 19.10.2015 Elen
// обновление цен от поставщика
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-waybill.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pricelist/';

function processPriceList($f,$UploadDir) {
	$html="";
	$fname=$str="";
	//$html.=$f;
	
	$sql="SELECT * FROM Netshop_Suppricelist WHERE id=".intval($f);
	if ($result=mysql_query($sql)) {
		if($row = mysql_fetch_array($result)) {
			$fname=$row['fname'];
			$html.="<p>{$fname} от ".date("d.m.Y",strtotime($row['created']))."</p>";
		}
	} else {
		die(mysql_error());
	}
	
	$fname=$UploadDir.$fname;
	if (file_exists($fname)) {
		$fp = fopen($fname, "r");
		while (!feof($fp)) { 
			$str.=fread($fp, 1024); 
		} 
		fclose($fp);
	} else {
		die("No file ".$fname);
	}
	$str=str_replace(chr(160),"",$str);
	$str=str_replace(chr(63),"",$str);
	$rws=explode("\n",$str); // строки прайса
	//print_r($rws);
	for ($i=0;$i<count($rws);$i++) {
		$tmp=explode(";",$rws[$i]);
		if ($tmp[1]!="") {
			//echo $tmp[1]."<br>"; // артикул
			$sql1="SELECT Message_ID,Name,Price FROM Message57 WHERE vendor_itemid LIKE '".iconv("windows-1251","utf-8",quot_smart(trim($tmp[1])))."'";
			//echo $sql1."<br>";
			if ($result1=mysql_query($sql1)) {
				if($row1 = mysql_fetch_array($result1)) {
					$html.=$row1['Name']."<br>";
					$oldpr=explode(" ",$tmp[4]);
					$newpr=explode(" ",$tmp[5]);
					//echo ord(substr($tmp[4], 6, 1));
					$oldprice=str_replace(chr(160),"",trim($oldpr[0]));
					$newprice=str_replace(chr(160),"",trim($newpr[0]));
					//echo $oldprice."|".$newprice."<br>";
					// update price
					if ($row1['Price']==$oldprice) {
						$html.="Изменение стоимости с {$oldprice} на {$newprice}<br>";
						$sql2="UPDATE Message57 SET Price=".$newprice." WHERE Message_ID=".$row1['Message_ID'];
						//echo $sql2."<br>";
						if (!mysql_query($sql2)) {
							die($sql2."<br>Error: ".mysql_error());
						}
					}
					// save history
					$sql3="INSERT INTO Message57_upprices (item_id,file_id) VALUES (".$row1['Message_ID'].",".$f.")";
					if (!mysql_query($sql3)) {
						die($sql3."<br>Error: ".mysql_error());
					}
				}
			} else {
				die($sql1."<br>".mysql_error());
			}
		}
	}
	// update Netshop_Suppricelist
	$str=iconv("windows-1251","utf-8",$str);
	$sql="UPDATE Netshop_Suppricelist SET value='".quot_smart($str)."',processed=1 WHERE id=".intval($f);
	
	//$html.=$sql;
	if (!mysql_query($sql)) {
		die($sql."<br>Error: ".mysql_error());
	}
	return $html;
}

// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
//print_r($incoming);
$printfrm=1;
$html="";
switch ($incoming['action']) {
	case "upload":
		if ($incoming['request_method']=="post") {
			if (move_uploaded_file($_FILES['fname']['tmp_name'], $UploadDir.$_FILES['fname']['name'])) {
				// save uploaded file in DB
				$sql="INSERT INTO Netshop_Suppricelist (created,fname,supplier_id) VALUES ('".date("Y-m-d H:i:s")."','".$_FILES['fname']['name']."',".intval($incoming['supplier']).")";
				if (!mysql_query($sql)) {
					die($sql."<br>Error: ".mysql_error());
				}
				$fid=getLastInsertID("Netshop_Suppricelist","id");
				$html.="<p>Файл загружен</p>
				<p><a href='/netcat/modules/netshop/interface/price-upload.php?action=process&f={$fid}'>Начать обработку файла.</a></p>";
				
				$printfrm=0;
			} else {
				$html.="<p>Файл не загружен!</p>";
			}
		}
	break;
	case "process":
		$html=processPriceList(intval($incoming['f']),$UploadDir);
		$printfrm=0;
	break;
	default:break;
}
//echo $html;
// ------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
	<title>Редактирование отпускной цены</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>
<body>
	
<?php
	echo printMenu();
	$err="";
?>
<h1>Редактирование отпускной цены</h1>
<?php 
if ($printfrm) {
?>
<form method="post" enctype='multipart/form-data' name="frm1" id="frm1" action="/netcat/modules/netshop/interface/price-upload.php">
<input type="hidden" name="action" id="action" value="upload">
<table cellpadding="3" cellspacing="0" border="1">
<tr>
	<td style="text-align:right;">Поставщик:</td>
	<td><?php echo selectSupplier($incoming); ?></td>
</tr>
<tr><td>Загрузить файл трассировки</td>
<td>	
	<input type='hidden' name='action' value='upload'>
	<input type='file' name='fname'>
	<input type='submit' value='Загрузить'>
</td></tr>
</table>
</form>
<?php 
}
echo $html;
?>

</body>
</html>
<?php

mysql_close($con);
?>

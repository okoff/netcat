<?php
include_once ("../../../../vars.inc.php");
session_start();
	include_once ("utils.php");
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
		case "upload":
			if ($incoming['request_method']=="post") {
				if (move_uploaded_file($_FILES['fname']['tmp_name'], $UploadDir.$_FILES['fname']['name'])) {
					$html.="<p>Файл загружен. <a href='/netcat/modules/netshop/interface/load-xml.php?action=process&f={$_FILES['fname']['name']}'>Начать обработку файла.</a></p>";
				
				} else {
					$html.="<p>Файл не загружен!</p>";
				}
			}
			break;
		case "process":
			if (isset($incoming['f'])) {
				$xmlfile="../../../..".$DownloadDir.$incoming['f'];
				//echo $xmlfile;
				$xml = simplexml_load_file($xmlfile);
				print_r($xml);
				foreach ($xml->Status as $status) {
					//print_r($status);
					echo $status->TrackNum."-".$status->StatusName." ".$status->StatusAttr." ".$status->StatusDate." ".$status->DestinAddr;
					echo "<br>";
				}
				//var_dump($xml);
				/*foreach($xml->children() as $key) {
					echo $key['title'] .'-'. $key['href'].'<br />';
				}*/
			}
			break;
		default:break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Трассировка отправлений</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>

<body>
	<h3>Загрузить файл трассировки</h3>
	<form enctype='multipart/form-data' action='/netcat/modules/netshop/interface/load-xml.php' method='post' name='frm1' id='frm1'>
		<input type='hidden' name='action' value='upload'>
		<input type='hidden' name='id' value='narv'>
		<input type='file' name='fname'>
		<input type='submit' value='Загрузить'>
	</form><br clear='both'>
	<?php echo $html; ?>
</body>
</html>
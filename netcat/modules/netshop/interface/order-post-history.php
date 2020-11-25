<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
	
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
function showListFromFile($fid) {
	$res="";
	$sql="SELECT * FROM Netshop_PostHistoryFiles WHERE id={$fid}";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<h2>".date("d.m.Y", strtotime($row["created"]))." {$row['name']}</h2>";
	}
	
	$res.="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Netshop_PostHistory WHERE file_id={$fid} ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<tr><!--td>".$row["id"]."</td-->
		<td>".date("d.m.Y", strtotime($row["created"]))."</td>
		<td>{$row['Order_ID']}</td>
		<td>{$row['status_id']}</td>
		<td>".(($row['status_id']!=8) ? "<b>{$row['status']}</b>" : $row['status'] )."</td>
		<td>{$row['address']}</td>
		</tr>";
	}
	$res.="</table>";
	return $res;
}	
	
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
					$sql="INSERT INTO Netshop_PostHistoryFiles (name, created, checked) 
								VALUES ('{$_FILES['fname']['name']}', '".date("Y-m-d")."', 0)";
					if (mysql_query($sql)) {
						$fileid=getLastInsertId("Netshop_PostHistoryFiles", "id");
						//echo $fileid;
						$html.="<p>Файл загружен. <a href='/netcat/modules/netshop/interface/order-post-history.php?action=process&fid={$fileid}&f={$_FILES['fname']['name']}'>Начать обработку файла.</a></p>";
					
					} else {
						die($sql."Ошибка: ".mysql_error());
					}
					
				} else {
					$html.="<p>Файл не загружен!</p>";
				}
			}
			break;
		case "process":
			//echo "process";
			if (isset($incoming['f'])) {
				//$xmlfile="../../../..".$DownloadDir.$incoming['f'];
				$xmlfile=$_SERVER['DOCUMENT_ROOT'].$DownloadDir.$incoming['f'];
				//echo $xmlfile."<br>";
				if (file_exists($xmlfile)) {
					$xml = simplexml_load_file($xmlfile);
				 
					//print_r($xml);
					foreach ($xml->Status as $status) {
						$html.="<hr>";
						$order_id=0;
						$html.="<b>".$status->TrackNum."</b> ".$status->StatusDate." - ".$status->StatusName." - ".$status->StatusAttr."  ".$status->DestinAddr."<br>";
						$sql="SELECT Message_ID FROM Message51 WHERE barcode LIKE '{$status->TrackNum}'";
						$result=mysql_query($sql);
						while ($row = mysql_fetch_array($result)) {
							$order_id=$row['Message_ID'];
						}
						//echo "<br>".$order_id."<br>";
						if ($order_id) {
							$sql="INSERT INTO Netshop_PostHistory (barcode, Order_ID, status_id, status, address, created, file_id) 
								VALUES ('{$status->TrackNum}', {$order_id}, {$status->StatusID}, '{$status->StatusName}. {$status->StatusAttr}',
								'{$status->DestinAddr}', '".date("Y-m-d",strtotime($status->StatusDate))."', {$incoming['fid']})";
							if (mysql_query($sql)) {
								$html.="Данные по заказу ".$order_id." сохранены. <br>";
							} else {
								die($sql."Ошибка: ".mysql_error());
							}
						} else {
							$html.="Заказ с таким баркодом не найден.";
						}
					}
				} else {
					exit("Не удалось открыть файл {$xmlfile}.");
				}
				$sql="INSERT INTO Netshop_PostHistoryFiles (checked) VALUES (1)";
				if (!mysql_query($sql)) {
					die($sql."Ошибка: ".mysql_error());
				}
			}
			break;
		case "read":
			$html.=showListFromFile($incoming['fid']);
			break;
		default:
			$html.=showListFiles();
			break;
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
	<?php
		echo printMenu();
	?>
	<h3>Загрузить файл трассировки</h3>
	<form enctype='multipart/form-data' action='/netcat/modules/netshop/interface/order-post-history.php' method='post' name='frm1' id='frm1'>
		<input type='hidden' name='action' value='upload'>
		<input type='hidden' name='id' value='narv'>
		<input type='file' name='fname'>
		<input type='submit' value='Загрузить'>
	</form><br clear='both'>
	<?php echo $html; ?>
</body>
</html>
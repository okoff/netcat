<?php
// 25.02.2014 Elen
// поиск и просмотр списка моделей и сертификатов
include_once ("../../../../vars.inc.php");

session_start();

function getModelsList($incoming) {
	$strwhere="";
	if ((isset($incoming['manufacturer']))&&($incoming['manufacturer']!="")) {
		$strwhere=" WHERE manufacturer_id=".intval($incoming['manufacturer']);
	}	
	$sql="SELECT *
		FROM Classificator_model {$strwhere}
		ORDER BY model_Name ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/models.php?id=".(getLastInsertID("Classificator_model","model_ID")+1)."&action=edit'>Добавить</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>Вкл.</td>
			<td>Вкл.#5</td>
			<td>#</td>
			<td>Название</td>
			<td>Производитель</td>
			<td>Сертификат #1</td>
			<td>Сертификат #2</td>
			<td>Сертификат #3</td>
			<td>Сертификат #4</td>
			<td>Превью</td>
			
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
			<td style='text-align:center;'>".(($row['Checked']==1) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
			<td style='text-align:center;'>".(($row['showrkfive']==1) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
			<td>{$row['model_ID']}</td>
			<td><a href='/netcat/modules/netshop/interface/models.php?id={$row['model_ID']}&action=edit'>{$row['model_Name']}</a></td>
			<td>".getManufacturer($row['manufacturer_id'])."</td>
			<td><a target='_blank' href='/netcat_files/certificates/{$row['certificate']}'>{$row['certificate']}</a></td>
			<td><a target='_blank' href='/netcat_files/certificates/{$row['certificate1']}'>{$row['certificate1']}</a></td>
			<td><a target='_blank' href='/netcat_files/certificates/{$row['certificate2']}'>{$row['certificate2']}</a></td>
			<td><a target='_blank' href='/netcat_files/certificates/{$row['certificate3']}'>{$row['certificate3']}</a></td>
			<td><a target='_blank' href='/netcat_files/certificates/{$row['certificate4']}'>{$row['certificate4']}</a></td>
			
		</tr>";
		}
	}
	$html.="</table>";
	return $html;
}

function editModel($id=0) {
	$sname=$sphone="";
	$sstat=$showrkfive=1;
	$srus=1;
	$sql="SELECT * FROM Classificator_model WHERE model_ID=".$id;
	$html="";
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$sname=$row['model_Name'];
			$sstat=$row['Checked'];
			$showrkfive=$row['showrkfive'];
		}
	}
	$html.="<form enctype='multipart/form-data' action='/netcat/modules/netshop/interface/models.php' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0' cellpadding='0' cellspacing='2'>
	<tr><td style='text-align:right;'>Название</td><td><input type='text' value='{$sname}' name='sname' id='sname' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Вкл.</td><td><input type='checkbox' value='1' name='sstat' id='sstat' ".(($sstat) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>Вкл. knivesshop.ru</td><td><input type='checkbox' value='1' name='showrkfive' id='showrkfive' ".(($showrkfive) ? "checked" : "")."></td></tr>
	<tr><td style='text-align:right;'>Производитель</td><td>".selectManufacturer(array('manufacturer'=>$row['manufacturer_id']))."</td></tr>
	<tr><td style='text-align:right;'>Сертификат #1</td><td style='vertical-align:top;'><input type='file' name='fname' id='fname'></td></tr>
	<tr><td style='text-align:right;'>Сертификат #2</td><td style='vertical-align:top;'><input type='file' name='fname1' id='fname'></td></tr>
	<tr><td style='text-align:right;'>Сертификат #3</td><td style='vertical-align:top;'><input type='file' name='fname2' id='fname'></td></tr>
	<tr><td style='text-align:right;'>Сертификат #4</td><td style='vertical-align:top;'><input type='file' name='fname3' id='fname'></td></tr>
	<tr><td style='text-align:right;'>Превью</td><td style='vertical-align:top;'><input type='file' name='fname4' id='fname'></td></tr>
	<tr><td style='text-align:right;'><input type='submit' value='Сохранить'></td><td>&nbsp;</td></tr>
	</table>
	<table cellpadding='2' cellspacing='0' border='0'>
	<tr>
		<td style='vertical-align:bottom;text-align:center;'>".(($row['certificate']) ? "<a href='/netcat_files/certificates/{$row['certificate']}' target='_blank'><img src='/netcat_files/certificates/{$row['certificate']}' width='200'></a><br>
		<a href='".$_SERVER['$_SERVER["REQUEST_URI"]']."?action=del&id={$id}&img=1'>удалить</a>" : "")."</td>
		<td style='vertical-align:bottom;text-align:center;'>".(($row['certificate1']) ? "<a href='/netcat_files/certificates/{$row['certificate1']}' target='_blank'><img src='/netcat_files/certificates/{$row['certificate1']}' width='200'></a><br>
		<a href='".$_SERVER['$_SERVER["REQUEST_URI"]']."?action=del&id={$id}&img=2'>удалить</a>" : "")."</td>
		<td style='vertical-align:bottom;text-align:center;'>".(($row['certificate2']) ? "<a href='/netcat_files/certificates/{$row['certificate2']}' target='_blank'><img src='/netcat_files/certificates/{$row['certificate2']}' width='200'></a><br>
		<a href='".$_SERVER['$_SERVER["REQUEST_URI"]']."?action=del&id={$id}&img=3'>удалить</a>" : "")."</td>
		<td style=vertical-align:bottom;text-align:center;'>".(($row['certificate3']) ? "<a href='/netcat_files/certificates/{$row['certificate3']}' target='_blank'><img src='/netcat_files/certificates/{$row['certificate3']}' width='200'></a><br>
		<a href='".$_SERVER['$_SERVER["REQUEST_URI"]']."?action=del&id={$id}&img=4'>удалить</a>" : "")."</td>
		<td style='vertical-align:bottom;text-align:center;'>".(($row['certificate4']) ? "<a href='/netcat_files/certificates/{$row['certificate4']}' target='_blank'><img src='/netcat_files/certificates/{$row['certificate4']}' width='200'></a><br>
		<a href='".$_SERVER['$_SERVER["REQUEST_URI"]']."?action=del&id={$id}&img=5'>удалить</a>" : "")."</td>
	</tr>
	</table>	
	
	</form>";
		
	return $html;
}

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$upload_dir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/certificates/";
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$show=1;
	//print_r($incoming);
	switch ($incoming['action']) {
		case "save":
			if (is_numeric($incoming['id'])) {
				//print_r($incoming)."<br>";
				$fname=$fname1=$fname2=$fname3=$fname4="";
				$fcert="";
				//print_r($_FILES);
				if ($_FILES["fname"]["size"]) {
					if ($_FILES["fname"]["error"] > 0) {
						$html.="<p>Error: " . $_FILES["fname"]["error"] . "</p>";
					} else {
						$tmp=explode(".",$_FILES["fname"]["name"]);
						$i=0;
						while (file_exists($upload_dir.$tmp[0].$i.".".$tmp[1])) {
							$i=$i+1;
						} 
						
						move_uploaded_file($_FILES["fname"]["tmp_name"],$upload_dir.$tmp[0].$i.".".$tmp[1]);
						$fname=$tmp[0].$i.".".$tmp[1];
						//$fcert="{$fname}";
						$html.="<p>Сертификат сохранен: " . $upload_dir. $fname."</p>";
						
					}
				}
				if ($_FILES["fname1"]["size"]) {
					if ($_FILES["fname1"]["error"] > 0) {
						$html.="<p>Error: " . $_FILES["fname1"]["error"] . "</p>";
					} else {
						$tmp=explode(".",$_FILES["fname1"]["name"]);
						$i=0;
						while (file_exists($upload_dir.$tmp[0].$i.".".$tmp[1])) {
							$i=$i+1;
						} 
						
						move_uploaded_file($_FILES["fname1"]["tmp_name"],$upload_dir.$tmp[0].$i.".".$tmp[1]);
						$fname1=$tmp[0].$i.".".$tmp[1];
						//$fcert="{$fname}";
						$html.="<p>Сертификат сохранен: " . $upload_dir. $fname1."</p>";
						
					}
				}
				if ($_FILES["fname2"]["size"]) {
					if ($_FILES["fname2"]["error"] > 0) {
						$html.="<p>Error: " . $_FILES["fname2"]["error"] . "</p>";
					} else {
						$tmp=explode(".",$_FILES["fname2"]["name"]);
						$i=0;
						while (file_exists($upload_dir.$tmp[0].$i.".".$tmp[1])) {
							$i=$i+1;
						} 
						
						move_uploaded_file($_FILES["fname2"]["tmp_name"],$upload_dir.$tmp[0].$i.".".$tmp[1]);
						$fname2=$tmp[0].$i.".".$tmp[1];
						//$fcert="{$fname}";
						$html.="<p>Сертификат сохранен: " . $upload_dir. $fname2."</p>";
						
					}
				}
				if ($_FILES["fname3"]["size"]) {
					if ($_FILES["fname3"]["error"] > 0) {
						$html.="<p>Error: " . $_FILES["fname3"]["error"] . "</p>";
					} else {
						$tmp=explode(".",$_FILES["fname3"]["name"]);
						$i=0;
						while (file_exists($upload_dir.$tmp[0].$i.".".$tmp[1])) {
							$i=$i+1;
						} 
						
						move_uploaded_file($_FILES["fname3"]["tmp_name"],$upload_dir.$tmp[0].$i.".".$tmp[1]);
						$fname3=$tmp[0].$i.".".$tmp[1];
						//$fcert="{$fname}";
						$html.="<p>Сертификат сохранен: " . $upload_dir. $fname3."</p>";
						
					}
				}
				if ($_FILES["fname4"]["size"]) {
					if ($_FILES["fname4"]["error"] > 0) {
						$html.="<p>Error: " . $_FILES["fname4"]["error"] . "</p>";
					} else {
						$tmp=explode(".",$_FILES["fname4"]["name"]);
						$i=0;
						while (file_exists($upload_dir.$tmp[0].$i.".".$tmp[1])) {
							$i=$i+1;
						} 
						
						move_uploaded_file($_FILES["fname4"]["tmp_name"],$upload_dir.$tmp[0].$i.".".$tmp[1]);
						$fname4=$tmp[0].$i.".".$tmp[1];
						//$fcert="{$fname}";
						$html.="<p>Сертификат сохранен: " . $upload_dir. $fname4."</p>";
						
					}
				}
				$chkd=((isset($incoming['sstat'])) ? 1 : 0);
				$showrkfive=((isset($incoming['showrkfive'])) ? 1 : 0);
				$all=getLastInsertID("Classificator_model","model_ID");
				if ($incoming['id']<=$all) {
					$sql="UPDATE Classificator_model SET 
						model_Name='".quot_smart($incoming['sname'])."',
						manufacturer_id=".intval($incoming['manufacturer']).",
						Checked=".$chkd.",
						showrkfive=".$showrkfive."
						".(($fname) ? ",certificate='{$fname}'" : "")."
						".(($fname1) ? ",certificate1='{$fname1}'" : "")."
						".(($fname2) ? ",certificate2='{$fname2}'" : "")."
						".(($fname3) ? ",certificate3='{$fname3}'" : "")."
						".(($fname4) ? ",certificate4='{$fname4}'" : "")."
						WHERE model_ID=".intval($incoming['id']);
				} else {
					$sql="INSERT INTO Classificator_model (model_Name,manufacturer_id,Checked,showrkfive,certificate,certificate1,certificate2,certificate3,certificate4)
						VALUES ('".quot_smart($incoming['sname'])."',".intval($incoming['manufacturer']).",{$chkd},{$showrkfive},'{$fname}','{$fname1}','{$fname2}','{$fname3}','{$fname4}')";
				}
				//echo $sql."<br>";
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/models.php'>Вернуться в список моделей.</a></p>";
			}
			$show=0;
			break;
		case "del":
			if (isset($incoming["id"])) {
				$sql="";
				if ($incoming["img"]==1) {
					$sql="UPDATE Classificator_model SET certificate='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==2) {
					$sql="UPDATE Classificator_model SET certificate1='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==3) {
					$sql="UPDATE Classificator_model SET certificate2='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==4) {
					$sql="UPDATE Classificator_model SET certificate3='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==5) {
					$sql="UPDATE Classificator_model SET certificate4='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($sql) {
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$html.="<p>Изменения сохранены.</p>";
				}
				$html.="<p><a href='/netcat/modules/netshop/interface/models.php'>Вернуться в список моделей.</a></p>";
			}
			$show=0;
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editModel($incoming['id']);
			}
			$show=0;
			break;
		default:
			//if (!isset($incoming['start'])) {
			
			$html=getModelsList($incoming);
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Модели ножей</title>
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
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	//print_r($_SERVER);
	
?>
	<h1>Модели</h1>
<?php
if ($show) {
?>	
	<p><a target="_blank" href="/netcat/modules/netshop/interface/items-nomodel.php">Товары без привязки к модели</a></p>
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Производитель</td><td><?php echo selectManufacturer($incoming); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	<?php 
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

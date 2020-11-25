<?php
// 2.02.2015 Elen
// бонусные карты 
include_once ("../../../../vars.inc.php");

session_start();

$ScriptURL="/netcat/modules/netshop/interface/actions.php";

function getActionsList($incoming) {
	$strwhere="";
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$strwhere=" WHERE supplier_id=".intval($incoming['supplier']);
	}	
	$cnum=0;
	$sql="SELECT *
		FROM Actions {$strwhere}
		ORDER BY id ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='{$_SERVER["REQUEST_URI"]}?id=".(getLastInsertID("Actions","id")+1)."&action=edit'>Добавить новую акцию</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>#</td>
			<td>Поставщик</td>
			<td>Название акции</td>
			<td>Участники</td>
			<td>Начало</td>
			<td>Окончание</td>
			<td>Описание</td>
			<td>Изменить описание</td>
			<td>Удалить</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
			<td><b>{$row['id']}</b></td>
			<td>".getSupplier($row['supplier_id'])."</td>
			<td>{$row['name']}</td>
			<td>
				<a href='/netcat/modules/netshop/interface/actions-card.php?action=list&id={$row['id']}'><img src='/images/icons/wblist.png' style='border:0;margin:0 5px;' alt='Список участников' title='Список участников'></a>
				<a href='/netcat/modules/netshop/interface/actions-card.php?action=add&id={$row['id']}'><img src='/images/icons/plus.png' style='border:0;margin:0 5px;' alt='Добавить участника' title='Добавить участника'></a>
			</td>
			<td>".date("d.m.Y",strtotime($row['startdate']))."</td>
			<td>".date("d.m.Y",strtotime($row['stopdate']))."</td>
			<td>{$row['description']}</td>
			<td><a href='{$_SERVER["REQUEST_URI"]}?id={$row['id']}&action=edit'><img src='/images/icons/edit.png' style='display:block;margin:0 auto;'></a></td>
			<td><a href='#' onclick='javascript:if(confirm(\"Удалить акцию #{$row['id']} {$row['name']}?\")){window.location=\"/netcat/modules/netshop/interface/actions.php?action=del&n={$row['id']}\";}'><img src='/images/icons/del.png' border='0' style='display:block;margin:0 auto;'></a></td>
		</tr>";
			$cnum=$cnum+1;
		}
	}
	$html.="</table>";
	$html.="<h3>Старые акции</h3>
	<ul>	
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/action-uk.php'>Карты Южный Крест</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/action-rvs.php'>Карты Р.В.С.</a></li>
	</ul>";
	return $html;
}

function editCard($id=0) {
	$supplier_id=0;
	$startdate=$stopdate=$name=$description="";
	$sstat=1;
	$srus=1;
	$html="";
	//print_r($_SESSION);
	$sql="SELECT * FROM Actions where id=".$id;
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$startdate=date("d.m.Y",strtotime($row['startdate']));
			$stopdate=date("d.m.Y",strtotime($row['stopdate']));
			$name=$row['name'];
			$description=$row['description'];
		}
	}
	((isset($_SESSION['cardnum']))&&($_SESSION['cardnum']!="")) ? $cardnum=$_SESSION['cardnum'] : "";
	((isset($_SESSION['uname']))&&($_SESSION['uname']!="")) ? $uname=$_SESSION['uname'] : "";
	((isset($_SESSION['uphone']))&&($_SESSION['uphone']!="")) ? $uphone=$_SESSION['uphone'] : "";
	((isset($_SESSION['email']))&&($_SESSION['email']!="")) ? $email=$_SESSION['email'] : "";
	((isset($_SESSION['order_id']))&&($_SESSION['order_id']!="")) ? $order_id=$_SESSION['order_id'] : "";
	((isset($_SESSION['comment']))&&($_SESSION['comment']!="")) ? $comment=$_SESSION['comment'] : "";
	((isset($_SESSION['created']))&&($_SESSION['created']!="")) ? $created=$_SESSION['created'] : $created=date("d.m.Y");
	
	$html.="
	<h3>Добавление новой акции</h3>
	<form action='{$_SERVER["REQUEST_URI"]}' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0' cellpadding='0' cellspacing='2'>
	<tr><td style='text-align:right;'>Поставщик</td><td>".selectSupplier($supplier_id)."</td></tr>
	<tr><td style='text-align:right;'>Название</td><td><input type='text' value='{$name}' name='name' id='name' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Начало</td><td><input type='text' value='{$startdate}' name='startdate' id='startdate' style='width:100px;' class='datepickerTimeField'></td></tr>
	<tr><td style='text-align:right;'>Окончание</td><td><input type='text' value='{$stopdate}' name='stopdate' id='stopdate' style='width:100px;' class='datepickerTimeField'></td></tr>
	<tr><td style='text-align:right;'>Описание</td><td><textarea name='description' id='description' style='width:700px;'>{$description}</textarea></td></tr>
	<tr><td style='text-align:right;'><input type='submit' value='Сохранить'></td><td>&nbsp;</td></tr>
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
			//print_r($incoming);
			$startdate=date("Y-m-d 00:00:00",strtotime($incoming['startdate']));
			$stopdate=date("Y-m-d 00:00:00",strtotime($incoming['stopdate']));
			$sql="";
			$subaction="insert";
			if (is_numeric($incoming['id'])) {
				$sql="SELECT * FROM Actions WHERE id=".intval($incoming['id']);
				if ($result=mysql_query($sql)) {
					if ($row=mysql_fetch_array($result)) {
						$subaction="update";
					}
				} 
				//echo $subaction;
				if($subaction=="insert") {
					$sql="INSERT INTO Actions (name,description,supplier_id,startdate,stopdate)
							VALUES (
							'".quot_smart($incoming['name'])."',
							'".quot_smart($incoming['description'])."',
							".intval($incoming['supplier']).",
							'{$startdate}','{$stopdate}')";
				}
				if($subaction=="update") {
					$sql="UPDATE Actions SET
							name='".quot_smart($incoming['name'])."',
							description='".quot_smart($incoming['description'])."',
							supplier_id=".intval($incoming['supplier']).",
							startdate='{$startdate}',
							stopdate='{$stopdate}'
						WHERE id=".intval($incoming['id']);
				}
				//echo $sql;
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p>Изменения сохранены.</p><p><a href='{$ScriptURL}'>Вернуться в список акций.</a></p>";
				
			}
			$show=0;
			break;
		case "del":
			if (isset($incoming["n"])) {
				$sql="SELECT id FROM Actions_cards WHERE action_id=".intval($incoming['n']);
				$j=0;
				if ($result=mysql_query($sql)) {
					while ($row=mysql_fetch_array($result)) {
						$j=$j+1;
					}
				}
				if ($j) {
					$html.="<p style='color:#f30000;font-weight:bold;'>В этой акции есть участники! Удаление невозможно!</p>";
				} else {
					$sql="DELETE FROM Actions WHERE id=".intval($incoming['n']);
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$html.="<p>Изменения сохранены.</p>";
				}
				$html.="<p><a href='/netcat/modules/netshop/interface/actions.php'>Вернуться в список акций.</a></p>";
			}
			$show=0;
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editCard($incoming['id']);
			}
			$show=0;
			break;
		default:
			//if (!isset($incoming['start'])) {
			$_SESSION['cardnum']=$_SESSION['uname']=$_SESSION['uphone']=$_SESSION['created']="";
			unset($_SESSION['cardnum']);
			unset($_SESSION['uname']);
			unset($_SESSION['uphone']);
			unset($_SESSION['created']);
			$html=getActionsList($incoming);
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Акции</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script type='text/javascipt'>
	function checkFrm(var all) {
		alert(all);
		//var alln=all.split(";");
		//alert(alln);
		//return false;
	}
	</script>
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	//print_r($_SERVER);
	
?>
	<h1>Акции</h1>
<?php
if ($show) {
?>	
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Поставщик</td><td><?php echo selectSupplier($incoming); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	<?php 
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
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
<?php

mysql_close($con);
?>

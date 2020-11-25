<?php
// 19.02.2015 Elen
// учёт
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");

function printKCedit($kc=0) {
	$id=0;
	$h3="<h3>Новый учёт</h3>";
	$created=$comment="";
	if (!is_numeric($kc)) {
		die("Неверный номер учёта!");
	}
	if ($kc) {
		$id=$kc;
		$h3="<h3>Изменение описания учёта #{$kc}</h3>";
		$sql="SELECT * FROM Keepcount WHERE id=".intval($kc);
		$result=mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {	
			$created=date("d.m.Y",strtotime($row['created']));
			$comment=$row['comment'];
		}
	} 
	$html=$h3."<form id='frm1' name='frm1' action='/netcat/modules/netshop/interface/keep-count.php' method='post'>
		<input type='hidden' name='action' value='save'>
		<input type='hidden' name='id' value='{$id}'>
		<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td>Дата начала учёта</td><td><input type='text' value='{$created}' name='created'></td></tr>
		<tr><td>Комментарий</td><td><textarea name='comment' style='width:800px;'>{$comment}</textarea></td></tr>
		</table><br>
		<input type='submit' value='Сохранить'>
		</form>";
	return $html;
}

// ------------------------------------------------------------------------------------------------
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

$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$incoming = parse_incoming();
//print_r($incoming);
	
$html="";
switch ($incoming['action']) {
	case "save":
		if ($incoming['id']==0) {
			$sql="INSERT INTO Keepcount (created,comment) VALUES 
				('".date("Y-m-d",strtotime($incoming['created']))."','".quot_smart($incoming['comment'])."')";
		} else {
			$sql="UPDATE Keepcount SET created='".date("Y-m-d",strtotime($incoming['created']))."',
				comment='".quot_smart($incoming['comment'])."'  
				WHERE id=".intval($incoming['id']);
		}
		if (mysql_query($sql)) {	
				$html.="<h2>Описание сохранено</h2>
				<p><a href='/netcat/modules/netshop/interface/keep-count.php'>Продолжить</a></p>";
				//$showall=0;
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
		break;
	case "new":
		$html=printKCedit();
		break;
	case "edit":
		$html=printKCedit($incoming['kc']);
		break;
	default:
		$sql="SELECT * FROM Keepcount ORDER BY id ASC";
		$result=mysql_query($sql);
		$html="<table cellpadding='2' cellspacing='0' border='1'>
			<tr><td><b>#</b></td>
				<td><b>Дата начала</b></td>
				<td><b>Описание</b></td>
				<td><b>Изменить описание</b></td>
				<td><b>Список накладных</b></td>
				<td><b>Добавить накладную в учет</b></td>
			</tr>";
		while ($row = mysql_fetch_array($result)) {	
			$html.="<tr>
				<td>{$row['id']}</a></td>
				<td>".date("d.m.Y",strtotime($row['created']))."</a></td>
				<td>{$row['comment']}</td>
				<td><a href='/netcat/modules/netshop/interface/keep-count.php?action=edit&kc={$row['id']}'><img src='/images/icons/edit.png' style='display:block;margin:0 auto;'></a></td>
				<td><a href='/netcat/modules/netshop/interface/keep-count-waybills.php?kc={$row['id']}'><img src='/images/icons/wblist.png' style='display:block;margin:0 auto;'></a></td>
				<td><a href='/netcat/modules/netshop/interface/keep-count-wbadd.php?kc={$row['id']}'><img src='/images/icons/plus.png' style='display:block;margin:0 auto;'></a></td>
			</tr>";
		}
		$html.="</table><br>
			<p><a href='/netcat/modules/netshop/interface/keep-count.php?action=new'>Начать новый учёт</a></p>";
		break;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Учёт</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	img {
		border:0;
	}
	</style>
</head>
<body>
<?php
	echo printMenu();
?>
	<h1>УЧЁТ</h1>
<?php echo $html; ?>
</body>
</html>
<?php

mysql_close($con);
?>

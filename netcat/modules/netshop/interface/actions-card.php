<?php
// 2.03.2015 Elen
// бонусные карты 
include_once ("../../../../vars.inc.php");

session_start();

$ScriptURL="/netcat/modules/netshop/interface/actions.php";

function getCardsList($incoming) {
	$strwhere="";
	if ((isset($incoming['id']))&&($incoming['id']!="")) {
		$strwhere=" WHERE action_id=".intval($incoming['id']);
	}	
	$cnum=0;
	$ccnum=0;
	$sql="SELECT Actions_cards.*,Message51.paid
		FROM Actions_cards
		LEFT JOIN Message51 ON (Message_ID=order_id)
		{$strwhere}
		ORDER BY id ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/actions-card.php?action=add&id={$incoming['id']}'>Добавить участника</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td># карты</td>
			<td>ФИО</td>
			<td>Телефон</td>
			<td>E-mail</td>
			<td>№ заказа</td>
			<td>Артикул</td>
			<td>Дата выдачи</td>
			<td>Дата закрытия</td>
			<td>Изменить</td>
		</tr>";
	$itog=0;
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$style=(($row['closed']==1) ? "style='background:#c0c0c0;'" : "");
			$html.="<tr>
			<td {$style}><b>{$row['cardnum']}</b></td>
			<td {$style}>{$row['uname']}</td>
			<td {$style}>{$row['uphone']}</td>
			<td {$style}>{$row['email']}</td>
			<td ".((($row['order_id'])&&($row['paid']==1)) ? "style='background:#ccffcc;'" : "")." ".(($row['order_id']) ? "" : " style='background:#ccffcc;'").">".(($row['order_id']) ? $row['order_id'] : "" )."</td>
			<td {$style}>{$row['comment']}</td>
			<td {$style}>".date("d.m.Y", strtotime($row['created']))."</td>
			<td {$style}>".((($row['closeddate']!="")&&($row['closeddate']!="0000-00-00 00:00:00")) ? date("d.m.Y", strtotime($row['closeddate'])) : "&nbsp;")."</td>
			<td {$style}><a href='/netcat/modules/netshop/interface/actions-card.php?action=edit&id={$incoming['id']}&n={$row['id']}'><img src='/images/icons/edit.png' alt='Редактировать данные' title='Редактировать данные' style='display:block;margin:0 auto;'></a></td>
		</tr>";
			$cnum=$cnum+1;
			if ($row['closed']==1) {
				$ccnum=$ccnum+1;
			}
		}
	}
	$html.="</table>";
	$html.="<p>Всего выдано <b>{$cnum}</b></p>";
	$html.="<p>Всего закрыто <b>{$ccnum}</b></p>";
	return $html;
}

function selectActions($id) {
	$html="<select id='action_id' name='action_id'>";
	$sql="SELECT * FROM Actions ORDER BY name ASC";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<option value='{$row['id']}' ".(($row['id']==$id) ? "selected" : "").">{$row['name']}</option>";
		}
	}
	$html.="</select>";
	return $html;
}

function editCard($id=0,$n=0) {
	$supplier_id=0;
	$created=$cardnum=$uname=$uphone=$email=$order_id=$comment=$closed="";
	$html="";
	//print_r($_SESSION);
	$sql="SELECT * FROM Actions_cards where id=".intval($n)." AND action_id=".intval($id);
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$created=date("d.m.Y",strtotime($row['created']));
			$cardnum=$row['cardnum'];
			$uname=$row['uname'];
			$uphone=$row['uphone'];
			$email=$row['email'];
			$order_id=$row['order_id'];
			$comment=$row['comment'];
			$closed=($row['closed']==1) ? "checked" : "";
			$closeddate=((($row['closeddate']!="")&&($row['closeddate']!="0000-00-00 00:00:00")) ? date("d.m.Y",strtotime($row['closeddate'])) : "");
		}
	}
	
	$html.="
	<h3>Добавление новой карты</h3>
	<form action='/netcat/modules/netshop/interface/actions-card.php' method='post'>
	<input type='hidden' value='{$n}' name='n'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0' cellpadding='0' cellspacing='2'>
	<tr><td style='text-align:right;'>Акция</td><td>".selectActions($id)."</td></tr>
	<tr><td style='text-align:right;'>Номер карты</td><td><input type='text' value='{$cardnum}' name='cardnum' id='cardnum' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>ФИО покупателя</td><td><input type='text' value='{$uname}' name='uname' id='uname' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Телефон</td><td><input type='text' value='{$uphone}' name='uphone' id='uphone' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>E-mail</td><td><input type='text' value='{$email}' name='email' id='email' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>№ заказа</td><td><input type='text' value='{$order_id}' name='order_id' id='order_id' style='width:100px;'></td></tr>
	<tr><td style='text-align:right;'>Артикул/комментарий</td><td><input type='text' value='{$comment}' name='comment' id='comment' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Дата выдачи</td><td><input type='text' value='{$created}' name='created' id='created'  style='width:100px;' class='datepickerTimeField'></td></tr>
	<tr><td style='text-align:right;'>Карта закрыта</td><td><input type='checkbox' value='1' name='closed' id='closed' {$closed}></td></tr>
	<tr><td style='text-align:right;'>Дата закрытия</td><td><input type='text' value='{$closeddate}' name='closeddate' id='closeddate'  style='width:100px;' class='datepickerTimeField'></td></tr>
	
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
			$closeddate="0000-00-00 00:00:00";
			if ($incoming['created']) {
				$created=date("Y-m-d",strtotime($incoming['created']))." ".date("H:i:s");
			} else {
				$created=date("Y-m-d H:i:s");
			}
			if ($incoming['closeddate']) {
				$closeddate=date("Y-m-d",strtotime($incoming['closeddate']))." ".date("H:i:s");
			} 
			$sql="";
			$subaction="insert";
			if (is_numeric($incoming['id'])) {
				if ($incoming['n']){
					$subaction="update";
				} 
				//echo $subaction;
				if($subaction=="insert") {
					$closed=0;
					if ((isset($incoming['closed'])) && ($incoming['closed']==1)) {
						$closed=1;
					}
					$sql="INSERT INTO Actions_cards (action_id,cardnum,uname,uphone,email,
							".(($incoming['order_id']) ? "order_id," : "")."
							comment,created,closed,closeddate)
							VALUES (
							".intval($incoming['action_id']).",
							".intval($incoming['cardnum']).",
							'".quot_smart($incoming['uname'])."',
							'".quot_smart($incoming['uphone'])."',
							'".quot_smart($incoming['email'])."',
							".(($incoming['order_id']) ? $incoming['order_id']."," : "")."
							'".quot_smart($incoming['comment'])."',
							'{$created}',
							{$closed},
							'{$closeddate}')";
				}
				if($subaction=="update") {
					$closed=0;
					if ((isset($incoming['closed'])) && ($incoming['closed']==1)) {
						$closed=1;
					}
					$sql="UPDATE Actions_cards SET
							action_id=".intval($incoming['id']).",
							cardnum=".intval($incoming['cardnum']).",
							uname='".quot_smart($incoming['uname'])."',
							uphone='".quot_smart($incoming['uphone'])."',
							email='".quot_smart($incoming['email'])."',
							".(($incoming['order_id']) ? "order_id=".$incoming['order_id']."," : "")."
							comment='".quot_smart($incoming['comment'])."',
							created='{$created}',
							closed=".$closed.",
							closeddate='{$closeddate}'
							WHERE id=".intval($incoming['n']);
				}
				//echo $sql;
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/actions-card.php?action=list&id={$incoming['id']}'>Вернуться в список участников.</a></p>";
				
			}
			$show=0;
			break;
		/*case "del":
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
			break;*/
		case "add":
			if (isset($incoming['id'])) {
				$html=editCard($incoming['id'],$incoming['n']);
			}
			$show=0;
			break;
		case "edit":
			if ((isset($incoming['id']))&&(isset($incoming['n']))) {
				$html=editCard($incoming['id'],$incoming['n']);
			}
			$show=0;
			break;
		case "list":
			$html=getCardsList($incoming);
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
	<p><a href='/netcat/modules/netshop/interface/actions.php'>Вернуться в список акций</a></p>
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

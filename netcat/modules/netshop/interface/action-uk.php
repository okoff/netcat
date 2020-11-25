<?php
// 11.08.2014 Elen
// бонусные карты от РВС
include_once ("../../../../vars.inc.php");

session_start();

function getCardsList($incoming) {
	$strwhere="";
	if ((isset($incoming['manufacturer']))&&($incoming['manufacturer']!="")) {
		$strwhere=" WHERE manufacturer_id=".intval($incoming['manufacturer']);
	}	
	$cnum=0;
	$sql="SELECT *
		FROM Action_UK {$strwhere}
		ORDER BY cardnum ASC";
	//echo "<br>".$sql;
	$html="<p>[<b><a href='/netcat/modules/netshop/interface/action-uk.php?id=".(getLastInsertID("Action_UK","id")+1)."&action=edit'>Добавить</a></b>]</p>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td># карты</td>
			<td>ФИО</td>
			<td>Телефон</td>
			<td>E-mail</td>
			<td>№ заказа</td>
			<td>Артикул</td>
			<td>Дата выдачи</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
			<td><a href='/netcat/modules/netshop/interface/action-uk.php?id={$row['id']}&action=edit'>{$row['cardnum']}</a></td>
			<td>{$row['uname']}</td>
			<td>{$row['uphone']}</td>
			<td>{$row['email']}</td>
			<td>".(($row['order_id']) ? $row['order_id'] : "" )."</td>
			<td>{$row['comment']}</td>
			<td>".date("d.m.Y", strtotime($row['created']))."</td>
		</tr>";
			$cnum=$cnum+1;
		}
	}
	$html.="</table>";
	$html.="<p>Всего выдано <b>{$cnum}</b></p>";
	return $html;
}

function editCard($id=0) {
	$cardnum=$uname=$uphone=$email=$order_id=$comment=$created="";
	$sstat=1;
	$srus=1;
	$html="";
	//print_r($_SESSION);
	$sql="SELECT * FROM Action_UK where id=".$id;
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$cardnum=$row['cardnum'];
			$uname=$row['uname'];
			$uphone=$row['uphone'];
			$email=$row['email'];
			$order_id=$row['order_id'];
			$comment=$row['comment'];
			$created=date("d.m.Y",strtotime($row['created']));
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
	
	<form action='/netcat/modules/netshop/interface/action-uk.php' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<table border='0' cellpadding='0' cellspacing='2'>
	<tr><td style='text-align:right;'>Номер карты</td><td><input type='text' value='{$cardnum}' name='cardnum' id='cardnum' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>ФИО покупателя</td><td><input type='text' value='{$uname}' name='uname' id='uname' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Телефон</td><td><input type='text' value='{$uphone}' name='uphone' id='uphone' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>E-mail</td><td><input type='text' value='{$email}' name='email' id='email' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>№ заказа</td><td><input type='text' value='{$order_id}' name='order_id' id='order_id' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Артикул</td><td><input type='text' value='{$comment}' name='comment' id='comment' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Дата выдачи</td><td><input type='text' value='{$created}' name='created' id='created' style='width:700px;'><br>
		дд.мм.гггг</td></tr>
	
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
			if (is_numeric($incoming['id'])) {
				$created=date("Y-m-d 00:00:00",strtotime($incoming['created']));
				//echo $created."<br>";
				$sv=true;
				$sql="SELECT cardnum FROM Action_UK WHERE NOT id=".intval($incoming['id']);
				if ($result=mysql_query($sql)) {
					while ($row=mysql_fetch_array($result)) {
						//echo $row['id']."<br>";
							if ($row['cardnum']==$incoming['cardnum']) {
								$_SESSION['cardnum']=$incoming['cardnum'];
								$_SESSION['uname']=$incoming['uname'];
								$_SESSION['uphone']=$incoming['uphone'];
								$_SESSION['email']=$incoming['email'];
								$_SESSION['order_id']=$incoming['order_id'];
								$_SESSION['comment']=$incoming['comment'];
								$_SESSION['created']=$incoming['created'];
								$html.="<p>Карта с таким номером существует!</p><p><a href='/netcat/modules/netshop/interface/action-uk.php?id={$incoming['id']}&action=edit'>Вернуться назад.</a></p>";
								$sv=false;
								break;
							}
						
					}
				}
				if ($sv) {
					$all=getLastInsertID("Action_UK","id");
					if ($incoming['id']<=$all) {
						$sql="UPDATE Action_UK SET 
							cardnum=".intval($incoming['cardnum']).",
							uname='".quot_smart($incoming['uname'])."',
							uphone='".quot_smart($incoming['uphone'])."',
							email='".quot_smart($incoming['email'])."',
							".(($incoming['order_id']) ? "order_id=".$incoming['order_id']."," : "")."
							comment='".quot_smart($incoming['comment'])."',
							created='{$created}'
							WHERE id=".intval($incoming['id']);
					} else {
						$sql="INSERT INTO Action_UK (cardnum,uname,uphone,email,
							".(($incoming['order_id']) ? "order_id," : "")."
							comment,created)
							VALUES (".intval($incoming['cardnum']).",'".quot_smart($incoming['uname'])."',
							'".quot_smart($incoming['uphone'])."',
							'".quot_smart($incoming['email'])."',
							".(($incoming['order_id']) ? $incoming['order_id']."," : "")."
							'".quot_smart($incoming['comment'])."',
							'{$created}')";
					}
					//echo $sql."<br>";
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$_SESSION['cardnum']=$_SESSION['uname']=$_SESSION['uphone']=$_SESSION['created']="";
					unset($_SESSION['cardnum']);
					unset($_SESSION['uname']);
					unset($_SESSION['uphone']);
					unset($_SESSION['email']);
					unset($_SESSION['order_id']);
					unset($_SESSION['comment']);
					unset($_SESSION['created']);
					$html.="<p>Изменения сохранены.</p><p><a href='/netcat/modules/netshop/interface/action-uk.php'>Вернуться в список карт.</a></p>";
				}
			}
			$show=0;
			break;
		case "del":
			if (isset($incoming["id"])) {
				$sql="";
				if ($incoming["img"]==1) {
					$sql="UPDATE Action_UK SET certificate='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==2) {
					$sql="UPDATE Action_UK SET certificate1='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==3) {
					$sql="UPDATE Action_UK SET certificate2='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==4) {
					$sql="UPDATE Action_UK SET certificate3='' WHERE model_ID=".intval($incoming['id']);
				}
				if ($incoming["img"]==5) {
					$sql="UPDATE Action_UK SET certificate4='' WHERE model_ID=".intval($incoming['id']);
				}
				/*if ($sql) {
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$html.="<p>Изменения сохранены.</p>";
				}*/
				$html.="<p><a href='/netcat/modules/netshop/interface/action-uk.php'>Вернуться в список моделей.</a></p>";
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
			$html=getCardsList($incoming);
			//}
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Карты Южный Крест</title>
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
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	//print_r($_SERVER);
	
?>
	<h1>Карты Южный Крест</h1>
<?php
if ($show) {
?>	
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<!--table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Производитель</td><td><?php echo selectManufacturer($incoming); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table-->
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

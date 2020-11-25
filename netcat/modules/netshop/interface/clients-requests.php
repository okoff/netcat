<?php
// 12.11.2013 Elen
// поиск и просмотр списка заказов
include_once ("../../../../vars.inc.php");

session_start();
function clean_value($val) {
    if ($val == "") {
    	return "";
    }
    $val = preg_replace( "/\n/"        , "<br>"          , $val ); // Convert literal newlines
    $val = preg_replace( "/\r/"        , ""              , $val ); // Remove literal carriage returns
    return $val;
}
function un_clean_value($val) {
    if ($val == "") {
    	return "";
    }
    $val = str_replace ("&lt;br&gt;","\n",$val); // Convert literal newlines
    return $val;
}
	
// ------------------------------------------------------------------------------------------------

function printRequestEditFrm($incoming) {
	$uname=$uemail="";
	if (isset($incoming['cid'])) {
		//return "<p style='color:#f30000;'>Необходимо выбрать клиента!</p>";
		$sql="SELECT * FROM User WHERE User_ID=".intval($incoming['cid']);
		$result=mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {
			$uname=$row['Login'];
			$uemail=$row['Email'];
		}
	}
	if (isset($incoming['n'])) {
		$sql="SELECT * FROM User_requests WHERE id=".intval($incoming['n']);
		$result=mysql_query($sql);
		$req = mysql_fetch_array($result);
	}
	$html="<h3>Добавление новой заявки по клиенту {$uname}</h3>";
	$html.="<p><a href='/netcat/modules/netshop/interface/clients-requests.php?cid={$incoming['cid']}'>Все заявки по клиенту</a></p>";
	$html.="<form name='frm1' id='frm1' action='/netcat/modules/netshop/interface/clients-requests.php' method='post'>
	<input type='hidden' name='cid' id='cid' value='{$incoming['cid']}'>
	<input type='hidden' name='n' id='n' value='{$incoming['n']}'>
	<input type='hidden' name='action' id='action' value='save'>
	".((isset($incoming['n'])) ? "<input type='hidden' name='subaction' id='subaction' value='edit'>" : "<input type='hidden' name='subaction' id='subaction' value='new'>")."
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td>Заголовок:</td>
		<td><textarea name='subject' id='subject' cols='100' rows='2'>".((isset($req['subject'])) ? un_clean_value(htmlspecialchars_decode($req['subject'])) : "")."</textarea></td></tr>
	<tr><td>Текст:</td>
		<td><textarea name='request' id='request' cols='100' rows='10'>".((isset($req['request'])) ? un_clean_value($req['request']) : "")."</textarea></td></tr>
	<tr><td>Дата создания:</td>
		<td><input type='text' name='created' id='created' value='".date("d.m.Y H:i")."'></td></tr>
	<tr><td>Закрыто</td>
		<td><input type='checkbox' name='closed' id='closed' value='1' ".(((isset($req['closed']))&&($req['closed']==1)) ? "checked" : "")."></td></tr>
	<tr><td>&nbsp;</td>
		<td><input type='submit' value='Сохранить'></td></tr>
	</form>";
	return $html;
}

function printRequest($row) {
	$html="";
	$st="";
	if ($row['closed']==1) {
		$st="background:#c0c0c0;color:#444444;";
	}
	$html.="<tr>
	<td style='vertical-align:top;{$st}'><b><a href='/netcat/modules/netshop/interface/clients-requests.php?action=edit&cid={$row['User_ID']}&n={$row['id']}'>{$row['id']}</a></b></td>
	<td style='vertical-align:top;{$st}'><b>{$row['Login']}</b><br>{$row['Email']}<br>{$row['phone']}</td>
	<td style='vertical-align:top;{$st}'>".date("d.m.Y H:i", strtotime($row['created']))."</td>
	<td style='vertical-align:top;{$st}'>".htmlspecialchars_decode($row['subject'])."</td>
	<td style='vertical-align:top;{$st}'>".htmlspecialchars_decode($row['request'])."</td>
	<td style='vertical-align:top;{$st}'><a href='/netcat/modules/netshop/interface/clients-requests.php?action=edit&cid={$row['User_ID']}&n={$row['id']}'><img src='/images/icons/edit.png' alt='Изменить заявку' title='Изменить заявку' style='display:block;margin:0 auto;'></a></td>
	<td style='vertical-align:top;{$st}'><a href='#' onclick='javascript:if(confirm(\"Удалить заявку #{$row['id']}?\")){window.location=\"/netcat/modules/netshop/interface/clients-requests.php?action=del&cid={$row['User_ID']}&n={$row['id']}\";}'><img src='/images/icons/del.png' style='display:block;margin:0 auto;'></a></td>
	</tr>\n";
	
	return $html;
}

function getRequestList($incoming) {
	
	$html=$where=$whereu=$whereclo="";
	//print_r($incoming);
	//echo "<br>";
	if ((isset($incoming['cid'])) && ($incoming['cid']!="")) {
		$whereu.=" User_requests.user_id=".intval($incoming['cid']);
	}
	$whereclo.=(($where!="") ? " AND " : "")." closed=0 ";
	if (isset($incoming['showclo'])) {
		if ($incoming['showclo']==1) {
			$whereclo="";
		}
	} 
	$where=" WHERE deleted=0 ".(($whereu!="") ? " AND ".$whereu : "").(($whereclo!="") ? " AND ".$whereclo : "");
	$sql="SELECT User_requests.*,User.Login,User.Email,User.phone FROM User_requests
		LEFT JOIN User ON (User.User_ID=User_requests.user_id)
		".$where."
		ORDER BY User_requests.id DESC";
	//echo $sql."<br>";
	//if ($incoming['start']!=1) {
	$html.="<p><a href='/netcat/modules/netshop/interface/clients-requests.php?action=add'>Создать новую заявку</a></p>";
	$html.="<form name='frm1' id='frm1' action='/netcat/modules/netshop/interface/clients-requests.php' method='post'>
		<input type='checkbox' name='showclo' id='showclo' value='1' ".((isset($incoming['showclo'])) ? "checked" : "").">Показать закрытые
		<input type='submit' value='Найти'>
	</form><br>";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td style='text-align:center;'><b>#</b></td>
		<td style='text-align:center;'><b>Клиент</b></td>
		<td style='text-align:center;'><b>Дата</b></td>
		<td style='text-align:center;'><b>Тема</b></td>
		<td style='text-align:center;'><b>Заявка</b></td>
		<td style='text-align:center;'><b>Изменить</b></td>
		<td style='text-align:center;'><b>Удалить</b></td>
	</tr>";
	if ($result=mysql_query($sql)) {
		$html="<p>Всего заявок: <b>".mysql_num_rows($result)."</b></p>".$html;
		while($row = mysql_fetch_array($result)) {
			$html.=printRequest($row);
		}
	}
	$html.="</table>";
	//}
	return $html;
}
// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	include_once ("utils-retail.php");
	//$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
	//$UploadDirNarv=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/narv/';
	//$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
	//$DownloadDirLink='/netcat_files/postfiles/';
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
			$user_id=0;
			if (isset($incoming['cid'])) {
				$user_id=intval($incoming['cid']);
			}
			$subject=htmlspecialchars(clean_value($incoming['subject']));
			$request=htmlspecialchars(clean_value($incoming['request']));
			$created=date("Y-m-d H:i:s",strtotime($incoming['created']));
			$closed=((isset($incoming['closed']))&&($incoming['closed']==1)) ? 1 : 0;
			$sql="";
			if ($incoming['subaction']=="new") {
				$sql="INSERT INTO User_requests (user_id,subject,request,created,closed)
					VALUES ({$user_id},'{$subject}','{$request}','{$created}',{$closed})";		
			} elseif ($incoming['subaction']=="edit") {
				$sql="UPDATE User_requests SET
					user_id={$user_id},
					subject='{$subject}',
					request='{$request}',
					created='{$created}',
					closed={$closed}
					WHERE id=".intval($incoming['n']);
			} else {
				echo "error";
			}
			if (mysql_query($sql)) {
				$show=0;
				$html="<p><b>Заявка сохранена.</b></p> 
				<p><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}'>Вернуться в карточку клиента</a></p>
				<p><a href='/netcat/modules/netshop/interface/clients-requests.php'>Вернуться в список заявок</a></p>";
				//header("Location:/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}");
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
			break;
		case "del":
			//print_r($incoming);
			$user_id=intval($incoming['cid']);
			$id=intval($incoming['n']);
			$sql="";
			$sql="UPDATE User_requests SET
					deleted=1 
					WHERE user_id={$user_id} AND id={$id}";
			if (mysql_query($sql)) {
				$show=0;
				$html="<p><b>Заявка удалена.</b></p> 
				<p><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}'>Вернуться в карточку клиента</a></p>
				<p><a href='/netcat/modules/netshop/interface/clients-requests.php'>Вернуться в список заявок</a></p>";
				//header("Location:/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming['cid']}");
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
			break;
		case "add":
			$show=0;
			$html=printRequestEditFrm($incoming);
			break;
		case "edit":
			$show=0;
			$html=printRequestEditFrm($incoming);
			break;
		default:
			$html=getRequestList($incoming);
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Клиенты</title>
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
	if ($show) {
?>
	<h1>Заявки</h1>
	
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

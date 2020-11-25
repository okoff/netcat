<?php
// 12.07.2016 Elen
// работа с PickPoint отправлениями. Печать этикеток
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("pickpoint-api.php");


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
$SessionId="";
//echo $SessionId." ".$_SESSION['PP_session']."<br>";
if ($_SESSION['PP_session']!="") {
	$SessionId=$_SESSION['PP_session'];
} else {
	// login. start session. get session id
	$loginstr = '{"Login":"apitest", "Password":"apitest"}';
	$lgn=sendReq("login",$loginstr);
	if (($lgn['ErrorMessage']=="null")&&($lgn['SessionId'])){
		$SessionId=$lgn['SessionId'];
		$_SESSION['PP_session']=$SessionId;
	} else {
		die("Error login in API!");
	}
}
if ((isset($incoming['f']))&&($incoming['f']!="")) {
	$fid=intval($incoming['f']);
} else {
	die("Не указан номер заказа");
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Работа с PickPoint</title>
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
//print_r($_SESSION);
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
?>	
<h3>Печать этикеток PickPoint для отправки №<?php echo $fid; ?></h3>
<?php
	$sql="SELECT itemscreated FROM Netshop_PickpointFiles WHERE id=".$fid;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$items=explode(";",$row['itemscreated']);
			foreach($items as $i) {
				if ($i!="") {
					echo "<p>Заказ #".$i." ";
					$sql1="SELECT pickpoint_invoice FROM Message51 WHERE Message_ID=".$i;
					if ($result1=mysql_query($sql1)) {
						while($row1 = mysql_fetch_array($result1)) {
							echo "<a target='_blank' href='/netcat_files/pickpointfiles/labels/{$row1['pickpoint_invoice']}.pdf'>печать</a></p>";
						}
					}
				}
			}
		}
	}
?>
<?php
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>

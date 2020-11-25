<?php
include_once ("../../../../vars.inc.php");
include_once ("utils.php");
session_start();

$incoming=parse_incoming();
//print_r($incoming);
if ($incoming['request_method']=="post") {
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	
	$sql="SELECT * FROM User WHERE Email='".quot_smart($incoming['AUTH_USER'])."' AND Password='".md5(quot_smart($incoming['AUTH_PW']))."'";
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			if ((isset($row['Email'])) && (isset($row['Password']))) {
				$_SESSION['insideadmin']=1;
				if($incoming['jump']){
					echo "
	<script type=\"text/javascript\">
		setTimeout('window.location.replace(\"{$incoming['jump']}\")', 2);
	</script>";
				}
			}
		}
	}
	
	mysql_close($con);

}
//print_r($_SESSION);
?>
<!DOCTYPE html>
<html>
<head>
	<title>Авторизация</title>
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
} else {
?>
<table>
<form action='/netcat/modules/netshop/interface/login.php' method='POST'>
<input type='hidden' name='AuthPhase' value='1'/>
	<input type='hidden' name='REQUESTED_FROM' value=''/>
	<input type='hidden' name='REQUESTED_BY' value='post'/>
	<input type='hidden' name='catalogue' value='1'/>
	<input type='hidden' name='sub' value=''/>
	<input type='hidden' name='cc' value=''/>
	<input type='hidden' name='jump' value='<?php echo $incoming['jump'];?>'/>
	<tr><td>E-mail:</td><td><input type='text' name='AUTH_USER' value='' style='width:200px;' /></td></tr>
	<tr><td>Пароль:</td><td><input type='password' name='AUTH_PW' style='width:200px;' /></td></tr>
	<tr><td valign='middle'  colspan='2'><input type='submit' value='Войти' /></td></tr>
	<!--input type='submit' class='auth_submit'  value='Вход' /-->
</form>
</table>
<?php
}
?>
</body>
</html>
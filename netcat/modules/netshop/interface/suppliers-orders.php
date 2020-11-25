<?php
// 15.09.2014 Elen
// заказы от поставщиков
include_once ("../../../../vars.inc.php");

session_start();

function getSupplierOrdersList($incoming) {
	$strwhere="";
	$fullprice="";
	$fulln="";
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$strwhere=" supplier_id=".intval($incoming['supplier']);
	}	
	if ((isset($incoming['min']))&&(isset($incoming['max']))) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" created BETWEEN '".date("Y-m-d",strtotime($incoming['min']))." 00:00:00' AND '".date("Y-m-d",strtotime($incoming['max']))." 23:59:59'";
	}
	if ((isset($incoming['uname']))&&($incoming['uname']!="")) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" uname LIKE '%".quot_smart($incoming['uname'])."%'";
	}
	if ((isset($incoming['uphone']))&&($incoming['uphone']!="")) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" uphone LIKE '%".quot_smart($incoming['uphone'])."%'";
	}
	if ((isset($incoming['issued']))&&($incoming['issued']==1)) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" issued=1 ";
	}
	if ((isset($incoming['payment']))&&($incoming['payment']==1)) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" payment=1 ";
	}
	if ((isset($incoming['returned']))&&($incoming['returned']==1)) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" returned=1 ";
	}
	if ((isset($incoming['canceled']))&&($incoming['canceled']==1)) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" canceled=1 ";
	}
	if ((isset($incoming['state1']))&&($incoming['state1']==1)) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" issued=1 AND payment=0 AND returned=0";
	}
	if ((isset($incoming['state2']))&&($incoming['state2']==1)) {
		$strwhere.=($strwhere!="") ? " AND " : "";
		$strwhere.=" shipped=1 AND issued=0 AND returned=0";
	}
	($strwhere!="") ? $strwhere=" WHERE ".$strwhere : "";
	$cnum=0;
	$sql="SELECT Supplier_orders.*,Classificator_Supplier.Supplier_Name
		FROM Supplier_orders INNER JOIN Classificator_Supplier ON (Classificator_Supplier.Supplier_ID=Supplier_orders.supplier_id) {$strwhere}
		ORDER BY id DESC";
	$k=1;
	//echo $sql;
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>#</td>
			<td>#</td>
			<td>Поставщик</td>
			<td>ФИО</td>
			<td>Телефон</td>
			<td>Заказ</td>
			<td>Комментарий</td>
			<td>Цена</td>
			<td>Дата создания</td>
			<td>Выдан</td>
			<td>Дата выдачи</td>
			<td>Оплачен</td>
			<td>Дата оплаты</td>
			<td>Возвращен</td>
			<td>Дата возврата</td>
			<td>Отменен</td>
			<td>Дата отмены</td>
			<td>Закрыт</td>
			<td>Удалить</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
			<td><b><a href='".$_SERVER['REQUEST_URI']."?id={$row['id']}&action=edit'>{$k}</a></b></td>
			<td><b><a href='".$_SERVER['REQUEST_URI']."?id={$row['id']}&action=edit'>{$row['spnumber']}</a></b></td>
			<td>{$row['Supplier_Name']}</td>
			<td>{$row['uname']}</td>
			<td>{$row['uphone']}</td>
			<td>{$row['ordertext']}</td>
			<td>{$row['comment']}</td>
			<td>{$row['price']}</td>
			<td>".date("d.m.Y",strtotime($row['created']))."</td>
			<td style='text-align:center;'>".(($row['issued']==1) ? "<img style='margin:0 auto;' src='/images/icons/ok.png'>" : "-" )."</td>
			<td>".(($row['dtissued']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtissued'])) : "")."&nbsp;</td>
			<td style='text-align:center;'>".(($row['payment']==1) ? "<img style='margin:0 auto;' src='/images/icons/ok.png'>" : "-" )."</td>
			<td>".(($row['dtpayment']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtpayment'])) : "")."&nbsp;</td>
			<td style='text-align:center;'>".(($row['returned']==1) ? "<img style='margin:0 auto;' src='/images/icons/ok.png'>" : "-" )."</td>
			<td>".(($row['dtreturned']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtreturned'])) : "")."&nbsp;</td>
			<td style='text-align:center;'>".(($row['canceled']==1) ? "<img style='margin:0 auto;' src='/images/icons/ok.png'>" : "-" )."</td>
			<td>".((($row['dtcanceled']!="0000-00-00 00:00:00")and($row['dtcanceled']!=null)) ? date("d.m.Y",strtotime($row['dtcanceled'])) : "")."&nbsp;</td>
			<td style='text-align:center;'>".(($row['closed']==1) ? "<img style='margin:0 auto;' src='/images/icons/ok.png'>" : "-" )."</td>
			<td style='text-align:center;'><a href='#' onclick='javascript:if(confirm(\"Удалить заказ №{$row['spnumber']}?\")){window.location=\"{$_SERVER['PHP_SELF']}?action=del&n={$row['id']}\";}'><img alt='Удалить заказ' title='Удалить заказ' style='margin:0 auto;' src='/images/icons/del.png'></a></td>
		</tr>";
			$fulln=$fulln+1;
			$fullprice=$fullprice+$row['price'];
			$k=$k+1;
		}
	}
	$html.="<tr style='font-weight:bold;background:#c0c0c0;'>
			<td>{$fulln}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>{$fullprice}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>";
	$html.="</table>";
	return $html;
}

function editSupplierOrder($id=0,$incoming) {
	$supplier=0;
	$uname=$spnumber=$uphone=$email=$order_id==$ordertext=$comment=$created=$issued=$dtissued=$payment=$dtpayment=$returned=$dtreturned=$canceled=$dtcanceled=$price="";
	$sstat=1;
	$srus=1;
	$html="";
	$subaction="";
	$created=date("d.m.Y");
	//print_r($_SESSION);
	$sql="SELECT * FROM Supplier_orders where id=".intval($id);
	if ($result=mysql_query($sql)) {
		if ($row=mysql_fetch_array($result)) {
			$supplier=$row['supplier_id'];
			$cardnum=$row['cardnum'];
			$spnumber=$row['spnumber'];
			$uname=$row['uname'];
			$uphone=$row['uphone'];
			$price=$row['price'];
			$ordertext=$row['ordertext'];
			$comment=$row['comment'];
			$created=($row['created']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['created'])) : "";
			$dtissued=($row['dtissued']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtissued'])) : "";
			$dtpayment=($row['dtpayment']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtpayment'])) : "";
			$dtreturned=($row['dtreturned']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtreturned'])) : "";
			$dtshipped=($row['dtshipped']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row['dtshipped'])) : "";
			$dtcanceled=(($row['dtcanceled']!="0000-00-00 00:00:00")&&($row['dtcanceled']!=null)) ? date("d.m.Y",strtotime($row['dtcanceled'])) : "";
			$issued=($row['issued']==1) ? "checked" : "";
			$shipped=($row['shipped']==1) ? "checked" : "";
			$payment=($row['payment']==1) ? "checked" : "";
			$returned=($row['returned']==1) ? "checked" : "";
			$canceled=($row['canceled']==1) ? "checked" : "";
			$closed=($row['closed']==1) ? "checked" : "";
		}
	}
	//$id=1;
	$h3="";
	if ($id==0) {
		$id=getLastInsertID("Supplier_orders","id") + 1;
		$subaction="insert";
		$h3="<h3>Добавление нового заказа</h3>";
	} else {
		$subaction="update";
		$h3="<h3>Редактирование заказа #{$id}</h3>";
	}

	$html.="{$h3}
	<form action='{$_SERVER["REQUEST_URI"]}' method='post'>
	<input type='hidden' value='{$id}' name='id'>
	<input type='hidden' value='save' name='action' id='action'>
	<input type='hidden' value='{$subaction}' name='subaction' id='subaction'>
	<table border='1' cellpadding='2' cellspacing='0'>
	<tr><td style='text-align:right;'>Номер поставщика</td><td colspan='3'><input type='text' value='{$spnumber}' name='spnumber' id='spnumber' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Поставщик</td><td colspan='3'>".selectSupplier(array('supplier'=>$supplier))."</td></tr>
	<tr><td style='text-align:right;'>ФИО</td><td colspan='3'><input type='text' value='{$uname}' name='uname' id='uname' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Телефон</td><td colspan='3'><input type='text' value='{$uphone}' name='uphone' id='uphone' style='width:700px;'></td></tr>
	<tr><td style='text-align:right;'>Сумма</td><td colspan='3'><input type='text' value='{$price}' name='price' id='price' style='width:100px;'></td></tr>
	<tr><td style='text-align:right;'>Заказ</td><td colspan='3'><textarea rows='5' name='ordertext' id='ordertext' style='width:700px;'>{$ordertext}</textarea></td></tr>
	<tr><td style='text-align:right;'>Комментарий</td><td colspan='3'><textarea name='comment' id='comment' style='width:700px;'>{$comment}</textarea></td></tr>
	<tr><td style='text-align:right;'>Дата создания</td>
		<td colspan='3'><input type='text' value='".date("d.m.Y",strtotime($created))."' name='created' id='created' style='width:100px' class='datepickerTimeField'></td></tr>
	<tr><td style='text-align:right;'>Отгружен</td>
		<td style='width:30px;'><input type='checkbox' value='1' name='shipped' id='shipped' onclick='setDate(4);' {$shipped}></td>
		<td style='text-align:right;width:100px;'>Дата отгрузки</td>
		<td><input type='text' value='".(($dtshipped) ? date("d.m.Y",strtotime($dtshipped)) : "")."' name='dtshipped' id='dtshipped' style='width:100px;' class='datepickerTimeField'></td></tr>
	<tr><td style='text-align:right;'>Выдан</td>
		<td style='width:30px;'><input type='checkbox' value='1' name='issued' id='issued' onclick='setDate(1);' {$issued}></td>
		<td style='text-align:right;width:100px;'>Дата выдачи</td>
		<td><input type='text' value='".(($dtissued) ? date("d.m.Y",strtotime($dtissued)) : "")."' name='dtissued' id='dtissued' style='width:100px;' class='datepickerTimeField'>
		<a href='#' onclick='freeDate(1);'><img src='/images/icons/del.png' width='16'></a>
		</td></tr>
	<tr><td style='text-align:right;'>Оплачен</td>
		<td style='width:30px;'><input type='checkbox' value='1' name='payment' id='payment' onclick='setDate(2);' {$payment}></td>
		<td style='text-align:right;width:100px;'>Дата оплаты</td>
		<td><input type='text' value='".(($dtpayment) ? date("d.m.Y",strtotime($dtpayment)) : "")."' name='dtpayment' id='dtpayment' style='width:100px;' class='datepickerTimeField'>
		<a href='#' onclick='freeDate(2);'><img src='/images/icons/del.png' width='16'></a>
		</td></tr>
	<tr><td style='text-align:right;'>Вернуть поставщику</td>
		<td style='width:30px;'><input type='checkbox' value='1' name='returned' id='returned' onclick='setDate(3);' {$returned}></td>
		<td style='text-align:right;width:100px;'>Дата возврата</td>
		<td><input type='text' value='".(($dtreturned) ? date("d.m.Y",strtotime($dtreturned)) : "")."' name='dtreturned' id='dtreturned' style='width:100px;' class='datepickerTimeField'>
		<a href='#' onclick='freeDate(3);'><img src='/images/icons/del.png' width='16'></a>
		</td></tr>
	<tr><td style='text-align:right;'>Отменен</td>
		<td style='width:30px;'><input type='checkbox' value='1' name='canceled' id='canceled' onclick='setDate(5);' {$canceled}></td>
		<td style='text-align:right;width:100px;'>Дата отмены</td>
		<td><input type='text' value='".(($dtcanceled) ? date("d.m.Y",strtotime($dtcanceled)) : "")."' name='dtcanceled' id='dtcanceled' style='width:100px;' class='datepickerTimeField'>
		<a href='#' onclick='freeDate(5);'><img src='/images/icons/del.png' width='16'></a>
		</td></tr>

	<tr><td style='text-align:right;'>Закрыт</td>
		<td colspan='3'><input type='checkbox' value='1' name='closed' id='closed' {$closed}></td></tr>
	<tr><td style='text-align:center;' colspan='4'><input type='submit' value='Сохранить'></td></tr>
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
		case "addnew":
			$html=editSupplierOrder(0,$incoming);
			$show=0;
			break;
		case "edit":
			if (isset($incoming['id'])) {
				$html=editSupplierOrder($incoming['id'],$incoming);
			}
			$show=0;
			break;
		case "save":
			//print_r($incoming);
			if (is_numeric($incoming['id'])) {
				$created=date("Y-m-d 00:00:00",strtotime($incoming['created']));
				$dtissued=((isset($incoming['issued']))&&($incoming['issued']!="")) ? date("Y-m-d 00:00:00",strtotime($incoming['dtissued'])) : "";
				$dtpayment=((isset($incoming['dtpayment']))&&($incoming['dtpayment']!="")) ? date("Y-m-d 00:00:00",strtotime($incoming['dtpayment'])) : "";
				$dtreturned=((isset($incoming['dtreturned']))&&($incoming['dtreturned']!="")) ? date("Y-m-d 00:00:00",strtotime($incoming['dtreturned'])) : "";
				$dtshipped=((isset($incoming['dtshipped']))&&($incoming['dtshipped']!="")) ? date("Y-m-d 00:00:00",strtotime($incoming['dtshipped'])) : "";
				$dtcanceled=((isset($incoming['dtcanceled']))&&($incoming['dtcanceled']!="")) ? date("Y-m-d 00:00:00",strtotime($incoming['dtcanceled'])) : "";
				//echo $created."<br>";
				if ($incoming['subaction']=="insert") {
					$sql="INSERT INTO Supplier_orders (supplier_id,spnumber,uname,uphone,ordertext,comment,price,created,issued,dtissued,shipped,dtshipped,payment,dtpayment,returned,dtreturned,canceled,dtcanceled,closed)
							VALUES (".intval($incoming['supplier']).",
								'".quot_smart($incoming['spnumber'])."',
								'".quot_smart($incoming['uname'])."',
								'".quot_smart($incoming['uphone'])."',
								'".quot_smart($incoming['ordertext'])."',
								'".quot_smart($incoming['comment'])."',
								".str_replace(",",".",$incoming['price']).",
								'{$created}',
								".(((isset($incoming['issued']))&&($incoming['issued']==1)) ? "1" : "0").",
								'{$dtissued}',
								".(((isset($incoming['shipped']))&&($incoming['shipped']==1)) ? "1" : "0").",
								'{$dtshipped}',
								".(((isset($incoming['payment']))&&($incoming['payment']==1)) ? "1" : "0").",
								'{$dtpayment}',
								".(((isset($incoming['returned']))&&($incoming['returned']==1)) ? "1" : "0").",
								'{$dtreturned}',
								".(((isset($incoming['canceled']))&&($incoming['canceled']==1)) ? "1" : "0").",
								'{$dtcanceled}',
								".(((isset($incoming['closed']))&&($incoming['closed']==1)) ? "1" : "0").")";
				} elseif ($incoming['subaction']=="update") {
					$sql="UPDATE Supplier_orders SET 
							supplier_id=".intval($incoming['supplier']).",
							spnumber='".quot_smart($incoming['spnumber'])."',
							uname='".quot_smart($incoming['uname'])."',
							uphone='".quot_smart($incoming['uphone'])."',
							ordertext='".quot_smart($incoming['ordertext'])."',
							comment='".quot_smart($incoming['comment'])."',
							price=".str_replace(",",".",$incoming['price']).",
							created='{$created}',
							shipped=".(((isset($incoming['shipped']))&&($incoming['shipped']==1)) ? "1" : "0").",
							dtshipped='{$dtshipped}',
							issued=".(((isset($incoming['issued']))&&($incoming['issued']==1)) ? "1" : "0").",
							dtissued='{$dtissued}',
							payment=".(((isset($incoming['payment']))&&($incoming['payment']==1)) ? "1" : "0").",
							dtpayment='{$dtpayment}',
							returned=".(((isset($incoming['returned']))&&($incoming['returned']==1)) ? "1" : "0").",
							dtreturned='{$dtreturned}',
							canceled=".(((isset($incoming['canceled']))&&($incoming['canceled']==1)) ? "1" : "0").",
							dtcanceled='{$dtcanceled}',
							closed=".(((isset($incoming['closed']))&&($incoming['closed']==1)) ? "1" : "0")."
						WHERE id=".intval($incoming['id']);
				} else {
					break;
				}
				//echo $sql."<br>";
				if (!mysql_query($sql)) {
					die(mysql_error());
				}
				$html.="<p><b>Изменения сохранены.</b></p><p><a href='".$_SERVER['PHP_SELF']."'>Вернуться в список заказов.</a></p>";
			}
			$show=0;
			break;
		case "del":
			if (isset($incoming["n"])) {
				$sql="DELETE FROM Supplier_orders WHERE id=".intval($incoming['n']);
				if ($sql) {
					if (!mysql_query($sql)) {
						die(mysql_error());
					}
					$html.="<p><b>Заказ удален.</b></p>";
				}
				$html.="<p><a href='".$_SERVER['PHP_SELF']."'>Вернуться в список заказов.</a></p>";
			}
			$show=0;
			break;
		
		default:
			$html=getSupplierOrdersList($incoming);
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Поставщики. Заказы</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script type='text/javascript'>
	function setDate(num) {
		var tmp=new Date();
		if (num==1) {
			document.getElementById("dtissued").value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
		if (num==2) {
			document.getElementById("dtpayment").value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
		if (num==3) {
			document.getElementById("dtreturned").value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
		if (num==4) {
			document.getElementById("dtshipped").value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
		if (num==5) {
			document.getElementById("dtcanceled").value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
		return true;
	}
	function freeDate(num) {
		//var tmp=new Date();
		if (num==1) {
			document.getElementById("dtissued").value="";
		}
		if (num==2) {
			document.getElementById("dtpayment").value="";
		}
		if (num==3) {
			document.getElementById("dtreturned").value="";
		}
		if (num==4) {
			document.getElementById("dtshipped").value="";
		}
		if (num==5) {
			document.getElementById("dtcanceled").value="";
		}
		return true;
	}
	</script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	//print_r($_SERVER);
	
?>
	<h1>Поставщики. Заказы [<a href='<?php echo $_SERVER["PHP_SELF"]; ?>?action=addnew'>новый</a>]</h1>
<?php
if ($show) {
?>	
	<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Поставщик</td><td><?php echo selectSupplier($incoming); ?></td>
	<td colspan="2">
	<table cellpadding="0" cellspacing="5" border="0"><tr>
		<td>с</td>
		<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.".date("m.Y") ?>" class="datepickerTimeField"></td>
		<td>по</td>
		<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
	</tr></table>
	</td></tr>
	<tr><td>ФИО</td>
		<td><input type="text" name="uname" value="<?php echo (isset($incoming['uname']) ? $incoming['uname'] : ""); ?>"></td>
		<td>Телефон</td>
		<td><input type="text" name="uphone" value="<?php echo (isset($incoming['uphone']) ? $incoming['uphone'] : ""); ?>"></td></tr>
	<tr>
		<td><input type="checkbox" value="1" name="issued" <?php echo ((isset($incoming['issued'])) ? "checked" : ""); ?>>выдан</td>
		<td><input type="checkbox" value="1" name="payment" <?php echo ((isset($incoming['payment'])) ? "checked" : ""); ?>>оплачен</td>
		<td><input type="checkbox" value="1" name="returned" <?php echo ((isset($incoming['returned'])) ? "checked" : ""); ?>>возвращен</td>
		<td><input type="checkbox" value="1" name="canceled" <?php echo ((isset($incoming['canceled'])) ? "checked" : ""); ?>>отменен</td>
	</tr>
	<tr>
		<td colspan='2'><input type='checkbox' value='1' name='state1' <?php echo ((isset($incoming['state1'])) ? "checked" : ""); ?>>Выдан, не оплачен</td>
		<td colspan='2'><input type='checkbox' value='1' name='state2' <?php echo ((isset($incoming['state2'])) ? "checked" : ""); ?>>Отгружен, не выдан </td>
	</tr>
	<tr><td colspan="4"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>
	<?php 
	}
	echo "<br clear='both'>";
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

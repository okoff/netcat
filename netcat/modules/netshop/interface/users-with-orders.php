<?php
// 26.09.2018 Elen
// список пользователей с заказами
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-mysqli.php");
include_once ("utils-template.php");
//include_once ("utils-conv.php");
include_once ("cdek-api.php");

function getOrderCostli($con, $oid) {
	$cost=0;
	$sql="SELECT SUM(ItemPrice*Qty) AS cost FROM Netshop_OrderGoods WHERE Order_ID=".$oid;
	$result=db_query($con, $sql);
	while ($row = mysqli_fetch_array($result)) {
		$cost=$row['cost'];
	}
	$sql="SELECT Discount_Sum FROM Netshop_OrderDiscounts WHERE Order_ID=".$oid." AND Item_Type=0 AND Item_ID=0 ";
	$result=db_query($con, $sql);
	while ($row = mysqli_fetch_array($result)) {
		$cost=$cost-$row['Discount_Sum'];
	}
	return $cost;
}

function allUsers($con,$incoming) {
	// courier
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td>#</td>
			<td>ФИО</td>
			<td>email</td>
			<td>кол-во заказов</td>
			<td>регион, город</td>
			<td>стоимость заказа, руб</td>
		</tr>";//<h3>Вызов курьера</h3>";
	$olduid = 0;
	$norder = 0;
	$strorder="";
	$costOrder=0;
	$sqlorders = "SELECT Message_ID, User_ID, ContactName, Email, Region, Town FROM Message51 
					WHERE Status=4 AND Created BETWEEN '2009-01-01 00:00:00' AND '2018-09-26 00:00:00'
					AND not Email like ''
					ORDER BY Email ASC";
	$result1=db_query($con,$sqlorders);
	while ($row1 = mysqli_fetch_array($result1)) {
		if (($olduid == $row1['Email'])&&($row1['Email']!=""))  {
			$norder = $norder + 1;
			$costOrder = $costOrder + getOrderCostli($con, $row1['Message_ID']);
			$tblrow="";
		} else {
			$html.=$tblrow;
			$norder = 1;
			$costOrder = getOrderCostli($con, $row1['Message_ID']);
			
		}
		$town = $row1["Town"];
		if (strpos($row1["Town"], ",")>1) {
			$tmp = explode(",", $row1["Town"]); 
			$town = $tmp[1];
		}
		
		$tblrow="<tr><td>".$row1['Message_ID']."</td>
			<td>".convstr($row1['ContactName'])."</td>
			<td>".$row1['Email']."</td>
			<td>".$norder."</td>
			<td>".(($row1['Region']!="") ? convstr($row1['Region']) : convstr($town))."</td>
			<!--td>".convstr($row1['Region'])." ".convstr($row1['Town'])."</td-->
			<td>".$costOrder."</td>
		</tr>";
		$olduid = $row1['Email'];
	}

	$html.="</table>";
	
	return $html;
}


isLogged();

$incoming = parse_incoming();
$con=db_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);

echo printHeader("Список пользователей");
//echo printTopMenu($con);

//$html = "<h1>Пользователи с заказами</h1>";
$html.= allUsers($con, $incoming);
echo $html; 


db_close($con);
printFooter();
?>

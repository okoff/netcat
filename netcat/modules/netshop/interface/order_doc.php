<?

  // do output only if invoked from add/edit page
//  if (preg_match("{/(add|message)\.php$}", $_SERVER["SCRIPT_NAME"], $script_name_regs) ||
//      preg_match("{/(add|message)\.php$}", $_SERVER["PATH_INFO"], $script_name_regs))
//  {

	//global $GLOBALS['shop']; //$shop;
	//include ("../function.inc.php");
	include ("../../../../vars.inc.php");
	
	$connection_id = mysql_connect( $MYSQL_HOST,
									$MYSQL_USER,
									$MYSQL_PASSWORD);
	if (!$connection_id) {
		die('Could not connect: ' . mysql_error());
	}
	mysql_query("SET CHARACTER SET cp1251");
	$db_selected = mysql_select_db($MYSQL_DB_NAME, $connection_id);
	
	$message = intval("0" . $_GET['message']);
	if ($message==0) die("������ ������ ���");
	
	$query = "SELECT Message51.*, Message56.Name AS DeliveryName, Message55.Name AS PaymentName FROM Message51 
			INNER JOIN Message56 ON (Message56.Message_ID=Message51.DeliveryMethod)
			INNER JOIN Message55 ON (Message55.Message_ID=Message51.PaymentMethod)
			WHERE Message51.Message_ID=".$message;
	$res = mysql_query($query, $connection_id);
	$row = mysql_fetch_array($res, MYSQL_ASSOC);
	//print_r($row);
	
	$delivery_cost = $row['DeliveryCost'];
		
		

?>
<html>
<head>
	<style>
	body, td {
		font:normal 12px/14px Arial;
	}
	h1 {
		text-align:center;
	}
	</style>
</head>
<body>
	<h1>��������� �<?php echo $message; ?>  �� <?php echo date("d.m.Y", strtotime($row['Created'])); ?></h1>
	<table cellpadding="5" cellspacing="0" border="1" style="margin:0 auto;">
	<tr>
		<td><b>������ ��������:</b></td>
		<td><?php echo $row['DeliveryName']; ?></td>
	</tr>
	<tr>
		<td><b>������ ������:</b></td>
		<td><?php echo $row['PaymentName']; ?></td>
	</tr>	
	<tr>
		<td colspan='2'><b><?php echo $row['ContactName']; ?></b></td>
	</tr>
	<tr>
		<td><b>E-mail:</b></td>
		<td><?php echo $row['Email']; ?></td>
	</tr>
	<tr>
		<td valign="top"><b>�����:</b></td>
		<td>�������� ������: <?php echo $row['PostIndex']; ?><br>
			������: <?php echo $row['country']; ?><br>
			�������: <?php echo $row['Region']; ?> <br>
			�����: <?php echo $row['Town']; ?><br>
			�����: <?php echo $row['Street']; ?><br>
			���: <?php echo $row['House']; ?><br> 
			��������/����: <?php echo $row['Flat'];?> </td>
	</tr>
	<tr>
		<td><b>�������:</b></td>
		<td><?php echo $row['Phone']; ?></td>
	</tr>
	<tr>
		<td><b>�����������:</b></td>
		<td><?php echo $row['Comments']; ?>&nbsp;</td>
	</tr>
	</table>
	<br><br>
	<h1>������ ������</h1>
	<?php
	$query = "SELECT Netshop_OrderGoods.*,Message57.Name AS ItemName FROM Netshop_OrderGoods
			INNER JOIN Message57 ON (Message57.Message_ID=Netshop_OrderGoods.Item_ID)
			WHERE Netshop_OrderGoods.Order_ID=".$message;
	$res = mysql_query($query, $connection_id);
	?>
	<table cellpadding="3" cellspacing="0" border="1" style="margin:0 auto;">
	<tr>
		<td bgcolor="#c8c8c8"><b>#</b></td>
		<td bgcolor="#c8c8c8"><b>�������� ������</b></td>
		<td bgcolor="#c8c8c8"><b>����������</b></td>
		<td bgcolor="#c8c8c8"><b>���� (���.)</b></td>
		<td bgcolor="#c8c8c8"><b>����� (���.)</b></td>
	</tr>
	<?php
	$i=1;
	$fullcost=0;
	while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
		//print_r($row)."<br>";
		echo "<tr>";
		echo "<td>{$i}</td>";
		echo "<td>{$row['ItemName']}</td>";
		echo "<td align='right'>{$row['Qty']}</td>";
		echo "<td>{$row['ItemPrice']}</td>";
		echo "<td>".($row['ItemPrice']*$row['Qty'])."</td>";
		echo "</tr>";
		$i++;
		$fullcost = $fullcost + $row['ItemPrice']*$row['Qty'];
	}
	?>
	<tr>
		<td align="right" colspan="4"><b>��������� ������:</b></td>
		<td><?php echo $fullcost; ?></td>
	</tr>
	<tr>
		<td align="right" colspan="4"><b>��������� ��������:</b></td>
		<td><?php echo $delivery_cost; ?></td>
	</tr>
	<tr>
		<td align="right" colspan="4"><b>�����:</b></td>
		<td><?php echo ($delivery_cost + $fullcost); ?></td>
	</tr>
	</table>
	<br><br>
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td align="left"><br><br><br>
		________________________________________<br>
		������� �����������.
		</td>
		<td align="right">
		����� �������.<br>
		��������� �� ���������� � �������� ���.<br><br>
		________________________________________<br>
		������� �������.
		</td>
	</tr>
	</table>
</body>
</html>
<?php
	mysql_close($connection_id);
?>
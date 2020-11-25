<?

include_once ("utils.php");
include_once ("utils-complect.php");
include_once ("../../../../vars.inc.php");

$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
mysql_set_charset("cp1251", $con);

$incoming = parse_incoming();
if (!isset($incoming['id'])) {
	die("Не указан номер заказа.");
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Списание товара</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	
	<script type="text/javascript">
		//setTimeout('window.location.replace("/netcat/message.php?catalogue=1&sub=57&cc=53&message=<?php echo $incoming['id']; ?>")', 5000);
		
	</script>
</head>
<body>
<?php

if (isset($incoming['action'])) {
	switch ($incoming['action']) {
		case "off":
			// списать товар
			// проверяем статус заказа
			$okstatus=1;
			$sql="SELECT writeoff FROM Message51 WHERE Message_ID={$incoming['id']}";
			if ($result=mysql_query($sql)) {
				if($row = mysql_fetch_array($result)) {
					if ($row['writeoff']==1) {
						echo "<b style='color:#f30000;'>По заказу #{$incoming['id']} уже было проведено списание.</b><br>";
						$okstatus=0;
						//break;
					}
				}
			}
			// проверяем наличие 
			$ok=0;
			if ($okstatus==1) {
				$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID={$incoming['id']}";
				if ($result=mysql_query($sql)) {
					while($row = mysql_fetch_array($result)) {
						$sql="SELECT StockUnits, status, Name,complect,Message_ID FROM Message57 WHERE Message_ID={$row['Item_ID']}";
						if ($res=mysql_query($sql)) {
							if ($row1=mysql_fetch_array($res)) {
								if ($row1["complect"]!="") {
									$rescheck=checkFullComplect($row1['complect'],$itm[1]);
									if ($rescheck!=1) {
										$ok=0;
									} else {
										$ok=1;
									}
								} else {
									if ($row1["StockUnits"]<$row['Qty']) {
										// ОШИБКА
										$ok=0;
										echo ("<b style='color:#f30000;'>На складе нет товара ".iconv("windows-1251","utf-8",$row1['Name']).". Списание не возможно.</b><br>");
									} else {
										$ok=1;									
									}
								}
							}
						} else {
							echo "<b style='color:#f30000;'>Товар с артикулом {$row['Item_ID']} не найден.</b><br>";
							$ok=0;
						}
						
					}
				}
				// списываем
				if ($ok==1) {
					$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID={$incoming['id']}";
					if ($result=mysql_query($sql)) {
						while($row = mysql_fetch_array($result)) {
							$sql="SELECT StockUnits, status, Name,complect,Message_ID FROM Message57 WHERE Message_ID={$row['Item_ID']}";
							if ($res=mysql_query($sql)) {
								if ($row1=mysql_fetch_array($res)) {
									if ($row1["complect"]!="") {
										echo "Состав комплекта: ".$row1["complect"]."<br>";
										$rescheck=checkFullComplect($row1['complect'],$itm[1]);
										if ($rescheck!=1) {
											//echo $rescheck."!";
											$ok=0;
										} else {
											// списание комплекта
											if (writeoffComplect($row1['Message_ID'],$row1['complect'],$row['Qty'],0,$incoming['id'])==1) {
												echo "<p>Комплект <b>".iconv("windows-1251","utf-8",$row1['Name'])."</b> списан.</p>";
												
											}
										}
									} else {
										if ($row1["StockUnits"]<$row['Qty']) {
											// ОШИБКА
											$ok=0;
											echo ("<b style='color:#f30000;'>На складе нет товара ".iconv("windows-1251","utf-8",$row1['Name']).". Списание не возможно.</b>");
										} else {
											if ($row1["StockUnits"]>$row['Qty']) {
												// просто обновить количество
												$sql="UPDATE Message57 SET StockUnits=StockUnits-{$row['Qty']} WHERE Message_ID={$row['Item_ID']}";
												if (!mysql_query($sql)) {
													die("(1)".$sql."<br>Error: ".mysql_error());
												}
											}
											if ($row1["StockUnits"]==$row['Qty']) {
												// обновить количество и изменить статус 
												$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- ".addslashes($row1['Name'])."' WHERE Message_ID={$row['Item_ID']}";
												//echo $sql."<br>";
												//mysql_query($sql);
												if (!mysql_query($sql)) {
													die("(2)".$sql."<br>Error: ".mysql_error());
												}
											}
											echo "Товар ".iconv("windows-1251","utf-8",$row1['Name'])." списан.<br>";
											
										}
									}
								}
								if ($ok==1) {
									$sql="INSERT INTO Writeoff (order_id, item_id, qty,created) VALUES ({$incoming['id']}, {$row['Item_ID']}, {$row['Qty']},'".date("Y-m-d H:i:s")."')";
									//mysql_query($sql);
									if (!mysql_query($sql)) {
										die("(3)".$sql."<br>Error: ".mysql_error());
									}
								}
							} else {
								echo "<b style='color:#f30000;'>Товар с артикулом {$row['Item_ID']} не найден.</b><br>";
								$ok=0;
							}
							
						}
						if ($ok==1) {
							$sql="UPDATE Message51 SET writeoff=1 WHERE Message_ID={$incoming['id']}";
							//echo $sql."<br>";
							//mysql_query($sql);
							if (!mysql_query($sql)) {
								die($sql."<br>Error: ".mysql_error());
							}
							
						}
					} else {
						die("Ошибка: {$sql} " . mysql_error());
					}
				}
			}
			break;
		case "on":
			// отменить списание
			$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID={$incoming['id']}";
			if ($result=mysql_query($sql)) {
				while($row = mysql_fetch_array($result)) {
					$sql="SELECT StockUnits, status, Name,complect FROM Message57 WHERE Message_ID={$row['Item_ID']}";
					if ($res=mysql_query($sql)) {
						if ($row1=mysql_fetch_array($res)) {
							if ($row1["complect"]!="") {
								$arset=explode(";",$row1['complect']);
								//print_r($arset);
								foreach($arset as $ars) {
									$ars=str_replace(" ","",$ars);
									if ($ars!="") {
										$t=explode(":",$ars);
										$sql="SELECT Message_ID,StockUnits,ItemID,status, Name,complect FROM Message57 WHERE ItemID LIKE '{$t[0]}'";
										//echo $sql;
										if ($res1=mysql_query($sql)) {
											if ($row2=mysql_fetch_array($res1)) {
												if ($row2['StockUnits']>0) {
													// просто обновить количество
													$sql="UPDATE Message57 SET StockUnits=".($row2['StockUnits']+$t[1])." WHERE Message_ID={$row2['Message_ID']}";
													//echo $sql."<br>";
													if (!mysql_query($sql)) {
														die($sql."<br>Error: ".mysql_error());
													}
												}
												if ($row2["StockUnits"]==0) {
													// обновить количество и изменить статус 
													$name=$row2['Name'];
													$name=trim($name);
													(substr($row2['Name'],0,1)=="-") ? $name=substr($row2['Name'], 1) : "" ;
													(substr($row2['Name'],0,1)==".") ? $name=substr($row2['Name'], 1) : "" ;
													//(substr($name,1,1)==" ") ? $name=substr($name, 1) : "" ;
													$name=trim($name);
													$sql="UPDATE Message57 SET StockUnits={$t[1]}, status=2, Name='".addslashes($name)."' WHERE Message_ID={$row2['Message_ID']}";
													//echo $sql."<br>";
													if (!mysql_query($sql)) {
														die($sql."<br>Error: ".mysql_error());
													}
												}
												echo "Списание по товару [{$row2['ItemID']}] ".iconv("windows-1251","utf-8",$row2['Name'])." в комплекте отменено.<br>";
												$sql="UPDATE Message51 SET writeoff=0 WHERE Message_ID={$incoming['id']}";
												//echo $sql."<br>";
												if (!mysql_query($sql)) {
													die($sql."<br>Error: ".mysql_error());
												}
											}
										}
									}
								}
							} else {
								//echo $row1["StockUnits"]."<br>";
								if ($row1["StockUnits"]>0) {
									// просто обновить количество
									$sql="UPDATE Message57 SET StockUnits=StockUnits+{$row['Qty']} WHERE Message_ID={$row['Item_ID']}";
									//echo $sql."<br>";
									mysql_query($sql);
								}
								if ($row1["StockUnits"]==0) {
									// обновить количество и изменить статус 
									$name=$row1['Name'];
									$name=trim($name);
									(substr($row1['Name'],0,1)=="-") ? $name=substr($row1['Name'], 1) : "" ;
									(substr($row1['Name'],0,1)==".") ? $name=substr($row1['Name'], 1) : "" ;
									//(substr($name,1,1)==" ") ? $name=substr($name, 1) : "" ;
									$name=trim($name);
									$sql="UPDATE Message57 SET StockUnits={$row['Qty']}, status=2, Name='".addslashes($name)."' WHERE Message_ID={$row['Item_ID']}";
									//echo $sql."<br>";
									if (!mysql_query($sql)) {
										die($sql."<br>Error: ".mysql_error());
									}
								}
								echo "Списание по товару ".iconv("windows-1251","utf-8",$row1['Name'])." отменено.<br>";
								$sql="UPDATE Message51 SET writeoff=0 WHERE Message_ID={$incoming['id']}";
								//echo $sql."<br>";
								if (!mysql_query($sql)) {
									die($sql."<br>Error: ".mysql_error());
								}
							}
						}
					}
					
				}
			} else {
				die("Ошибка: {$sql} " . mysql_error());
			}
			break;
	}
}

mysql_close($con);
?>
<p><b><a href="/netcat/message.php?catalogue=1&sub=57&cc=53&message=<?php echo $incoming['id']; ?>">Вернуться в заказ</a></b></p>
</body>
</html>
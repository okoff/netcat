<?php
// 17.01.2014 Elen
// создание накладной 
include_once ("../../../../vars.inc.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------


// get discount for an item
// @id = Message_ID in Message57

function printCrtHold($str,$id,$wbtype=1) {
	$html=$wbtype=$wbtypename="";
	$tmp=array();
	$tmp=explode(";",$str);
	//echo $wbtype."<br><br>";
	//print_r($tmp);
	//echo $str."<br><br>";
	$total=$total1=$totalqty=0;
	$j=$m=0;
	$k=1;
	$_SESSION['items']="";
	
	$sql="SELECT Waybills.*,Waybills_type.name AS TypeName FROM Waybills 
				INNER JOIN Waybills_type ON (Waybills.type_id=Waybills_type.id) 
				WHERE Waybills.id=".$id;
		//echo $sql;
	if ($res=mysql_query($sql)) {
		if ($row=mysql_fetch_array($res)) {
			$wbtype=$row['type_id'];
			$wbtypename=$row['TypeName'];
		}
	}
	$html.="<h1>Накладная #{$id} {$wbtypename}</h1>";
		
	$html.="
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td><b>#</b></td>
		<td><b>Артикул</b></td>
		<td><b>Название</b></td>
		<td><b>Кол.</b></td>
		<td><b>На складе</b></td>
		<td><b>Цена<br>отпускная</b></td>
		<td><b>Цена<br>розничная</b></td>
		<td><b>% наценки</b></td>
		<td><b>Сумма<br>отпускная</b></td>
		<td><b>Сумма<br>розничная</b></td>";
	if ($wbtype==2) {
		$html.="
		<td><b>Списано</b></td>
		<td><b>Оплачено</b></td>";
	} else {
		//$html.="<td><b>Удалить</b></td>";
	}
	$html.="
		
	</tr>";
	$html1="";
	$dsbl=($wbtype==2) ? "disabled" : "";
	$m=count($tmp)-1;
	foreach($tmp as $t) {
	//while ($m>=0) {
		//$t=$tmp[$m];
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[0]==$id) {
				//if ($itm[2]!=0) {
					$result = mysql_query("SELECT * FROM Message57 WHERE Message_ID=".$itm[1]);
					while($row = mysql_fetch_array($result)) {
						$discount=getDiscount($row['Message_ID'], $row['Price']);
						$iprice1=($itm[3]) ? $itm[3] : getPrice1($row['Message_ID']); // отпускная цена
						$iprice=($itm[4]) ? $itm[4] : $row['Price']; // розничная цена
						//($row['video']) ? $bk="style='background:#addfad;'" : $bk="";
						$html1.="<tr ".(($row['Checked']==0) ? "style='background:#b0b0b0;'" : "").">
						<td><b>".($m)."</b></td>
						<td {$bk}><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>".$row['ItemID']."</a>[{$row['Message_ID']}]</td>";
						//$image_arr = explode(":", $row['Image']);
						//$image_url = "/netcat_files/".$image_arr[3];
						//$html.="<td><img src='".$image_url."' width='300'></td>";
						$html1.="<td>{$row['Name']}</td>";
						//$html.="<td style='text-align:right;'>{$row['Price']}</td>";
						$html1.="<td>{$itm[2]}</td>";
						$html1.="<td style='text-align:right;'>{$row['StockUnits']}</td>";
						$html1.="<td>{$iprice1}</td>";
						$html1.="<td>{$iprice}</td>";
						$html1.="<td align='right'>".number_format(($iprice*100/$iprice1-100),2,'.',' ')."</td>";
						$html1.="<td style='text-align:right;width:70px;'>".($iprice1*$itm[2])."</td>";
						$html1.="<td style='text-align:right;width:70px;'>".($iprice*$itm[2])."</td>";
						$total=$total+$iprice*$itm[2];
						$total1=$total1+$iprice1*$itm[2];
						$totalqty=$totalqty+$itm[2];
						if ($wbtype==2) {
							$sqlq="SELECT sales,paid FROM Waybills_goods WHERE waybill_id={$id} AND item_id={$row['Message_ID']}";
							//echo $sqlq."<br>";
							if ($rq=mysql_query($sqlq)) {
								while ($rwq=mysql_fetch_array($rq)) {
									//print_r($rwq);
									//echo "<br>";
									$html1.="<td style='text-align:right;'>{$rwq['sales']}</td>";
									$html1.="<td style='text-align:right;'>{$rwq['paid']}</td>";
								}
							}
						} else {
							//$html1.="<td><a href='#' onclick='delItem({$j});'><img src='/images/icons/del.png' border='0' title='Удалить' alt='Удалить'></a></td>";		
						}
						
						
						//$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
						$html1.="</tr>";
						
						//$_SESSION["items"].=$id.":".$row['Message_ID'].":".$itm[2].":".$iprice1.":".$iprice.";";
						$j=$j+1;
					}
					
				//}
			}
			$k=$k+1;
		}
		$m=$m-1;
	}
	//print_r($_SESSION);
	/*$html.="<tr>
	<td colspan='".(($wbtype==2) ? "12" : "11")."' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать' {$dsbl}>
	</td></tr>";
	
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='3' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td><td colspan='".(($wbtype==2) ? "3" : "2")."'>&nbsp;</td></tr>";
	*/
	$html.=$html1;
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='4' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td><td  colspan='".(($wbtype==2) ? "2" : "1")."'>&nbsp;</td></tr>";
	$html.="<tr>
	<td colspan='".(($wbtype==2) ? "12" : "11")."' style='text-align:center;'>
	<input type='button' id='btnSaveCart' onclick='saveCart();' value='Провести' {$dsbl}>
	</td></tr>";
	$html.="</table>";
	return $html;
}
function getCartCost($str,$id) {
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[4]*$itm[2];
				}
			}
		}
	}
	//echo $total;
	return $total;
}
function getCartCostOriginal($str,$id) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[3]*$itm[2];
				}
			}
		}
	}
	return $total;
}
function getCartQty($str,$id) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[3]*$itm[2];
				}
			}
		}
	}
	return $total;
}

// $wb_status=2 - save items count in DB
// $wb_type=9 приход по учету
// $wb_type=10 расход по учету
function saveWaybillGoodsHold($wb_id) {
	//echo "saveWaybillGoodsHold<bR>";
	$html="";
	$total=0;
	$j=0;
	$wbtype=0;
	$wbstatus=0;
	$sql="SELECT * FROM Waybills WHERE id=".$wb_id;
	if ($res=mysql_query($sql)) {
		if ($row=mysql_fetch_array($res)) {
			$wbtype=$row['type_id'];
			$wbstatus=$row['status_id'];
			if ($row['status_id']==2) {
				$html.="<p style='color:#f30000;font-weight:bold;'>Эта накладная уже проведена!</p>";
				return $html;
			}
		}
	}
	//echo $wbtype.";".$wbstatus."<br>";
	$sql="SELECT * FROM Waybills_goods WHERE waybill_id={$wb_id} ORDER BY id ASC";
	if ($res=mysql_query($sql)) {
		while ($row=mysql_fetch_array($res)) {
			$str.=$wb_id.":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
		}
	}
	//echo $str."<br>";
	$tmp=explode(";",$str);
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			// $itm[0] - waybill id 
			// $itm[1] - item id 
			// $itm[2] - qty in retail order
			// $itm[3] - приходная цена
			// $itm[4] - розничная цена
			if ($itm[0]==$wb_id) {
				//if ($itm[2]!=0) {
					// добавление  товара!
					if (($wbstatus==1)&&($wbtype==9)) {
						$sql="SELECT StockUnits, status, Name FROM Message57 WHERE Message_ID={$itm[1]}";
						if ($res=mysql_query($sql)) {
							if ($row1=mysql_fetch_array($res)) {
								if ($row1["StockUnits"]>0) {
									// просто обновить количество
									$sql="UPDATE Message57 SET StockUnits=".($row1["StockUnits"]+$itm[2]).", Price={$itm[4]} WHERE Message_ID={$itm[1]}";
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
									$sql="UPDATE Message57 SET StockUnits={$itm[2]}, status=2, Name='".$name."',Price={$itm[4]} WHERE Message_ID={$itm[1]}";
									mysql_query($sql);
								}
								//echo $sql."<br>";
								$html.="<p>Товар <b>".$row1['Name']."</b> добавлен в базу.</p>";
									
								
							}
						}
						$iprice1=getPrice1($itm[1]);
						if ($iprice1!=$itm[3]) {
							// изменилась отпускная цена
							if ($iprice1==0) {
								// новый товар, добавить цену
								$sql="INSERT INTO Message57_p (Message_ID,Price,created) VALUES ({$itm[1]},{$itm[3]},'".date("Y-m-d H:i:s")."')";
							} else {
								// обновить цену
								$sql="UPDATE Message57_p SET Price={$itm[3]}, created='".date("Y-m-d H:i:s")."' WHERE Message_ID={$itm[1]}";
							}
							//echo $sql;
							mysql_query($sql);
						}
					}
					// убавление товара при расходе по учету
					if (($wbstatus==1)&&($wbtype==10)) {
						$sql="SELECT StockUnits, status, Name FROM Message57 WHERE Message_ID={$itm[1]}";
						if ($res=mysql_query($sql)) {
							if ($row1=mysql_fetch_array($res)) {
								if (($row1["StockUnits"]!=0)&&($itm[2]!=0)) {
								if ($row1["StockUnits"]<$itm[2]) {
									// просто обновить количество
									//$sql="UPDATE Message57 SET StockUnits=".($row1["StockUnits"]+$itm[2]).", Price={$itm[4]} WHERE Message_ID={$itm[1]}";
									//mysql_query($sql);
									$html.="<p><b style='color:#f30000;font-weight:bold;'>ОШИБКА! при учете товара на складе больше, чем на сайте</b> Товар <b>".$row1['Name']."</b> отсутствует в базе.</p>";
								}
								if ($row1["StockUnits"]==$itm[2]) {
									// обновить количество и изменить статус 
									$name=$row1['Name'];
									$name="- ".trim($name);
									
									$sql="UPDATE Message57 SET StockUnits=0, status=3, Name='".$name."' WHERE Message_ID={$itm[1]}";
									mysql_query($sql);
								} 
								if ($row1["StockUnits"]>$itm[2]) {
									// обновить количество 
									$sql="UPDATE Message57 SET StockUnits=".($row1["StockUnits"]-$itm[2])." WHERE Message_ID={$itm[1]}";
									mysql_query($sql);
								} 
								//echo $sql."<br>";
								$html.="<p>Товар <b>".$row1['Name']."</b> списан из базы.</p>";
									
								}
							}
						}
						/*$iprice1=getPrice1($itm[1]);
						if ($iprice1!=$itm[3]) {
							// изменилась отпускная цена
							if ($iprice1==0) {
								// новый товар, добавить цену
								$sql="INSERT INTO Message57_p (Message_ID,Price,created) VALUES ({$itm[1]},{$itm[3]},'".date("Y-m-d H:i:s")."')";
							} else {
								// обновить цену
								$sql="UPDATE Message57_p SET Price={$itm[3]}, created='".date("Y-m-d H:i:s")."' WHERE Message_ID={$itm[1]}";
							}
							//echo $sql;
							mysql_query($sql);
						}*/
					}
				//}
			}
		}
	}
	$sql="UPDATE Waybills SET 
			status_id=2
			WHERE id=".$wb_id;
	if (!mysql_query($sql)) {	
		die($sql."Ошибка: ".mysql_error());
	}
	$_SESSION['items']="";
	return $html;
}

// save ONLY PRICES items count in DB
function saveItemsPrice($str) {
	$html="";
	$tmp=explode(";",$str);
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			// $itm[0] - item id 
			// $itm[1] - qty in retail order
			// $itm[2] - приходная цена
			// $itm[3] - розничная цена
			$sql="UPDATE Message57 SET Price={$itm[3]} WHERE Message_ID={$itm[0]}";
			//echo $sql;
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
			$iprice1=getPrice1($itm[0]);
			if ($iprice1==0) {
				// новый товар, добавить цену
				$sql="INSERT INTO Message57_p (Message_ID,Price,created) VALUES ({$itm[0]},{$itm[2]},'".date("Y-m-d H:i:s")."')";
			} else {
				// обновить цену
				$sql="UPDATE Message57_p SET Price={$itm[2]}, created='".date("Y-m-d H:i:s")."' WHERE Message_ID={$itm[0]}";
			}
			//echo $sql;
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
		}
	}
	$_SESSION['items']="";
	$html.="<p><b>Цены обновлены</b></p>";
	return $html;
}
// ------------------------------------------------------------------------------------------------

include_once ("utils.php");
include_once ("utils-waybill.php");

$incoming = parse_incoming();
//print_r($incoming);
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$userinfo="";
$cart="";
$search="";
$showall=1;
//echo $incoming['action'];
//print_r($_SESSION['items']);

if ((isset($incoming['start'])) && ($incoming['start']==1)) {
	$_SESSION['items']="";
//	$_SESSION['wbid']="";
}

(isset($_SESSION['cid'])) ? $cid=$_SESSION['cid'] : $cid=0;
(isset($_SESSION['items'])) ? $cart=printCrtHold($_SESSION['items'],$incoming['id']) : $cart="";

switch ($incoming['action']) {
	case "wbdel":
		if (!(is_numeric($incoming['n']))) {
			die("Неверный номер накладной!");
		}
		$sql="DELETE FROM Keepcount_WB WHERE id=".intval($incoming['n']);
		if (!mysql_query($sql)) {	
			die($sql."<br>Ошибка: ".mysql_error());
		}
		$html="<h1>Накладные учёта {$incoming['kc']}</h1>
		<p><a href='/netcat/modules/netshop/interface/keep-count-wbadd.php?kc={$incoming['kc']}'>добавить накладную в учет</a></p>";
		$html.=getWaybillListHold(array('kc'=>$incoming['kc'],'wbtype'=>8,'max'=>date("Y-m-d")));
		break;
	case "cart-save":
		//print_r($incoming);
		$wb_id=0;
		$wb_id=$incoming['id'];
		$wbtype=0;
		$wbtypename="";
		$items=array();
		
		$sql="SELECT Waybills.*,Waybills_type.name AS TypeName FROM Waybills 
				INNER JOIN Waybills_type ON (Waybills.type_id=Waybills_type.id) 
				WHERE Waybills.id=".$wb_id;
		//echo $sql;
		if ($res=mysql_query($sql)) {
			if ($row=mysql_fetch_array($res)) {
				$wbtype=$row['type_id'];
				$wbtypename=$row['TypeName'];
			}
		}
		$prihod=$rashod="";
		$html.="<h3>Обработка накладной <b>#{$wb_id} {$wbtypename}</b></h3>";
		
		$dte=date("Y-m-d H:i:s");
		
		if ($wbtype==8) {
			// обработка накладной УЧЁТ
			// create 2 waybills - приход по учету (9), расход по учету (10)
			$sql="INSERT INTO Waybills (created, name, type_id, status_id, vendor_id, vendor_date, payment_id, moneytype_id, paid, summoriginal,summ, comments,onsite,title,intro) 
					VALUES ('".$dte."','',9,1,10,'0000-00-00 00:00:00',1,1,0,0,0,'',0,'','')";
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
			$wb_prihod=getLastInsertID("Waybills");
			$html.="<p>Накладная: приход по учёту #{$wb_prihod} <a target='_blank' href='/netcat/modules/netshop/interface/keep-count-waybills.php?id={$wb_prihod}&action=hold'>&gt;&gt;&gt;</a></p>";
			$sql="INSERT INTO Waybills (created, name, type_id, status_id, vendor_id, vendor_date, payment_id, moneytype_id, paid, summoriginal,summ, comments,onsite,title,intro) 
					VALUES ('".$dte."','',10,1,10,'0000-00-00 00:00:00',1,1,0,0,0,'',0,'','')";
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
			$wb_rashod=getLastInsertID("Waybills");
			$html.="<p>Накладная: расход по учёту #{$wb_rashod} <a target='_blank' href='/netcat/modules/netshop/interface/keep-count-waybills.php?id={$wb_rashod}&action=hold'>&gt;&gt;&gt;</a></p>";
			$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($wb_id)." ORDER BY item_id  ASC";
			//echo $sql;
			$result=mysql_query($sql);
			$n=0;
			$j=0;
			$nqty=0;
			$k=0;
			$list=array();
			while ($row = mysql_fetch_array($result)) {
				if (($n==$row['item_id'])&&($n!=0)) {
					//echo $j."-".$row['item_id'].":".$row['qty']."-".$nqty."-".($row['qty']+$nqty)."<br>";
					$list[$j-1]=$incoming["id"].":".$row['item_id'].":".($row['qty']+$nqty).":".$row['originalprice'].":".$row['itemprice'];
				} else {
					//echo "2<br>";
					$list[$j]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'];
					$nqty=$row['qty'];		
				}
				//$sorting[$row['item_id']]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
				$n=$row['item_id'];
				$j=$j+1;
				$k=$k+1;
			}
			//print_r($list);
			//echo $k."-".count($list)."<br>";
			// 0 - waybill_id
			// 1 - item_id
			// 2 - qty
			// 3 - original price
			// 4 - item price
			for ($j=0;$j<$k;$j++) {
				if ($list[$j]) {
					$row=explode(":",$list[$j]);
					$itemid=$row[1];
					
					// текущее количество на сайте
					$n1=getItemCount($itemid);
					$items[$i]=$itemid; // список позиций в накладной учета
					//echo $itemid."-".$row[2]."-".$n1."<br>";
					// делаем расход по учету
					if ($n1>$row[2]) {
						$sql="INSERT INTO Waybills_goods (waybill_id, item_id, qty, originalprice, itemprice) 
								VALUES ({$wb_rashod}, {$itemid}, ".($n1-$row[2]).", {$row[3]}, {$row[4]})";
						if (!mysql_query($sql)) {	
							die($sql."Ошибка: ".mysql_error());
						}
						//echo $sql."<br>";
						$rashod.=$wb_rashod.":".$itemid.":".($n1-$row[2]).":".$row[3].":".$row[4].";";
					}
					// делаем приход по учету
					if ($n1<$row[2]) {
						$sql="INSERT INTO Waybills_goods (waybill_id, item_id, qty, originalprice, itemprice) 
								VALUES ({$wb_prihod}, {$itemid}, ".($row[2]-$n1).", {$row[3]}, {$row[4]})";
						if (!mysql_query($sql)) {	
							die($sql."Ошибка: ".mysql_error());
						}
						//echo $sql."<br>";
						$prihod.=$wb_prihod.":".$itemid.":".($row[2]-$n1).":".$row[3].":".$row[4].";";
					}
					$i=$i+1;
				}
			}
			// перебираем товар из базы, вдруг он есть, а  внакладную не попал. Тогда добавляем его в расход.
			$sql="SELECT Message57.Message_ID,Message57.StockUnits AS qty,Message57.Price AS itemprice,Message57_p.Price AS originalprice FROM Message57 
			RIGHT JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID) 
			ORDER BY Message_ID ASC";
			if ($result=mysql_query($sql)) {
				while($row = mysql_fetch_array($result)) {
					if ($row['Message_ID']) {
						$k=0;
						for($i=0;$i<count($items);$i++) {
							if ($items[$i]==$row['Message_ID']) {
								$k=1; // товар есть в накладной
								break;
							}
						}
						if ($k==0) {
							$sql="INSERT INTO Waybills_goods (waybill_id, item_id, qty, originalprice, itemprice) 
								VALUES ({$wb_rashod}, {$row['Message_ID']}, ".(($row['qty']) ? $row['qty'] : 0).", {$row['originalprice']}, {$row['itemprice']})";
							if (!mysql_query($sql)) {	
								die($sql."Ошибка: ".mysql_error());
							}
							//echo $sql."<br>";
							$rashod.=$wb_rashod.":".$itemid.":".(($row['qty']) ? $row['qty'] : 0).":".$row['originalprice'].":".$row['itemprice'].";";
						}
					}
				}
			}
			//echo "prihod=".$prihod."<br>";
			//echo "rashod=".$rashod."<br>";
			// считаем сумму по накладным прихода-расхода
			$summ1=getCartCost($prihod,$wb_prihod);
			$summoriginal1=getCartCostOriginal($prihod,$wb_prihod);
			$qty=getCartQty($prihod,$wb_prihod);
			$sql="UPDATE Waybills SET 
					summoriginal={$summoriginal1},
					summ={$summ1}
					WHERE id=".$wb_prihod;
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
			//echo $sql."<br>";
			$summ2=getCartCost($rashod,$wb_rashod);
			$summoriginal2=getCartCostOriginal($rashod,$wb_rashod);
			$qty=getCartQty($rashod,$wb_rashod);
			$sql="UPDATE Waybills SET 
					summoriginal={$summoriginal2},
					summ={$summ2}
					WHERE id=".$wb_rashod;
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
			//echo $sql."<br>";	
			$sql="UPDATE Waybills SET 
					status_id=2
					WHERE id=".$wb_id;
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
		}
		if (($wbtype==9)||($wbtype==10)) {
			// обработка накладных ПРИХОД ПО УЧЁТУ и РАСХОД ПО УЧЁТУ
			$html.=saveWaybillGoodsHold($wb_id); 
		}
		$html.="<p style='color:#f30000;'>Обработка накладной <b>#{$wb_id} {$wbtypename}</b> закончена.</p>";
		$_SESSION['items']="";
		$showall=0;
		break;
	case "hold":
		if ((isset($incoming['id'])) && ($incoming['id'])) {
			//$_SESSION['wbid']=$incoming['id'];
			$qty=array();
			$items=array();
			$str="";
			$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($incoming['id'])." ORDER BY item_id  ASC";
			//echo $sql;
			$result=mysql_query($sql);
			$n=0;
			$j=0;
			$nqty=0;
			$k=0;
			while ($row = mysql_fetch_array($result)) {
				if (($n==$row['item_id'])&&($n!=0)) {
					//echo $j."-".$row['item_id'].":".$row['qty']."-".$nqty."-".($row['qty']+$nqty)."<br>";
					$items[$j-1]=$incoming["id"].":".$row['item_id'].":".($row['qty']+$nqty).":".$row['originalprice'].":".$row['itemprice'];
				} else {
					//echo "2<br>";
					$items[$j]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'];
					$qty[$j]=$row['qty'];
					$nqty=$row['qty'];
					
				}
				//$sorting[$row['item_id']]=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
				$n=$row['item_id'];
				$j=$j+1;
				$k=$k+1;
			}
			//print_r($qty);
			//print_r($items);
			for ($j=0;$j<$k;$j++) {
				if ($items[$j]) {
					$str.=$items[$j].";";
				}
			}
			//echo $str;
			$html=printCrtHold($str,$incoming['id'],$incoming['wbtype']);
			//$html=printCrtHold($newstr,$incoming['id'],$incoming['wbtype']);
		}
		break;
	default: 
		$html="<h1>Накладные учёта {$incoming['kc']}</h1>
		<p><a href='/netcat/modules/netshop/interface/keep-count-wbadd.php?kc={$incoming['kc']}'>добавить накладную в учет</a></p>";
		$html.=getWaybillListHold(array('kc'=>$incoming['kc'],'wbtype'=>8,'max'=>date("Y-m-d")));
		
		//(isset($incoming['wbtype'])) ?  "" : $incoming['wbtype']=1;
		//if (isset($_SESSION['items'])) {
		//	$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['wbtype']);
		//}
		break;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Накладные учёта.</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript">
		function showAnonim() {
			document.getElementById("addclient").style.display="none";
			document.getElementById("findclient").style.display="none";
			document.getElementById('action').value='cli-anonim';
			document.getElementById("frm").submit();
		}
		function showAddNewClient() {
			document.getElementById("addclient").style.display="block";			
			document.getElementById("findclient").style.display="none";
		}
		function submitAddClient() {
			document.getElementById('action').value='cli-add';
			document.getElementById("frm").submit();
		}
		function showFindClient() {
			document.getElementById("findclient").style.display="block";
			document.getElementById("addclient").style.display="none";
		}
		function submitFindClient() {
			document.getElementById('action').value='cli-search';
			document.getElementById("frm").submit();
		}
		function addToOrder(mesid, price1, price) {
			//alert(mesid);
			document.getElementById('action').value='item-add';			
			document.getElementById('scharticul').value=mesid;
			document.getElementById('schprice').value=price;
			document.getElementById('schitemprice').value=price1;
			document.getElementById("frm").submit();
		}
		function delItem(itemid) {
			document.getElementById('qty_'+itemid).value=0;
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function recalcCart() {
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function rewritePrices() {
			document.getElementById('action').value='cart-rewrite';
			document.getElementById('frm').submit();
		}
		function saveCart() {
			document.getElementById('action').value='cart-save';
			document.getElementById('frm').submit();
		}
		function searchItem() {
			document.getElementById('action').value='item-search';
			document.getElementById('frm').submit();
		}
		function addNewItem() {
			if (document.getElementById('addcategory').selectedIndex==0) {
				document.getElementById('err-addcategory').style.display="block";
				return false;
			} else {
				document.getElementById('err-addcategory').style.display="none";
			}
			document.getElementById('action').value='item-addnew';
			document.getElementById('frm').submit();
		}
		function changewbtype() {
		}
		function setPaidDate() {
			var tmp=new Date();
			document.getElementById('paid_date').value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
	</script>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	#addclient, #findclient {
		padding-left:50px;
	}
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	//print_r($incoming);
	echo printMenu();
	//$title=($incoming['id']) ? "Накладная ".$incoming['id'] : "Список накладных &quot;учёт&quot;";
	
	if ($showall) {
?>
<h1><?php echo $title; ?></h1>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/keep-count-waybills.php" method="post">
<input type="hidden" id="id" name="id" value="<?php echo (isset($incoming['id']) ? $incoming['id'] : "");?>">
<input type="hidden" id="action" name="action" value="">
<?php
		}
	echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</form>
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

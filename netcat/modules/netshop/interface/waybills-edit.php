<?php
// 17.01.2014 Elen
// создание накладной 
include_once ("../../../../vars.inc.php");
include_once ("utils-shtrihcode.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------


// get discount for an item
// @id = Message_ID in Message57

function printCrt($str,$id,$wbstatus=1) {
	$html="";
	$tmp=array();
	$tmp=explode(";",$str);
	//echo $wbstatus."<br><br>";
//	print_r($tmp);
//	echo "<br><br>";
	$total=$total1=$totalqty=0;
	$j=$m=0;
	$k=1;
	$_SESSION['items']="";
	$html.="
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td><b>#</b></td>
		<td><b>Артикул</b></td>
		<td><b>Название</b></td>
		<td><b>Кол.</b></td>
		<td><b>Цена<br>отпускная</b></td>
		<td><b>Цена<br>розничная</b></td>
		<td><b>% наценки</b></td>
		<td><b>Сумма<br>отпускная</b></td>
		<td><b>Сумма<br>розничная</b></td>";
	if ($wbstatus==2) {
		$html.="
		<td><b>Списано</b></td>
		<td><b>Оплачено</b></td>";
	} else {
		$html.="<td><b>Удалить</b></td>";
	}
	$html.="
		<td><b>На складе</b></td>
	</tr>";
	$html1="";
	$dsbl=($wbstatus==2) ? "disabled" : "";
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
						($row['video']) ? $bk="style='background:#addfad;'" : $bk="";
						$html1.="<tr ".(($row['Checked']==0) ? "style='background:#b0b0b0;'" : "").">
						<td><b>".($m)."</b></td>
						<td {$bk}><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>".$row['ItemID']."</a>
						<input type='hidden' name='iid[{$j}]' id='iid[{$j}]' value='{$row['Message_ID']}'>
						</td>";
						//$image_arr = explode(":", $row['Image']);
						//$image_url = "/netcat_files/".$image_arr[3];
						//$html.="<td><img src='".$image_url."' width='300'></td>";
						$html1.="<td>{$row['Name']}</td>";
						//$html.="<td style='text-align:right;'>{$row['Price']}</td>";
						$html1.="<td><input type='text' value='{$itm[2]}' name='qty[{$j}]' id='qty_{$j}' style='text-align:right;width:30px;'></td>";
						$html1.="<td><input type='text' value='{$iprice1}' name='itemprice1[{$j}]' id='itemprice1_{$j}' style='text-align:right;width:70px;'></td>";
						$html1.="<td><input type='text' value='{$iprice}' name='itemprice[{$j}]' id='itemprice_{$j}' style='text-align:right;width:70px;'></td>";
						$html1.="<td align='right'>".number_format(($iprice*100/$iprice1-100),2,'.',' ')."</td>";
						$html1.="<td style='text-align:right;width:70px;'>".($iprice1*$itm[2])."</td>";
						$html1.="<td style='text-align:right;width:70px;'>".($iprice*$itm[2])."</td>";
						$total=$total+$iprice*$itm[2];
						$total1=$total1+$iprice1*$itm[2];
						$totalqty=$totalqty+$itm[2];
						if ($wbstatus==2) {
							$sqlq="SELECT sales,paid FROM Waybills_goods WHERE waybill_id={$id} AND item_id={$row['Message_ID']}";
							//echo $sqlq."<br>";
							if ($rq=mysql_query($sqlq)) {
								if ($rwq=mysql_fetch_array($rq)) {
									//print_r($rwq);
									//echo "<br>";
									$html1.="<td style='text-align:right;'>{$rwq['sales']}</td>";
									$html1.="<td style='text-align:right;'>{$rwq['paid']}</td>";
								}
							}
						} else {
							$html1.="<td><a href='#' onclick='delItem({$j});'><img src='/images/icons/del.png' border='0' title='Удалить' alt='Удалить' style='display:block;margin:0 auto;'></a></td>";		
						}
						
						$html1.="<td style='text-align:right;'>{$row['StockUnits']}</td>";
						//$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
						$html1.="</tr>";
						
						$_SESSION["items"].=$id.":".$row['Message_ID'].":".$itm[2].":".$iprice1.":".$iprice.";";
						$j=$j+1;
					}
					
				//}
			}
			$k=$k+1;
		}
		$m=$m-1;
	}
	//print_r($_SESSION);
	$html.="<tr>
	<td colspan='".(($wbstatus==2) ? "12" : "11")."' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать' {$dsbl}>
	</td></tr>";
	
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='3' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td><td colspan='".(($wbstatus==2) ? "3" : "2")."'>&nbsp;</td></tr>";
	$html.=$html1;
	$html.="<tr style='background:#c0c0c0;font-weight:bold;'><td colspan='3' style='text-align:right;'>Итого:</td>
		<td style='text-align:right;'>{$totalqty}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td style='text-align:right;'><b>{$total1}</b></td><td style='text-align:right;'><b>{$total}</b></td><td  colspan='".(($wbstatus==2) ? "3" : "2")."'>&nbsp;</td></tr>";
	$html.="<tr>
	<td colspan='".(($wbstatus==2) ? "12" : "11")."' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать' {$dsbl}>
	<input type='button' id='btnSaveCart' onclick='saveCart();' value='Сохранить' {$dsbl}>
	<input type='button' onclick='rewritePrices();' value='Обновить цены в товаре' {$dsbl}>
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
				//if ($itm[2]!=0) {
					$total=$total+$itm[4]*$itm[2];
				//}
			}
		}
	}
	return $total;
}
function getCartCostOriginal($str,$id) {
	$tmp=explode(";",$str);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			if ($itm[0]==$id) {
				//if ($itm[2]!=0) {
					$total=$total+$itm[3]*$itm[2];
				//}
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
				//if ($itm[2]!=0) {
					$total=$total+$itm[2];
				//}
			}
		}
	}
	return $total;
}

// $wb_status=2 - save items count in DB
// $wb_type=2 накладная прихода
// $wb_type=7 возврат поставщику
// $wb_type=5 отложено брак
// $wb_type=3 возврат розничной продажи
function saveWaybillGoods($wb_id,$str, $wb_status,$wb_type=2) {
	$html="";
	
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	$j=0;
	
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
					$sql="INSERT INTO Waybills_goods (waybill_id, item_id, qty, originalprice, itemprice) 
						VALUES ({$wb_id}, {$itm[1]}, {$itm[2]}, {$itm[3]}, {$itm[4]})";
					//echo $sql."<br>";
					if (!mysql_query($sql)) {
						die($sql." Error: ".mysql_error());
					}
					// добавление  товара!
					if (($wb_status==2)&&(($wb_type==2)||($wb_type==3))) {
						$sql="SELECT StockUnits, status, Name FROM Message57 WHERE Message_ID={$itm[1]}";
						if ($res=mysql_query($sql)) {
							if ($row1=mysql_fetch_array($res)) {
								if ($row1["StockUnits"]>0) {
									// просто обновить количество
									$sql="UPDATE Message57 SET StockUnits=".($row1["StockUnits"]+$itm[2]).", Price={$itm[4]} WHERE Message_ID={$itm[1]}";
									if (!mysql_query($sql)) {
										die($sql." Error: ".mysql_error());
									}
									
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
									if (!mysql_query($sql)) {
										die($sql." Error: ".mysql_error());
									}
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
					// убавление товара при возврате!
					if (($wb_status==2)&&(($wb_type==7)||($wb_type==5)||($wb_type==10))) {
						$sql="SELECT StockUnits, status, Name FROM Message57 WHERE Message_ID={$itm[1]}";
						if ($res=mysql_query($sql)) {
							if ($row1=mysql_fetch_array($res)) {
								if ($row1["StockUnits"]<$itm[2]) {
									// просто обновить количество
									//$sql="UPDATE Message57 SET StockUnits=".($row1["StockUnits"]+$itm[2]).", Price={$itm[4]} WHERE Message_ID={$itm[1]}";
									//mysql_query($sql);
									$html.="<p>Товар <b>".$row1['Name']."</b> отсутствует в базе.</p>";
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
	$_SESSION['items']="";
	return $html;
}

// save ONLY PRICES items  in DB
function saveItemsPrice($str) {
	$html="";
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
			//print_r($itm);
			$sql="UPDATE Message57 SET Price={$itm[4]} WHERE Message_ID={$itm[1]}";
			//echo $sql."<br>";
			if (!mysql_query($sql)) {
				die($sql." Error: ".mysql_error());
			}
			$iprice1=getPrice1($itm[1]);
			//echo $iprice1."<br>";
			if ($iprice1==0) {
				// новый товар, добавить цену
				$sql="INSERT INTO Message57_p (Message_ID,Price,created) VALUES ({$itm[1]},{$itm[3]},'".date("Y-m-d H:i:s")."')";
			} else {
				// обновить цену
				$sql="UPDATE Message57_p SET Price={$itm[3]}, created='".date("Y-m-d H:i:s")."' WHERE Message_ID={$itm[1]}";
			}
			//echo $sql."<br>";
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
//print_r($incoming);
//print_r($_SESSION['items']);

if ((isset($incoming['start'])) && ($incoming['start']==1)) {
	$_SESSION['items']="";
//	$_SESSION['wbid']="";
}

(isset($_SESSION['cid'])) ? $cid=$_SESSION['cid'] : $cid=0;
(isset($_SESSION['items'])) ? $cart=printCrt($_SESSION['items'],$incoming['id']) : $cart="";

switch ($incoming['action']) {
	case "item-addnew":
		//print_r($incoming);
		if ($incoming['category']!="") {
			$isub=explode(":",$incoming['category']);
		} else {
			$isub=array(0,0);
		}
		// взять значение последнего артикула из БД
		$iart="0-".(getNewArticul()+1);
		
		// 18/07/2018 Elen
		// check shtrih-code
		$icode = createCode($iart);
		$sql = "SELECT * FROM Message57 WHERE shtrihcode LIKE '%".$icode."%'";
		if ($result=mysql_query($sql)) {
			if (mysql_num_rows($result) > 0) {
				die("Товар с таким штрих-кодом существует");
			}
		}
		
		
		if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
			$sql="INSERT INTO Message57 (Subdivision_ID,Sub_Class_ID, Checked, Name,ItemID,Price,supplier,Vendor,shtrihcode)
				VALUES ({$isub[0]}, {$isub[1]},0,'".quot_smart($incoming['iname'])."','{$iart}',
				".(($incoming['iprice']) ? quot_smart($incoming['iprice']) : 0).",{$incoming['supplier']},".(($incoming['manufacturer']!="") ? $incoming['manufacturer'] : 0).",
				'".$icode."')";
		} else {
			$sql="INSERT INTO Message57 (Subdivision_ID,Sub_Class_ID, Checked, Name,ItemID,Price,Vendor,shtrihcode)
				VALUES ({$isub[0]}, {$isub[1]},0,'".quot_smart($incoming['iname'])."','{$iart}',
				".(($incoming['iprice']) ? quot_smart($incoming['iprice']) : 0).",".(($incoming['manufacturer']!="") ? $incoming['manufacturer'] : 0).",
				'".$icode."')";
		}
		if (mysql_query($sql)) {
			$userinfo.="Товар создан<br>";
			$sql="SELECT Message_ID FROM Message57 WHERE ItemID LIKE '".quot_smart($incoming['iart'])."'";
			if ($result=mysql_query($sql)) {
				if ($row = mysql_fetch_array($result)) {
					$t=$row['Message_ID'];
				}
			}
			if ($t) {
				$_SESSION['items']=$incoming["id"].":".$t.":1:".$incoming['iprice1'].":".$incoming['iprice'].";".$_SESSION['items'];
				$cart=printCrt($_SESSION['items'],$incoming['id']);
			}
			$sql="UPDATE ItemSettings SET articul=articul+1";
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			}
		} else {
			die($sql."Ошибка: ".mysql_error());
		}
		break;
	case "item-add":
		//print_r($incoming);
		$tmp=explode(";",$_SESSION['items']);
		$strerror="";
		foreach($tmp as $t) {
			//echo $incoming['scharticul']."|".$incoming['schart']."-".$t."<br>";
			$tmp1=explode(":",$t);
			if ($incoming['scharticul']==$tmp1[1]) {
				$strerror="<div style='margin:0 0 15px 0;border:1px solid #a0a0a0;padding:5px;background:#ffe0e0;'>
				<input type='hidden' value='' name='scharticuladd' id='scharticuladd'>
				<span style='color:#f30000;'><b>Такой артикул уже есть в накладной! ({$tmp1[2]} шт.)</b></span><br>
				Количество, которое нужно добавить: <input type='text' value='1' name='schqty' id='schqty' style='width:30px;'><br>
				<input type='button' value='Добавить' onclick='addToOrderQty({$incoming['scharticul']});'>
				</div>";
			}
		}
		if (!$strerror) {
			$_SESSION['items']=$incoming["id"].":".$incoming['scharticul'].":1:".$incoming['schitemprice'].":".$incoming['schprice'].";".$_SESSION['items'];
		}
		$cart=printCrt($_SESSION['items'],$incoming['id']);
		//$incoming['scharticul']="";
		unset($tmp);
		break;
	case "item-addqty":
		$strtmp="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			//if ($incoming['qty'][$j]) {
			$strtmp.=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['itemprice1'][$j].":".$incoming['itemprice'][$j].";";
			//}
		}
		$tmp=explode(";",$strtmp);
		$_SESSION['items']="";
		foreach($tmp as $t) {
			if ($t) {
				$tmp1=explode(":",$t);
				if ($incoming['scharticuladd']==$tmp1[1]) {
					$_SESSION['items'].=$tmp1[0].":".$tmp1[1].":".($tmp1[2]+$incoming['schqty']).":".$tmp1[3].":".$tmp1['4'].";";
					
				} else {
					$_SESSION['items'].=$tmp1[0].":".$tmp1[1].":".$tmp1[2].":".$tmp1[3].":".$tmp1['4'].";";
				}
			}
		}
		//echo $_SESSION['items'];
		$cart=printCrt($_SESSION['items'],$incoming['id']);
		$incoming['scharticul']="";
		$incoming['scharticulqty']="";
		unset($tmp);
		break;
	case "item-search":
		// recalc cart
		//print_r($incoming);
		//echo "<br>";
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			//if ($incoming['qty'][$j]) {
			$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['itemprice1'][$j].":".$incoming['itemprice'][$j].";";
			//}
		}
		$cart=printCrt($_SESSION['items'],$incoming['id']);
		//print_r($incoming);
		if (($incoming['schart']!="")||($incoming['schname']!="")||($incoming['schvendor_itemid']!="")||($incoming['schvendor_shtrihcode']!="")) {
			$search="";
			$sql="";
			$sql="SELECT Message57.*,Sub_Class.Sub_Class_Name,Subdivision.Subdivision_Name,Classificator_Manufacturer.Manufacturer_Name FROM Message57 
				INNER JOIN Sub_Class ON (Sub_Class.Sub_Class_ID=Message57.Sub_Class_ID)
				INNER JOIN Subdivision ON (Subdivision.Subdivision_ID=Message57.Subdivision_ID)
				LEFT JOIN Classificator_Manufacturer ON (Classificator_Manufacturer.Manufacturer_ID=Message57.Vendor)";
			$supsrch=1;
			if ($incoming['schvendor_itemid']!="") { 
				$sql.="	WHERE vendor_itemid LIKE '%".$incoming['schvendor_itemid']."%' ORDER BY ItemID ASC";
			} elseif ($incoming['schvendor_shtrihcode']!="") { 
				$sql.="	WHERE vendor_shtrihcode LIKE '%".$incoming['schvendor_shtrihcode']."%' ORDER BY ItemID ASC";
			} elseif ($incoming['schart']!="") { 
				$sql.=" WHERE ItemID LIKE '%".$incoming['schart']."%' ORDER BY ItemID ASC";
				$supsrch=0;
			} elseif ($incoming['schname']!="") {
				$strw="";
				if ($incoming['schcategory']) {
					$tt=explode(":",$incoming['schcategory']);
					//print_r($tt);
					$strw=" AND Message57.Subdivision_ID={$tt[0]} AND Message57.Sub_Class_ID={$tt[1]} ";
				}
				$sql.="	WHERE Message57.Name LIKE '%".$incoming['schname']."%' ".((($incoming['supplier'])||($supsrch==1)) ? "AND supplier=".$incoming['supplier'] : "").$strw." ORDER BY ItemID ASC";
				//$sql.="	WHERE Message57.Name LIKE '%".$incoming['schname']."%' ".$strw." ORDER BY ItemID ASC";
				
			}	
			//echo $sql;
			$search.="
			<input type='hidden' value='{$cid}' name='cid' id='cid'>
			<!--input type='hidden' value='' name='scharticul' id='scharticul'-->
			<input type='hidden' value='' name='schprice' id='schprice'>
			<input type='hidden' value='' name='schitemprice' id='schitemprice'>
			<table>";
			$result = mysql_query($sql);
			while($row = mysql_fetch_array($result)) {
				$search.="<tr><td colspan='2' ".(($row['Checked']==0) ? "style='background:#c0c0c0;'" : "")."><b>[{$row['ItemID']}]</b> {$row['Name']}</td><tr>";
				($row['backcount']) ? $search.="<tr><td colspan='2' style='font-size:8pt;'><img src='/images/icons/ok.png'>В отложенных заказах: {$row['backcount']}шт. {$row['backcomment']}</td><tr>" : $search.="";
				$search.="<tr><td colspan='2' style='font-size:8pt;'>Категория: {$row['Sub_Class_Name']}<br>
				Производитель: {$row['Manufacturer_Name']}</td><tr>";
				$search.="<tr><td>На складе: {$row['StockUnits']}</td><td><b>{$row['Price']}</b> руб.</td><tr>";
				$image_arr = explode(":", $row['Image']);
				$image_url = "/netcat_files/".$image_arr[3];
				$search.="<tr><td><img src='".$image_url."' width='300'></td>";
				//$search.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].", ".getDiscount($row['Message_ID'], $row['Price']).")'></td>";
				$search.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".getPrice1($row['Message_ID']).", ".$row['Price'].")'></td>";
				$search.="</tr>";
			}
			$search.="</table>
			</fieldset>";
		}
		break;
	case "cart-recalc":
		//print_r($incoming);
		//echo "<br>";
		//echo count($incoming['iid'])."<br>";
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			//if ($incoming['qty'][$j]) {
			if ($incoming['delitem']!=$incoming['iid'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['itemprice1'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
		$cart=printCrt($_SESSION['items'],$incoming['id']);
		break;
	case "cart-rewrite":
		//print_r($incoming);
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			//if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['itemprice1'][$j].":".$incoming['itemprice'][$j].";";
			//}
		}
		$html.=saveItemsPrice($_SESSION['items']);
		$showall=0;
		break;
	case "cart-save":
		//print_r($incoming);
		$wb_id=0;
		$dte=date("Y-m-d H:i:s");
		if ((!isset($incoming["id"]))||($incoming["id"]=="")) {
			$sql="INSERT INTO Waybills (created, name, type_id, status_id, vendor_id, vendor_date, payment_id, moneytype_id, paid, summoriginal,summ, comments,onsite,title,intro,organiz_id) 
				VALUES ('".$dte."','',1,1,1,'',1,1,0,0,0,'',0,'','',0)";
			//echo $sql."<br>";
			if (!mysql_query($sql)) {	
				die($sql."Ошибка: ".mysql_error());
			}
			
			$incoming["id"]=getLastInsertID("Waybills", "id");
		}
		$wb_id=intval($incoming['id']);
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			//if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['itemprice1'][$j].":".$incoming['itemprice'][$j].";";
			//}
		}
		($cid==0) ? $cid=-1 : "";
		$summ=getCartCost($_SESSION['items'],$incoming["id"]);
		$summoriginal=getCartCostOriginal($_SESSION['items'],$incoming["id"]);
		$qty=getCartQty($_SESSION['items'],$incoming["id"]);
		($incoming['paid']==1) ? $paid=1 : $paid=0;
		($incoming['onsite']==1) ? $onsite=1 : $onsite=0;
		($incoming['put1c']==1) ? $put1c=1 : $put1c=0;
		$dte=date("Y-m-d H:i:s");
		//if ((isset($_SESSION['wbid'])) && ($_SESSION['wbid'])) {
		//echo $wb_id;
		if ($wb_id!=0) {
			$sql="SELECT status_id FROM Waybills WHERE id={$wb_id}";
			//echo $sql;
			if ($result=mysql_query($sql)) {
				if ($row = mysql_fetch_array($result)) {
					if ($row['status_id']==2) {
						$html="<p style='color:#f30000;font-weight:bold;'>Накладная #{$wb_id} уже проведена! Изменить эту накладную уже нельзя!</p>";
						$showall=0;
						break;
					}
				}
			}
			if ($row['status_id']!=2) {
				$sql="DELETE FROM Waybills_goods WHERE waybill_id=".$wb_id;
				if (!mysql_query($sql)) {
					die($sql."Ошибка: ".mysql_error());
				}
				if ($paid==1) {
					$dtpaid=date("Y-m-d", strtotime(quot_smart($incoming['paid_date'])));
				} else {
					$dtpaid="";
				}
				if ($put1c==1) {
					$dtput1c=date("Y-m-d", strtotime(quot_smart($incoming['put1cdate'])));
				} else {
					$dtput1c="";
				}
				$sql="UPDATE Waybills SET edited='".$dte."', name='".quot_smart($incoming['name'])."', 
					vendor_date='".(($incoming['vendor_date']) ? date("Y-m-d", strtotime(quot_smart($incoming['vendor_date']))) : "")."', 
					type_id={$incoming['wbtype']}, 
					status_id=".(($incoming['wbstatus']) ? $incoming['wbstatus'] : 0).", 
					vendor_id=".(($incoming['supplier']) ? $incoming['supplier'] : 0).", 
					kontragent_id=".(($incoming['kontragent']) ? $incoming['kontragent'] : 0).", 
					payment_id=".(($incoming['wbpayment']) ? $incoming['wbpayment'] : 0).", 
					moneytype_id=".(($incoming['wbpaymenttype']) ? $incoming['wbpaymenttype'] : 0).", 
					paid={$paid}, paid_date='{$dtpaid}', 
					put1c={$put1c}, put1cdate='{$dtput1c}', 
					summoriginal={$summoriginal},summ={$summ}, 
					comments='".quot_smart($incoming['comments'])."', onsite={$onsite},title='".quot_smart($incoming['title'])."',intro='".quot_smart($incoming['intro'])."',
					organiz_id=".(($incoming['organiz']) ? $incoming['organiz'] : 0)." 
					WHERE id=".$wb_id;
				//echo $sql;
				if (mysql_query($sql)) {	
					$html.="<h2>Накладная сохранена</h2>
					<p><a href='/netcat/modules/netshop/interface/waybills-list.php'>Перейти в список накладных</a></p>";
					//$showall=0;
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
			}
		} else {
			
		}
		//echo "<br>wb_id=".$wb_id;
		// если накладная проведена, обновляем товар в базе
		$html.=saveWaybillGoods($wb_id, $_SESSION['items'],$incoming['wbstatus'],$incoming['wbtype']);
		
		$_SESSION['items']="";
		$showall=0;
		break;
	case "addwaybill":	
		$bwb=array(); // накладная, которую нужно перенести $incoming['addwaybill']
		$tmp=array(); // текущая накладная, которая останется после слияния
		$wbid=$incoming['id'];
		$htmlb="";
		//echo $wbid."<br>";
		/*$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['itemprice1'][$j].":".$incoming['itemprice'][$j].";";
		}*/
		$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($wbid);
		$result=mysql_query($sql);
		$i=0;
		while ($row = mysql_fetch_array($result)) {
			$tmp[$i]="{$row['waybill_id']}:{$row['item_id']}:{$row['qty']}:{$row['originalprice']}:{$row['itemprice']}";
			$i=$i+1;
		}
		//$oldn=count($bwb);
		//$tmp=explode(";",$_SESSION['items']); 
		//print_r($tmp);
		//echo "<br><br>";
		//echo count($tmp)."<br><br>";
		$_SESSION['items']="";
		$newstr="";
		if (is_numeric($incoming['addwaybill'])) {
			// состав накладной, которую переносим
			$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($incoming['addwaybill']);
			$result=mysql_query($sql);
			$i=0;
			while ($row = mysql_fetch_array($result)) {
				$bwb[$i]="{$row['waybill_id']}:{$row['item_id']}:{$row['qty']}:{$row['originalprice']}:{$row['itemprice']}";
				$i=$i+1;
			}
			$oldn=count($bwb);
			//print_r($bwb);
			//echo $oldn."<br><br>";
			foreach($tmp as $t) {
				if ($t) {
					$tmp1=explode(":",$t);
					if ($tmp1[0]==$wbid) {
						$no=0;
						for($i=0;$i<count($bwb);$i++) {
							$btmp1=explode(":",$bwb[$i]);
							if ($btmp1[1]==$tmp1[1]) {
								$newstr.=$wbid.":".$tmp1[1].":".($tmp1[2]+$btmp1[2]).":".$btmp1[3].":".$btmp1[4].";";
								//echo $wbid.":".$tmp1[1].":".($tmp1[2]+$btmp1[2]).":".$tmp1[3]."-".$btmp1[3].":".$tmp1[4]."-".$btmp1[4].";<br>";
								$bwb[$i]="";
								unset($bwb[$i]);
								$no=1;
								break;
							}
						}
						if ($no==0) {
							$newstr.=$wbid.":".$tmp1[1].":".$tmp1[2].":".$tmp1[3].":".$tmp1[4].";";
						}
						//echo $newstr."<br>";
					}
				}
			}
			//echo "<br><br>";
			//print_r($bwb);
			//echo count($bwb)." кол. элементов в н.2 после копирования н.2<br><br>";
			$tst=explode(";",$newstr);
			//echo count($tst)." кол. элементов в н.1 после копирования н.2<br><br>";
			for($i=0;$i<$oldn;$i++) {
				if ($bwb[$i]!="") {
					$btmp1=explode(":",$bwb[$i]);
					$newstr.=$wbid.":".$btmp1[1].":".$btmp1[2].":".$btmp1[3].":".$btmp1[4].";";
					//echo "--";
				}
				//echo $i."<br>";
			}
			//echo "<br>";
			//echo $newstr."<br><br>";
			//$newstr=str_replace(";;",";",$newstr);
			//$tst=explode(";",$newstr);
			//print_r($tst);
			//echo count($tst)." объединенная накладная<br><br>";
			
			// установить у скопированной накладной статус проведена
			$sql="UPDATE Waybills SET status_id=2 WHERE id=".intval($incoming['addwaybill']);
			if (mysql_query($sql)) {	
				$htmlb.="<h3 style='color:#00f300;'>Накладная скопирована</h3>";
				//$showall=0;
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
			//echo $newstr."<br>";
			//$_SESSION['items']=$newstr;
			//$html.=saveWaybillGoods($wbid,$_SESSION['items'],$incoming['wbstatus'],$incoming['wbtype']);
			//$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['wbstatus']);
			$summ=getCartCost($newstr,$wbid);
			$summoriginal=getCartCostOriginal($newstr,$wbid);
			$qty=getCartQty($newstr,$wbid);
			($incoming['paid']==1) ? $paid=1 : $paid=0;
			($incoming['onsite']==1) ? $onsite=1 : $onsite=0;
			($incoming['put1c']==1) ? $put1c=1 : $put1c=0;
			$dte=date("Y-m-d H:i:s");
			//if ((isset($_SESSION['wbid'])) && ($_SESSION['wbid'])) {
			//echo $wb_id;
			if ($wbid!=0) {
				
				$sql="DELETE FROM Waybills_goods WHERE waybill_id=".$wbid;
				if (!mysql_query($sql)) {
					die($sql."Ошибка: ".mysql_error());
				}
				if ($paid==1) {
					$dtpaid=date("Y-m-d", strtotime(quot_smart($incoming['paid_date'])));
				} else {
					$dtpaid="";
				}
				if ($put1c==1) {
					$dtput1c=date("Y-m-d", strtotime(quot_smart($incoming['put1cdate'])));
				} else {
					$dtput1c="";
				}
				$sql="UPDATE Waybills SET edited='".$dte."', name='".quot_smart($incoming['name'])."', 
					vendor_date='".(($incoming['vendor_date']) ? date("Y-m-d", strtotime(quot_smart($incoming['vendor_date']))) : "")."', 
					type_id={$incoming['wbtype']}, 
					status_id=".(($incoming['wbstatus']) ? $incoming['wbstatus'] : 0).", 
					vendor_id=".(($incoming['supplier']) ? $incoming['supplier'] : 0).", 
					kontragent_id=".(($incoming['kontragent']) ? $incoming['kontragent'] : 0).", 
					payment_id=".(($incoming['wbpayment']) ? $incoming['wbpayment'] : 0).", 
					moneytype_id=".(($incoming['wbpaymenttype']) ? $incoming['wbpaymenttype'] : 0).", 
					paid={$paid}, paid_date='{$dtpaid}', 
					put1c={$put1c}, put1cdate='{$dtput1c}', 
					summoriginal={$summoriginal},summ={$summ}, 
					comments='".quot_smart($incoming['comments'])."', onsite={$onsite},title='".quot_smart($incoming['title'])."',intro='".quot_smart($incoming['intro'])."'
					WHERE id=".$wbid;
				//echo $sql;
				if (mysql_query($sql)) {	
					$html.="<h2>Накладная сохранена</h2>
					<p><a href='/netcat/modules/netshop/interface/waybills-list.php'>Перейти в список накладных</a></p>";
					//$showall=0;
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
				$html.=saveWaybillGoods($wbid,$newstr,$incoming['wbstatus'],$incoming['wbtype']);
				//$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['wbstatus']);
				$showall=0;
			} else {
				
			}
		} else {
			$htmlb.="<h3 style='color:#f30000;'>Ошибка в номере накладной</h3>";
		}
		$incoming['action']="";
		break;
	case "savedefectaway":	
		$wb_id=$incoming['id'];
		if (is_numeric($wb_id)) {
			// установить у скопированной накладной статус проведена
			$defectaway=((isset($incoming['defectaway']))&&($incoming['defectaway']==1)) ? 1 : 0;
			$sql="UPDATE Waybills SET defectaway={$defectaway},defectawaydate='".date("Y-m-d",strtotime($incoming['defectawaydate']))."' WHERE id=".$wb_id;
			//echo $sql;
			if (mysql_query($sql)) {	
				$htmlb.="<h3 style='color:#00f300;'>Изменения сохранены</h3>";
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
			
			// заполение данных о накладной
			$_SESSION['items']="";
			$sql="SELECT * FROM Waybills WHERE id=".intval($wb_id);
			$result=mysql_query($sql);
			while ($row = mysql_fetch_array($result)) {
				$incoming['name']=$row['name'];
				$incoming['vendor_date']=($row['vendor_date']=="0000-00-00") ? "" : date("d.m.Y",strtotime($row['vendor_date']));
				$incoming['comments']=$row['comments'];
				$incoming['paid_comment']=$row['paid_comment'];
				$incoming['wbtype']=$row['type_id'];
				$incoming['wbstatus']=$row['status_id'];
				$startsta=$row['status_id'];
				$incoming['wbpayment']=$row['payment_id'];
				$incoming['wbpaymenttype']=$row['moneytype_id'];
				$incoming['supplier']=$row['vendor_id'];
				$incoming['kontragent']=$row['kontragent_id'];
				$incoming['summ']=$row['summ'];
				$incoming['cash']=($row['cash']==1) ? "1" : "0";
				$incoming['paid']=($row['paid']==1) ? "1" : "0";
				$incoming['paid_date']=($row['paid_date']=="0000-00-00") ? "" : date("d.m.Y",strtotime($row['paid_date'])) ;
				$incoming['put1c']=($row['put1c']==1) ? "1" : "0";
				$incoming['put1cdate']=($row['put1cdate']=="0000-00-00 00:00:00") ? "" : $row['put1cdate'] ;
				$incoming['closed']=($row['closed']==1) ? "1" : "0";
				$incoming['onsite']=($row['onsite']==1) ? "1" : "0";
				$incoming['defectaway']=($row['defectaway']==1) ? "1" : "0";
				$incoming['defectawaydate']=date("d.m.Y",strtotime($row['defectawaydate']));
				$incoming['title']=$row['title'];
				$incoming['intro']=$row['intro'];
				$incoming['organiz']=$row['organiz_id'];
			}
			$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($wb_id)." ORDER BY id  ASC";
			$result=mysql_query($sql);
			while ($row = mysql_fetch_array($result)) {
				$_SESSION['items'].=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
			}
			if (isset($_SESSION['items'])) {
				$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['wbstatus']);
			}
		} else {
			$html.="<h3 style='color:#f30000;'>Ошибка в номере накладной</h3>";
		}
		$incoming['action']="";
		break;
	default: 
		if ((isset($incoming['start'])) && ($incoming['start']==1)) {
			$_SESSION['items']="";
			//$_SESSION['wbid']="";
			$incoming['paid_date']="";
		}
		
		if ((isset($incoming['id'])) && ($incoming['id'])) {
			//$_SESSION['wbid']=$incoming['id'];
			$_SESSION['items']="";
			$sql="SELECT * FROM Waybills WHERE id=".intval($incoming['id']);
			$result=mysql_query($sql);
			while ($row = mysql_fetch_array($result)) {
				//print_r($row);
				$incoming['name']=$row['name'];
				$incoming['vendor_date']=($row['vendor_date']=="0000-00-00") ? "" : date("d.m.Y",strtotime($row['vendor_date']));
				$incoming['comments']=$row['comments'];
				$incoming['paid_comment']=$row['paid_comment'];
				$incoming['wbtype']=$row['type_id'];
				$incoming['wbstatus']=$row['status_id'];
				$startsta=$row['status_id'];
				$incoming['wbpayment']=$row['payment_id'];
				$incoming['wbpaymenttype']=$row['moneytype_id'];
				$incoming['supplier']=$row['vendor_id'];
				$incoming['kontragent']=$row['kontragent_id'];
				$incoming['summ']=$row['summ'];
				$incoming['cash']=($row['cash']==1) ? "1" : "0";
				$incoming['paid']=($row['paid']==1) ? "1" : "0";
				$incoming['paid_date']=($row['paid_date']=="0000-00-00") ? "" : date("d.m.Y",strtotime($row['paid_date'])) ;
				$incoming['put1c']=($row['put1c']==1) ? "1" : "0";
				$incoming['put1cdate']=($row['put1cdate']=="0000-00-00 00:00:00") ? "" : $row['put1cdate'] ;
				$incoming['closed']=($row['closed']==1) ? "1" : "0";
				$incoming['onsite']=($row['onsite']==1) ? "1" : "0";
				$incoming['defectaway']=($row['defectaway']==1) ? "1" : "0";
				$incoming['defectawaydate']=(($row['defectawaydate']!="")&&($row['defectawaydate']!="0000-00-00 00:00:00")) ? date("d.m.Y",strtotime($row['defectawaydate'])) : "";
				$incoming['title']=$row['title'];
				$incoming['intro']=$row['intro'];
				$incoming['organiz']=$row['organiz_id'];
			}
			$sql="SELECT * FROM Waybills_goods WHERE waybill_id=".intval($incoming['id'])." ORDER BY id  ASC";
			$result=mysql_query($sql);
			while ($row = mysql_fetch_array($result)) {
				$_SESSION['items'].=$incoming["id"].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
			}
		}
		if (isset($incoming['cid'])) {
			$_SESSION['cid']=$incoming['cid'];
			$userinfo=printUserInfo($incoming['cid']);
		}
		(isset($incoming['wbstatus'])) ?  "" : $incoming['wbstatus']=1;
		if (isset($_SESSION['items'])) {
			$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['wbstatus']);
		}
		break;
}
$articul="0-".(getNewArticul()+1);
$shtrih = createCode($articul);

//echo $_SESSION['wbid'];
//print_r($_SESSION["items"]);
//print_r($incoming);
?>
<!DOCTYPE html>
<html>
<head>
	<title>Накладные прихода.</title>
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
			//document.getElementById('id').value=id;	
			document.getElementById('action').value='item-add';			
			document.getElementById('scharticul').value=mesid;
			document.getElementById('schprice').value=price;
			document.getElementById('schitemprice').value=price1;
			document.getElementById("frm").submit();
		}
		function addToOrderQty(mesid) {
			//alert(mesid);
			//document.getElementById('id').value=id;			
			document.getElementById('action').value='item-addqty';			
			document.getElementById('scharticuladd').value=mesid;
			document.getElementById("frm").submit();
		}
		function delItem(itemid) {
			document.getElementById('delitem').value=document.getElementById('iid['+itemid+']').value;
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function recalcCart() {
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function rewritePrices() {
			document.getElementById('action').value='cart-rewrite';
			//alert(document.getElementById('action').value);
			document.getElementById('frm').submit();
		}
		function saveCart() {
			var elem= new Array('supplier', 'wbtype', 'wbstatus', 'wbpayment','wbpaymenttype');
			
			if (document.getElementById('wbtype').value!=8) {
				for (var i=0; i<elem.length; i++) {
					if (document.getElementById(elem[i]).value=="") {
						document.getElementById("err-"+elem[i]).style.display="";
						return false;
					} else {
						document.getElementById("err-"+elem[i]).style.display="none";
					}
				}
			}
			//alert("!");
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
		function AddWaybill() {
			document.getElementById('action').value='addwaybill';
			//alert(document.getElementById('addwaybill').value);
			document.getElementById('frm').submit();
		}
		function saveDefectAway() {
			document.getElementById('action').value='savedefectaway';
			//alert(document.getElementById('addwaybill').value);
			document.getElementById('frm').submit();
		}
		function changeWBStatus() {
		}
		function setPaidDate() {
			var tmp=new Date();
			document.getElementById('paid_date').value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
		}
		function set1cDate() {
			var tmp=new Date();
			document.getElementById('put1cdate').value=tmp.getDate()+"."+(tmp.getMonth()+1)+"."+tmp.getFullYear();
			
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
	$title=($incoming['id']) ? "Редактирование накладной ".$incoming['id'] : "Новая накладная";
	$dsbl="";
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$dsbl=(($startsta==2) ? "disabled" : "");
	
	if ($showall) {
?>
<h1><?php echo $title; ?></h1>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/waybills-edit.php" method="post">
<input type="hidden" id="id" name="id" value="<?php echo (isset($incoming['id']) ? $incoming['id'] : "");?>">
<input type="hidden" id="action" name="action" value="">
<table cellpadding='2' cellspacing='0' border='1'>
<tr><td style='text-align:right;'>Номер накладной</td><td><input type='text' name='name' id='name' size='20' value='<?php echo $incoming['name']; ?>' <?php echo $dsbl; ?>></td>
	<td style='text-align:right;'>Дата поставщика накладной</td><td><input type='text' name='vendor_date' id='vendor_date' size='20' value='<?php echo $incoming['vendor_date']; ?>' <?php echo $dsbl; ?> class="datepickerTimeField"></td>
	<td colspan='2'><input type='checkbox' name='paid' id='paid' value='1' onclick='setPaidDate()' <?php echo ($incoming['paid']) ? "checked" : "";?> <?php echo $dsbl; ?>>Оплачено
	<input type='text' value='<?php echo (($incoming['paid_date']) ? date("d.m.Y",strtotime($incoming['paid_date'])) : ""); ?>' id='paid_date' name='paid_date' <?php echo $dsbl; ?> class="datepickerTimeField"> 
	</td>
	<td colspan='2'><!--input type='checkbox' name='put1c' id='put1c' value='1'  onclick='set1cDate()' <?php echo ($incoming['put1c']) ? "checked" : "";?> <?php echo $dsbl; ?>>Передать в 1С 
		<input type='text' name='put1cdate' id='put1cdate' value='<?php echo (($incoming['put1cdate']) ? date("d.m.Y",strtotime($incoming['put1cdate'])) : ""); ?>' <?php echo $dsbl; ?>-->
	Организация:
		<?php echo selectOrganiz($incoming); ?>
	</td>
	</tr>
<tr>
	<td style='text-align:right;'>Статус</td><td><?php echo selectWaybillStatus($incoming,$startsta); ?>
	<span id="err-wbstatus" style="display:none;color:#f30000;"><br>Необходимо выбрать статус накладной!</span></td>
	<td style='text-align:right;'>Поставщик</td><td><?php echo selectSupplier($incoming,$startsta); ?>
	<span id="err-supplier" style="display:none;color:#f30000;"><br>Необходимо выбрать поставщика!</span></td>
	<td style='text-align:right;'>Контрагент</td><td><?php echo selectKontragent($incoming,$startsta); ?>
	<span id="err-kontragent" style="display:none;color:#f30000;"><br>Необходимо выбрать контрагента!</span></td>
	<td style='text-align:right;'>Тип накладной</td><td><?php echo selectWaybillType($incoming,$startsta); ?>
	<span id="err-wbtype" style="display:none;color:#f30000;"><br>Необходимо выбрать тип накладной!</span></td></tr>
<tr></tr>
<tr><td style='text-align:right;'>Тип оплаты</td><td><?php echo selectWaybillPayment($incoming,$startsta); ?>
	<span id="err-wbpayment" style="display:none;color:#f30000;"><br>Необходимо выбрать тип оплаты!</span></td>
	<td style='text-align:right;'>Способ оплаты</td><td><?php echo selectWaybillPaymentType($incoming,$startsta); ?>
	<span id="err-wbpaymenttype" style="display:none;color:#f30000;"><br>Необходимо выбрать способ оплаты!</span></td>
	<td colspan='2'>Комментарий<br><input type='text' name='comments' id='comments' size='50' <?php echo $dsbl; ?> value="<?php echo $incoming['comments']; ?>" style='font-size:8pt;'></td>
	<td colspan="2"><input type='checkbox' name='onsite' id='onsite' value='1' <?php echo ($incoming['onsite']) ? "checked" : "";?> <?php echo $dsbl; ?>>Показывать в новинках</td>
</tr>
</table>
<?php
	// брак. отложено.
	// признак defectaway - брак отдали
	if ($incoming['wbtype']==5) {
?>
	<div style="padding:10px;margin:10px;border:1px solid #a0a0a0;width:400px;">
		<input type="checkbox" name="defectaway" id="defectaway" value="1" <?php echo (($incoming['defectaway']==1) ? "checked" : "") ; ?>> Брак. Вернули поставщику.
		<input type="text" name="defectawaydate" id="defectawaydate" value="<?php echo  (($incoming['defectawaydate']!="") ? $incoming['defectawaydate'] : date("d.m.Y")); ?>" style="text-align:right;width:100px;">
		<input type="button" value="Сохранить" onclick="saveDefectAway();">
	</div>
<?php
	}
	//print_r($incoming);
	// только для учёта. Объединение накладных
	echo $htmlb;
	if ($incoming['wbtype']==8) {
?>
	<br>
	Добавить сюда накладную <input type="text" value="" name="addwaybill" id="addwaybill">
	<input type="button" value="Добавить" onclick="AddWaybill();">
<?php
	}
?>
<br class="clear">
<h3>Состав накладной <a target="_blank" href="/netcat/modules/netshop/interface/waybills-print.php?wb=<?php echo $incoming['id']; ?>">распечатать</a></h3>
<input type="hidden" value="" name="delitem" id="delitem">
<table style='width:98%'>
<tr>
<td style='vertical-align:top;width:70%;'><?php echo $cart; ?></td>
<td style='vertical-align:top;padding-left:10px;width:30%;'>
<p><b>Поиск товара</b></p>
<?php echo $strerror; ?>
	<input type='hidden' value='<?php echo $cid; ?>' name='cid' id='cid'>
	<input type='hidden' value='' name='scharticul' id='scharticul'>
<table cellpadding="1" cellspacing="0" border="0">
	<tr><td style="text-align:right;">Артикул:</td><td><input type='text' id='schart' name='schart' value='<?php echo (($incoming['schart']!="") ? $incoming['schart'] : ""); ?>'></td></tr>
	<tr><td style="text-align:right;">Название:</td><td><input type='text' id='schname' name='schname' value='<?php echo (($incoming['schname']!="") ? $incoming['schname'] : ""); ?>'></td></tr>
	<tr><td style="text-align:right;">Категория:</td><td><?php echo selectCategory((($incoming['schcategory']) ? $incoming['schcategory'] : ""),"sch"); ?></td></tr>
	<tr><td style="text-align:right;">Артикул поставщика:</td><td><input type='text' id='schvendor_itemid' name='schvendor_itemid' value='<?php echo (($incoming['schvendor_itemid']!="") ? $incoming['schvendor_itemid'] : ""); ?>'></td></tr>
	<tr><td style="text-align:right;">Штрихкод поставщика:</td><td><input type='text' id='schvendor_shtrihcode' name='schvendor_shtrihcode' value='<?php echo (($incoming['schvendor_shtrihcode']!="") ? $incoming['schvendor_shtrihcode'] : ""); ?>'></td></tr>
	<tr><td colspan="2"><input type='button' value='Найти' onclick="searchItem();" <?php echo $dsbl; ?>></td></tr>
</table>

<?php echo $search; ?>
<br><br>
<p><b>Добавить новый товар</b></p>
<table cellpadding="1" cellspacing="0" border="0">
	<tr>
		<td style="text-align:right;">Артикул:</td><td><input type='text' id='iart' name='iart' value='<?php echo $articul; ?>'></td>
		<td style="text-align:right;">Штрих-код:</td><td><input type='text' id='ishtrih' name='ishtrih' value='<?php echo $shtrih; ?>'></td>
	</tr>
	<tr><td style="text-align:right;">Название:</td><td colspan='3'><input type='text' id='iname' name='iname' value='' style='width:98%;'></td></tr>
	<tr><td style="text-align:right;">Цена отпускная:</td><td colspan='3'><input type='text' id='iprice1' name='iprice1' value=''></td></tr>
	<tr><td style="text-align:right;">Цена розничная:</td><td colspan='3'><input type='text' id='iprice' name='iprice' value=''></td></tr>
	<tr><td style="text-align:right;">Производитель:</td><td colspan='3'><?php echo selectManufacturer(); ?></td></tr>
	<tr><td style="text-align:right;">Категория:</td><td colspan='3'><div id='err-addcategory' style='display:none;color:#f30000;'>Необходимо выбрать категорию!</div>
		<?php echo selectCategory("","","add"); ?></td></tr>
	<tr><td colspan="4"><input type='button' value='Сохранить' onclick="addNewItem();" <?php echo $dsbl; ?>></td></tr>
</table>
</td></tr>
</table>

</td>
</tr>
</table>

</form>
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

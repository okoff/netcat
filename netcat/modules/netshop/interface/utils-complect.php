<?php
// 2.09.2015. 
// Работа с комплектами

// проверка, есть ли на складе полный комплект 
function checkFullComplect($strcomplect,$qty) {
	$arset=explode(";",$strcomplect);
	//print_r($arset);
	foreach($arset as $ars) {
		$ars=str_replace(" ","",$ars);
		if ($ars!="") {
			$t=explode(":",$ars);
			//print_r($t);
			$sql="SELECT Message_ID,StockUnits, status, Name,complect,ItemID FROM Message57 WHERE ItemID LIKE '{$t[0]}'";
			if ($res1=mysql_query($sql)) {
				if ($row2=mysql_fetch_array($res1)) {
					//echo $row2['StockUnits']."-".$t[1]."-".$qty;
					// проверить количество на складе. Если больше, чем в комплекте - списать. Иначе - ошибка
					if ($row2['StockUnits']<$t[1]*$qty) {
						$html.="<p style='color:#f30000;font-weight:bold;'>На складе нет товара ".$row2['ItemID']." &quot;".$row2['Name']."&quot;. Списание комплекта не возможно.</p>";
						return $html;
					} 
				}
			}
		}
	}
	return 1;
}

function writeoffComplect($complect_id,$strcomplect,$qty,$retail_id=0,$order_id=0) {
	//echo "writeoffComplect<br>";
	$arset=explode(";",$strcomplect);
	foreach($arset as $ars) {
		$ars=str_replace(" ","",$ars);
		if ($ars!="") {
			$t=explode(":",$ars);
			//print_r($t);
			//echo "<br>";
			$sql="SELECT Message_ID,StockUnits,status,Name,complect FROM Message57 WHERE ItemID LIKE '{$t[0]}'";
			//echo $sql."<br>"; 
			if ($res1=mysql_query($sql)) {
				if ($row2=mysql_fetch_array($res1)) {
					//echo $row2['Message_ID']."-".$row2['StockUnits']."-".$t[1]."-".$qty."<br>";
					// проверить количество на складе. Если больше, чем в комплекте - списать. Иначе - ошибка
					if ($row2['StockUnits']<$t[1]*$qty) {
						if ($order_id) {
							echo "<p style='color:#f30000;font-weight:bold;'>На складе нет товара ".(($retail_id) ? $row2['Name'] : iconv("windows-1251","utf-8",$row2['Name'])).". Списание не возможно.</p>";
						}
						return 0;
					} else {
						if ($row2["StockUnits"]>$t[1]*$qty) {
							// просто обновить количество
							$sql="UPDATE Message57 SET StockUnits=StockUnits-".($t[1]*$qty)." WHERE Message_ID={$row2['Message_ID']}";
							//echo $sql."<br>";
							if (!mysql_query($sql)) {
								die("(1)".$sql."<br>Error: ".mysql_error());
							}
							
						}
						if ($row2["StockUnits"]==$t[1]*$qty) {
							// обновить количество и изменить статус 
							$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- {$row2['Name']}' WHERE Message_ID={$row2['Message_ID']}";
							//echo $sql."<br>";
							if (!mysql_query($sql)) {
								die("(2)".$sql."<br>Error: ".mysql_error());
							}
							// списать набор, если кол-во ножей в нем=0
							$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name=concat('- ',Name) WHERE Message_ID={$complect_id}";
							//echo $sql."<br>";
							if (!mysql_query($sql)) {
								die("(3)".$sql."<br>Error: ".mysql_error());
							}
						}
						if ($order_id) {
							echo "Товар [{$t[0]}] ".(($retail_id) ? $row2['Name'] : iconv("windows-1251","utf-8",$row2['Name']))." из комплекта списан.<br>";
						}
						// запись в лог о том, что списали текущую позицию
						$sqloff="";
						$sqloff="INSERT INTO Complects_off 
							(complect_id, item_id, qty, created, retail_id, order_id)
							VALUES
							({$complect_id}, {$row2['Message_ID']},".($t[1]*$qty).",'".date("Y-m-d H:i:s")."',{$retail_id},{$order_id})";
						//echo $sqloff."<br>";
						if (!mysql_query($sqloff)) {
							die("(4)".$sqloff."<br>Error: ".mysql_error());
						}
					}
				}
			}
		}
	}
	return 1;
}
?>

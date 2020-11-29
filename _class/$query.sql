UPDATE Class SET RecordTemplateFull="\";
$oid=1;
$query=\"SELECT Message_ID FROM Message51 ORDER BY Message_ID DESC LIMIT 0,1\";
	$res=(array) $nc_core->db->get_results($query);
	for ($i=0; $i<count($res); $i++) { 
		$oid=$res[$i]->Message_ID;
	}
$discount = $GLOBALS[\"shop\"]->ItemDiscountSum($sub, $classID, $f_RowID, $Price, $Currency);

if ($admin_mode) {
	$newhref = \"/netcat/message.php?catalogue=\".$catalogue.\"&sub=\".$sub.\"&cc=\".$cc.\"&message=\".$f_RowID;
echo \"
<br />
<p style=\'text-align:center;\'>Если форма редактирования не загрузилась, нажмите 
	<a href=\'\".$newhref.\"\'<b>Редактировать</b>
	</a></p><br>
<script language = \'javascript\'>
  	document.location.href=\'\".$newhref.\"\';
</script>
\";

} else {
echo
$f_AdminCommon.\"
<table cellpadding=\'0\' cellspacing=\'0\' border=\'0\' style=\'margin-left:14px;\'>
<tr>
	<td class=\'maincol2_top\'></td>
	<td class=\'rightcol2_top\'></td>
</tr>
<tr>
	<td class=\'maincol2\' valign=\'top\'>
	<div id=\'lcol2\'>

<h2 class=\'hh2\'>$f_Name</h2>
\".opt($f_Image, \"<p><a href=\'$f_Image_url\' target=\'_blank\'><img src=\'$f_Image_url\' alt=\'Фотография, картинка, $f_Name\' title=\'Фотография, картинка, $f_Name\' width=\'670\' class=\'img_preview clear\'></a></p>\").\"
\";
if ($f_status==\'есть на складе\') {
	echo \"<div style=\'padding:20px;text-align:center;\'>
	<a class=\'btn_cart_red\' data-toggle=\'modal\' data-target=\'#quickOrder\' href=\'#\'>Купить в 1 клик</a></div>\";
}
//echo $f_series;
if ($f_series!=\"\") {
	$s=0;
	$query=\"SELECT series FROM Message57 WHERE Message_ID=\".$f_RowID;
	$res=(array) $nc_core->db->get_results($query);
	for ($i=0; $i<count($res); $i++) { //>
		$s=$res[$i]->series;
	}
	$tmp1=$tmp2=\"\";
	$query=\"SELECT Value FROM Classificator_series WHERE series_ID={$s} AND Checked=1\";
	$res=(array) $nc_core->db->get_results($query);
	for ($i=0; $i<count($res); $i++) { //>
		$tmp1=$res[$i]->Value;
	}
	echo \"<div style=\'text-align:left;width:50%;font-size:16px;padding:20px 0 0 0;\'><a href=\'/series/{$tmp1}/\' style=\'color:#000;font-weight:bold;\'>Посмотреть все изделия этой серии</a></div>\";
}
if ($f_model!=\"\") {
	$tmp1=$tmp2=\"\";
	$query=\"SELECT Vendor,model FROM Message57 WHERE Message_ID=\".$f_RowID.\" AND Checked=1\";
	$res=(array) $nc_core->db->get_results($query);
	for ($i=0; $i<count($res); $i++) { //>
		$tmp1=$res[$i]->Vendor;
		$tmp2=$res[$i]->model;
	}
		echo \"<div style=\'text-align:right;width:50%;font-size:16px;padding:20px 0 0 0;\'><a href=\'/Manufacturer/Manufacturer-{$tmp1}/models/{$tmp2}/\' style=\'color:#000;font-weight:bold;\'>Посмотреть все изделия этой модели</a></div><br>\";	
}
echo \"<br clear=\'both\'>\";
$steel_id=0;
$man_id=0;
$query=\"SELECT steel,Vendor FROM Message57 WHERE Message_ID=\".$f_RowID;
$res1=(array) $nc_core->db->get_results($query);
for ($i=0; $i<count($res1); $i++) { //>
	$steel_id=$res1[$i]->steel;
	$man_id=$res1[$i]->Vendor;
}		
echo \"
<p class=\'hh2\'>Характеристики:</p>
		<table style=\'width:98%;\'>
<tr>
<td valign=\'top\' width=\'50%\'>
<table>
\".opt($f_steel, 	\"<tr><td width=\'60%\'>Марка стали клинка:</td><td><b> <a href=\'/steel/steel-\".$steel_id.\"/\'>$f_steel</a></b></td></tr>\").\"
\".opt($f_knifelength, 	\"<tr><td>Длина ножа (мм):</td><td> <b>$f_knifelength</b></td></tr>\").\"
\".opt($f_klinoklength, 	\"<tr><td>Длина клинка (мм):</td><td> <b>$f_klinoklength</b></td></tr>\").\"
\".opt($f_klinoklength, 	\"<tr><td>Длина рукояти (мм):</td><td> <b>$f_hoselength</b></td></tr>\").\"
\".opt($f_Hvostlength, 	\"<tr><td>Длина хвостовика (мм):</td><td> <b>$f_Hvostlength</b></td></tr>\").\"
\".opt($f_klinokwide, 	\"<tr><td>Наибольшая ширина клинка (мм):</td><td> <b>$f_klinokwide</b></td></tr>\").\"
\".opt($f_obuh, 		\"<tr><td>Толщина обуха (мм):</td><td> <b>$f_obuh</b></td></tr>\").\"
\".opt($f_strong, 	\"<tr><td>Твердость стали:</td><td><b> $f_strong</b></td></tr>\").\"
\".opt($f_locktype, 	\"<tr><td>Тип замка:</td><td><b> $f_locktype</b></td></tr>\").\"
</table>
</td><td valign=\'top\'>
<table>
\".opt($f_weight, 	\"<tr><td width=\'60%\'>Вес (без ножен):</td><td> <b>$f_weight</b></td></tr>\").\"
\".opt($f_handle, 	\"<tr><td>Материал рукояти:</td><td><b> $f_handle</b></td></tr>\").\"
\".opt($f_hose, 		\"<tr><td>Материал ножен:</td><td><b> $f_hose</b></td></tr>\").\"
\".opt($f_country, 	\"<tr><td>Страна изготовитель:</td><td><b> $f_country</b></td></tr>\").\"
\".opt($f_Vendor, 	\"<tr><td>Производитель:</td><td><b> <a href=\'/Manufacturer/Manufacturer-\".$man_id.\"/\'>$f_Vendor</a></b></td></tr>\").\"
<tr><td colspan=\'2\'> <div class=\'share42init\'></div>
<script type=\'text/javascript\' src=\'/share42/share42.js\'></script> </td></tr>
</table>
</td></tr></table>
<br />

\".opt($f_deliverytime, 	\"$f_deliverytime\").\"
</form>
	
	<p class=\'hh2\'>Изображения</p>
	\".opt($f_addImage1, \"<a target=\'_blank\' href=\'$f_addImage1_url\'><img src=\'$f_addImage1_url\' alt=\'$f_Name\' title=\'$f_Name\' class=\'img_preview\' width=\'200\' /></a>\").\"
	\".opt($f_addImage2, \"<a target=\'_blank\' href=\'$f_addImage2_url\'><img src=\'$f_addImage2_url\' alt=\'$f_Name\' title=\'$f_Name\' class=\'img_preview\' width=\'200\' /></a>\").\"
	\".opt($f_addImage3, \"<a target=\'_blank\' href=\'$f_addImage3_url\'><img src=\'$f_addImage3_url\' alt=\'$f_Name\' title=\'$f_Name\' class=\'img_preview\' width=\'200\' /></a>\").\"
	\".opt($f_addImage4, \"<a target=\'_blank\' href=\'$f_addImage4_url\'><img src=\'$f_addImage4_url\' alt=\'$f_Name\' title=\'$f_Name\' class=\'img_preview\' width=\'200\' /></a>\").\"
	\".opt($f_addImage5, \"<a target=\'_blank\' href=\'$f_addImage5_url\'><img src=\'$f_addImage5_url\' alt=\'$f_Name\' title=\'$f_Name\' class=\'img_preview\' width=\'200\' /></a>\").\"
	
\".opt($f_Details, \"<p class=\'hh2\'>Описание</p>$f_Details\");
echo opt($f_videobig, 	\"<div class=\'cntr\'>$f_videobig</div>\");
if ($f_analog) {
		echo \"<p class=\'hh2\'>Предлагаем посмотреть другие изделия этой серии:</p>\";
		//echo $f_analog;
$tmp=explode(\";\",$f_analog);
foreach($tmp as $t) {
if ($t!=\"\") {
$query=\"SELECT m.Message_ID,m.Name,m.status,m.ItemID,m.Preview, m.Price, m.Status, m.StockUnits, CONCAT( u.Hidden_URL, s.EnglishName, \'_\', m.Message_ID, \'.html\' ) AS URL
		FROM (`Message57` AS m, `Subdivision` AS u, `Sub_Class` AS s)
		WHERE s.`Subdivision_ID` = m.`Subdivision_ID`
		AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
		AND u.`Subdivision_ID` = m.`Subdivision_ID`  AND m.ItemID=\'{$t}\' AND m.Checked=1 ORDER BY m.ItemID DESC\";
		//echo $query;
$res = (array) $nc_core->db->get_results($query);
for ($i=0; $i<count($res); $i++){
$tt=explode(\":\",$res[$i]->Preview);

echo \"<div class=\'item\'>
<form method=post action=\'/netcat/modules/netshop/post.php\' style=\'margin:0\'>
<table border=0 cellspacing=0 cellpadding=0 width=\'100%\'>
<tr valign=top>
<td colspan=\'2\' align=\'center\' height=\'40\'>
	<p class=\'hh4\'><a href=\'\".$res[$i]->URL.\"\'><b>\".$res[$i]->Name.\"</b> \".$res[$i]->ItemID.\" (\".$res[$i]->StockUnits.\")</a></p>
</td>
</tr>
<tr valign=top>
<td colspan=\'2\' align=\'center\' height=\'70\'>
<a href=\'\".$res[$i]->URL.\"\'><img class=\'img_preview\' src=\'/netcat_files/\".$tt[3].\"\' alt=\'\".$res[$i]->Name.\"\'></a>
</td>
</tr>
<tr>
<td align=\'center\' width=\'50%\' valign=\'middle\' nowrap>
<b class=\'price\'>\".$res[$i]->Price.\" руб.</b>  
</td>
<td> 
<input type=\'hidden\' name=\'redirect_url\' value=\'\'>
<input type=\'hidden\' name=\'cart_mode\' value=\'add\'>
<input type=\'hidden\' name=\'cart[57][\".$res[$i]->Message_ID.\"]\' value=\'1\'>
\".($res[$i]->status==\'3\' ? \"\" : 
	  ($res[$i]->status==\'1\' ? \"<button style=\'width:75px; background:transparent;\' type=\'submit\'><span style=\'color:#FF6803;text-decoration:underline;\'>под заказ</span></button>\" :
	     	\"<button style=\'width:75px; background:transparent;\' type=\'submit\' onClick=\\\"yaAddCart(\'\".$f_ItemID.\"\', \'\".htmlspecialchars($f_Name).\"\', \".($Price-$discount).\", \'\".s_browse_path_range (-1,$sub_level_count-1,$browse_globalnl).\"\', 1);\\\"><span class=\'btn_cart1\'>в корзину</span></button>\").\"\").\"
<!--button style=\'width:75px; background:transparent;\' type=\'submit\'><span class=\'btn_cart1\'>в корзину</span></button-->
</td>
</tr>
</table>
</form>
</div>\";	
}
}
}
echo \"<br clear=\'both\'>\";
}
$sql = \"SELECT * FROM Message1 WHERE Message_ID=171\";
$res = (array) $nc_core->db->get_results($sql);
for ($i=0; $i<count($res); $i++){
	echo $res[$i]->TextContent;
}

echo \"
</div>
</td>

	<td valign=\'top\' class=\'rightcol2\'>
	<div>
		<p>Артикул: <span class=\'artic\'>$f_ItemID</span></p><br />
		<hr /><br />
		<p><b>Цена:    <span class=\'price\'>\". $shop->FormatCurrency($Price-$discount, $Currency, true, \"\").\"</span></b></p><br />
		\".(($discount!=0) ? \"<p>Старая цена: <strike>\".$shop->FormatCurrency($Price, $Currency, true, \"\").\"</strike></p><br />\" : \"\").\"
		\".($f_status==\'нет\' ? \"\" : \"<form method=post action=\'\".$SUB_FOLDER.$HTTP_ROOT_PATH.\"modules/netshop/post.php\' style=\'margin:0\'>
	          <input type=hidden name=redirect_url value=\'$GLOBALS[uri_path]\'>
	          <input type=hidden name=cart_mode value=add>
	          <input type=hidden name=\'cart[$classID][$f_RowID]\' value=1> 
	          <center>
	          <input type=\'submit\' onclick=\\\"yaAddCart(\'\".$f_ItemID.\"\', \'\".htmlspecialchars($f_Name).\"\', \".($Price-$discount).\", \'\".s_browse_path_range (-1,$sub_level_count-1,$browse_globalnl).\"\', 1);\\\" class=\'btn_cart\' style=\'background:url(/images/buttons/btn_cart.gif) no-repeat top left; color:#fff; text-decoration:none; font-weight:bold;\' value=\'В корзину\'>
	      	  </center>
	        </form>\").\"
		<hr /><br />
		<p>Наличие:<br /><br /><center>
		\".($f_status==\'есть на складе\' ? \"<span style=\'color:#00BF1B; font-size:16px;\'><b>$f_status</b>\" : \"\").\"
			\".($f_status==\'нет\' ? \"<span style=\'color:#f80000; font-size:16px;\'><b>нет на складе</b>\" : \"\").\"
		\".($f_status==\'под заказ\' ? \"<span style=\'color:#FF6803; font-size:16px;\'>Товара нет на складе, но его можно заказать\" : \"\").\"
		
		</span>
		</center></p><br />
</div>
<img src=\'/images/backgrounds/bg_sepitem.jpg\'>
	<div>
\";

$sql = \"SELECT * FROM Message1 WHERE Message_ID=161\";
$res = (array) $nc_core->db->get_results($sql);
for ($i=0; $i<count($res); $i++){
	echo $res[$i]->TextContent;
}
echo \"
	</div>
<img src=\'/images/backgrounds/bg_sepitem.jpg\'>
	<div>
\";

if ($f_series!=\"\") {
echo \"<p class=\'cntr hh2\'>Все ножи серии<br> $f_series</p>\";

$sql=\"SELECT m.Message_ID,m.Name,m.status,m.ItemID,m.Preview, m.Price,m.StockUnits, CONCAT( u.Hidden_URL, s.EnglishName, \'_\', m.Message_ID, \'.html\' ) AS URL
		FROM (Message57 AS m, Subdivision AS u, Sub_Class AS s)
		WHERE s.Subdivision_ID = m.Subdivision_ID AND s.Sub_Class_ID = m.Sub_Class_ID AND u.Subdivision_ID = m.Subdivision_ID
		AND m.Checked=1 AND m.series=(SELECT series FROM Message57 WHERE Message_ID={$f_RowID}) AND NOT m.Message_ID={$f_RowID} ORDER BY m.Name\";
//echo $sql;
$res = (array) $nc_core->db->get_results($sql);
for ($i=0; $i<count($res); $i++){
	$tt=explode(\":\",$res[$i]->Preview);
			echo \"<div style=\'width:150px;margin:0;padding:0;\'>
			<form method=post action=\'/netcat/modules/netshop/post.php\' style=\'margin:0;padding:0;\'>
				<table border=0 cellspacing=0 cellpadding=0 width=\'150\' style=\'margin:0;padding:0;\'>
<tr valign=top>
<td colspan=\'2\' align=\'center\' height=\'40\'>
	<p class=\'hh4\'><a href=\'\".$res[$i]->URL.\"\'><b>\".$res[$i]->Name.\"</b> \".$res[$i]->ItemID.\"</a></p>
</td>
</tr>
<tr valign=top>
<td colspan=\'2\'>
<a href=\'\".$res[$i]->URL.\"\'><img class=\'img_preview\' src=\'/netcat_files/\".$tt[3].\"\' alt=\'\".$res[$i]->Name.\"\' width=\'150\'></a>
</td>
</tr>
<tr>
<td width=\'50%\' nowrap>
<b class=\'price\'>\".$res[$i]->Price.\" руб.</b>  
</td>
<td> 
<input type=\'hidden\' name=\'redirect_url\' value=\'\'>
<input type=\'hidden\' name=\'cart_mode\' value=\'add\'>
<input type=\'hidden\' name=\'cart[57][\".$res[$i]->Message_ID.\"]\' value=\'1\'>
\".($res[$i]->status==\'3\' ? \"\" : 
	  ($res[$i]->status==\'1\' ? \"<button style=\'width:75px; background:transparent;\' type=\'submit\'><span style=\'color:#FF6803;text-decoration:underline;\'>под заказ</span></button>\" :
	     	\"<button style=\'width:75px; background:transparent;\' type=\'submit\' onclick=\\\"yaCounter2308948.reachGoal(\'ADDTOCART\'); return true;\\\"><span class=\'btn_cart1\'>в корзину</span></button>\").\"\").\"
</td>
</tr>
</table>
</form>
<br>
</div>\";
}
}
echo \" 
<br />
<hr>
<p class=\'hh2\'>Новые поступления</p>\";								
$query=\"SELECT * FROM Subdivision WHERE Parent_Sub_ID=366 AND Checked=1 ORDER BY Subdivision_ID DESC LIMIT 5\";
$res = (array) $nc_core->db->get_results($query);
for ($j=0; $j<count($res); $j++) {  //>
	$where=$res[$j]->Subdivision_Name;
	if ($where) {				
		$query=\"SELECT * FROM Waybills WHERE id=\".intval($where);
		$res1 = (array) $nc_core->db->get_results($query);
		for ($i=0; $i<count($res1); $i++) {  //>
			echo \"<p><b><a href=\'/new-arrivals/\".intval($where).\"/\'>\".date(\"d.m.Y\",strtotime($res1[$i]->created)).\"</a></b></p>\";
			echo (($res1[$i]->title!=\"\") ? \"<p><a href=\'/new-arrivals/\".intval($where).\"/\'>\".($res1[$i]->title).\"</a></p>\" : \"\");
			echo $res1[$i]->intro.\"<br>\";			
			
		}
	}
}	
switch ($sub) {	
case 108:
// КЛИНКИ ==========================================================================
echo \"<hr />			
<p class=\'hh2\'>Для мастеров сборки</p><br />
<p><b>Клинки:</b></p>
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/klin-00.jpg\' alt=\'Клинки\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'>Купить клинки</a> 
</p>  <br /><br />
<p><b>Литьё:</b></p>
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/lit-00.jpg\' alt=\'Литьё\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'>Купить литьё</a> 
</p>  <br /><br />
\";
break;
case 154:
// ЛИТЬЕ ==========================================================================
echo \"<hr />			
<p class=\'hh2\'>Для мастеров сборки</p><br />
<p><b>Клинки:</b></p>
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/klin-00.jpg\' alt=\'Клинки\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'>Купить клинки</a> 
</p>  <br /><br />
<p><b>Литьё:</b></p>
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/lit-00.jpg\' alt=\'Литьё\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'>Купить литьё</a> 
</p>  <br /><br />
\";
break;
case 155:
// ДЕРЕВО ==========================================================================
echo \"<hr />			
<p class=\'hh2\'>Для мастеров сборки</p><br />
<p><b>Клинки:</b></p>
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/klin-00.jpg\' alt=\'Клинки\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'>Купить клинки</a> 
</p>  <br /><br />
<p><b>Литьё:</b></p>
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/lit-00.jpg\' alt=\'Литьё\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'>Купить литьё</a> 
</p>  <br /><br />
\";
break;
case 355:
// МАСЛО ==========================================================================
echo \"<hr />			
<p class=\'hh2\'>Для мастеров сборки</p><br />
<p><b>Клинки:</b></p>
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/klin-00.jpg\' alt=\'Клинки\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'>Купить клинки</a> 
</p>  <br /><br />
<p><b>Литьё:</b></p>
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/lit-00.jpg\' alt=\'Литьё\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/casting/\' target=\'_blank\'>Купить литьё</a> 
</p>  <br /><br />
\";
break;
case 109: case 147:
// ПОДАРОЧНЫЕ и АВТОРСКИЕ ========================================================
echo \"<hr>			
<p class=\'hh2\'>Для оформления подарка</p><br />
<p><b>Подарочные коробки и шкатулки:</b></p>
<p><a href=\'/additcharacter/boxes/\' target=\'_blank\'><img class=\'img_preview\' src=\'/netcat_files/140/175/5285v_thumb1.jpeg\' alt=\'Подарочная коробка\' width=145></a> 
</p>  <br />
<p><a href=\'/additcharacter/boxes/\' target=\'_blank\'>Купить подарочную коробку</a> 
</p>  <br /><hr />
<br />\";
break;
default:		
// ОСТАЛЬНЫЕ =================================================================
echo \"<hr>

<p>Для заточки ножей</p><br />
<p><b>Точилки и заточные устройства:</b></p>
<p><a href=\'/additcharacter/tochilki/\' target=\'_blank\'><img class=\'img_preview\' src=\'/netcat_files/140/175/Skladishok_thumb1_1.jpeg\' alt=\'Точилки и заточные устройства\' width=145></a> 
</p>  <br />
<p><a href=\'/additcharacter/tochilki/\' target=\'_blank\'>Точилки и заточные устройства</a> 
</p>  
<br /><br />
<p><b>Японские водные камни:</b></p>
<p><a href=\'/additcharacter/water-stones/\' target=\'_blank\'><img class=\'img_preview\' src=\'/netcat_files/140/175/2650_thumb1.jpeg\' alt=\'Японские водные камни\' width=145></a> 
</p>  <br />
<p><a href=\'/additcharacter/water-stones/\' target=\'_blank\'>Купить водные камни</a> 
</p>  
<br /><br />
<hr /><br />			
<p>Для мастеров сборки</p><br />
<p><b>Клинки:</b></p>
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'><img class=\'img_preview\' src=\'/newsimg/klin-00.jpg\' alt=\'Клинки\' width=145></a> 
</p>  <br />
<p><a href=\'/Netshop/klinok/\' target=\'_blank\'>Купить клинки</a> 
</p>  <br /><br />
<hr /><br />\";	
break;
}
echo \"
<br />
\".opt($f_additional, 	\"<p>Дополнительные предложения:<br /><br /> <b>$f_additional</b></p><br /><hr />\").\"
		
</div>
<img src=\'/images/backgrounds/bg_sepitem.jpg\'>
<div>\";
$query = \"SELECT * FROM Message22  WHERE Checked=1 AND Subdivision_ID=134 AND Sub_Class_ID=170
			ORDER BY Priority DESC LIMIT 1\";
$res = (array) $nc_core->db->get_results($query);
if (count($res)>0) {
			//print_r($res);
	echo \"<p class=\'hh2\'>Главная новость</p>\";
	for($i=0;$i<count($res);$i++) { //>
			echo \"<h3><a href=\'/news/news_{$res[$i]->Message_ID}.html\'>\".$res[$i]->Title.\"</a></h3>
			{$res[$i]->Announce}<br>\".date(\"d.m.Y\",strtotime($res[$i]->Date)).\"<br><br>\";
	}
	echo \"<p><a href=\'/news/\'>все новости</a></p>\";
}
echo \"
</div>
<img src=\'/images/backgrounds/bg_sepitem.jpg\'>
\";
$query = \"SELECT * FROM Message22  WHERE Checked=1 AND Subdivision_ID=136 AND Sub_Class_ID=863
			ORDER BY Priority DESC LIMIT 2\";
$res = (array) $nc_core->db->get_results($query);
if (count($res)>0) {
			//print_r($res);
	echo \"<div><p class=\'hh2\'>Акции</p>\";
	for($i=0;$i<count($res);$i++) { //>
			echo \"<h3><a href=\'/special-offer/actions_{$res[$i]->Message_ID}.html\'>\".$res[$i]->Title.\"</a></h3>
			{$res[$i]->Announce}\".date(\"d.m.Y\",strtotime($res[$i]->Date)).\"<br><br>\";
	}
	echo \"<p><a href=\'/special-offer/\'>все акции</a></p></div>\";
}
			
echo \"	
	</td>
</tr>

<tr>
	<td class=\'maincol2_btm\'></td>
	<td class=\'rightcol2_btm\'></td>
</tr>
</table>

<br />
<table cellpadding=\'0\' cellspacing=\'0\' border=\'0\' style=\'margin-left:15px;\'>
<tr><td class=\'maincol1_top\'></td></tr>
<tr><td class=\'maincol1\'>
<div id=\'lcol1\'>

<b>комментарии:</b> \".$nc_comments->count($f_RowID).\"
<div>\".$nc_comments->wall($f_RowID).\"</div>
</div>
</td></tr>
<tr><td class=\'maincol1_btm\'></td></tr>
</table>
\";
}
echo \"
<script type=\\\"text/javascript\\\">

dataLayer.push({
  \'ecommerce\' : {
    \'detail\' : {
      \'products\' : [
        {
          \'name\' : \'\".$f_Name.\"\', 
          \'id\' : \'\".$f_ItemID.\"\', 
          \'price\' : \".($Price-$discount).\",
          \'brand\' : \'\".$f_Vendor.\"\',
          \'category\' : \'\".s_browse_path_range (-1,$sub_level_count-1,$browse_globalnl).\"\',
          \'variant\' : \'\'
        }
      ]
    }
  }
});
</script>

<!-- Rating@Mail.ru counter dynamic remarketing appendix -->
 <script type=\\\"text/javascript\\\">
	var _tmr = _tmr || [];
	_tmr.push({
	   type: \'itemView\',
	   productid: \'\".sprintf(\"%d%05d\", 57, $f_RowID).\"\',
	   pagetype: \'product\',
	   list: \'1\',
	   totalvalue: \'\".($Price-$discount).\"\'
	});
 </script>
<!-- // Rating@Mail.ru counter dynamic remarketing appendix -->
\";

if (!$admin_mode) {
echo \"
<script src=\'https://code.jquery.com/jquery-3.3.1.min.js\'></script>
<script src=\'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js\' integrity=\'sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ\' crossorigin=\'anonymous\'></script>
<!-- 
<script src=\'https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js\' integrity=\'sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm\' crossorigin=\'anonymous\'></script>
<script src=\'/popup-win/js/jquery.maskedinput.min.js\'></script>
-->
\";

$quickorder_name = $NETCAT_FOLDER.\"popup-win\\quickorder_popup.inc.html\";
$quickorder_form = file_get_contents($quickorder_name);
//error_log(\"templatefull name:\".$quickorder_name.\" \".$quickorder_form);
if ($quickorder_form) {
	echo $quickorder_form;
}

echo \"	
<!-- Инициализация и Ajax-запрос -->
<script type=\\\"text/javascript\\\">
	document.querySelector(\'#itemid\').value=\'\".$f_RowID.\"\';
	document.querySelector(\'#itemart\').value=\'\".$f_ItemID.\"\';
	document.querySelector(\'#itemname\').value=\'\".$f_Name.\"\';
	document.querySelector(\'#itemprice\').value=\'\".($Price-$discount).\"\';
	document.querySelector(\'#itemorprice\').value=\'\".$Price.\"\';
</script>
<script src=\'/popup-win/js/main.js\'></script>
\";
}
echo \"" where Class_ID=57;
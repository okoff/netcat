<?php
if (!class_exists("nc_System")) die("Unable to load file.");

global $MODULE_FOLDER;
require_once ($MODULE_FOLDER."netshop/kxlib.php");
// do output only if invoked from add/edit page
if (preg_match("{/(add|message)\.php$}", $_SERVER["SCRIPT_NAME"], $script_name_regs) ||
        preg_match("{/(add|message)\.php$}", $_SERVER["PATH_INFO"], $script_name_regs)) {
    if (!is_object($shop)) die("Error initializing shop");
    $shop->LoadOrder($message);

    $prop = array_combine($fld, $fldName);
    ?>
    <style>

        .insert_text { padding:0px 25px; cursor: pointer;
                       background: url('<?= $SUB_FOLDER.$HTTP_ROOT_PATH
    ?>modules/netshop/images/stamp.gif') no-repeat;
        }

        .before_order_table { font: bold 8pt Tahoma; padding: 5px 0px }

        .order_table { border: 1px solid #777777; border-collapse: collapse; }
        .order_table ul { margin: 0px 0px 0px 20px; }
        .order_table img, .order_table input { vertical-align: middle; }
        .order_table td,  .order_table th { border: 1px solid #777777; font: 8pt Tahoma; padding: 1px 2px }
        .order_table .highlight td, .order_table .highlight input { background: #F0F0F0 }
        .order_table .highlight input { font-weight: bold }
        .order_table th { background: #CCCCCC; font-weight: bold; padding:10px 3px; }
        .order_table td { height:22px; }
        .order_table input { border: 1px solid #CCCCCC; font: 8pt Tahoma; text-align:center; width:100%; }
        .order_table .name { padding-left:5px  }
        .order_table .ro { border: none; padding: 1px; }

        #client_details p { margin: 6px 0px }

    </style>

    <script>
        // format number
        function fnum(num) { return Math.round(new Number(num)*100)/100; }

        /**
         * if 'percent' string contains '%', return sum*percent/100,
         * otherwise return 'percent' back
         */
        function percent(sum, percent)
        {
            if (percent.match(/(\-?\d+\.?\d*)\s?%/)) // e.g. discount in percent
            { return (sum * new Number(RegExp.$1) / 100); }
            else
            { return percent; }
        }

        // refresh sums in order.
        // @param row : row that was changed
        function calc(row) {
            var f = document.adminForm;

            if (row) // recalculate row
            {
                var original_price = f["item"+row+"[OriginalPrice]"].value,
                discount = percent(original_price, f["discount"+row].value);


                f["item"+row+"[ItemPrice]"].value = fnum(original_price - discount);
                f["totals"+row].value = fnum(f["item"+row+"[ItemPrice]"].value * f["item"+row+"[Qty]"].value);

                // get cart sum (without discounts) explicitly
                var cart_sum = 0;
                for (var i in item_ids) {
                    if (typeof item_ids[i] != 'string') {
                        continue;
                    }
                    cart_sum += new Number(f["totals"+item_ids[i]].value);
                }
                f.cart_totals.value = fnum(cart_sum);
            }

            // recalculate other sums

            // minus cart discount, plus delivery and payment costs
            var fields = {//'cart_discount_sum': '-',
                'f_PaymentCost': '+',
                'f_DeliveryCost': '+' };

            var totals = new Number(f.cart_totals.value),
            cost_w_discount = totals - percent(totals, f.cart_discount_sum.value),
            totals = cost_w_discount;

            for (var i in fields)
            {
                var multiplier = new Number(fields[i]+"1");
                totals += fnum(multiplier * percent(cost_w_discount, f[i].value));
            }

            f.totals.value = fnum(totals);
        }

        function switch_client_details()
        {
            var dst = document.getElementById('client_details');
            dst.style.display = (dst.style.display=='none' ? '' : 'none');
        }
		function switch_client_comments()
        {
            var dst = document.getElementById('client_comments');
            dst.style.display = (dst.style.display=='none' ? '' : 'none');
        }

        function insert_text(dst, text)
        {
            document.adminForm[dst].focus();
            document.adminForm[dst].value += (document.adminForm[dst].value ? "\n":"") + text;
            document.adminForm[dst].focus();
        }

        function form_submit()
        {
            var f = document.adminForm,
            fields = {//'cart_discount_sum': '-',
                'f_PaymentCost': '+',
                'f_DeliveryCost': '+' },
            totals = new Number(f.cart_totals.value),
            cart_discount = percent(totals, f.cart_discount_sum.value),
            cost_w_discount = totals - cart_discount,
            totals = cost_w_discount;

            f['cart_discount_sum'].value = cart_discount; // convert percents to absolute

            for (var i in fields)
            {
                var multiplier = new Number(fields[i]+"1");
                f[i].value = fnum(percent(cost_w_discount, f[i].value)); // to absolute
            }

            return true;
        }
		function changeDelivery() {
			if (document.getElementById("deliverymethod").value!=0) {
				window.location = "/netcat/modules/netshop/interface/order-edit.php?action=delivery&id="+document.adminForm.message.value+"&val="+document.getElementById("deliverymethod").value;
			}
		}
		function changePayment() {
			if (document.getElementById("paymentmethod").value!=0) {
				window.location = "/netcat/modules/netshop/interface/order-edit.php?action=payment&id="+document.adminForm.message.value+"&val="+document.getElementById("paymentmethod").value;
			}
		}
		
		$(function() {
			console.log("Up!");
		  $("#f_Town").autocomplete({
			source: function(request,response) {
			  $.ajax({
				url: "https://api.cdek.ru/city/getListByTerm/jsonp.php?callback=?",
				dataType: "jsonp",
				data: {
					q: function () { return $("#f_Town").val() },
					name_startsWith: function () { return $("#f_Town").val() }
				},
				success: function(data) {
				  response($.map(data.geonames, function(item) {
					return {
					  label: item.name,
					  value: item.name,
					  id: item.id
					}
				  }));
				}
			  });
			},
			minLength: 1,
			select: function(event,ui) {
				console.log("Yep!");
				$('#receiverCityId').val(ui.item.id);
			}
		  });
		  
		});

    </script>
	<link type="text/css" href="/css/latest.css" rel="Stylesheet" />
	<script type="text/javascript" src="/js/ui.datepicker1.js"></script>
	
    <?
    print "<h4>";
    // number and date of the order:
    print strftime(sprintf(NETCAT_MODULE_NETSHOP_ORDER_EDIT, $message), timestamp($shop->Order["Created"]));
    print "</h4>
	<p style='text-align:right;'><a href='/netcat/modules/netshop/interface/order-print.php?id={$message}' target='_blank'>ПЕЧАТЬ</a><br></p>
         <form name='adminForm' method='post' onsubmit='return form_submit();'
          action='{$admin_url_prefix}{$script_name_regs[1]}.php'>";
?>
	<input name='senderCityId' value='44' hidden />
	<input name='receiverCityId' id='receiverCityId' value='' hidden />
<?
    if ($admin_mode) print "<input type='hidden' name='admin_mode' value='1'>";
    if ($inside_admin)
            print "<input type='hidden' name='inside_admin' value='1'>";

	switch ($shop->Order[FromWhere]) {
		case 1: print "<p>Заказ с сайта Русские ножи</p>"; break;
		case 2: print "<p>Заказ с сайта Складные ножи</p>"; break;
		case 3: print "<p>Заказ с сайта ножи Пампухи И.Ю.</p>"; break;
		case 5: print "<p>Заказ с сайта русскиеножи.рф</p>"; break;
		default:break;
	}

    print "<input name='catalogue' type='hidden' value='$catalogue'>
         <input name='sub' type='hidden' value='$sub'>
         <input name='cc' type='hidden' value='$cc'>
         <input name='message' type='hidden' value='$message'>
         ".$nc_core->token->get_input()."
         <input name=posting type=hidden value=1>

         <table border='0' cellspacing=0 cellpadding=2 width=100%>
          <tr valign=top>
           <td width='150' align=right>".NETCAT_MODULE_NETSHOP_CUSTOMER.":&nbsp;</td>
           <td width='350'>";

    if ($shop->Order["User_ID"]) {
        $user = row("SELECT * FROM User WHERE User.User_ID={$shop->Order[User_ID]}");
        $sqlhist="SELECT * FROM User LEFT JOIN User_comments ON (User.User_ID=User_comments.user_id) WHERE User.User_ID={$shop->Order[User_ID]} ORDER BY id DESC";
		$htmluser="";
		$sql="SELECT Message51.*, Classificator_ShopOrderStatus.ShopOrderStatus_Name AS OrderStatus FROM Message51
			INNER JOIN Classificator_ShopOrderStatus ON (Classificator_ShopOrderStatus.ShopOrderStatus_ID=Message51.Status)
			WHERE Message51.User_ID={$shop->Order[User_ID]} ORDER BY Message51.Message_ID DESC";
		$uords = $nc_core->db->get_results($sql, ARRAY_A );
		// Client Details
		$htmluser.="<table cellpading='0' cellspacing='0' border='1'>";
		$onum=0;
		//print_r($uords);
		if (!empty($uords)) {
			foreach ($uords as $uo) {
				$htmluser.="<tr><td style='padding:2px;'><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$uo['Message_ID']}' target='_blank'>{$uo['Message_ID']}</a></td>
					<td style='padding:2px;'>".date("d.m.Y", strtotime($uo['Created']))."</td>
					<td style='padding:2px;'>{$uo['OrderStatus']}</td>
					</tr>";
					$onum=$onum+1;
			}
		}
		$htmluser.="</table>";
		
		$countu=0;
		$uhist = $nc_core->db->get_results($sqlhist, ARRAY_A );
		$htmlcom="<table cellpading='2' cellspacing='0' border='1'>";
		if (!empty($uhist)) {
			foreach ($uhist as $uo) {
				if ($uo['created']!="") {
					$htmlcom.="<tr>
					<td style='padding:2px;'>".date("d.m.Y", strtotime($uo['created']))."</td>
					<td style='padding:2px;'>{$uo['comment']}</td>
					</tr>";
					$countu=$countu+1;
				}
			}
		}
		$htmlcom.="</table>";
        print "<b><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$user['User_ID']}'>{$user['Login']}</a></b><!--&nbsp;<a href='mailto:{$user['Email']}'><img src='/images/icons/mail.png'></a--><br>
		[Заказы: <b>{$onum}</b> <u onclick='switch_client_details()' style='cursor:hand;cursor:pointer;'>".NETCAT_MODULE_NETSHOP_MORE."</u>]<br>
		{$user['comment']} [<u onclick='switch_client_comments()' style='cursor:hand;cursor:pointer;'>комментарии: {$countu}</u>] <br> 
		[<a target='_blank' href='/netcat/modules/netshop/interface/client-edit.php?cid={$shop->Order[User_ID]}&oid={$shop->OrderID}'>добавить комментарий</a>]";
		
		
		$u_group = row("SELECT * FROM User_Group WHERE User_ID={$shop->Order[User_ID]} ORDER BY ID DESC");
		//print_r($u_group);
		if ($u_group['PermissionGroup_ID']==4) {
			print "<p style='color:#f30000; font-weight:bold;'>BLACK LIST</p>";
		}
		
		print "<div style='display:none' id=client_details>";
       	print $htmluser;
        print "<br></div>";
		print "<div style='display:none' id=client_comments>";
       	print $htmlcom;
        print "<br></div>";
    } else {
        print NETCAT_MODULE_NETSHOP_NOT_REGISTERED_USER;
    }

    print "</td>
	<td rowspan='12' style='padding:2px;'>";

    $res_field = q("SELECT *
                FROM Field
                WHERE Class_ID=$classID
                  AND (TypeOfEdit_ID=1 OR TypeOfEdit_ID=2)
                ORDER BY Priority");
	$html=$htmlclsd=$htmlpost=$htmlsenddate=$htmlcourier=$htmlreturn=$htmlfrm=$htmlwroff=$htmlpp=$htmlcdek="";
	while ($row = mysql_fetch_assoc($res_field)) {
	
		if (($row["Field_Name"]=="closed") || ($row["Field_Name"]=="paid") || ($row["Field_Name"]=="wroff") || ($row["Field_Name"]=="wroffdate")) {
			if (($row["Field_Name"]!="wroffdate")) {
				$htmlclsd.="<div style='margin-bottom:7px;'>{$row[Description]}:&nbsp;".nc_bool_field("$f_$row[Field_Name]", "", $classID, 0)."</div>\n";
			} else {
				$htmlclsd.="<div>{$row[Description]}:&nbsp;<input type='text' name='f_wroffdatestr' value='".((($f_wroffdate!="0000-00-00 00:00:00")&&($f_wroffdate)) ? date("d.m.Y", strtotime($f_wroffdate)) : "")."' style='width:100px;' class='datepickerTimeField'></div>\n";
			}
		}
		if ($row["Field_Name"]=="courier") {
			$htmlcourier.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td>".nc_list_field("$row[Field_Name]", "", $classID, 0)."</td></tr>\n";
		}
		if (($row["Field_Name"]=="barcode") || ($row["Field_Name"]=="sendtype")) {
			$htmlpost.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td>".nc_string_field("$f_$row[Field_Name]", "style='width:120px;'", $classID, 0)."</td></tr>\n";
		}
		if ($row["Field_Name"]=="writeoff"){
			$htmlwroff.="{$row[Description]}".nc_bool_field("$f_$row[Field_Name]", "", $classID, 0)."<br>\n";
		}
		if ($row["Field_Name"]=="senddate") {
			$htmlsenddate.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td style='width:170px;'>".nc_date_field("$row[Field_Name]", "", $classID, 0,"-",":",false,1)."</td></tr>\n";
			//$htmlsenddate.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td style='width:170px;'><input type='text' name='f_senddatestr' value='".((($f_senddate!="0000-00-00 00:00:00")&&($f_senddate)) ? date("d.m.Y", strtotime($f_senddate)) : "")."' style='width:100px;'></td></tr>\n";
		}
		if ($row["Field_Name"]=="acceptdate") {
			if ($f_acceptdate!="") {
				$htmlpost.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td><input type='text' value='".date("d.m.Y", strtotime($f_acceptdate))."' style='width:120px;'></td></tr>\n";
			}
		}
		if ($row["Field_Name"]=="paydate") {
			if ($f_paydate!="") {
				$htmlpost.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td><input type='text' value='".date("d.m.Y", strtotime($f_paydate))."' style='width:120px;'></td></tr>\n";
			}
		}
		if (($row["Field_Name"]=="weight") || ($row["Field_Name"]=="sendprice") || ($row["Field_Name"]=="sendinsurance") || ($row["Field_Name"]=="sendnp") || ($row["Field_Name"]=="paysum")) {
			$htmlpost.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td>".nc_float_field("$f_$row[Field_Name]", "style='width:120px;'", $classID, 0)."</td></tr>\n";
		}
		
		// return order
		if (($row["Field_Name"]=="rtbarcode")||($row["Field_Name"]=="rtsenddate")||($row["Field_Name"]=="rtgetdate")||($row["Field_Name"]=="rtcomment")) {
			$htmlreturn.="<tr><td style='text-align:right;'>{$row[Description]}:</td><td style='width:170px;'>".nc_string_field("$f_$row[Field_Name]", "style='width:120px;'", $classID, 0)."</td></tr>\n";
		}
	
		//pickpoint
		if (($row["Field_Name"]=="pickpoint_id")||($row["Field_Name"]=="pickpoint_address")||($row["Field_Name"]=="pickpoint_zone")||($row["Field_Name"]=="pickpoint_barcode")) {
			$htmlpp.="<tr><td style='text-align:right;font-size:11px;'>{$row[Description]}:</td><td>".nc_string_field("$f_$row[Field_Name]", "", $classID, 0)."</td></tr>\n";
		}
		if ($row["Field_Name"]=="pickpoint_coef") {
			$htmlpp.="<tr><td style='text-align:right;font-size:11px;'>{$row[Description]}:</td><td>".nc_float_field("$f_$row[Field_Name]", "style='width:120px;'", $classID, 0)."</td></tr>\n";
		}
		// CDEK
		if (($row["Field_Name"]=="cdek_barcode")||($row["Field_Name"]=="cdek_cityid")||($row["Field_Name"]=="cdek_perioddlv")||($row["Field_Name"]=="cdek_pvz")||($row["Field_Name"]=="cdek_modeid")) {
			$tmp="";
			if ($row['Field_Name']=="cdek_modeid") {
				$htmlcdek.="<tr><td colspan='2' style='text-align:center;font-weight:bold;'>".(($f_cdek_modeid=="4") ? "склад-склад" : "")."</td></tr>";
				$htmlcdek.="<tr><td colspan='2' style='text-align:center;font-weight:bold;'>".(($f_cdek_modeid=="3") ? "склад-дверь" : "")."</td></tr>";
			} else {
				$htmlcdek.="<tr><td style='text-align:right;font-size:11px;'>{$row[Description]}:</td><td>".nc_string_field("$f_$row[Field_Name]", "", $classID, 0)."</td></tr>\n";
			}
		}
		
		// f_Town
		//$htmltown="1111";
		//$htmltown = "<tr><td style='text-align:right;'>Город:</td><td>
		//	<input id='f_Town' name='f_Town' maxlength='255' size='50' type='text' value='' class='form-control'  />
		//	</td></tr>";
		

        if (!preg_match("/^(Type|Status|Comments|OrderCurrency|
                          PaymentMethod|PaymentCost|PaymentInfo|
                          DeliveryMethod|DeliveryCost|closed|paid|wroff|wroffdate|summinsurance|summnp|weight|barcode|senddate|sendtype|sendprice|sendinsurance|sendnp|
						  Address|FromWhere|acceptdate|paydate|paysum|rtbarcode|rtsenddate|rtgetdate|rtcomment|writeoff|courier|couriercost|
						  agree|
						  pickpoint_id|pickpoint_address|pickpoint_zone|pickpoint_coef|
						  cdek_barcode|cdek_cityid|cdek_perioddlv|cdek_modeid|cdek_pvz)$/x", $row["Field_Name"])) {
            $htmlfrm.="<tr>
                <td align=right style='width:150px;'>$row[Description]:&nbsp;</td>
                <td>";

            switch ($row["TypeOfData_ID"]) {
                case 1:
                    # String
                    $htmlfrm.=nc_string_field($row["Field_Name"], "", $classID, 0)."\n";
                    break;

                case 2:
                    # Int
                    $htmlfrm.=nc_int_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 3:
                    # Text
                    $htmlfrm.=nc_text_field(htmlspecialchars_decode($row["Field_Name"]), "style='width:300px;'", $classID, 0)."\n";
                    break;

                case 4:
                    # List
                    $htmlfrm.=nc_list_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 5:
                    # Bool
                    $htmlfrm.=nc_bool_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 6:
                    # File
                    $htmlfrm.=nc_file_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 7:
                    # Float
                    $htmlfrm.=nc_float_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 8:
                    # DateTime
                    $htmlfrm.=nc_date_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 9:
                    # Relation
                    $htmlfrm.=nc_related_field("$row[Field_Name]")."\n";
                    break;

                case 10:
                    # Multiselect
                    $htmlfrm.=nc_multilist_field("$row[Field_Name]", "", "", $classID, 0, $selected)."\n";
                    break;
            }
            $htmlfrm.="</td>
                 </tr>";
			if ($row['Field_Name']=="Region") {
				$htmlfrm.=$htmltown;
			}
			if ($row['Field_Name']=="Town") {
				$htmlfrm.="<tr><td>&nbsp;</td><td><a href='/netcat/modules/netshop/interface/order_address.php?oid={$shop->OrderID}'>Выбрать адрес доставки (СДЕК)</a></td></tr>";
			}
        }
    }
	//print $shop->Order[User_ID];
	//if ($shop->Order[User_ID]==1) {
		print "<p style='text-align:right;'><a href='/netcat/modules/netshop/interface/client-add.php?oid={$shop->OrderID}'>Создать нового клиента для этого заказа</a></p>";
	//}
	print "<div style='background:#fffdf1;margin:5px;border:#e1decf solid 1px;width:150px; height:110px;padding:5px;text-align:right;float:right;'>{$htmlclsd}</div>
	<br clear='both'>
	<table cellpadding='0' cellspacing='0' border='0' style='float:right;'><br>
	{$htmlcourier}
	{$htmlsenddate}
	{$htmlpost}</table>
	<br clear='both'>
	<hr>
	<b>Возврат:&nbsp;</b>
	<table cellpadding='0' cellspacing='0' border='0' style='float:right;'>
	{$htmlreturn}
	</table>
	<br clear='both'>
	</td></tr>
	{$htmlfrm}";
    print "<tr valign=top>
           <td align=right><br>$prop[Comments]:&nbsp;</td>
           <td><br><textarea name=f_Comments rows=3  style='width:300px;'>".htmlspecialchars_decode($f_Comments)."</textarea></td>
          </tr>";
// Elen 05.02.2013 --------------------------------------------------------------------------------
// $f_PostIndex, $f_country, $f_Region, $f_Town, $f_Street, $f_House, $f_Flat
$address = "";
(strlen($f_PostIndex)>0) ? $address.=$f_PostIndex.", " : $address.="";
(strlen($f_country)>0) ? $address.=$f_country.", " : $address.="";
(strlen($f_Region)>0) ? $address.=$f_Region.", " : $address.="";
(strlen($f_Town)>0) ? $address.=$f_Town.", " : $address.="";
(strlen($f_Street)>0) ? $address.=$f_Street.", " : $address.="";
(strlen($f_House)>0) ? $address.=$f_House.", " : $address.="";
(strlen($f_Flat)>0) ? $address.=$f_Flat." " : $address.="";

//print "<tr><td align=\"right\"><br>Полный адрес доставки:<br><br></td><td style=\"padding-left:20px;\">$address</td></tr>";
print "<tr valign=top>
           <td align=right>$prop[Address]:&nbsp;</td>
           <td colspan='2'><input type='text' name='f_Address' style='width:500px;' value='".(($f_Address) ? htmlspecialchars_decode($f_Address) : $address)."' ".((($f_Status==8) || ($f_Status==9)) ? "disabled" : "")."/></td>
          </tr>";
// ------------------------------------------------------------------------------------------------
// PickPoint
	print "<tr><td colspan='3'>
				<div  style='background:#fffdf1;margin:5px;border:#e1decf solid 1px;'>
					<table><tr><td width='50%' style='vertical-align:top;'><table>".$htmlpp."</table></td>
						<td width='50%' style='vertical-align:top;'><table>".$htmlcdek."</table></td></table>
				</div>
			</td></tr>";
// ------------------------------------------------------------------------------------------------  
    print   "<tr valign=top>
           <td align=right><b>$prop[PaymentInfo]:&nbsp;</b></td>
           <td colspan='2'>
             <textarea name=f_PaymentInfo rows=3 cols=80 style='width:700px;'>".htmlspecialchars_decode($f_PaymentInfo)."</textarea><br />
             <!--a class=insert_text onclick=\"insert_text('f_PaymentInfo',this.innerHTML)\">".strftime(NETCAT_MODULE_NETSHOP_PAYED_ON)."</a> &nbsp;
             <a class=insert_text onclick=\"insert_text('f_PaymentInfo',this.innerHTML)\">".strftime(NETCAT_MODULE_NETSHOP_PAYMENT_DOCUMENT)."</a-->
			<a href='/netcat/modules/netshop/interface/order-printcheck.php?id={$message}' target='_blank'>РАСПЕЧАТАТЬ ТОВАРНЫЙ ЧЕК</a>
           </td>
          </tr>
          <tr><td colspan='3'>&nbsp;</td></tr>
          ";
	/*$res=q("SELECT Ordertypes_ID, Ordertypes_Name FROM Classificator_Ordertypes WHERE Checked=1 AND Ordertypes_ID=".$f_Type);
	while (list($k, $v) = mysql_fetch_row($res)) {
        $tmp_type = $v;
    }*/
	print "<tr><td style='text-align:right;vertical-align:top;'>".$prop['Type'].":</td><td colspan='2'>\n\r"; //.$tmp_type."</td></tr>";
	print "<select name='f_Type'>\n\r";
	$res=q("SELECT Ordertypes_ID, Ordertypes_Name FROM Classificator_Ordertypes WHERE Checked=1 ORDER BY Ordertypes_Priority ASC");
		while (list($k, $v) = mysql_fetch_row($res)) {
        print "<option value='".$k."' ".(($f_Type==$k) ? " selected" : "").">".$v."</option>\n\r";
    }
	print "</select>
	<br><br></tr>\n\r";
    
	
	print "<tr><td style='text-align:right;vertical-align:top;'><b>$prop[Status]:&nbsp;</b></td><td colspan='2'>"; //<select name=f_Status>";

    $res = q("SELECT ShopOrderStatus_ID, ShopOrderStatus_Name FROM Classificator_ShopOrderStatus WHERE Checked = 1 ORDER BY ShopOrderStatus_Priority ASC");
    while (list($k, $v) = mysql_fetch_row($res)) {
        if ($f_Status == $k) $v = "<b>$v</b>";
		if (($k==16)||($k==18)||($k==7)||($k==8)) print "<br><hr>";
        print "<nobr><input type=radio name=f_Status value=$k id=rbSt$k".
                ($f_Status == $k ? " checked" : "").
                "><label for=rbSt$k>$v</label></nobr>&nbsp; ";
    }


    // OUTPUT ORDER CONTENTS:::

    print "</table>"; /*<br />
	<div style='position:absolute; border:1px solid #777; width:130px; height:30px;top:350px; right:40px;padding:5px;text-align:right;'>{$html}</div>
	<div style='position:absolute; border:1px solid #777; width:365px; height:250px;top:400px; right:40px;padding:5px;text-align:right;'>{$htmlpost}
	<hr>
	<b>Возврат:&nbsp;</b>
	{$htmlreturn}
	</div> */

	$secret_key='GKoKoCxEDPBrHUSC';
	$auto=md5($secret_key.$shop->OrderID);
	print "<br><p><a href='/netcat/modules/netshop/interface/order-history.php?oid=$shop->OrderID' target='_blank'>История заказа</a>
	| <a href='/netcat/modules/netshop/interface/pickpoint-history.php?oid={$shop->OrderID}' target='_blank'>Посмотреть статус отправления PickPoint</a>
	| <a href='/netcat/modules/netshop/post.php?action=print_bill&system=sberbank&mode=print_bill&order_id={$shop->OrderID}&key={$auto}' target='_blank'>Квитанция на оплату</a></p> 
		<table border=0 cellspacing=0 cellpadding=0 width=100%>
          <tr>
           <td class=before_order_table>
             $prop[OrderCurrency]: {$shop->Currencies[$f_OrderCurrency]}
             <input type=hidden name=f_OrderCurrency value=$f_OrderCurrency>
           </td>
           <td align=right class=before_order_table>
             <a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/netshop/export/commerceml.php?order_id=$shop->OrderID'>".NETCAT_MODULE_NETSHOP_EXPORT_COMMERCEML."</a>
           </td>
          </tr>
         </table>

         <table border=0 cellspacing=0 width=100% class=order_table>
           <tr>
            <th width=40%>".NETCAT_MODULE_NETSHOP_ITEM."</th>
            <th width='150'>".NETCAT_MODULE_NETSHOP_ITEM_PRICE."</th>
            <th width='50'>".NETCAT_MODULE_NETSHOP_DISCOUNT."</th>
            <th width='150'>".NETCAT_MODULE_NETSHOP_PRICE_WITH_DISCOUNT."</th>
            <th width='50'>".NETCAT_MODULE_NETSHOP_QTY." [На&nbsp;складе]</th>
            <th width='150'>".NETCAT_MODULE_NETSHOP_COST."</th>
			<th>Удалить</th>
           </tr>
         ";

    $item_ids = array();
	$fullcost=0;
    foreach ($shop->CartContents as $item) {
        $item_ids[] = $item["RowID"];

		//print_r($item);
		
        print "<tr><td class=name><font color=gray>".($item["ItemID"] ? $item["ItemID"] : $item["Message_ID"])."</font> &nbsp; ";
        print "<a class='olink' target=_blank href='".$SUB_FOLDER.$HTTP_ROOT_PATH."message.php?catalogue=$catalogue&sub=$item[Subdivision_ID]&cc=$item[Sub_Class_ID]&message=$item[Message_ID]' tabindex=-1>$item[Name]</a></td>
              <td align='center'><input onkeyup='calc(\"$item[RowID]\")' type=text name='item".$item["RowID"]."[OriginalPrice]' value='$item[OriginalPrice]' size=6 style='width:100px;'></td>";
		$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID={$shop->OrderID} AND Item_ID={$item["Message_ID"]}";
		$res1 = (array) $nc_core->db->get_results($sql);
		if (is_array($res1)) {
			foreach ($res1 as $r) {
				$item['ItemPrice']=$r->ItemPrice;
				$item['TotalPrice']=$item['ItemPrice']*$item['Qty'];
				$fullcost=$fullcost+$item['TotalPrice'];
			}
		}	  
        print "<td align='center' width='50'><nobr><input style='width:30px;' onkeyup='calc(\"$item[RowID]\")' type='text' name='discount".$item["RowID"]."' value='".($item["OriginalPrice"] - $item["ItemPrice"])."' size=6>";

        /*if ($item["Discounts"]) {
            //print " <img src='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/netshop/interface/qmark.png' width=16 height=16 border=0 alt=\"\n";
            foreach ($item["Discounts"] as $discount) {
                print "   ".$shop->FormatCurrency($discount["Sum"] / $item["Qty"])."<!-- (".htmlspecialchars($discount["Name"]).")  --> \n";
            }

            if ($discount["PriceMinimum"]) {
                printf("\n   ".NETCAT_MODULE_NETSHOP_ITEM_MINIMAL_PRICE_REACHED."\n", $item["ItemPriceF"]);
            }

           // print "\" />";
        }*/
		
        print "</nobr></td>
              <td align='center'><input type='text' name='item".$item["RowID"]."[ItemPrice]' value='$item[ItemPrice]' style='width:100px;'></td>
              <td align='center'><input onkeyup='calc(\"$item[RowID]\")' type=text name='item".$item["RowID"]."[Qty]' value='$item[Qty]' size=4 style='width:30px;margin:0 auto;'>
				<b>[{$item['StockUnits']}]</b>
			  </td>
				<td><input disabled type='text' name='totals".$item["RowID"]."' value='$item[TotalPrice]' size=6 style='width:100px;'></td>
			  <td><a href='/netcat/modules/netshop/interface/del-item.php?oid=$shop->OrderID&iid=".$item["Message_ID"]."'>удалить</a></td>
             </tr>";
    }
	// 26.08.2013 Elen
	// add new item to order
	print "<tr><td colspan='7'><a href='/netcat/modules/netshop/interface/add-item.php?oid=$shop->OrderID' target='_blank'><b>Добавить позицию в заказ</b></a></td></tr>";
	
    print "<tr class=highlight>
           <td colspan=5 class=name><b>".NETCAT_MODULE_NETSHOP_ITEM_COST."</b></td>
           <!--td><input class=ro tabindex=-1 readonly type=text name='cart_totals' value='".$shop->CartFieldSum("ItemPrice")."' size=10></td><td>&nbsp;</td-->
           <td><input class=ro tabindex=-1 readonly type=text name='cart_totals' value='".$fullcost."' size=10></td><td>&nbsp;</td>
          </tr>\n";
	$cartdiscount=0;
    //{
        print "<tr><td colspan=5 class=name><b>";
		//print_r($shop->CartDiscounts);
		/*$sql="SELECT * FROM Netshop_OrderDiscounts WHERE Order_ID=".$shop->OrderID;
		$res = (array) $nc_core->db->get_results($query);
		if (is_array($res)) {
			foreach ($res as $r) {
				 $shop->CartDiscounts[$i]['SumF']=$r->Discount_Sum;
				 $shop->CartDiscounts[$i]['Name']=$r->Discount_Name;
			}
		}*/
        print (sizeof($shop->CartDiscounts) > 1) ? NETCAT_MODULE_NETSHOP_DISCOUNTS.":</b><ul><li>" :
                        NETCAT_MODULE_NETSHOP_DISCOUNT.":</b> ";

        foreach ((array) $shop->CartDiscounts as $i => $discount) {
            if ($i > 0) print "<li>";
            print "$discount[Name] &mdash; $discount[SumF]";
        }

        if (sizeof($shop->CartDiscounts) > 1) print "</ul>";

        print "</td><td><input onkeyup='calc()' type=text name='cart_discount_sum' value='$shop->CartDiscountSum' size=10 style='width:100px;'></td><td>&nbsp;</td></tr>";
	$cartdiscount=$shop->CartDiscountSum;
   //}
	// 29.05.2013 Elen
	// Change delivery method
	$tmp="<select id='deliverymethod' style='font-size:11px;'>\n<option value='0'>---</option>\n";
	$query = "SELECT * FROM Message56 WHERE Checked=1 ORDER BY Message_ID DESC";
	$res = (array) $nc_core->db->get_results($query);
	if (is_array($res)) {
		foreach ($res as $r) {
			$tmp.="<option value='".$r->Message_ID."'>".$r->Name."</option>\n";
		}
	}
	$tmp.="</select>\n<input type='button' value='Изменить' onclick='changeDelivery();' style='width:100px; float:right;'>\n";
	
	// Change payment method
	$tmp1="<select id='paymentmethod' style='font-size:11px;'>\n<option value='0'>---</option>\n";
	$query = "SELECT * FROM Message55 WHERE Checked=1 ORDER BY Message_ID DESC";
	$res = (array) $nc_core->db->get_results($query);
	if (is_array($res)) {
		foreach ($res as $r) {
			$tmp1.="<option value='".$r->Message_ID."'>".$r->Name."</option>\n";
		}
	}
	$tmp1.="</select>\n<input type='button' value='Изменить' onclick='changePayment();' style='width:100px; float:right;'>";
   
	$fullcost=$fullcost+$f_DeliveryCost+$f_PaymentCost-$cartdiscount;
	print "<tr>\n<td class=name colspan=5><b>$prop[DeliveryMethod]:</b>
           <input type=hidden name=f_DeliveryMethod value=$f_DeliveryMethod>".
            value1("SELECT Name FROM Message$shop->delivery_methods_table WHERE Message_ID='$f_DeliveryMethod'")."<br><br>".$tmp.
            "</td>\n<td><input onkeyup='calc()' type=text name='f_DeliveryCost' value='$f_DeliveryCost' size=10 style='width:100px;'></td>\n<td>&nbsp;</td>\n</tr>

          <tr><td class=name colspan=5><b>$prop[PaymentMethod]:</b>
           <input type=hidden name=f_PaymentMethod value=$f_PaymentMethod>".
            value1("SELECT Name FROM Message$shop->payment_methods_table WHERE Message_ID='$f_PaymentMethod'")."<br><br>".$tmp1.
            "</td><td><input onkeyup='calc()' type=text name='f_PaymentCost' value='$f_PaymentCost' size=10 style='width:100px;'></td><td>&nbsp;</td></tr>

          <tr class=highlight>
           <td colspan=5 class=name><b>".NETCAT_MODULE_NETSHOP_SUM."</b></td>
           <!--td><input class=ro tabindex=-1 readonly type=text name='totals' value='".$shop->CartSum()."' size=10 style='width:100px;'></td><td>&nbsp;</td-->
           <td><input class=ro tabindex=-1 readonly type=text name='totals' value='".$fullcost."' size=10 style='width:100px;'></td><td>&nbsp;</td>
          </tr>";
	// расчет страховой суммы
	if ($f_PaymentMethod==7) {
		$sinsurance=$shop->CartSum();
		$snp=$shop->CartSum();
	} else {
		$sinsurance=$shop->CartFieldSum("ItemPrice");
		$snp=0;
	}
	print "<tr>
			<td colspan=3 class=name><b>Страховая сумма (для Почты РФ)</b></td>
			<td><input type='text' name='f_summinsurance' value='".($f_summinsurance ? $f_summinsurance : $sinsurance)."' size=10 style='width:100px;font-weight:bold;'></td>
			<td colspan='3'>&nbsp;</td>
		</tr>
		  <tr>
           <td colspan=3 class=name><b>Сумма наложенного платежа (для Почты РФ)</b></td>
           <td><input type=text name='f_summnp' value='".($f_summnp ? $f_summnp : $snp)."' size=10 style='width:100px;font-weight:bold;'></td>
			<td colspan='3'>&nbsp;</td>
		</tr>
		<tr>
           <td colspan=3 class=name><b>Оплата курьеру</b></td>
           <td><input type=text name='f_couriercost' value='".$f_couriercost."' size=10 style='width:100px;font-weight:bold;'></td>
			<td colspan='3'>&nbsp;</td>
		</tr>
         ";

    print "</table>";
    print "<input type=hidden name=f_Checked value='$f_Checked'><br><br>";
	
	print "<div style='display:none;'>".$htmlwroff."</div>";
	if ($shop->Order[writeoff]==1) {
		print "<p style='text-align:left;font-weight:bold;'>[<a href='/netcat/modules/netshop/interface/order-writeoff.php?id={$shop->Order[Message_ID]}&action=on'>отменить списание</a>]</p>";
	} else {
		print "<p style='text-align:left;font-weight:bold;'>[<a href='/netcat/modules/netshop/interface/order-writeoff.php?id={$shop->Order[Message_ID]}&action=off'>списать товар</a>]</p>";
	}
	
    print "<br /><center><input type=submit value='".CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE."' class='btnk'></center>";

    print "</form><script>item_ids = ['".join("','", $item_ids)."'];</script>";
	
	$query="SELECT * FROM Netshop_PostHistory WHERE Order_ID={$shop->Order[Message_ID]} ORDER BY id ASC";
	//print $query;
	$res = (array) $nc_core->db->get_results($query);
	if ((is_array($res)) && !(empty($res))) {
		print "<br><p style='text-align:left;'><b>Трассировка почтового отправления</b></p>";
		print "<table cellpadding='2' cellspacing='0' border='0' class='order_table' align='left'>";
	
		//print_r($res);
		foreach ($res as $r) {
			print "<tr><td>".date("d.m.Y", strtotime($r->created))."</td><td>".$r->status."</td><td>".$r->address."</td></tr>";
		}
		print "</table>";
	}

	//print_r($shop->Order);
	
}
?>
<link type="text/css" href="/css/latest.css" rel="Stylesheet" />

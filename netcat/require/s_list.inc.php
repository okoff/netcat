<?php
/* $Id: s_list.inc.php 8492 2012-11-30 10:00:11Z lemonade $ */
function getDiscount($id, $originalprice) {
	//echo $id;
	global $systemTableID, $db, $srchPat, $nc_core;
	$rs=$originalprice;
	$sql="SELECT Name, Description, UserGroups, Goods, ValidFrom, ValidTo, `Condition`,
                 Function, FunctionDestination, FunctionOperator, StopItem
            FROM Message54
           WHERE AppliesTo = 1
             AND ((ValidFrom IS NULL AND ValidTo IS NULL) OR
                  (ValidFrom <= NOW() AND ValidTo >= NOW()))
             AND Checked = 1
			 AND Goods LIKE '%57:{$id},%'
           ORDER BY Priority DESC";
	//echo $sql."<br>";
	$res = $db->get_row($sql, ARRAY_A);
	
	if (!empty($res)) {
		if ($res['FunctionOperator']=="*=") {
			$rs=$originalprice*$res['Function'];
		}
		if ($res['FunctionOperator']=="-=") {
			$rs=$originalprice-$res['Function'];
		}
	} else {
		$sql="SELECT Name, Description, UserGroups, Goods, ValidFrom, ValidTo, `Condition`,
                 Function, FunctionDestination, FunctionOperator, StopItem
            FROM Message54
           WHERE AppliesTo = 1
             AND ((ValidFrom IS NULL AND ValidTo IS NULL) OR
                  (ValidFrom <= NOW() AND ValidTo >= NOW()))
             AND Checked = 1
			 AND Goods LIKE '%57:{$id}'
           ORDER BY Priority DESC";
		//echo $sql."<br>";
		$res = $db->get_row($sql, ARRAY_A);
		
		if (!empty($res)) {
			if ($res['FunctionOperator']=="*=") {
				$rs=$originalprice*$res['Function'];
			}
			if ($res['FunctionOperator']=="-=") {
				$rs=$originalprice-$res['Function'];
			}
		}
	}
	return $rs;
}
function getItemStatus($id) {
	//echo $id;
	global $systemTableID, $db, $srchPat, $nc_core;
	$rs="";
	$sql="SELECT Value From Classificator_ItemStatus WHERE ItemStatus_ID=".$id;
	$res = $db->get_row($sql, ARRAY_A);
	if (!empty($res)) {
		foreach ($res as $row) {
			$rs=$row;
		}
	}
	return $rs;
}
function getCurrency() {
	//echo $id;
	global $systemTableID, $db, $srchPat, $nc_core;
	$rs="";
	$sql="SELECT Value From Classificator_ShopCurrency WHERE ShopCurrency_ID=1";
	$res = $db->get_row($sql, ARRAY_A);
	if (!empty($res)) {
		foreach ($res as $row) {
			$rs=$row;
		}
	}
	return $rs;
}

function printItemById($RowID,$sub,$classID,$ItemID,$Name,$url,$StockUnits,$Preview,$Price,$status) {
	$result="";
	$image_arr = explode(":", $Preview);
	$image_url = "/netcat_files/".$image_arr[3];
	$discount=0;
	$discount=getDiscount($RowID, $Price);
	$currency=getCurrency();
	
	$result.="<div class='item'>";
	$result.="<form method='post' action='/netcat/modules/netshop/post.php' style='margin:0'>
<table border='0' cellspacing='0' cellpadding='0' width='100%'>
<tr valign='top'>
  	<td colspan='2' align='center' height='40'>
		<p class='hh4'><a href='{$url}'><b>{$Name}</b> {$ItemID}</a> ({$StockUnits})</p>
	</td>
</tr>
<tr valign='top'>
  	<td colspan='2' align='center' height='70'>
		<a href='{$url}'><img class='img_preview' src='{$image_url}' alt='{$Name}'></a>
	</td>
</tr>
<tr>
    <td align='center' width='50%' valign='middle' nowrap>
     	
	<b class='price'>".number_format(($discount),0,'.',' ')."&nbsp;{$currency}</b>
	".(($discount!=$Price) ? "<br><span style='text-decoration:line-through;'>".number_format($Price,0,'.',' ')."</span>&nbsp;{$currency}" : "")."&nbsp;&nbsp;	
	</td>
	<td> 
		<input type='hidden' name='redirect_url' value=''>
	    <input type='hidden' name='cart_mode' value='add'>
	    <input type='hidden' name='cart[57][{$RowID}]' value='1'>
		".($status==4 ? "" : 
	     	($status==3 ? "<button style='width:75px; background:transparent;' type='submit'><span style='color:#FF6803;text-decoration:underline;'>".getItemStatus(3)."</span></button>" :
	     	"<button style='width:75px;background:transparent;' type='submit'><span class='btn_cart1'>".getItemStatus(2)."</span></button>")."")."

	</td>
</tr>
</table>
</form>";
	
	$result.="</div>";
	return $result;
}
// for bootstrap sites
function printItemById5($RowID,$sub,$classID,$ItemID,$Name,$url,$StockUnits,$Preview,$Price,$status) {
	$result="";
	$image_arr = explode(":", $Preview);
	$image_url = "/netcat_files/".$image_arr[3];
	$discount=0;
	$discount=getDiscount($RowID, $Price);
	$currency=getCurrency();
	
	$result.="<div class='panel panel-default item'>
	<div class=\"panel-body\">";
	$result.="
	<div class='title'><a href='{$url}'><b>{$Name}</b> {$ItemID}</a> ({$StockUnits})</div>
  	<div class='itemimg'>
		<a href='{$url}'><img class='img_preview' src='{$image_url}' alt='{$Name}'></a>
	</div>
	</div>
	<div class='panel-footer'>
	<div class='row itemprice'>
		<div class='col-md-6'>
			<div class='text-nowrap price'>
			<strong>".number_format(($discount),0,'.',' ')."&nbsp;{$currency}</strong>
			".(($discount!=$Price) ? "<br><span style='text-decoration:line-through;'>".number_format($Price,0,'.',' ')."</span>&nbsp;{$currency}" : "")."&nbsp;&nbsp;	
			</div>
		</div>
		<div class='col-md-4 text-right'> 
			<form method='post' action='/netcat/modules/netshop/post.php'>
			<input type='hidden' name='redirect_url' value=''>
			<input type='hidden' name='cart_mode' value='add'>
			<input type='hidden' name='cart[57][{$RowID}]' value='1'>
			".((($status==4)||($status==3)) ? "" : "<button class='btn btn-success btn-sm' type='submit'>".getItemStatus(2)."</button>")."
			</form>
		</div>
		<div class='col-md-2'>&nbsp;</div>
	</div>
";
	
	$result.="</div>
	</div>";
	return $result;
}
function printItemById5nobox($RowID,$sub,$classID,$ItemID,$Name,$url,$StockUnits,$Preview,$Price,$status) {
	$result="";
	$image_arr = explode(":", $Preview);
	$image_url = "/netcat_files/".$image_arr[3];
	$discount=0;
	$discount=getDiscount($RowID, $Price);
	$currency=getCurrency();
	
	$result.="<div class='item'>";
	$result.="<div>
		<div class='title'><a href='{$url}'><b>{$Name}</b> {$ItemID}</a> ({$StockUnits})</div>
		<div class='itemimg'><a href='{$url}'><img class='img_preview' src='{$image_url}' alt='{$Name}'></a></div>
	</div>
	<div class='row itemprice'>
		<div class='col-md-7'>
			<div class='text-nowrap price'>
			<strong>".number_format(($discount),0,'.',' ')."&nbsp;{$currency}</strong>
			".(($discount!=$Price) ? "<br><span style='text-decoration:line-through;'>".number_format($Price,0,'.',' ')."</span>&nbsp;{$currency}" : "")."&nbsp;&nbsp;	
			</div>
		</div>
		<div class='col-md-3 text-right'> 
			<form method='post' action='/netcat/modules/netshop/post.php'>
			<input type='hidden' name='redirect_url' value=''>
			<input type='hidden' name='cart_mode' value='add'>
			<input type='hidden' name='cart[57][{$RowID}]' value='1'>
			".((($status==4)||($status==3)) ? "" : "<button class='btn btn-success btn-sm' type='submit'>".getItemStatus(2)."</button>")."
			</form>
		</div>
		<div class='col-md-2'>&nbsp;</div>
	</div>
";
	
	$result.="</div>
	<hr>";
	return $result;
}

function printItemByIdb($RowID,$sub,$classID,$ItemID,$Name,$url,$StockUnits,$Preview,$Price,$status) {
	$result="";
	$image_arr = explode(":", $Preview);
	$image_url = "/netcat_files/".$image_arr[3];
	$discount=0;
	$discount=getDiscount($RowID, $Price);
	$currency=getCurrency();
	
	$result.="<div class='item'>";
	$result.="<form method='post' action='/netcat/modules/netshop/post.php' style='margin:0'>
<table border='0' cellspacing='0' cellpadding='0' width='100%'>
<tr valign='top'>
  	<td colspan='2' align='center' height='40'>
		<p class='hh4'><a href='{$url}'><b>{$Name}</b> {$ItemID}</a> ({$StockUnits})</p>
	</td>
</tr>
<tr valign='top'>
  	<td colspan='2' align='center' height='270'>
		<a href='{$url}'><img class='img_preview' src='{$image_url}' alt='{$Name}' style='width:400px;'></a>
	</td>
</tr>
<tr>
    <td align='center' width='50%' valign='middle' nowrap>
     	
	<b class='price'>".number_format(($discount),0,'.',' ')."&nbsp;{$currency}</b>
	".(($discount!=$Price) ? "<br><span style='text-decoration:line-through;'>".number_format($Price,0,'.',' ')."</span>&nbsp;{$currency}" : "")."&nbsp;&nbsp;	
	</td>
	<td> 
		<input type='hidden' name='redirect_url' value=''>
	    <input type='hidden' name='cart_mode' value='add'>
	    <input type='hidden' name='cart[57][{$RowID}]' value='1'>
		".($status==4 ? "" : 
	     	($status==3 ? "<button style='width:75px; background:transparent;' type='submit'><span style='color:#FF6803;text-decoration:underline;'>".getItemStatus(3)."</span></button>" :
	     	"<button style='width:75px;background:transparent;' type='submit'><span class='btn_cart1'>".getItemStatus(2)."</span></button>")."")."

	</td>
</tr>
</table>
</form>";
	
	$result.="</div>";
	return $result;
}
// for bootstrap sites
function printItemById5b($RowID,$sub,$classID,$ItemID,$Name,$url,$StockUnits,$Preview,$Price,$status) {
	$result="";
	$image_arr = explode(":", $Preview);
	$image_url = "/netcat_files/".$image_arr[3];
	$discount=0;
	$discount=getDiscount($RowID, $Price);
	$currency=getCurrency();
	
	$result.="<div class='panel panel-default itemb'>
	<div class=\"panel-body\">";
	$result.="
	<div class='title'><a href='{$url}'><b>{$Name}</b> {$ItemID}</a> ({$StockUnits})</div>
  	<div class='itemimgb'>
		<a href='{$url}'><img class='img_preview' src='{$image_url}' alt='{$Name}'></a>
	</div>
	</div>
	<div class='panel-footer itembfooter'>
	<div class='row itembfooter'>
		<div class='col-md-7'>
			<div class='text-nowrap price'>
			<strong>".number_format(($discount),0,'.',' ')."&nbsp;{$currency}</strong>
			".(($discount!=$Price) ? "<br><span style='text-decoration:line-through;'>".number_format($Price,0,'.',' ')."</span>&nbsp;{$currency}" : "")."&nbsp;&nbsp;	
			</div>
		</div>
		<div class='col-md-4 text-right'> 
			<form method='post' action='/netcat/modules/netshop/post.php'>
			<input type='hidden' name='redirect_url' value=''>
			<input type='hidden' name='cart_mode' value='add'>
			<input type='hidden' name='cart[57][{$RowID}]' value='1'>
			".((($status==4)||($status==3)) ? "" : "<button class='incart' type='submit'>".getItemStatus(2)."</button>")."
			</form>
		</div>
		<div class='col-md-1'>&nbsp;</div>
	</div>
";
	
	$result.="</div>
	</div>";
	return $result;
}
function nc_objects_list($sub, $cc, $query_string = "", $show_in_admin_mode = false) {
    try {
        $nc_core = nc_Core::get_object();
        $cc_env = $nc_core->sub_class->get_by_id(+$cc, null);
        $current_cc = $nc_core->sub_class->get_current();
        $mode_array = array(0 => 'db', 1 => 'file');
        $function_name = 'nc_objects_list_' . $mode_array[+$cc_env['File_Mode']];
        if (function_exists($function_name)) {
            $result = $function_name(+$sub, +$cc, $query_string, $show_in_admin_mode);
            if ($nc_core->admin_mode && !$GLOBALS['isNaked'] && ($cc == $current_cc['Sub_Class_ID'])) {
                $result = "<div id='nc_admin_mode_content' " . ($nc_core->inside_admin ? "" : "style='border: 1px dashed #999'") . ">$result</div>";
            }
            return $result;
        } else {
            throw new Exception('unknown work mode');
        }
    } catch (Exception $e) {
        nc_print_status($e->getMessage(), 'error');
        return null;
    }
}

function s_list_class($sub, $cc, $query_string = "", $show_in_admin_mode = false) {
    return nc_objects_list($sub, $cc, $query_string, $show_in_admin_mode);
}

function showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt) {
    global $systemTableID, $db, $srchPat, $nc_core;

    $result = '';
    $j = 0;

    for ($i = 0; $i < count($fldName); $i++) {
        $fld_prefix = "<div>";
        $fld_suffix = "</div>\n";
        $fldNameTempl = $fld_prefix . "" . $fldName[$i] . ": ";

        if (!$fldDoSearch[$i])
            continue;

		$stringValue = htmlspecialchars(stripcslashes($srchPatValues[$j]), ENT_QUOTES);
        $stringValue = addcslashes($stringValue, '$');
        switch ($fldType[$i]) {
            case 1: // Char
                $result .= $fldNameTempl . "<br><input type='text' name='".$srchPatName."[" . $j . "]' size='50' maxlength='255' value='" . $stringValue . "'>" . $fld_suffix;
                $j++;
                break;
            case 3: // Text
                $result .= $fldNameTempl . "<br><input type='text' name='".$srchPatName."[" . $j . "]' size='50' maxlength='255' value='" . $stringValue . "'>" . $fld_suffix;
                $j++;
                break;
            case 6: // File
                $result .= $fldNameTempl . "<br><input type='text' name='".$srchPatName."[" . $j . "]' size='50' maxlength='255' value='" . $stringValue . "'>" . $fld_suffix;
                $j++;
                break;

            case 2:   // Int
                $result .= $fldNameTempl . "&nbsp;&nbsp;" . NETCAT_MODERATION_MOD_FROM . " <input type='text' name='srchPat[" . $j . "]' size='10' maxlength='16' value='" . ($srchPat[$j] ? (int) $srchPat[$j] : "") . "'>";
                $j++;
                $result .= NETCAT_MODERATION_MOD_DON . "<input type='text' name='srchPat[" . $j . "]' size='10' maxlength='16' value='" . ($srchPat[$j] ? (int) $srchPat[$j] : "") . "'>" . $fld_suffix;
                $j++;
                break;

            case 7:   // Float
                $result .= $fldNameTempl . "&nbsp;&nbsp;" . NETCAT_MODERATION_MOD_FROM . " <input type='text' name='srchPat[" . $j . "]' size='10' maxlength='16' value='" . ($srchPat[$j] ? (float) $srchPat[$j] : "") . "'>";
                $j++;
                $result .= NETCAT_MODERATION_MOD_DON . "<input name='srchPat[" . $j . "]' type='text' size='10' maxlength='16' value='" . ($srchPat[$j] ? (float) $srchPat[$j] : "") . "'>" . $fld_suffix;
                $j++;
                break;

            case 4:   // List
                if ($fldFmt[$i]) {
                    $result .= $fldNameTempl . "<br><select name='srchPat[" . $j . "]' size='1'>";
                    $result .= "<option value=''>" . NETCAT_MODERATION_MODA . "</option>";

                    $SortType = $SortDirection = 0;
                    $res = $db->get_row("SELECT `Sort_Type`, `Sort_Direction` FROM `Classificator` WHERE `Table_Name` = '" . $db->escape($fldFmt[$i]) . "'", ARRAY_N);
                    if ($db->num_rows != 0) {
                        $row = $res;
                        $SortType = $row[0];
                        $SortDirection = $row[1];
                    }

                    $s = "SELECT * FROM `Classificator_" . $db->escape($fldFmt[$i]) . "` ORDER BY ";
                    switch ($SortType) {
                        case 1: $s .= "`" . $db->escape($fldFmt[$i]) . "_Name`";
                            break;
                        case 2: $s .= "`" . $db->escape($fldFmt[$i]) . "_Priority`";
                            break;
                        default: $s .= "`" . $db->escape($fldFmt[$i]) . "_ID`";
                    }

                    if ($SortDirection == 1)
                        $s .= " DESC";

                    $selected = (int) $srchPat[$j];
                    $lstRes = (array) $db->get_results($s, ARRAY_N);
                    foreach ($lstRes as $q) {
                        list($lstID, $lstName) = $q;
                        $result .= "<option value='" . $lstID . "'" . ($selected == $lstID ? "selected" : "") . ">" . $lstName . "</option>";
                    }
                    $result .= '</select>' . $fld_suffix;
                }
                $j++;
                break;

            case 5: // Bool
                $result .= $fldNameTempl;
                $result .= "&nbsp;&nbsp;<input type='radio' name='srchPat[" . $j . "]' id='t" . $j . "_1' value='' style='vertical-align:middle'" . (!$srchPat[$j] ? " checked" : "") . "><label for='t" . $j . "_1'>" . NETCAT_MODERATION_MOD_NOANSWER . '</label> ';
                $result .= "&nbsp;&nbsp;<input type='radio' name='srchPat[" . $j . "]' id='t" . $j . "_2' value='1' style='vertical-align:middle'" . ($srchPat[$j] == '1' ? " checked" : "") . "><label for='t" . $j . "_2'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . '</label> ';
                $result .= "&nbsp;&nbsp;<input type='radio' name='srchPat[" . $j . "]' id='t" . $j . "_3' value='0' style='vertical-align:middle'" . ($srchPat[$j] == '0' ? " checked" : "") . "><label for='t" . $j . "_3'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . '</label>';
                $result .= $fld_suffix;
                $j++;
                break;

            case 8:   // DateTime
                $format = nc_field_parse_format($fldFmt[$i], 8);
                $result .= $fldNameTempl . "&nbsp;&nbsp;";
                if ($format['calendar'])
                    $result .= nc_set_calendar(0) . "<br/>";
                $result .= NETCAT_MODERATION_MOD_FROM;



                if ($format['type'] != 'event_time') {
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='4' maxlength='4' value='" . ($srchPat[$j] ? sprintf("%04d", $srchPat[$j]) : "") . "'> ";
                    $j++;
                } else
                    $j += 3;
                if ($format['type'] != 'event_date') {
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'> ";
                    $j++;
                } else
                    $j += 3;

                if ($format['calendar'] && $format['type'] != 'event_time') {
                    $result .= "<div style='display: inline; position: relative;'>
                         <img  id='nc_calendar_popup_img_srchPat[" . ($j - 6) . "]' onclick=\\\"nc_calendar_popup('srchPat[" . ($j - 6) . "]', 'srchPat[" . ($j - 5) . "]', 'srchPat[" . ($j - 4) . "]', '0');\\\" src='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/calendar/images/calendar.jpg' style='cursor: pointer; position: absolute; left: 7px; top: -3px;'/>
                       </div>
                       <div style='display: none; z-index: 10000;' id='nc_calendar_popup_srchPat[" . ($j - 6) . "]'></div><br/>";
                }

                $result .= NETCAT_MODERATION_MOD_DON;
                if ($format['type'] != 'event_time') {
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='4' maxlength='4' value='" . ($srchPat[$j] ? sprintf("%04d", $srchPat[$j]) : "") . "'> ";
                    $j++;
                } else
                    $j += 3;
                if ($format['type'] != 'event_date') {
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'>:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='srchPat[" . $j . "]' size='2' maxlength='2' value='" . ($srchPat[$j] ? sprintf("%02d", $srchPat[$j]) : "") . "'> ";
                    $j++;
                } else
                    $j += 3;

                if ($format['calendar'] && $format['type'] != 'event_time') {
                    $result .= "<div style='display: inline; position: relative;'>
                         <img  id='nc_calendar_popup_img_srchPat[" . ($j - 6) . "]' onclick=\\\"nc_calendar_popup('srchPat[" . ($j - 6) . "]', 'srchPat[" . ($j - 5) . "]', 'srchPat[" . ($j - 4) . "]', '0');\\\" src='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/calendar/images/calendar.jpg' style='cursor: pointer; position: absolute; left: 7px; top: -3px;'/>
                       </div>
                       <div style='display: none; z-index: 10000;' id='nc_calendar_popup_srchPat[" . ($j - 6) . "]'></div><br/>";
                }

                $result .= $fld_suffix;
                break;

            case 10:   //Multi List
                if ($fldFmt[$i]) {
                    list( $clft_name, $type_element, $type_size ) = explode(":", $fldFmt[$i]);

                    if (!$type_element)
                        $type_element = "select";
                    if (!$type_size)
                        $type_size = 3;

                    $fldFmt[$i] = $clft_name;

                    $SortType = $SortDirection = 0;
                    $res = $db->get_row("SELECT `Sort_Type`, `Sort_Direction` FROM `Classificator` WHERE `Table_Name` = '" . $db->escape($fldFmt[$i]) . "'", ARRAY_N);
                    if ($db->num_rows != 0) {
                        $row = $res;
                        $SortType = $row[0];
                        $SortDirection = $row[1];
                    }

                    $s = "SELECT * FROM Classificator_" . $fldFmt[$i] . " ORDER BY ";
                    switch ($SortType) {
                        case 1: $s .= $fldFmt[$i] . "_Name";
                            break;
                        case 2: $s .= $fldFmt[$i] . "_Priority";
                            break;
                        default: $s .= $fldFmt[$i] . "_ID";
                    }

                    if ($SortDirection == 1)
                        $s .= " DESC";

                    $selected = (int) $srchPat[$j];
                    $lstRes = (array) $db->get_results($s, ARRAY_N);

                    $result .= $fldNameTempl . "<br>";

                    if ($type_element == 'select') {
                        $result .= "<select name='srchPat[" . $j . "][]' size='" . $type_size . "' multiple>";
                        $result .= "<option value=''>" . NETCAT_MODERATION_MODA . "</option>";
                    }


                    foreach ($lstRes as $q) {
                        list($lstID, $lstName) = $q;
                        $temp_str = '';
                        if ($lstID == $selected)
                            $temp_str = ($type_element == "select" ) ? " selected" : " checked";

                        if ($type_element == 'select') {  #TODO сделать возможность передавать селектед в виде массива
                            $result .= "<option value='" . $lstID . "' " . $temp_str . ">" . $lstName . "</option>";
                        } else {
                            $result .="<input type='checkbox' value='" . $lstID . "' name='srchPat[" . $j . "][]' " . $temp_str . "> " . $lstName . "<br>\r\n";
                        }
                    }

                    if ($type_element == 'select')
                        $result .= '</select><br>'; //.$fld_suffix;

                    $j++;
                    $result .= "<input type='hidden' name='srchPat[" . $j . "]' value='0'>\n";
                    $result .= $fld_suffix;
                }
                $j++;
                break;
        }
        $result .= "<br>\n";
    }

    if (!$j)
        return false;

    return $result;
}

function getSearchParams($field_name, $field_type, $field_search, $srchPat) {
    global $db;

    // return if search params not setted
    if (empty($srchPat))
        return array("query" => "", "link" => "");
    $search_param = array();
    for ($i = 0, $j = 0; $i < count($field_name); $i++) {
        if ($field_search[$i]) {
            switch ($field_type[$i]) {
                case 1:   // Char
                    if ($srchPat[$j] == "")
                        break;
                    $srch_str = $db->escape($srchPat[$j]);
                    $fullSearchStr .= " AND a." . $field_name[$i] . " LIKE '%" . urldecode($srch_str) . "%'";
                    $search_param[] = "srchPat[" . $j . "]=" . urldecode($srchPat[$j]);
                    break;
                case 2:   // Int
                    if ($srchPat[$j] != "") {
                        $srchPat[$j] += 0;
                        $fullSearchStr .= " AND a." . $field_name[$i] . ">=" . $srchPat[$j];
                        $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    }
                    $j++;
                    if ($srchPat[$j] != "") {
                        $srchPat[$j] += 0;
                        $fullSearchStr .= " AND a." . $field_name[$i] . "<=" . $srchPat[$j];
                        $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    }
                    break;
                case 3:   // Text
                    if ($srchPat[$j] == "")
                        break;
                    $srch_str = $db->escape($srchPat[$j]);
                    $fullSearchStr .= " AND a." . $field_name[$i] . " LIKE '%" . urldecode($srch_str) . "%'";
                    $search_param[] = "srchPat[" . $j . "]=" . urldecode($srchPat[$j]);
                    break;
                case 4: // List
                    if ($srchPat[$j] == "")
                        break;
                    $srchPat[$j] += 0;
                    $fullSearchStr .= " AND a." . $field_name[$i] . "=" . $srchPat[$j];
                    $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    break;
                case 5:   // Boolean
                    if ($srchPat[$j] == "")
                        break;
                    $srchPat[$j] += 0;
                    $fullSearchStr .= " AND a." . $field_name[$i] . "=" . $srchPat[$j];
                    $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    break;
                case 6:   // File
                    if ($srchPat[$j] == "")
                        break;
                    $srch_str = $db->escape($srchPat[$j]);
                    $fullSearchStr .= " AND SUBSTRING_INDEX(a." . $field_name[$i] . ",':',1) LIKE '%" . urldecode($srch_str) . "%'";
                    $search_param[] = "srchPat[" . $j . "]=" . urldecode($srchPat[$j]);
                    break;
                case 7:   // Float
                    if ($srchPat[$j] != "") {
                        $srchPat[$j] = floatval($srchPat[$j]);
                        $fullSearchStr .= " AND a." . $field_name[$i] . ">=" . $srchPat[$j];
                        $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    }
                    $j++;
                    if ($srchPat[$j] != "") {
                        $srchPat[$j] = floatval($srchPat[$j]);
                        $fullSearchStr .= " AND a." . $field_name[$i] . "<=" . $srchPat[$j];
                        $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    }
                    break;
                case 8:   // DateTime
                    $date_from['d'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_from['m'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_from['Y'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%04d", $srchPat[$j]) : false);
                    $j++;
                    $date_from['H'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_from['i'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_from['s'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_to['d'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_to['m'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_to['Y'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%04d", $srchPat[$j]) : false);
                    $j++;
                    $date_to['H'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_to['i'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                    $j++;
                    $date_to['s'] = ($srchPat[$j] && ($search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);

                    $date_format_from = ($date_from['Y'] ? '%Y' : '') . ($date_from['m'] ? '%m' : '') . ($date_from['d'] ? '%d' : '') . ($date_from['H'] ? '%H' : '') . ($date_from['i'] ? '%i' : '') . ($date_from['s'] ? '%s' : '');
                    $date_format_to = ($date_to['Y'] ? '%Y' : '') . ($date_to['m'] ? '%m' : '') . ($date_to['d'] ? '%d' : '') . ($date_to['H'] ? '%H' : '') . ($date_to['i'] ? '%i' : '') . ($date_to['s'] ? '%s' : '');

                    if ($date_format_from)
                        $fullSearchStr .= " AND DATE_FORMAT(a." . $field_name[$i] . ",'" . $date_format_from . "')>=" . $date_from['Y'] . $date_from['m'] . $date_from['d'] . $date_from['H'] . $date_from['i'] . $date_from['s'];
                    if ($date_format_to)
                        $fullSearchStr .= " AND DATE_FORMAT(a." . $field_name[$i] . ",'" . $date_format_to . "')<=" . $date_to['Y'] . $date_to['m'] . $date_to['d'] . $date_to['H'] . $date_to['i'] . $date_to['s'];

                    break;

                case 10: // MultiList
                    if ($srchPat[$j] == "") {
                        $j++;
                        break;
                    }

                    $id = array(); // массив с id искомых элементов

                    if (is_array($srchPat[$j])) {
                        foreach ((array) $srchPat[$j] as $v) {
                            $id[] = +$v;
                        }
                    } else {
                        $temp_id = explode('-', $srchPat[$j]);
                        foreach ((array) $temp_id as $v) {
                            $id[] = +$v;
                        }
                    }
                    $j++; //второй параметр - это тип посика

                    if (empty($id))
                        break;

                    $fullSearchStr .= " AND (";
                    switch ($srchPat[$j]) {
                        case 1: //Полное совпадение
                            $fullSearchStr .= "a." . $field_name[$i] . " LIKE CONCAT(',' ,  '" . join(',', $id) . "', ',') ";
                            break;

                        case 2: //Хотя бы один. Выбор между LIKE и REGEXP выпал в сторону первого
                            foreach ($id as $v)
                                $fullSearchStr .= "a." . $field_name[$i] . " LIKE CONCAT('%,', '" . $v . "', ',%') OR ";
                            $fullSearchStr .= "0 "; //чтобы "закрыть" последний OR
                            break;
                        case 0: // как минимум выбранные - частичное совпадение - по умолчанию
                        default:
                            $srchPat[$j] = 0;
                            $fullSearchStr .= "a." . $field_name[$i] . "  REGEXP  \"((,[0-9]+)*)";
                            $prev_v = -1;
                            foreach ($id as $v) {
                                $fullSearchStr .= "(," . $v . ",)([0-9]*)((,[0-9]+)*)";
                                $prev_v = $v;
                            }
                            $fullSearchStr .= '"';
                            break;
                    }
                    $fullSearchStr .= ")";

                    $search_param[] = "srchPat[" . ($j - 1) . "]=" . join('-', $id);
                    $search_param[] = "srchPat[" . $j . "]=" . $srchPat[$j];
                    break;
            }
            $j++;
        }
    }

    if (!empty($search_param))
        $search_params['link'] = join('&amp;', $search_param);
    $search_params['query'] = $fullSearchStr;

    return $search_params;
}

function nc_objects_list_db($sub, $cc, $query_string, $show_in_admin_mode) {

    $LIST_VARS = array();
    parse_str($query_string, $LIST_VARS);

    $ignore_array = array(
            "nc_core", "inside_admin", "admin_mode", "catalogue", "cc", "cond_catalogue", "cond_catalogue_add", "cond_catalogue_addtable",
            "cond_cc", "cond_check", "cond_date", "cond_distinct", "cond_group", "cond_having", "cond_mod", "cond_parent",
            "cond_search", "cond_select", "cond_sub", "cond_user", "cond_where", "distinct", "distinctrow", "ignore_all", "ignore_limit", "ignore_cc", "ignore_check",
            "ignore_parent", "ignore_sub", "ignore_user", "ignore_calc", "query_from", "query_group", "query_join", "query_order", "query_select",
            "query_where", "query_group", "query_having", "query_limit", "result_vars", "sort_by", "sub", "user_table_mode",
            "cc_env", "field_vars", "message_select", "ignore_eval", "nc_data");

    $ignore_array = array_flip($ignore_array);
    $LIST_VARS = array_diff_key($LIST_VARS, $ignore_array);
    extract($LIST_VARS);

    global $UI_CONFIG, $perm, $_cache, $admin_url_prefix, $classPreview;
    global $AUTH_USER_ID, $AUTH_USER_GROUP, $current_user;
    global $sub_level_count, $parent_sub_tree;
    global $current_catalogue, $current_sub, $current_cc;
    global $nc_minishop;
    global $cc_array;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $MODULE_VARS = $nc_core->modules->get_module_vars();
    $FILES_FOLDER = $nc_core->get_variable("FILES_FOLDER");
    $HTTP_ROOT_PATH = $nc_core->get_variable("HTTP_ROOT_PATH");
    $ADMIN_PATH = $nc_core->get_variable("ADMIN_PATH");
    $ADMIN_TEMPLATE = $nc_core->get_variable("ADMIN_TEMPLATE");
    $DOMAIN_NAME = $nc_core->get_variable("DOMAIN_NAME");
    $SHOW_MYSQL_ERRORS = $nc_core->get_variable("SHOW_MYSQL_ERRORS");
    $AUTHORIZE_BY = $nc_core->get_variable("AUTHORIZE_BY");
    $HTTP_FILES_PATH = $nc_core->get_variable("HTTP_FILES_PATH");
    $DOCUMENT_ROOT = $nc_core->get_variable("DOCUMENT_ROOT");
    $SUB_FOLDER = $nc_core->get_variable("SUB_FOLDER");

    $inside_admin = $nc_core->inside_admin;
    $admin_mode = $nc_core->admin_mode;

    $system_env = $nc_core->get_settings();
    $current_catalogue = $nc_core->catalogue->get_current();
    $current_sub = $nc_core->subdivision->get_current();
    $current_cc = $nc_core->sub_class->get_current();

    $ignore_eval = array();

    if (!isset($nc_title))
        $nc_title = 0;
    if (!isset($isMainContent))
        $isMainContent = 0;
    if (!isset($isSubClassArray))
        $isSubClassArray = 0;
    if (!isset($query_select))
        $query_select = false;
    if (!isset($cur_cc))
        $cur_cc = false;

    $catalogue = +$catalogue;
    $template = +$template;
    $parent_message = +$parent_message;
    $nc_ctpl = +$nc_ctpl;

    if (+$_REQUEST['isModal']) {
        $inside_admin = false;
        $admin_mode = false;
    }

    if (!$nc_ctpl && $nc_title)
        $nc_ctpl = 'title';

    if (!$cc)
        return false;
    if (($cc != $current_cc['Sub_Class_ID'] || !$isMainContent) && $admin_mode && !$show_in_admin_mode)
        return false;

    try {
        $cc_env = $nc_core->sub_class->get_by_id($cc, null, $nc_ctpl);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    if ($admin_mode && $cc_env['Edit_Class_Template']) {
        $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Edit_Class_Template']);
    }


    // зеркальный
    if ($cc_env['SrcMirror']) {
        $mirror_data = $nc_core->sub_class->get_by_id($cc_env['SrcMirror']);
        $cc = $mirror_data['Sub_Class_ID'];
        $sub = $mirror_data['Subdivision_ID'];
    }

    if ($nc_ctpl === 'title')
        $nc_ctpl = $cc_env['Real_Class_ID'];
    if (!$sub)
        $sub = $cc_env['Subdivision_ID'];

    if ($cc_env['Type'] == 'rss' || $cc_env['Type'] == 'xml') {
        $cc_env['Cache_Access_ID'] = 2;
    }

    // если preview для нашего класса, то подменим cc_env из $_SESSION
    if ($classPreview == ($cc_env["Class_Template_ID"] ? $cc_env["Class_Template_ID"] : $cc_env["Class_ID"])) {
        $magic_gpc = get_magic_quotes_gpc();
        if (!empty($_SESSION["PreviewClass"][$classPreview])) {
            foreach ($_SESSION["PreviewClass"][$classPreview] as $tkey => $tvalue) {
                $cc_env[$tkey] = $magic_gpc ? stripslashes($tvalue) : $tvalue;
            }
        }
        // Запретим кеширование в режиме предпросмотра.
        $cc_env['Cache_Access_ID'] = 2;
    }

    // Если присутствует параметр isSubClassArray в вызове функции s_list_class(), то добавляем
    // в массив $cc_env элемент cur_cc, который будет участвовать в формировании навигации по страницам
    // при отображении нескольких шаблонов на странице
    if (isset($isSubClassArray) && $isSubClassArray)
        $cc_env['cur_cc'] = $cc;

    if ($cc_env['Read_Access_ID'] > 1 && !$AUTH_USER_ID)
        return false;
    if ($AUTH_USER_ID && $cc_env['Read_Access_ID'] > 2) {
        $HasRights = CheckUserRights($cc, 'read', 1);
        if (!$HasRights)
            return false;
    }

    // set user table mode
    $user_table_mode = (bool) $cc_env['System_Table_ID'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && !$user_table_mode) {
        // startup values
        $cached_data = "";
        $cached_eval = false;

        try {
            $nc_cache_list = nc_cache_list::getObject();
            // cache auth addon string
            $cache_for_user = $nc_cache_list->authAddonString($cc_env['CacheForUser'], $current_user);
            $cache_key = $query_string . $cache_for_user . "type=" . $cc_env['Type'] . "classtemplate=" . $cc_env['ClassTemplate'];
            // check cached data
            $cached_result = $nc_cache_list->read($sub, $cc, $cache_key, $cc_env['Cache_Lifetime']);
            if ($cached_result != -1) {
                // get cached parameters
                list ($cached_data, $cached_eval, $cache_vars) = $cached_result;
                // debug info
                $cache_debug_info = "Readed, sub[" . $sub . "], cc[" . $cc . "], Access_ID[" . $cc_env['Cache_Access_ID'] . "], Lifetime[" . $cc_env['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "], eval[" . (int) $cached_eval . "]";
                $nc_cache_list->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache if not eval flag setted
                if (!$cached_eval) {
                    $result = $cached_data;
                    return $result;
                }
            }
            // set marks into the fields
            $no_cache_marks = $nc_cache_list->nocacheStore($cc_env);
        } catch (Exception $e) {
            // for debug
            $nc_cache_list->errorMessage($e);
        }
    }

	$allowTags = $cc_env['AllowTags'];
    $NL2BR = $cc_env['NL2BR'];
    $catalogue = $mirror_data['Catalogue_ID'] ? $mirror_data['Catalogue_ID'] : $cc_env['Catalogue_ID'];
    $intQueryStr = '?';

	// $subLink, $ccLink, $cc_keyword
	if ($admin_mode) {
		$subLink = $admin_url_prefix . ($mirror_data['Subdivision_ID'] ? '?catalogue=' . $cc_env['Catalogue_ID'] . '&amp;sub=' . $cc_env['Subdivision_ID'] : '?catalogue=' . $catalogue . '&amp;sub=' . $sub);
		$ccLink = $subLink . ($mirror_data['Sub_Class_ID'] ? '&amp;cc=' . $cc_env['Sub_Class_ID'] : '&amp;cc=' . $cc);
		$intQueryStr = $ccLink;
	} else {
		$subLink = $SUB_FOLDER . $cc_env['Hidden_URL'];
		$cc_keyword = $cc_env['EnglishName'];
		$ccLink = $subLink . $cc_keyword . '.html';
	}

    $cc_settings = &$cc_env["Sub_Class_Settings"];

    // переменные curPos, recNum нужно привести к "правильному" виду
    // до И после выполнения системных настроек компонента
    $curPos = +$curPos;
    if ($curPos < 0)
        $curPos = 0;
    $recNum = +$recNum;
    if ($recNum < 0)
        $recNum = 0;

    // if RecordsPerPage not setted in component - set ignore_limit
    $ignore_limit = (!($maxRows = $cc_env['RecordsPerPage']) );
    $sortBy = $cc_env['SortBy'];
    $classID = $cc_env['Class_ID'];
    $userTableID = $cc_env['System_Table_ID'];

    $ignore_all = 0;
    $ignore_catalogue = 1;
	$ignore_sub = $ignore_cc = 0;
    $ignore_check = $ignore_parent = 0;
    if (isset($ignore_prefix)) $ignore_prefix = +$ignore_prefix;
    if (isset($ignore_suffix)) $ignore_suffix = +$ignore_suffix;
    $ignore_calc = 0;
    $ignore_user = true;
    $result_vars = '';
    $cond_date = '';
    $no_cache_marks = 0;

    if (isset($MODULE_VARS['searchold']['INDEX_TABLE']) && $MODULE_VARS['searchold']['INDEX_TABLE'] == $classID) {
        $ignore_eval['sort_by'] = true;
    }

    // component "system settings"
    if ($cc_env['Settings']) {
        eval($cc_env['Settings']);
    }

    // переменные попадают в запрос
    // нужно привести к целому типу
    $curPos = +$curPos;
    if ($curPos < 0)
        $curPos = 0;
    $recNum = +$recNum;
    if ($recNum < 0)
        $recNum = 0;

    // clear this variable after system settings eval!
    $result = "";

    if (!$sortBy) {
        $sort_by = "a." . ($user_table_mode ? "`" . $AUTHORIZE_BY . "`" : "`Priority` DESC") . ", a.`LastUpdated` DESC";
    } else {
        $sort_by = $sortBy;
    }

    //выйдем, если нет идентификатора шаблона, поскольку дальше работа функции бессмысленна
    if (!$classID)
        return false;

    $component = new nc_Component($classID, $cc_env['System_Table_ID']);
    $component->make_query();

    $field_names = $component->get_fields_query();
    $field_vars = $component->get_fields_vars();
    $multilist_fileds = $component->get_fields(10);
    $date_field = $component->get_date_field();

    // разрешить html-теги и перенос строки
    $cc_env['convert2txt'] = "";
    $text_fields = $component->get_fields(3);
    foreach ($text_fields as $field) {
        $format = nc_field_parse_format($field['format'], 3);
        // разрешить html
        if (!$cc_env['AllowTags'] && !$format['html'] || $format['html'] == 2)
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = htmlspecialchars_decode(\$f_" . $field['name'] . ");";
        // перенос строки
        if ($cc_env['NL2BR'] && !$format['br'] || $format['br'] == 1)
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nl2br(\$f_" . $field['name'] . ");";
        if ($format['bbcode'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nc_bbcode(\$f_" . $field['name'] . ",  (\$fullDateLink ? \$fullDateLink : \$fullLink) );";
    }
    $text_fields = $component->get_fields(1);
    foreach ($text_fields as $field) {
        if (!$cc_env['AllowTags'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = htmlspecialchars_decode(\$f_" . $field['name'] . ");";
        if ($cc_env['NL2BR'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nl2br(\$f_" . $field['name'] . ");";
    }
    unset($format);
    unset($text_fields);


    // $fullSearchParams = getSearchParams($field_name, $field_type, $field_search, $srchPat);

    //$srchPat дважды urldecodeд и "+" теряется, берем значения из $_REQUEST которые уже один раз urldecodeд
    //если $_REQUEST['srchPat'] пустой, то srchPat передался через s_list_class, сохраняем его
    $fullSearchParams = $component->get_search_query(isset($srchPat) ? $_REQUEST['srchPat'] ? $_REQUEST['srchPat'] : $srchPat : array());


    $fullSearchStr = $fullSearchParams['query'];
    $fullSearchURL = $fullSearchParams['link'];

    if (!$ignore_catalogue) {
        $cond_catalogue = " AND sub.`Catalogue_ID` = '" . $catalogue . "' ";
        $cond_catalogue_add = " AND a.`Subdivision_ID` = sub.`Subdivision_ID` ";
        $cond_catalogue_addtable = ", `Subdivision` AS sub ";
    }

    $cond_sub = !$ignore_sub ? " AND a.`Subdivision_ID` = '" . ($mirror_data['Subdivision_ID'] ? $mirror_data['Subdivision_ID'] : $sub) . "' " : "";
    $cond_cc = !$ignore_cc ? " AND a.`Sub_Class_ID` = '" . ($mirror_data['Sub_Class_ID'] ? $mirror_data['Sub_Class_ID'] : $cc) . "' " : "";
    $cond_user = !$ignore_user ? " AND a.`User_ID` = '" . $AUTH_USER_ID . "' " : "";
    $cond_parent = !$ignore_parent ? " AND a.`Parent_Message_ID` = '" . $parent_message . "' " : "";
    $cond_search = $fullSearchStr;
    if (!$admin_mode && !$ignore_check)
        $cond_mod = " AND a.`Checked` = 1 ";
    if (isset($date) && $date && $date_field && strtotime($date) > 0)
        $cond_date = " AND a.`" . $date_field . "` LIKE '" . $db->escape($date) . "%' ";

    $cond_distinct = isset($distinct) && $distinct ? "DISTINCTROW" : "";
    $cond_distinct = isset($distinctrow) && $distinctrow ? "DISTINCTROW" : "";
    $cond_select = isset($query_select) && $query_select ? ", " . $query_select : "";
    $cond_where = isset($query_where) && $query_where ? " AND " . $query_where : "";
    $cond_group = isset($query_group) && $query_group ? " GROUP BY " . $query_group : "";
    $cond_having = isset($query_having) && $query_having ? " HAVING " . $query_having : "";
    if (isset($query_order) && $query_order)
        $sort_by = $query_order;
    if (!isset($query_from))
        $query_from = '';
    if (!isset($query_join))
        $query_join = '';

    if ($user_table_mode) {
        $cond_sub = "";
        $cond_cc = "";
        $cond_catalogue_add = "";
        $cond_catalogue = "";
        $cond_catalogue_addtable = "";
        $cond_parent = "";
    }

    if ($fullSearchURL)
        $intQueryStr .= (($intQueryStr == '?') ? '' : '&amp;') . $fullSearchURL;

    if (!$recNum) {
        $recNum = $maxRows;
    } else {
        $maxRows = $recNum;
        // для совместимости со старыми версиями до 2.4.5 и 3.0.0
        if (!isset($ignore_eval['maxRows']) || !$ignore_eval['maxRows'])
            eval("\$maxRows = \"" . $maxRows . "\";");
    }

    $maxRows = +$maxRows;



    // для совместимости со старыми версиями до 2.4.5 и 3.0.0
    if (!isset($ignore_eval['sort_by']) || !$ignore_eval['sort_by'])
        eval("\$sort_by = \"" . $sort_by . "\";");


    if (!$ignore_all) {
        $message_select = "SELECT" . (!$ignore_calc ? " SQL_CALC_FOUND_ROWS" : "") . " " . $cond_distinct . " " . $field_names . $cond_select . "
                       FROM (" . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a " . ( $query_from ? ", " . $query_from : "") . ")
                       " . $component->get_joins() . " " . $query_join . "
                       WHERE 1 " . $cond_parent . $cond_where . $cond_catalogue . $cond_sub . $cond_cc . $cond_user . $cond_mod . $cond_search . $cond_date .
                $cond_group .
                $cond_having .
                ($sort_by ? " ORDER BY " . $sort_by : "") .
                (!$ignore_limit ? " LIMIT " . ( isset($cc_env['cur_cc']) && $cc_env['cur_cc'] == $cur_cc ? $curPos : (!isset($cc_env['cur_cc']) ? $curPos : "0") ) . "," . $maxRows : ($query_limit ? " LIMIT " . $query_limit : "") );
    } elseif ($query_select && $query_from) {
        $message_select = "SELECT" . (!$ignore_calc ? " SQL_CALC_FOUND_ROWS" : "") . " " . $query_select . " FROM " . $query_from .
                ($query_join ? " " . $query_join : "") .
                ($query_where ? " WHERE " . $query_where : "") .
                ($query_group ? " GROUP BY " . $query_group : "") .
                ($query_having ? " HAVING  " . $query_having : "") .
                ($query_order ? " ORDER BY " . $query_order : "") .
                ($query_limit ? " LIMIT " . $query_limit : "");
    }

    $nc_prepared_data = 0;
    if (isset($nc_data) && (is_array($nc_data) || $nc_data instanceof ArrayAccess)) {
        $nc_prepared_data = 1;
        $message_select = false;
    }

    $addLink = $SUB_FOLDER . $cc_env['Hidden_URL'] . "add_" . $cc_env['EnglishName'] . ".html";
    $rssLink = $cc_env['AllowRSS'] ? $SUB_FOLDER . $cc_env['Hidden_URL'] . $cc_env['EnglishName'] . ".rss" : "";
    $xmlLink = $cc_env['AllowXML'] ? $SUB_FOLDER . $cc_env['Hidden_URL'] . $cc_env['EnglishName'] . ".xml" : "";
    $xmlFullLimk = '';
    $subscribeLink = $SUB_FOLDER . $cc_env['Hidden_URL'] . "subscribe_" . $cc_env['EnglishName'] . ".html";
    $searchLink = $SUB_FOLDER . $cc_env['Hidden_URL'] . "search_" . $cc_env['EnglishName'] . ".html";

    $cc_env['LocalQuery'] = $intQueryStr;
    $cc_env['curPos'] = $curPos;
    $cc_env['dateField'] = $date_field;
    $cc_env['recNum'] = $recNum;
    $cc_env['maxRows'] = $maxRows;
    $cc_env['addLink'] = $addLink;
    $cc_env['subscribeLink'] = $subscribeLink;
    $cc_env['searchLink'] = $searchLink;
    $cc_env['fieldCount'] = count($component->get_fields());
    // cache eval section
    if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && is_object($nc_cache_list) && $cached_eval && $cached_result != -1) {
        // get cached objects blocks
        $component_cache_blocks = $nc_cache_list->getCachedBlocks($cached_data);

        // cached prefix
        eval("\$result = \"" . $component_cache_blocks['prefix'] . "\";");

        if (is_array($component_cache_blocks) && !empty($component_cache_blocks)) {
            // concat cached objects
            foreach ($component_cache_blocks['objects'] as $k => $v) {
                // extract cached object variables
                if (!empty($cache_vars) && is_array($cache_vars[$k])) {
                    extract($cache_vars[$k]);
                }
                // append obect data
                eval("\$result.= \"" . $v . "\";");
            }
        }

        // cached suffix
        eval("\$result.= \"" . $component_cache_blocks['suffix'] . "\";");

        return $result;
    }

    // in this section check usable or not add/search forms in component
    $cc_env["AddTemplate"] = $cc_env["AddTemplate"] ? $cc_env["AddTemplate"] : $component->add_form($catalogue, $sub, $cc);
    $cc_env["FullSearchTemplate"] = $cc_env["FullSearchTemplate"] ? $cc_env["FullSearchTemplate"] : $component->search_form(1);
    $component_body = (!$ignore_prefix ? $cc_env['FormPrefix'] : "") . (!$ignore_suffix ? $cc_env['FormSuffix'] : "") . $cc_env['RecordTemplate'] . $cc_env['RecordTemplateFull'] . $cc_env['Settings'];

    if (nc_strpos($component_body, '$addForm') !== false) {
        $SQL = "SELECT Field_Name,
                       Description
                    FROM Field
                        WHERE Class_ID = $classID
                          AND TypeOfData_ID = 11";
        $multifield = (array) $db->get_results($SQL);
        $multifield_names = array();

        foreach ($multifield as $multifield_row) {
            ${'f_' . $multifield_row->Field_Name} = new nc_multifield($multifield_row->Field_Name, $multifield_row->Description);
            $multifield_names[] = 'f_' . $multifield_row->Field_Name;
        }

        eval("\$addForm = \"" . $cc_env["AddTemplate"] . "\";");

        foreach ($multifield_names as $multifield_name) {
            unset(${$multifield_name});
        }

        unset($multifield_names);
    }

    if (nc_strpos($component_body, '$searchForm') !== false)
        eval("\$searchForm = \"" . $cc_env["FullSearchTemplate"] . "\";");
    unset($component_body);
    // ==================================================


    $db->last_error = "";

    // main query execution
    if ($message_select) {
        $res = $db->get_results($message_select, ARRAY_N);
    }


    // error in query
    if ($db->last_error) {
        // determine error
        switch (true) {
            case preg_match("/Table '\w+\.Classificator_(\w+)' doesn't exist/i", $db->last_error, $regs):
                $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR, $regs[1]);
                break;
            case preg_match("/Unknown column '(.+?)' in 'field list'/i", $db->last_error, $regs):
                $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_UNKNOWN, $regs[1]);
                break;
            case preg_match("/Unknown column '(.+?)' in 'order clause'/i", $db->last_error, $regs):
                $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_CLAUSE, $regs[1]);
                break;
            case $SHOW_MYSQL_ERRORS == "on":
                $err = $db->last_error;
                break;
            default:
                $err = "";
        }

        // error message
        if (is_object($perm) && $perm->isSupervisor()) {
            // error info for the supervisor
            if ($nc_core->inside_admin) {
				nc_print_status(NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER, 'error');
			}
            trigger_error(sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_SUPERVISOR, $sub, $cc, $query_string, ($err ? $err . ", " : "")), E_USER_WARNING);
        } else {
            // error info for the simple users
            echo NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER;
        }
        // return
        return false;
    }

    if ($message_select) {
        // object in this page
        $rowCount = $db->num_rows;
        // total objects
        $totRows = !$ignore_calc ? $db->get_var("SELECT FOUND_ROWS()") : $rowCount;
        $totRows += 0;
    } else if ($nc_prepared_data) {
        $rowCount = sizeof($nc_data);
        $totRows += 0;
        if (!$totRows) {
            $totRows = $rowCount;
        }
    } else {
        $rowCount = 0;
        $totRows = 0;
    }

    $_get_arr = $nc_core->input->fetch_get();
    $get_param_str = '';
    // в nextLink и prevLink нужно сохранить get-параметры
    if (!empty($_get_arr)) {
        // ignore array
        $ignore_arr = array('sid', 'ced', 'inside_admin', 'catalogue', 'sub', 'cc', 'curPos', 'cur_cc', 'REQUEST_URI');
        if ($inside_admin || $admin_mode) {
            $ignore_arr[] = 'isNaked';
        }
        // check
        foreach ($_get_arr as $k => $v) {
            if (!in_array($k, $ignore_arr))
                $get_param[$k] = $v;
        }
        // build string
        if (!empty($get_param))
            $get_param_str = $nc_core->url->build_url($get_param);
    }
    // clear
    unset($_get_arr);

    $begRow = $curPos + 1;
    $prevLink = $nextLink = '';
    if ($curPos > $maxRows) {
        $prevLink = (!$admin_mode ? $nc_core->url->get_parsed_url('path') : "") . $cc_env['LocalQuery'] . (nc_strlen($cc_env['LocalQuery']) > 1 ? "&amp;" : "") . "curPos=" . ($curPos - $maxRows) . ($cc_env['cur_cc'] ? "&amp;cur_cc=" . $cc_env['cur_cc'] : "") . ($classPreview == $cc_env["Class_ID"] ? "&amp;classPreview=" . $classPreview : "") . ($get_param_str ? "&amp;" . $get_param_str : "");
    }

    if ($curPos == $maxRows) {
        $prevLink = (!$admin_mode ? $nc_core->url->get_parsed_url('path') : "") . $cc_env['LocalQuery'] . (nc_strlen($cc_env['LocalQuery']) > 1 ? "&amp;" : "") . ( $get_param_str ? $get_param_str : "");
    }

    $endRow = $curPos + $maxRows;
    if ($endRow < $totRows) {
        $nextLink = (!$admin_mode ? $nc_core->url->get_parsed_url('path') : "") . $cc_env['LocalQuery'] . (nc_strlen($cc_env['LocalQuery']) > 1 ? "&amp;" : "") . "curPos=" . $endRow . ( isset($cc_env['cur_cc']) && $cc_env['cur_cc'] ? "&amp;cur_cc=" . $cc_env['cur_cc'] : "") . ($classPreview == $cc_env["Class_ID"] ? "&amp;classPreview=" . $classPreview : "") . ($get_param_str ? "&amp;" . $get_param_str : "");
    } else {
        $endRow = $totRows;
    }

    $cc_env['begRow'] = $begRow;
    $cc_env['endRow'] = $endRow;
    $cc_env['totRows'] = $totRows;
    $cc_env['prevLink'] = $prevLink;
    $cc_env['nextLink'] = $nextLink;

    if (!$ignore_all) {
        $fetch_row = "list(" . $field_vars . ($result_vars ? ", " . $result_vars : "") . ") = \$res[\$f_RowNum];";
    } else {
        $fetch_row = $result_vars ? "list(" . $result_vars . ") = \$res[\$f_RowNum];" : "";
    }

    if ($nc_prepared_data && isset($nc_data[0])) {
        $fetch_row = '$f_Checked = 1; ';
        // нужно подготовить $fetch_row вида:
        // $f_a = $nc_data[$f_RowNum]['a']; $f_b = $nc_data[$f_RowNum]['b']; ...
        // элементы $nc_data могут быть как массивом, так и объектом, реализующим Iterator, поэтому array_keys не подходит
        foreach ($nc_data[0] as $key => $value) {
            $fetch_row .= '$f_' . $key . ' = $nc_data[$f_RowNum]["' . $key . '"]; ';
        }
    }

    // Право на модерирование и изменение объектов.
    $modPerm = false;
    $changePerm = false;

    if ($admin_mode) {
        $modPerm = CheckUserRights($cc, 'moderate', 1); // право модератора
        $changePerm = s_auth($cc_env, 'change', 1); //               или просто на изменение объектов

        if (is_object($perm) && $perm->isBanned($cc_env, 'change'))
            $modPerm = $changePerm = false; // пользователю запретили изменение объектов

        $f_AdminCommon_add = $admin_url_prefix."add.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc;
        $addLink = $f_AdminCommon_add;
        // удалить все
        $f_AdminCommon_delete_all = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;delete=1";
        // экспорт и импорт в CSV
        $f_AdminCommon_export_csv = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;export=1";
        $f_AdminCommon_import_csv = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;import=1";
        // экспорт и импорт в XML
        $f_AdminCommon_export_xml = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;export=2";
        $f_AdminCommon_import_xml = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;import=2";

        // Js и форма для пакетной обработки объектов
        /*$f_AdminCommon_package = "<script type='text/javascript' language='javascript'>\n";
        $f_AdminCommon_package.= "\tnc_package_obj.new_cc(".$cc.", '".NETCAT_MODERATION_NOTSELECTEDOBJ."');\n";
        $f_AdminCommon_package.= "</script>\n";*/
        $f_AdminCommon_package.= "<form id='nc_form_selected_".$cc."' action='".$SUB_FOLDER.$HTTP_ROOT_PATH."message.php' method='post'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='catalogue' value='".$catalogue."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='sub' value='".$sub."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='cc' value='".$cc."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='curPos' value='".$curPos."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='admin_mode' value='".$admin_mode."'>\n";
        $f_AdminCommon_package.= "</form>\n";

        if ($list_mode != "select") {
            if ($inside_admin && $isMainContent && $UI_CONFIG) {
                // в админке нет AdminCommon, но нужна часть для пакетной обработки
                if ($totRows != 0)
                    $result.= $f_AdminCommon_package;
                // add button
                $UI_CONFIG->actionButtons = array();
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "addObject",
                        "caption" => NETCAT_MODERATION_BUTTON_ADD,
                        "action" => "parent.nc_form('{$SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}add.php?inside_admin=1&cc=$cc')",
                        "align" => "left"
                );

                // кнопки пакетной обработки нужны только если есть объекты
                if ($totRows != 0) {
                    //  button "delete all"
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "deleteAll",
                            "caption" => NETCAT_MODERATION_REMALL,
                            "style" => "delete",
                            "action" => "urlDispatcher.load('subclass.purge(" . $cc . ")')"
                    );
                }
            }
            if (!$inside_admin) {
                $f_AdminCommon = nc_AdminCommon($sub, $cc, $cc_env, $f_AdminCommon_package, $f_AdminCommon_add, $f_AdminCommon_delete_all);
            }
        }
    } else {
        // not admin mode
        $f_AdminCommon_cc = "";
        $f_AdminCommon_cc_name = "";
        $f_AdminCommon_add = "";
        $f_AdminCommon_delete_all = "";
        $f_AdminCommon = "";
        $f_AdminButtons = "";
    }

    $row_ids = array(); // массив, в который будут складываться ID всех узлов
    for ($i = 0; $i < $rowCount; $i++) {
        $row_ids[] = $res[$i][0];
    }

    // component prefix
    if ($cc_env['FormPrefix'] && !$ignore_prefix) {
        eval("\$result.= \"" . $cc_env["FormPrefix"] . "\";");
    } else {
        $result.= $f_AdminCommon;
    }

    // если список пуст, внутри админки нужно показать сообщение "нет объектов"
    if ($inside_admin && $totRows == 0 && !nc_strlen(trim($result))) {
        $result.= nc_print_status(NETCAT_MODERATION_NO_OBJECTS_IN_SUBCLASS, 'info', null, 1);
        //"<div class='status_info'><span style='margin-left: 15px; padding-top: 15px; vertical-align: middle;'><p align='center'><b>" . NETCAT_MODERATION_NO_OBJECTS_IN_SUBCLASS . "</b></p></span></div>";
    }

    if (!empty($row_ids)) {

        $multifile_fileds = $component->get_fields(11);
        if (!empty($multifile_fileds)) {
            extract(nc_get_arrays_multifield_for_nc_object_list_and_full_php($multifile_fileds, $row_ids));
        }

        // получаем все файлы выбранных объектов
        $nc_fields_files = $component->get_fields(6, 0);
        $nc_files_in_class = array();
        if (!empty($nc_fields_files)) {
            // get data for all file_fields
            $SQL = "SELECT `Field_ID`,
                       `Message_ID`,
                       `Virt_Name`,
                       `Real_Name`,
                       `Download`
                    FROM `Filetable`
                        WHERE `Field_ID` IN (" . join(", ", array_keys($nc_fields_files)) . ")
                          AND `Message_ID` IN (" . join(", ", $row_ids) . ")";
            $filetable = $db->get_results($SQL, ARRAY_A);

            // sorting array
            if (!empty($filetable))
                foreach ($filetable AS $v) {
                    $nc_files_in_class[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['Real_Name'], $v['Download']);
                }
            unset($filetable);
        }
    }

    // требуется получить все группы пользователей
    if ($user_table_mode && !empty($row_ids)) {
        $nc_user_group = $db->get_results("SELECT ug.`User_ID`, ug.`PermissionGroup_ID`, g.`PermissionGroup_Name`
                                       FROM `User_Group` AS ug,`PermissionGroup` AS g
                                       WHERE User_ID IN (" . join(', ', $row_ids) . ")
                                       AND g.`PermissionGroup_ID` = ug.`PermissionGroup_ID` ", ARRAY_A);
        if (!empty($nc_user_group)) {
            foreach ($nc_user_group as $v) {
                $nc_user_group_sort[$v['User_ID']][$v['PermissionGroup_ID']] = $v['PermissionGroup_Name'];
            }
        }
        unset($nc_user_group);
    }


    // =========================  Листинг объектов ======================================

    $cache_vars = array();

    for ($f_RowNum = 0; $f_RowNum < $rowCount; $f_RowNum++) {
        // all result vars
        eval($fetch_row);

        if ($no_cache_marks) {
            // caching variables array
            $cache_vars[$f_RowNum] = array();
            // get variables names string
            preg_match("/^list\((.*?)\).*?$/", $fetch_row, $matches);
            if ($matches[1]) {
                // variables by name array
                $cache_vars_name = explode(",", $matches[1]);
                if (!empty($cache_vars_name)) {
                    // correcting
                    foreach ($cache_vars_name as $k => $v) {
                        $_variable_name = trim(str_replace('$', "", $v));
                        $cache_vars[$f_RowNum][$_variable_name] = $$_variable_name;
                        // clear
                        unset($_variable_name);
                    }
                    // clear
                    unset($cache_vars_name);
                }
            }
        }

        if (!empty($multifile_filed_names)) {
            foreach ($multifile_filed_names as $field_name) {
                ${'f_' . $field_name} = new nc_multifield($field_name);
                ${'f_' . $field_name}->set_data($multifiles[$f_RowID][$field_name])->template->set(${'f_' . $field_name . '_tpl'});
            }
        }

        if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY === 'User_ID') {
            $f_AdminInterface_user_add = $f_UserID;
            $f_AdminInterface_user_change = $f_LastUserID;
        }


        // Multiselect
        if (!empty($multilist_fileds)) {
            // просмотр каждого поля типа multiselect
            foreach ($multilist_fileds as $multilist_filed) {
                // таблицу с элементами можно взять их кэша, если ее там нет - то добавить
                if (!$_cache['classificator'][$multilist_filed['table']]) {
                    $db_res = $db->get_results("SELECT `" . $multilist_filed['table'] . "_ID` AS ID, `" . $multilist_filed['table'] . "_Name` AS Name, `Value`
                                       FROM `Classificator_" . $multilist_filed['table'] . "`", ARRAY_A);
                    if (!empty($db_res)) {
                        foreach ($db_res as $v) { // запись в кэш
                            $_cache['classificator'][$multilist_filed['table']][$v['ID']] = array($v['Name'], $v['Value']);
                        }
                    }
                    unset($db_res);
                }


                ${"f_" . $multilist_filed['name'] . "_id"} = array();
                ${"f_" . $multilist_filed['name'] . "_value"} = array();

                if (($value = ${"f_" . $multilist_filed['name']})) { // значение из базы
                    ${"f_" . $multilist_filed['name']} = array();
                    ${"f_" . $multilist_filed['name'] . "_id"} = array();
                    $ids = explode(',', $value);
                    if (!empty($ids)) {
                        foreach ($ids as $id) { // для каждого элемента по id определяем имя и значение
                            if ($id) {
                                array_push(${"f_" . $multilist_filed['name']}, $_cache['classificator'][$multilist_filed['table']][$id][0]);
                                array_push(${"f_" . $multilist_filed['name'] . "_value"}, $_cache['classificator'][$multilist_filed['table']][$id][1]);
                                array_push(${"f_" . $multilist_filed['name'] . "_id"}, $id);
                            }
                        }
                    }
                }
                // default values
                if (!is_array(${"f_" . $multilist_filed['name']}))
                    ${"f_" . $multilist_filed['name']} = array();
            }
            unset($ids);
            unset($id);
            unset($value);
        } //end multiselect
        // get files for message

        if (!empty($nc_fields_files)) {

            foreach ($nc_fields_files AS $field_id => $field_name) {
				unset(${"f_" . $field_name . "_name"});
                unset(${"f_" . $field_name . "_type"});
                unset(${"f_" . $field_name . "_size"});
                unset(${"f_" . $field_name . "_download"});
                unset(${"f_" . $field_name . "_url"});
                //  apparently we don't have a file
                if (!${"f_" . $field_name}) {
                    ${"f_" . $field_name . "_url"} = "";
                    continue;
                }
                //file_data - массив с ориг.названием, типом, размером, [именем_файла_на_диске]
                $file_data = explode(':', ${"f_" . $field_name});


                $filetable_path = ($user_table_mode ? "u" : $f_Subdivision_ID . "/" . $f_Sub_Class_ID);
                ${"f_" . $field_name . "_name"} = $file_data[0];
                ${"f_" . $field_name . "_type"} = $file_data[1];
                ${"f_" . $field_name . "_size"} = $file_data[2];
                // обнулим
                $Virt_Name = "";
                $nc_download = 0;

                // get data from files array
                if (is_array($nc_files_in_class[$f_RowID][$field_id])) {
                    list($Virt_Name, $Real_Name, $nc_download) = $nc_files_in_class[$f_RowID][$field_id];
                }

                if ($Virt_Name) { // файловая система c Filetable
                    ${"f_" . $field_name} = $SUB_FOLDER . $HTTP_FILES_PATH . $filetable_path . "/h_" . $Virt_Name;
                    ${"f_" . $field_name . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $filetable_path . "/" . $Virt_Name;
                    ${"f_" . $field_name . "_download"} = $nc_download;
                } else {
                    if ($file_data[3]) { // файловая система "Original"
                        ${"f_" . $field_name} = ${"f_" . $field_name . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $file_data[3];
                    } else {
                        $ext = substr($file_data[0], strrpos($file_data[0], "."));
                        ${"f_" . $field_name . "_url"} = ${"f_" . $field_name} = $SUB_FOLDER . $HTTP_FILES_PATH . $field_id . "_" . $f_RowID . $ext;
                    }
                }
            }
        }

        if ($user_table_mode) {
            $f_PermissionGroup = &$nc_user_group_sort[$f_RowID];
        }

        // created date
        $f_Created_year = substr($f_Created, 0, 4);
        $f_Created_month = substr($f_Created, 5, 2);
        $f_Created_day = substr($f_Created, 8, 2);
        $f_Created_hours = substr($f_Created, 11, 2);
        $f_Created_minutes = substr($f_Created, 14, 2);
        $f_Created_seconds = substr($f_Created, 17, 2);
        $f_Created_date = $f_Created_day . "." . $f_Created_month . "." . $f_Created_year;
        $f_Created_time = $f_Created_hours . ":" . $f_Created_minutes . ":" . $f_Created_seconds;

        // last updated date
        if (isset($f_LastUpdated) && $f_LastUpdated) {
            $f_LastUpdated_year = substr($f_LastUpdated, 0, 4);
            $f_LastUpdated_month = substr($f_LastUpdated, 4, 2);
            $f_LastUpdated_day = substr($f_LastUpdated, 6, 2);
            $f_LastUpdated_hours = substr($f_LastUpdated, 8, 2);
            $f_LastUpdated_minutes = substr($f_LastUpdated, 10, 2);
            $f_LastUpdated_seconds = substr($f_LastUpdated, 12, 2);
        }

        if ($admin_mode && !$nc_prepared_data) {
            $dateLink = '';
            if ($date_field) {
                eval("\$dateLink = \"&date=\".\$f_" . $date_field . "_year.\"-\".\$f_" . $date_field . "_month.\"-\".\$f_" . $date_field . "_day;");
            }

            // full link for object
            $fullLink = nc_get_fullLink($admin_url_prefix, $catalogue, $sub, $cc, $f_RowID);
            $fullDateLink = nc_get_fullDateLink($fullLink, $dateLink);

            // ID объекта в шаблоне
            $f_AdminButtons_id = $f_RowID;

            // Приоритет объекта
            $f_AdminButtons_priority = $f_Priority;

            // ID добавившего пользователя
            $f_AdminButtons_user_add = $f_UserID;

            // ID изменившего пользователя
            $f_AdminButtons_user_change = nc_get_AdminButtons_user_change($f_LastUserID);

            // копировать объект
            $f_AdminButtons_copy = nc_get_AdminButtons_copy($ADMIN_PATH, $catalogue, $sub, $cc, $classID, $f_RowID);

            // изменить
            $f_AdminButtons_change = nc_get_AdminButtons_change($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos);
            $editLink = $f_AdminButtons_change;

            // удалить
            $f_AdminButtons_delete = nc_get_AdminButtons_delete($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos);
            $deleteLink = $f_AdminButtons_delete;
            $dropLink = nc_get_dropLink($deleteLink, $nc_core);

            // включить-выключить
            $f_AdminButtons_check = nc_get_AdminButtons_check($f_Checked, $any_url_prefix, $SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $classID, $f_RowID, $curPos, $admin_mode, $admin_url_prefix, $nc_core);
            $checkedLink = $f_AdminButtons_check;

            // выбрать связанный (JS код!!!) -- когда список вызван в popup для выбора связанного объекта
            $f_AdminButtons_select = nc_get_AdminButtons_select($f_AdminButtons_id);

            if ($list_mode == 'select') {
                $f_AdminButtons_buttons = nc_get_list_mode_select_AdminButtons_buttons($f_AdminButtons_select, $ADMIN_TEMPLATE);
                $f_AdminButtons  = nc_get_list_mode_select($f_Checked, $classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_buttons);
            } else {
                if ($system_env['AdminButtonsType']) {
                    eval("\$f_AdminButtons = \"" . $system_env['AdminButtons'] . "\";");
                } else {
                    $f_AdminButtons_buttons = nc_get_AdminButtons_buttons($f_RowID, $f_Checked, $f_AdminButtons_check, $f_AdminButtons_copy, $f_AdminButtons_change, $f_AdminButtons_delete, $classID);
                    $f_AdminButtons = nc_get_AdminButtons_prefix($f_Checked, $cc);
                    // проверка прав
                    if ($modPerm || ($changePerm && $f_AdminButtons_user_add == $AUTH_USER_ID)) {
                        $f_AdminButtons.= nc_get_AdminButtons_modPerm($classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_priority, $f_AdminInterface_user_add, $f_AdminButtons_user_add, $f_AdminInterface_user_change, $f_AdminButtons_user_change, $f_AdminButtons_buttons, $cc);
                    } else {
                        $f_AdminButtons.= nc_get_AdminButtons_modPerm_else($classID, $f_RowID);
                    }
                    $f_AdminButtons.= nc_get_AdminButtons_suffix();
                }
            }
            if ($user_table_mode)
                $f_AdminButtons = "";
        }
        else {
            $f_AdminButtons_id = "";
            $f_AdminButtons_priority = "";
            $f_AdminButtons_user_add = "";
            $f_AdminButtons_user_change = "";
            $f_AdminButtons_copy = "";
            $f_AdminButtons_change = "";
            $f_AdminButtons_delete = "";
            $f_AdminButtons_check = "";
            $f_AdminButtons_select = "";
            $f_AdminButtons = "";
            if ($ignore_link || $cc_env['SrcMirror']) {
                    $subLink = $SUB_FOLDER . $cc_env['Hidden_URL'];
                    $cc_keyword = $cc_env['EnglishName'];
            }
            //$msgLink = ($f_Keyword && $f_Sub_Class_ID==$cc) ? $f_Keyword : $cc_keyword."_".$f_RowID;
            if (!isset($f_Keyword))
                $f_Keyword = '';
            $msgLink = ($f_Keyword) ? $f_Keyword : $cc_keyword . "_" . $f_RowID;
            $dateLink = '';
            if ($date_field)
                eval("\$dateLink = \$f_" . $date_field . "_year.\"/\".\$f_" . $date_field . "_month.\"/\".\$f_" . $date_field . "_day.\"/\";");

            // current host
            if ($mirror_data['Hidden_Host']) {
                $subHost = "http://" . ($mirror_data['Hidden_Host'] ? ( strchr($mirror_data['Hidden_Host'], ".") ? $mirror_data['Hidden_Host'] : $mirror_data['Hidden_Host'] . "." . $DOMAIN_NAME ) : $DOMAIN_NAME);
            }
            $subHost = "http://" . ($cc_env['Hidden_Host'] ? ( strchr($cc_env['Hidden_Host'], ".") ? $cc_env['Hidden_Host'] : $cc_env['Hidden_Host'] . "." . $DOMAIN_NAME ) : $DOMAIN_NAME);

            // $subLink, $ccLink, $cc_keyword
            if ($admin_mode) {
                $subLink = $admin_url_prefix . ($mirror_data['Subdivision_ID'] ? '?catalogue=' . $cc_env['Catalogue_ID'] . '&amp;sub=' . $cc_env['Subdivision_ID'] : '?catalogue=' . $catalogue . '&amp;sub=' . $sub);
                $ccLink = $subLink . ($mirror_data['Sub_Class_ID'] ? '&amp;cc=' . $cc_env['Sub_Class_ID'] : '&amp;cc=' . $cc);
                $intQueryStr = $ccLink;
            } else {
                $ccLink = $subLink . $cc_keyword . '.html';
            }

            if ($catalogue == $current_catalogue['Catalogue_ID']) {
                $fullLink = $subLink . $msgLink . ".html"; // полный вывод
                $fullRSSLink = $cc_env['AllowRSS'] ? $subLink . $msgLink . ".rss" : ""; // rss
                $fullXMLLimk = $cc_env['AllowXML'] ? $subLink . $msgLink . ".xml" : "";
                $fullDateLink = $subLink . $dateLink . $msgLink . ".html"; // полный вывод с датой
                $editLink = $subLink . "edit_" . $msgLink . ".html"; // ccылка для редактирования
                $deleteLink = $subLink . "delete_" . $msgLink . ".html"; // удаления
                $dropLink = $subLink . "drop_" . $msgLink . ".html" . ($nc_core->token->is_use('drop') ? "?" . $nc_core->token->get_url() : ""); // удаления без подтверждения
                $checkedLink = $subLink . "checked_" . $msgLink . ".html"; // включения\выключения
                $subscribeMessageLink = $subLink . "subscribe_" . $msgLink . ".html"; // подписка на объект
            } else {
                $fullLink = $subHost . $subLink . $msgLink . ".html";
                $fullRSSLink = $cc_env['AllowRSS'] ? $subHost . $subLink . $msgLink . ".html" : "";
                $fullXMLLimk = $cc_env['AllowXML'] ? $subHost . $subLink . $msgLink . ".xml" : "";
                $fullDateLink = $subHost . $subLink . $dateLink . $msgLink . ".html";
                $editLink = $subHost . "edit_" . $msgLink . ".html";
                $deleteLink = $subHost . "delete_" . $msgLink . ".html";
                $dropLink = $subHost . "drop_" . $msgLink . ".html";
                $checkedLink = $subHost . "checked_" . $msgLink . ".html";
                $subscribeMessageLink = $subHost . "subscribe_" . $msgLink . ".html"; // подписка на объект
            }

            // Если это превью данного компонента то, мы добавляем перенменную к ссылкам на полный просмотр объекта
            if ($classPreview == $cc_env["Class_ID"]) {
                $fullLink.= "?classPreview=" . $classPreview;
                $fullDateLink.="?classPreview=" . $classPreview;
            }
        }
        eval($cc_env['convert2txt']);
        eval("\$row = \"" . nc_preg_replace('/\$result\b/', '$row', $cc_env["RecordTemplate"]) . "\";");

        // внутри админки: для того, чтобы объекты можно было перетаскивать...
        // ... сделаем "обертку" с ID, номером класса и ID родителя:
        if ($inside_admin) {
            $row_id_string = "id='message" . $classID . "-" . $f_RowID . "' messageParent='" . $parent_message . "' messageClass='" . $classID . "' messageSubclass='" . $cc . "' dragLabel='" . htmlspecialchars_decode($cc_env['Class_Name'] . " #" . $f_RowID) . "'";
            // попытаемся найти тэг, в который вложена строка...
            if (nc_preg_match("@^\s*<(\w+).+</\\1>\s*$@s", $row, $regs)) {
                $row = nc_preg_replace("@^(\s*<" . $regs[1] . ")@s", "$1 " . $row_id_string, $row);
            } // если не удалось - добавим <div>
            else {
                $row = "<div " . $row_id_string . ">" . $row . "</div>";
            }
        }
        $result.= ($no_cache_marks ? "<!-- nocache_object_" . $f_RowNum . " -->" : "") . $row . ($no_cache_marks ? "<!-- /nocache_object_" . $f_RowNum . " -->" : "");
    }

    // component suffix
    if ($cc_env['FormSuffix'] && !$ignore_suffix) {
        eval("\$result .= \"" . $cc_env["FormSuffix"] . "\";");
    }
    // добавить скрипт для D&D
    if ($inside_admin && !$user_table_mode && $perm->isSubClassAdmin($cc)) {
        // приоритет позволять менять только если отсортировано по умолчанию (Priority DESC)
        $change_priority = (((preg_match('/^[\s]*[a.`]*Priority`?[\s]*(desc|asc)?[\s]*$/i', $cc_env['SortBy']) || !$cc_env['SortBy']) && !$query_order ) ? 'true' : 'false');
        $result.= "<script type='text/javascript' language='Javascript'>";
        $result.= "if (typeof formAsyncSaveEnabled!='undefined') messageInitDrag(" . nc_array_json(array($classID => $row_ids)) . ", " . $change_priority . ");";
        $result.= "</script>";
    }


    // title
    global $cc_array;
    if ($isMainContent && (!$isSubClassArray || $cc_array[0] == $cc)) {
        $title = '';
        if ($cc_env['TitleList'])
            eval("\$title = \"" . $cc_env['TitleList'] . "\";");
        if ($title) {
            $nc_core->page->set_metatags('title', $title);
            $cc_env['Cache_Access_ID'] = 2;
        }
    }


    // cache section
    if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && is_object($nc_cache_list) && !$user_table_mode && !$nc_prepared_data) {
        try {
            $bytes = $nc_cache_list->add($sub, $cc, $cache_key, $result, $cache_vars);
            // drop cache marks
            if ($no_cache_marks)
                $result = $nc_cache_list->nocacheClear($result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, sub[" . $sub . "], cc[" . $cc . "], Access_ID[" . $cc_env['Cache_Access_ID'] . "], Lifetime[" . $cc_env['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_list->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_list->errorMessage($e);
        }
    }

    return $result;
}

function nc_objects_list_file($sub, $cc, $query_string, $show_in_admin_mode) {
    $LIST_VARS = array();

    parse_str($query_string, $LIST_VARS);

    $ignore_array = array(
            "nc_core", "inside_admin", "admin_mode", "catalogue", "cc", "cond_catalogue", "cond_catalogue_add", "cond_catalogue_addtable",
            "cond_cc", "cond_check", "cond_date", "cond_distinct", "cond_group", "cond_having", "cond_mod", "cond_parent",
            "cond_search", "cond_select", "cond_sub", "cond_user", "cond_where", "distinct", "distinctrow", "ignore_all", "ignore_limit", "ignore_cc", "ignore_check",
            "ignore_parent", "ignore_sub", "ignore_user", "ignore_calc", "query_from", "query_group", "query_join", "query_order", "query_select",
            "query_where", "query_group", "query_having", "query_limit", "result_vars", "sort_by", "sub", "user_table_mode",
            "cc_env", "field_vars", "message_select", "ignore_eval", "nc_data"
    );

    $ignore_array = array_flip($ignore_array);
    $LIST_VARS = array_diff_key($LIST_VARS, $ignore_array);
    extract($LIST_VARS);

    global $UI_CONFIG, $perm, $_cache, $admin_url_prefix, $classPreview;
    global $AUTH_USER_ID, $AUTH_USER_GROUP, $current_user;
    global $sub_level_count, $parent_sub_tree;
    // for old modules (forum)
    global $current_catalogue, $current_sub, $current_cc;
    global $nc_minishop;
    global $cc_array, $CLASS_TEMPLATE_FOLDER, $INCLUDE_FOLDER;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    // modules variables
    $MODULE_VARS = $nc_core->modules->get_module_vars();
    // system variables
    $FILES_FOLDER = $nc_core->get_variable("FILES_FOLDER");
    $HTTP_ROOT_PATH = $nc_core->get_variable("HTTP_ROOT_PATH");
    $ADMIN_PATH = $nc_core->get_variable("ADMIN_PATH");
    $ADMIN_TEMPLATE = $nc_core->get_variable("ADMIN_TEMPLATE");
    $DOMAIN_NAME = $nc_core->get_variable("DOMAIN_NAME");
    $SHOW_MYSQL_ERRORS = $nc_core->get_variable("SHOW_MYSQL_ERRORS");
    $AUTHORIZE_BY = $nc_core->get_variable("AUTHORIZE_BY");
    $HTTP_FILES_PATH = $nc_core->get_variable("HTTP_FILES_PATH");
    $DOCUMENT_ROOT = $nc_core->get_variable("DOCUMENT_ROOT");
    $SUB_FOLDER = $nc_core->get_variable("SUB_FOLDER");
    $inside_admin = $nc_core->inside_admin;
    $admin_mode = $nc_core->admin_mode;
    $system_env = $nc_core->get_settings();
    $current_catalogue = $nc_core->catalogue->get_current();
    $current_sub = $nc_core->subdivision->get_current();
    $current_cc = $nc_core->sub_class->get_current();

    $ignore_eval = array();

    if (!isset($nc_title))
        $nc_title = 0;
    if (!isset($isMainContent))
        $isMainContent = 0;
    if (!isset($isSubClassArray))
        $isSubClassArray = 0;
    if (!isset($query_select))
        $query_select = false;
    if (!isset($cur_cc))
        $cur_cc = false;

    // validate
    $catalogue = +$catalogue;
    $template = +$template;
    $parent_message = +$parent_message;
    $nc_ctpl = +$nc_ctpl;

    if (!$nc_ctpl && $nc_title) {
        $nc_ctpl = 'title';
    }

    if (!$cc) {
        return false;
    }

    if (($cc != $current_cc['Sub_Class_ID'] || !$isMainContent) && $admin_mode && !$show_in_admin_mode) {
        $Subdivision_ID = $nc_core->sub_class->get_by_id($cc, 'Subdivision_ID');
        $Subdivision_Name = $nc_core->subdivision->get_by_id($Subdivision_ID, 'Subdivision_Name');
        return nc_print_status( sprintf(CONTROL_CONTENT_SUBCLASS_EDIT_IN_PLACE, "/netcat/index.php?sub=" . $Subdivision_ID . "&cc=" . $cc, $Subdivision_Name), 'info', NULL, true );
    }

    try {
        $cc_env = $nc_core->sub_class->get_by_id($cc, null, $nc_ctpl);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    if ($admin_mode && $cc_env['Edit_Class_Template']) {
        try {
            $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Edit_Class_Template']);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    if ($admin_mode && $cc_env['Admin_Class_Template']) {
        $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Admin_Class_Template']);
    }

    $_db_cc = $cc;
    $_db_sub = $sub;
    $_db_Class_ID = $cc_env['Class_ID'];
    $_db_File_Path = $cc_env['File_Path'];
    $_db_File_Hash['File_Hash'];

    if ($cc_env['SrcMirror']) {
        $mirror_env = $cc_env;
        $mirror_data = $cc_env = $nc_core->sub_class->get_by_id($cc_env['SrcMirror']);
        $cc = $mirror_data['Sub_Class_ID'];
        $sub = $mirror_data['Subdivision_ID'];
    }

    // записываем реальный номер шаблона компонента
    if ($nc_ctpl === 'title') {
        $nc_ctpl = $cc_env['Real_Class_ID'];
    }

    if (!$sub) {
        $sub = $cc_env['Subdivision_ID'];
    }

    if ($cc_env['Type'] == 'rss' || $cc_env['Type'] == 'xml') {
        $cc_env['Cache_Access_ID'] = 2;
    }

    // если preview для нашего класса, то подменим cc_env из $_SESSION
    if ($classPreview == ($cc_env["Class_Template_ID"] ? $cc_env["Class_Template_ID"] : $cc_env["Class_ID"])) {
        $magic_gpc = get_magic_quotes_gpc();
        if (!empty($_SESSION["PreviewClass"][$classPreview])) {
            foreach ($_SESSION["PreviewClass"][$classPreview] as $tkey => $tvalue) {
                $cc_env[$tkey] = $magic_gpc ? stripslashes($tvalue) : $tvalue;
            }
        }
        // Запретим кеширование в режиме предпросмотра.
        $cc_env['Cache_Access_ID'] = 2;
    }

    // Если присутствует параметр isSubClassArray в вызове функции s_list_class(), то добавляем
    // в массив $cc_env элемент cur_cc, который будет участвовать в формировании навигации по страницам
    // при отображении нескольких шаблонов на странице
    if (isset($isSubClassArray) && $isSubClassArray)
        $cc_env['cur_cc'] = $cc;

    if ($cc_env['Read_Access_ID'] > 1 && !$AUTH_USER_ID)
        return false;
    if ($AUTH_USER_ID && $cc_env['Read_Access_ID'] > 2) {
        $HasRights = CheckUserRights($cc, 'read', 1);
        if (!$HasRights) {
            return false;
        }
    }

    // set user table mode
    $user_table_mode = (bool) $cc_env['System_Table_ID'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && !$user_table_mode) {
        // startup values
        $cached_data = "";
        $cached_eval = false;

        try {
            $nc_cache_list = nc_cache_list::getObject();
            // cache auth addon string
            $cache_for_user = $nc_cache_list->authAddonString($cc_env['CacheForUser'], $current_user);
            $cache_key = $query_string . $cache_for_user . "type=" . $cc_env['Type'] . "classtemplate=" . $cc_env['ClassTemplate'];
            // check cached data
            $cached_result = $nc_cache_list->read($sub, $cc, $cache_key, $cc_env['Cache_Lifetime']);
            if ($cached_result != -1) {
                // get cached parameters
                list ($cached_data, $cached_eval, $cache_vars) = $cached_result;
                // debug info
                $cache_debug_info = "Readed, sub[" . $sub . "], cc[" . $cc . "], Access_ID[" . $cc_env['Cache_Access_ID'] . "], Lifetime[" . $cc_env['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "], eval[" . (int) $cached_eval . "]";
                $nc_cache_list->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache if not eval flag setted
                if (!$cached_eval) {
                    $result = $cached_data;
                    return $result;
                }
            }
            // set marks into the fields
            $no_cache_marks = $nc_cache_list->nocacheStore($cc_env);
        } catch (Exception $e) {
            $nc_cache_list->errorMessage($e);
        }
    }

    $allowTags = $cc_env['AllowTags'];
    $NL2BR = $cc_env['NL2BR'];
    $catalogue = $mirror_data['Catalogue_ID'] ? $mirror_data['Catalogue_ID'] : $cc_env['Catalogue_ID'];
    $intQueryStr = '?';

    $cc_settings = &$cc_env["Sub_Class_Settings"];

    // current host
    $subHost = "http://" . ($cc_env['Hidden_Host'] ? ( strchr($cc_env['Hidden_Host'], ".") ? $cc_env['Hidden_Host'] : $cc_env['Hidden_Host'] . "." . $DOMAIN_NAME ) : $DOMAIN_NAME);

    // $subLink, $ccLink, $cc_keyword
    if ($admin_mode) {
        $subLink = $admin_url_prefix . ($mirror_data['Subdivision_ID'] ? '?catalogue=' . $cc_env['Catalogue_ID'] . '&amp;sub=' . $cc_env['Subdivision_ID'] : '?catalogue=' . $catalogue . '&amp;sub=' . $sub);
        $ccLink = $subLink . ($mirror_data['Sub_Class_ID'] ? '&amp;cc=' . $cc_env['Sub_Class_ID'] : '&amp;cc=' . $cc);
        $intQueryStr = $ccLink;
    } else {
        $subLink = $SUB_FOLDER . ($mirror_env['SrcMirror'] ? $mirror_env['Hidden_URL'] : $cc_env['Hidden_URL']);
        $cc_keyword = ($mirror_env['SrcMirror'] ? $mirror_env['EnglishName'] : $cc_env['EnglishName']);
        $ccLink = $subLink . $cc_keyword . '.html';
    }

    // переменные curPos, recNum нужно привести к "правильному" виду
    // до И после выполнения системных настроек компонента
    $curPos = +$curPos;
    if ($curPos < 0)
        $curPos = 0;
    $recNum = +$recNum;
    if ($recNum < 0)
        $recNum = 0;

    // if RecordsPerPage not setted in component - set ignore_limit
    $ignore_limit = (!($maxRows = $cc_env['RecordsPerPage']) );
    $sortBy = $cc_env['SortBy'];
    $classID = $cc_env['Class_ID'];
    $userTableID = $cc_env['System_Table_ID'];

    $ignore_all = 0;
    $ignore_catalogue = $ignore_sub = $ignore_cc = 0;
    $ignore_check = $ignore_parent = 0;
    if (isset($ignore_prefix)) $ignore_prefix = +$ignore_prefix;
    if (isset($ignore_suffix)) $ignore_suffix = +$ignore_suffix;
    $ignore_calc = 0;
    $ignore_user = true;
    $result_vars = '';
    $cond_date = '';
    $no_cache_marks = 0;

    if (isset($MODULE_VARS['searchold']['INDEX_TABLE']) && $MODULE_VARS['searchold']['INDEX_TABLE'] == $classID) {
        $ignore_eval['sort_by'] = true;
    }

    $file_class = new nc_class_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
    $file_class->load($_db_Class_ID, $_db_File_Path, $_db_File_Hash);
    $nc_parent_class_folder_path = nc_get_path_to_main_parent_folder($cc_env['File_Path']);

    $nc_class_agregator_path = $nc_core->INCLUDE_FOLDER . 'classes/nc_class_aggregator_setting.class.php';

	// clear this variable after system settings eval!
    $result = "";

    $nc_parent_field_path = $file_class->get_parent_fiend_path('Settings');
    $nc_field_path = $file_class->get_field_path('Settings');
    // check and include component part
	try {
		if ( nc_check_php_file($nc_field_path) ) {
			include $nc_field_path;
		}
	}
	catch (Exception $e) {
		if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
			// error message
			$result.= sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM);
		}
	}
    $nc_parent_field_path = null;
    $nc_field_path = null;

    $curPos = +$curPos;
    if ($curPos < 0) {
        $curPos = 0;
    }

    $recNum = +$recNum;
    if ($recNum < 0) {
        $recNum = 0;
    }

	if (!$sortBy) {
        $sort_by = "a." . ($user_table_mode ? "`" . $AUTHORIZE_BY . "`" : "`Priority` DESC") . ", a.`LastUpdated` DESC";
    } else {
        $sort_by = $sortBy;
    }

    //выйдем, если нет идентификатора шаблона, поскольку дальше работа функции бессмысленна
    if (!$classID) {
        return false;
    }

    $component = new nc_Component($classID, $cc_env['System_Table_ID']);
    $component->make_query();

    $field_names = $component->get_fields_query();
    $field_vars = $component->get_fields_vars();
    $multilist_fileds = $component->get_fields(10);
    $date_field = $component->get_date_field();
    // разрешить html-теги и перенос строки
    $cc_env['convert2txt'] = "";

    $text_fields = $component->get_fields(3);
    foreach ($text_fields as $field) {
        $format = nc_field_parse_format($field['format'], 3);
        // разрешить html
        if (!$cc_env['AllowTags'] && !$format['html'] || $format['html'] == 2)
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = htmlspecialchars(\$f_" . $field['name'] . ");";
        // перенос строки
        if ($cc_env['NL2BR'] && !$format['br'] || $format['br'] == 1)
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nl2br(\$f_" . $field['name'] . ");";
        if ($format['bbcode'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nc_bbcode(\$f_" . $field['name'] . ",  (\$fullDateLink ? \$fullDateLink : \$fullLink) );";
    }

    $text_fields = $component->get_fields(1);
    foreach ($text_fields as $field) {
        if (!$cc_env['AllowTags'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = htmlspecialchars(\$f_" . $field['name'] . ");";
        if ($cc_env['NL2BR'])
            $cc_env['convert2txt'] .= "\$f_" . $field['name'] . " = nl2br(\$f_" . $field['name'] . ");";
    }

    unset($format);
    unset($text_fields);

    //$srchPat дважды urldecodeд и "+" теряется, берем значения из $_REQUEST которые уже один раз urldecodeд
    //если $_REQUEST['srchPat'] пустой, то srchPat передался через s_list_class, сохраняем его
    $fullSearchParams = $component->get_search_query(isset($srchPat) ? $_REQUEST['srchPat'] ? $_REQUEST['srchPat'] : $srchPat : array());
    $fullSearchStr = $fullSearchURL = '';
    if (!empty($fullSearchParams['query'])) {
        $fullSearchStr = $fullSearchParams['query'];
        $fullSearchURL = $fullSearchParams['link'];
    }

    if (!$ignore_catalogue) {
        $cond_catalogue = " AND sub.`Catalogue_ID` = '" . $catalogue . "' ";
        $cond_catalogue_add = " AND a.`Subdivision_ID` = sub.`Subdivision_ID` ";
        $cond_catalogue_addtable = ", `Subdivision` AS sub ";
    }

    $cond_sub = !$ignore_sub ? " AND a.`Subdivision_ID` = '" . ($mirror_data['Subdivision_ID'] ? $mirror_data['Subdivision_ID'] : $sub) . "' " : "";
    $cond_cc = !$ignore_cc ? " AND a.`Sub_Class_ID` = '" . ($mirror_data['Sub_Class_ID'] ? $mirror_data['Sub_Class_ID'] : $cc) . "' " : "";
    $cond_user = !$ignore_user ? " AND a.`User_ID` = '" . $AUTH_USER_ID . "' " : "";
    $cond_parent = !$ignore_parent ? " AND a.`Parent_Message_ID` = '" . $parent_message . "' " : "";
    $cond_search = $fullSearchStr;

    if (!$admin_mode && !$ignore_check) {
        $cond_mod = " AND a.`Checked` = 1 ";
    }

    if (isset($date) && $date && $date_field && strtotime($date) > 0) {
        $cond_date = " AND a.`" . $date_field . "` LIKE '" . $db->escape($date) . "%' ";
    }
    $cond_distinct = isset($distinct) && $distinct ? "DISTINCTROW" : "";
    $cond_distinct = isset($distinctrow) && $distinctrow ? "DISTINCTROW" : "";
    $cond_select = isset($query_select) && $query_select ? ", " . $query_select : "";
    $cond_where = isset($query_where) && $query_where ? " AND " . $query_where : "";
    $cond_group = isset($query_group) && $query_group ? " GROUP BY " . $query_group : "";
    $cond_having = isset($query_having) && $query_having ? " HAVING " . $query_having : "";

    if (isset($query_order) && $query_order) {
        $sort_by = $query_order;
    }

    if (!isset($query_from)) {
        $query_from = '';
    }

    if (!isset($query_join)) {
        $query_join = '';
    }

    if ($user_table_mode) {
        $cond_sub = "";
        $cond_cc = "";
        $cond_catalogue_add = "";
        $cond_catalogue = "";
        $cond_catalogue_addtable = "";
        $cond_parent = "";
    }

    if ($fullSearchURL)
        $intQueryStr .= ( ($intQueryStr == '?') ? '' : '&amp;') . $fullSearchURL;

    if (!$recNum) {
        $recNum = $maxRows;
    } else {
        $maxRows = $recNum;
        // для совместимости со старыми версиями до 2.4.5 и 3.0.0
        if (!isset($ignore_eval['maxRows']) || !$ignore_eval['maxRows']) {
            eval("\$maxRows = \"" . $maxRows . "\";");
        }
    }

    $maxRows = +$maxRows;

    // для совместимости со старыми версиями до 2.4.5 и 3.0.0
    if (!isset($ignore_eval['sort_by']) || !$ignore_eval['sort_by']) {
        eval("\$sort_by = \"" . $sort_by . "\";");
    }

    if (!$ignore_all) {
        $message_select = "SELECT" . (!$ignore_calc ? " SQL_CALC_FOUND_ROWS" : "") . " " . $cond_distinct . " " . $field_names . $cond_select . "
                       FROM (" . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a " . ( $query_from ? ", " . $query_from : "") . ")
                       " . $component->get_joins() . " " . $query_join . "
                       WHERE 1 " . $cond_parent . $cond_where . $cond_catalogue . $cond_sub . $cond_cc . $cond_user . $cond_mod . $cond_search . $cond_date .
                $cond_group .
                $cond_having .
                ($sort_by ? " ORDER BY " . $sort_by : "") .
                (!$ignore_limit ? " LIMIT " . ( isset($cc_env['cur_cc']) && $cc_env['cur_cc'] == $cur_cc ? $curPos : (!isset($cc_env['cur_cc']) ? $curPos : "0") ) . "," . $maxRows : ($query_limit ? " LIMIT " . $query_limit : "") );
    } elseif ($query_select && $query_from) {
        $message_select = "SELECT" . (!$ignore_calc ? " SQL_CALC_FOUND_ROWS" : "") . " " . $query_select . " FROM " . $query_from .
                ($query_join ? " " . $query_join : "") .
                ($query_where ? " WHERE " . $query_where : "") .
                ($query_group ? " GROUP BY " . $query_group : "") .
                ($query_having ? " HAVING  " . $query_having : "") .
                ($query_order ? " ORDER BY " . $query_order : "") .
                ($query_limit ? " LIMIT " . $query_limit : "");
    }

    $nc_prepared_data = 0;
    if (isset($nc_data) && (is_array($nc_data) || $nc_data instanceof ArrayAccess)) {
        $nc_prepared_data = 1;
        $message_select = false;
    }

    $addLink = $SUB_FOLDER . $cc_env['Hidden_URL'] . "add_" . $cc_env['EnglishName'] . ".html";
    $rssLink = $cc_env['AllowRSS'] ? $SUB_FOLDER . $cc_env['Hidden_URL'] . $cc_env['EnglishName'] . ".rss" : "";
    $xmlLink = $cc_env['AllowXML'] ? $SUB_FOLDER . $cc_env['Hidden_URL'] . $cc_env['EnglishName'] . ".xml" : "";

    $subscribeLink = $SUB_FOLDER . $cc_env['Hidden_URL'] . "subscribe_" . $cc_env['EnglishName'] . ".html";
    $searchLink = $SUB_FOLDER . $cc_env['Hidden_URL'] . "search_" . $cc_env['EnglishName'] . ".html";

    $cc_env['LocalQuery'] = $intQueryStr;
    $cc_env['curPos'] = $curPos;
    $cc_env['dateField'] = $date_field;
    $cc_env['recNum'] = $recNum;
    $cc_env['maxRows'] = $maxRows;
    $cc_env['addLink'] = $addLink;
    $cc_env['subscribeLink'] = $subscribeLink;
    $cc_env['searchLink'] = $searchLink;
    $cc_env['fieldCount'] = count($component->get_fields());

    // cache eval section
    if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && is_object($nc_cache_list) && $cached_eval && $cached_result != -1) {
        // get cached objects blocks
        $component_cache_blocks = $nc_cache_list->getCachedBlocks($cached_data);

        // cached prefix
        eval("\$result = \"" . $component_cache_blocks['prefix'] . "\";");

        if (is_array($component_cache_blocks) && !empty($component_cache_blocks)) {
            // concat cached objects
            foreach ($component_cache_blocks['objects'] as $k => $v) {
                // extract cached object variables
                if (!empty($cache_vars) && is_array($cache_vars[$k])) {
                    extract($cache_vars[$k]);
                }
                // append obect data
                eval("\$result.= \"" . $v . "\";");
            }
        }

        // cached suffix
        eval("\$result.= \"" . $component_cache_blocks['suffix'] . "\";");

        return $result;
    }

    // in this section check usable or not add/search forms in component
    $component_body = nc_check_file($file_class->get_field_path('Class')) ? nc_get_file($file_class->get_field_path('Class')) : null;

    if (nc_strpos($component_body, '$addForm') !== false) {

        $SQL = "SELECT Field_Name,
                       Description
                    FROM Field
                        WHERE Class_ID = $classID
                          AND TypeOfData_ID = 11";
        $multifield = (array) $db->get_results($SQL);
        $multifield_names = array();

        foreach ($multifield as $multifield_row) {
            ${'f_' . $multifield_row->Field_Name} = new nc_multifield($multifield_row->Field_Name, $multifield_row->Description);
            $multifield_names[] = 'f_' . $multifield_row->Field_Name;
        }

        $nc_parent_field_path = $file_class->get_parent_fiend_path('AddTemplate');
        $nc_field_path = $file_class->get_field_path('AddTemplate');
        $addForm = '';
        // check and include component part
		try {
			if ( nc_check_php_file($nc_field_path) ) {
				ob_start();
				include $nc_field_path;
				$addForm = ob_get_clean();
			}
		}
		catch (Exception $e) {
			if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
				// error message
				$addForm = sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDFORM);
			}
		}
        $nc_parent_field_path = null;
        $nc_field_path = null;

        foreach ($multifield_names as $multifield_name) {
            unset(${$multifield_name});
        }

        unset($multifield_names);

    }

    if (nc_strpos($component_body, '$searchForm') !== false) {
        $nc_parent_field_path = $file_class->get_parent_fiend_path('FullSearchTemplate');
        $nc_field_path = $file_class->get_field_path('FullSearchTemplate');
        $searchForm = '';
        // check and include component part
		try {
			if ( nc_check_php_file($nc_field_path) ) {
				ob_start();
				include $nc_field_path;
				$searchForm = ob_get_clean();
			}
		}
		catch (Exception $e) {
			if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
				// error message
				$searchForm = sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_QSEARCH);
			}
		}
        $nc_parent_field_path = null;
        $nc_field_path = null;
    }
    unset($component_body);

    $db->last_error = "";

    // main query execution
    if ($message_select) {
        $res = $db->get_results($message_select, ARRAY_A);
    }

    // error in query
    if ($db->last_error) {
        // determine error
        switch (true) {
            case preg_match("/Table '\w+\.Classificator_(\w+)' doesn't exist/i", $db->last_error, $regs):
                $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR, $regs[1]);
                break;
            case preg_match("/Unknown column '(.+?)' in 'field list'/i", $db->last_error, $regs):
                $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_UNKNOWN, $regs[1]);
                break;
            case preg_match("/Unknown column '(.+?)' in 'order clause'/i", $db->last_error, $regs):
                $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_CLAUSE, $regs[1]);
                break;
            case $SHOW_MYSQL_ERRORS == "on":
                $err = $db->last_error;
                break;
            default:
                $err = "";
        }

        // error message
        if (is_object($perm) && $perm->isSupervisor()) {
            // error info for the supervisor
            if ($nc_core->inside_admin) {
				nc_print_status(NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER, 'error');
			}
			trigger_error(sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_SUPERVISOR, $sub, $cc, $query_string, ($err ? $err . ", " : "")), E_USER_WARNING);
        } else {
            // error info for the simple users
            echo NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER;
        }
        return false;
    }

    $nc_core->query_order = $query_order;

    if (class_exists('nc_class_aggregator_setting')) {
        $nc_class_aggregator_settings = nc_class_aggregator_setting::get_instanse();
    }

    if (is_object($nc_class_aggregator_settings) && is_array($res)) {
        require_once $INCLUDE_FOLDER . "classes/nc_class_aggregator.class.php";

        $class_data = array();

        foreach ($res as $row) {
            $class_data[] = array('db_Class_ID' => $row['db_Class_ID'], 'db_Message_ID' => $row['db_Message_ID']);
        }

        $nc_class_aggregator = new nc_class_aggregator($nc_class_aggregator_settings, $class_data);
        $nc_class_aggregator_data = $nc_class_aggregator->get_full_data();

    }

    if ($message_select) {
        // object in this page
        $rowCount = $db->num_rows;
        // total objects
        $totRows = !$ignore_calc ? $db->get_var("SELECT FOUND_ROWS()") : $rowCount;
        $totRows += 0;
    } else if ($nc_prepared_data) {
        $rowCount = sizeof($nc_data);
        $totRows += 0;
        if (!$totRows) {
            $totRows = $rowCount;
        }
    } else {
        $rowCount = 0;
        $totRows = 0;
    }

    $_get_arr = $nc_core->input->fetch_get();
    $get_param_str = '';
    // в nextLink и prevLink нужно сохранить get-параметры
    if (!empty($_get_arr)) {

        $ignore_arr = array('sid', 'ced', 'inside_admin', 'catalogue', 'sub', 'cc', 'curPos', 'cur_cc', 'REQUEST_URI');
        if ($inside_admin || $admin_mode) {
            $ignore_arr[] = 'isNaked';
        }

        foreach ($_get_arr as $k => $v) {
            if (!in_array($k, $ignore_arr)) {
                $get_param[$k] = $v;
            }
        }
        if (!empty($get_param)) {
            $get_param_str = $nc_core->url->build_url($get_param);
        }
    }

    unset($_get_arr);

    $begRow = $curPos + 1;
    $prevLink = $nextLink = '';

    if ($curPos > $maxRows) {
        $prevLink = (!$admin_mode ? $nc_core->url->get_parsed_url('path') : "") . $cc_env['LocalQuery'] . (nc_strlen($cc_env['LocalQuery']) > 1 ? "&amp;" : "") . "curPos=" . ($curPos - $maxRows) . ($cc_env['cur_cc'] ? "&amp;cur_cc=" . $cc_env['cur_cc'] : "") . ($classPreview == $cc_env["Class_ID"] ? "&amp;classPreview=" . $classPreview : "") . ($get_param_str ? "&amp;" . $get_param_str : "");
    }

    if ($curPos == $maxRows) {
        $prevLink = (!$admin_mode ? $nc_core->url->get_parsed_url('path') : "") . $cc_env['LocalQuery'] . (nc_strlen($cc_env['LocalQuery']) > 1 ? "&amp;" : "") . ( $get_param_str ? $get_param_str : "");
    }

    $endRow = $curPos + $maxRows;
    if ($endRow < $totRows) {
        $nextLink = (!$admin_mode ? $nc_core->url->get_parsed_url('path') : "") . $cc_env['LocalQuery'] . (nc_strlen($cc_env['LocalQuery']) > 1 ? "&amp;" : "") . "curPos=" . $endRow . ( isset($cc_env['cur_cc']) && $cc_env['cur_cc'] ? "&amp;cur_cc=" . $cc_env['cur_cc'] : "") . ($classPreview == $cc_env["Class_ID"] ? "&amp;classPreview=" . $classPreview : "") . ($get_param_str ? "&amp;" . $get_param_str : "");
    } else {
        $endRow = $totRows;
    }

    $cc_env['begRow'] = $begRow;
    $cc_env['endRow'] = $endRow;
    $cc_env['totRows'] = $totRows;
    $cc_env['prevLink'] = $prevLink;
    $cc_env['nextLink'] = $nextLink;

    if ($nc_prepared_data && isset($nc_data[0])) {
        $f_Checked = 1;
        $fetch_row = $nc_data;
    } else {
        $fetch_row = $res;
    }

    // Право на модерирование и изменение объектов.
    $modPerm = false;
    $changePerm = false;

    if ($admin_mode) {
        $modPerm = CheckUserRights($cc, 'moderate', 1); // право модератора
        $changePerm = s_auth($cc_env, 'change', 1); //               или просто на изменение объектов

        if (is_object($perm) && $perm->isBanned($cc_env, 'change'))
            $modPerm = $changePerm = false; // пользователю запретили изменение объектов

        $f_AdminCommon_add = $admin_url_prefix."add.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc;
        $f_AdminCommon_delete_all = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;delete=1";
        $f_AdminCommon_export_csv = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;export=1";
        $f_AdminCommon_import_csv = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;import=1";
        $f_AdminCommon_export_xml = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;export=2";
        $f_AdminCommon_import_xml = $admin_url_prefix."message.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classID=".$classID."&amp;import=2";

        // Js и форма для пакетной обработки объектов
        /*$f_AdminCommon_package = "<script type='text/javascript' language='javascript'>\n";
        $f_AdminCommon_package.= "\tnc_package_obj.new_cc(".$cc.", '".NETCAT_MODERATION_NOTSELECTEDOBJ."');\n";
        $f_AdminCommon_package.= "</script>\n";*/
        $f_AdminCommon_package.= "<form id='nc_form_selected_".$cc."' action='".$SUB_FOLDER.$HTTP_ROOT_PATH."message.php' method='post'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='catalogue' value='".$catalogue."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='sub' value='".$sub."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='cc' value='".$cc."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='curPos' value='".$curPos."'>\n";
        $f_AdminCommon_package.= "\t<input type='hidden' name='admin_mode' value='".$admin_mode."'>\n";
        $f_AdminCommon_package.= "</form>\n";
        if ($list_mode != "select") {

            if ($inside_admin && $isMainContent && $UI_CONFIG) {
                // в админке нет AdminCommon, но нужна часть для пакетной обработки
                if ($totRows != 0)
                    $result.= $f_AdminCommon_package;
                // add button
                $UI_CONFIG->actionButtons = array();
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "addObject",
                        "align" => "left",
                        "caption" => NETCAT_MODERATION_BUTTON_ADD,
                        "action" => "parent.nc_form('{$SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}add.php?inside_admin=1&cc=$cc')",
                );

                // кнопки пакетной обработки нужны только если есть объекты
                if ($totRows != 0) {
                    //  button "delete all"
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "deleteAll",
                            "caption" => NETCAT_MODERATION_REMALL,
                            "align" => "right",
                            "action" => "urlDispatcher.load('subclass.purge(" . $cc . ")')"
                    );
                }
            }

            if (!$inside_admin) {
                $f_AdminCommon = nc_AdminCommon($sub, $cc, $cc_env, $f_AdminCommon_package, $f_AdminCommon_add, $f_AdminCommon_delete_all);
            }
        }
    } else {
        $f_AdminCommon_cc = "";
        $f_AdminCommon_cc_name = "";
        $f_AdminCommon_add = "";
        $f_AdminCommon_delete_all = "";
        $f_AdminCommon = "";
        $f_AdminButtons = "";
    }

    $row_ids = array(); // массив, в который будут складываться ID всех узлов
    $res_key = $user_table_mode ? 'User_ID' : 'Message_ID';
    for ($i = 0; $i < $rowCount; $i++) {
        $row_ids[] = $res[$i][$res_key];
    }

    // component prefix
    if (!$ignore_prefix) {
        $nc_parent_field_path = $file_class->get_parent_fiend_path('FormPrefix');
        $nc_field_path = $file_class->get_field_path('FormPrefix');
        // check and include component part
        try {
			if ( nc_check_php_file($nc_field_path) ) {
				ob_start();
				include $nc_field_path;
				$result.= ob_get_clean();
			}
		}
		catch (Exception $e) {
			if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
				// show moderation bar
				$result.= $f_AdminCommon;
				// error message
				$result.= sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX);
			}
		}
        $nc_parent_field_path = null;
        $nc_field_path = null;
    } else {
        $result .= $f_AdminCommon;
    }

    // если список пуст, внутри админки нужно показать сообщение "нет объектов"
    if ($inside_admin && $totRows == 0 && !nc_strlen(trim($result))) {
        $result.= nc_print_status(NETCAT_MODERATION_NO_OBJECTS_IN_SUBCLASS, 'info', null, 1);
    }

    if (!empty($row_ids)) {

        $multifile_fileds = $component->get_fields(11);
        if (!empty($multifile_fileds)) {
            extract(nc_get_arrays_multifield_for_nc_object_list_and_full_php($multifile_fileds, $row_ids));
        }

        // получаем все файлы выбранных объектов
        $nc_fields_files = $component->get_fields(6, 0);
        $nc_files_in_class = array();
        if (!empty($nc_fields_files)) {
            // get data for all file_fields
            $SQL = "SELECT `Field_ID`,
                       `Message_ID`,
                       `Virt_Name`,
                       `Real_Name`,
                       `Download`
                    FROM `Filetable`
                        WHERE `Field_ID` IN (" . join(", ", array_keys($nc_fields_files)) . ")
                          AND `Message_ID` IN (" . join(", ", $row_ids) . ")";
            $filetable = $db->get_results($SQL, ARRAY_A);

            // sorting array
            if (!empty($filetable))
                foreach ($filetable AS $v) {
                    $nc_files_in_class[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['Real_Name'], $v['Download']);
                }
            unset($filetable);
        }
    }

    // требуется получить все группы пользователей
    if ($user_table_mode && !empty($row_ids)) {
        $nc_user_group = $db->get_results("SELECT ug.`User_ID`, ug.`PermissionGroup_ID`, g.`PermissionGroup_Name`
                                       FROM `User_Group` AS ug,`PermissionGroup` AS g
                                       WHERE User_ID IN (" . join(', ', $row_ids) . ")
                                       AND g.`PermissionGroup_ID` = ug.`PermissionGroup_ID` ", ARRAY_A);
        if (!empty($nc_user_group)) {
            foreach ($nc_user_group as $v) {
                $nc_user_group_sort[$v['User_ID']][$v['PermissionGroup_ID']] = $v['PermissionGroup_Name'];
            }
        }
        unset($nc_user_group);
    }

    // =========================  Листинг объектов ======================================
    $cache_vars = array();
    $iteration_RecordTemplate = array();

    for ($f_RowNum = 0; $f_RowNum < $rowCount; $f_RowNum++) {

        if(is_object($nc_class_aggregator)) {
            $fetch_row[$f_RowNum] = array_merge($fetch_row[$f_RowNum], $nc_class_aggregator_data[$f_RowNum]);
        }

        if ($fetch_row[$f_RowNum] instanceof Iterator) {
            extract($fetch_row[$f_RowNum]->to_array(), EXTR_PREFIX_ALL, 'f');
            //добываем старые переменные
            extract($component->get_old_vars($fetch_row[$f_RowNum]->to_array()), EXTR_PREFIX_ALL, 'f');
        } else {
            extract($fetch_row[$f_RowNum], EXTR_PREFIX_ALL, 'f');
            //добываем старые переменные
            extract($component->get_old_vars($fetch_row[$f_RowNum]), EXTR_PREFIX_ALL, 'f');
        }

        $f_RowID = $f_Message_ID ? $f_Message_ID : $f_User_ID;

        if (!empty($multifile_filed_names)) {
            $iteration_multifiles = array();

            foreach ($multifile_filed_names as $field_name) {
                ${'f_' . $field_name} = new nc_multifield($field_name);
                ${'f_' . $field_name}->set_data($multifiles[$f_RowID][$field_name])->template->set(${'f_' . $field_name . '_tpl'});
                $iteration_multifiles['f_' . $field_name] = ${'f_' . $field_name};
            }

            $iteration_RecordTemplate[$f_RowNum]['multifiles_fileds'] = $iteration_multifiles;
            unset($iteration_multifiles);
        }

        $f_UserID = $f_User_ID;

        $Hidden_URL = $f_Hidden_URL;
        /*
         * fix fulllink для системных таблиц, у которых в old_vars не попадает EnglishName
         */
        if ($user_table_mode) {
            $f_EnglishName = $cc_env['EnglishName'];
        }
        /*переопределение $subLink и $cc_keyword, чтобы ссылки $fullLink вел в сабкласс, в котором был добавлен объект
        иначе будет вести в сабкласс, в котором объект выводится.
         */
        if (!$ignore_link && !$mirror_env['SrcMirror']) {
            $subLink = $SUB_FOLDER . $f_Hidden_URL;
            $cc_keyword = $f_EnglishName;
        }
        if ($no_cache_marks) {
            // caching variables array
            $cache_vars[$f_RowNum] = array();
            // get variables names string
            if (is_array($fetch_row[$f_RowNum])) {
                $cache_vars_name = array_keys($fetch_row[$f_RowNum]);

                foreach ($cache_vars_name as $k => $_variable_name) {
                    $_variable_name = 'f_' . $_variable_name;
                    $cache_vars[$f_RowNum][$_variable_name] = $$_variable_name;
                    unset($_variable_name);
                }
                unset($cache_vars_name);
            }
        }

        if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY === 'User_ID') {
            $f_AdminInterface_user_add = $f_UserID;
            $f_AdminInterface_user_change = $f_LastUserID;
        }

        // Multiselect
        if (!empty($multilist_fileds)) {
            // просмотр каждого поля типа multiselect
            foreach ($multilist_fileds as $multilist_filed) {
                // таблицу с элементами можно взять их кэша, если ее там нет - то добавить
                if (!$_cache['classificator'][$multilist_filed['table']]) {
                    $db_res = $db->get_results("SELECT `" . $multilist_filed['table'] . "_ID` AS ID, `" . $multilist_filed['table'] . "_Name` AS Name, `Value`
                                       FROM `Classificator_" . $multilist_filed['table'] . "`", ARRAY_A);
                    if (!empty($db_res)) {
                        foreach ($db_res as $v) { // запись в кэш
                            $_cache['classificator'][$multilist_filed['table']][$v['ID']] = array($v['Name'], $v['Value']);
                        }
                    }
                    unset($db_res);
                }

                ${"f_" . $multilist_filed['name'] . "_id"} = array();
                ${"f_" . $multilist_filed['name'] . "_value"} = array();

                if (($value = ${"f_" . $multilist_filed['name']})) { // значение из базы
                    ${"f_" . $multilist_filed['name']} = array();
                    ${"f_" . $multilist_filed['name'] . "_id"} = array();
                    $ids = explode(',', $value);
                    if (!empty($ids)) {
                        foreach ($ids as $id) { // для каждого элемента по id определяем имя и значение
                            if ($id) {
                                array_push(${"f_" . $multilist_filed['name']}, $_cache['classificator'][$multilist_filed['table']][$id][0]);
                                array_push(${"f_" . $multilist_filed['name'] . "_value"}, $_cache['classificator'][$multilist_filed['table']][$id][1]);
                                array_push(${"f_" . $multilist_filed['name'] . "_id"}, $id);
                            }
                        }
                    }
                }
                // default values
                if (!is_array(${"f_" . $multilist_filed['name']})) {
                    ${"f_" . $multilist_filed['name']} = array();
                }
                $iteration_multilist_fileds = array();
                $iteration_multilist_fileds['f_' . $multilist_filed['name']] = ${"f_" . $multilist_filed['name']};
                $iteration_multilist_fileds['f_' . $multilist_filed['name'] . '_value'] = ${"f_" . $multilist_filed['name'] . "_value"};
                $iteration_multilist_fileds['f_' . $multilist_filed['name'] . '_id'] = ${"f_" . $multilist_filed['name'] . "_id"};
                $iteration_RecordTemplate[$f_RowNum]['multilist_fileds'] = $iteration_multilist_fileds;
                unset($iteration_multilist_fileds);
            }
            unset($ids);
            unset($id);
            unset($value);
        }

        // get files for message
        if (!empty($nc_fields_files)) {
			$fields_files = array();
			$files_sub_vars = array('f_%s_name', 'f_%s_type', 'f_%s_size', 'f_%s_download', 'f_%s_url');
            foreach ($nc_fields_files AS $field_id => $field_name) {
                // sub-variables
                foreach ($files_sub_vars as $row) {unset( ${sprintf($row, $field_name)} );}

                //  apparently we don't have a file
                if (!${"f_" . $field_name}) {
					// sub-variables
					foreach ($files_sub_vars as $row) {$fields_files[ sprintf($row, $field_name) ] = '';}
					// vars space
					$iteration_RecordTemplate[$f_RowNum]['fields_files'] = $fields_files;
					// skip
                    continue;
                }

                //file_data - массив с ориг.названием, типом, размером, [именем_файла_на_диске]
                $file_data = explode(':', ${"f_" . $field_name});
                $filetable_path = ($user_table_mode ? "u" : $f_Subdivision_ID . "/" . $f_Sub_Class_ID);
                ${"f_" . $field_name . "_name"} = $file_data[0];
                ${"f_" . $field_name . "_type"} = $file_data[1];
                ${"f_" . $field_name . "_size"} = $file_data[2];
                $Virt_Name = "";
                $nc_download = 0;

                // get data from files array
                if (is_array($nc_files_in_class[$f_RowID][$field_id])) {
                    list($Virt_Name, $Real_Name, $nc_download) = $nc_files_in_class[$f_RowID][$field_id];
                }

                if ($Virt_Name) { // файловая система c Filetable
                    ${"f_" . $field_name} = $SUB_FOLDER . $HTTP_FILES_PATH . $filetable_path . "/h_" . $Virt_Name;
                    ${"f_" . $field_name . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $filetable_path . "/" . $Virt_Name;
                    ${"f_" . $field_name . "_download"} = $nc_download;
                } else {
                    if ($file_data[3]) { // файловая система "Original"
                        ${"f_" . $field_name} = ${"f_" . $field_name . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $file_data[3];
                    } else {
                        $ext = substr($file_data[0], strrpos($file_data[0], "."));
                        ${"f_" . $field_name . "_url"} = ${"f_" . $field_name} = $SUB_FOLDER . $HTTP_FILES_PATH . $field_id . "_" . $f_RowID . $ext;
                    }
                }

                $fields_files['f_' . $field_name] = ${'f_' . $field_name};
                // sub-variables
                foreach ($files_sub_vars as $row) {$fields_files[ sprintf($row, $field_name) ] = ${sprintf($row, $field_name)};}
                // vars space
                $iteration_RecordTemplate[$f_RowNum]['fields_files'] = array_merge((array) $iteration_RecordTemplate[$f_RowNum]['fields_files'], $fields_files);
            }
            unset($fields_files);
        }

        if ($user_table_mode) {
            $f_PermissionGroup = &$nc_user_group_sort[$f_RowID];
        }

        $f_Created_year = substr($f_Created, 0, 4);
        $f_Created_month = substr($f_Created, 5, 2);
        $f_Created_day = substr($f_Created, 8, 2);
        $f_Created_hours = substr($f_Created, 11, 2);
        $f_Created_minutes = substr($f_Created, 14, 2);
        $f_Created_seconds = substr($f_Created, 17, 2);
        $f_Created_date = $f_Created_day . "." . $f_Created_month . "." . $f_Created_year;
        $f_Created_time = $f_Created_hours . ":" . $f_Created_minutes . ":" . $f_Created_seconds;

        if (isset($f_LastUpdated) && $f_LastUpdated) {
            $f_LastUpdated_year = substr($f_LastUpdated, 0, 4);
            $f_LastUpdated_month = substr($f_LastUpdated, 5, 2);
            $f_LastUpdated_day = substr($f_LastUpdated, 8, 2);
            $f_LastUpdated_hours = substr($f_LastUpdated, 11, 2);
            $f_LastUpdated_minutes = substr($f_LastUpdated, 14, 2);
            $f_LastUpdated_seconds = substr($f_LastUpdated, 17, 2);
        }

        if ($admin_mode && !$nc_prepared_data) {
            $dateLink = '';
            if ($date_field) {
                eval("\$dateLink = \"&date=\".\$f_" . $date_field . "_year.\"-\".\$f_" . $date_field . "_month.\"-\".\$f_" . $date_field . "_day;");
            }

            // full link for object
            $fullLink = nc_get_fullLink($admin_url_prefix, $catalogue, $_db_sub, $_db_cc, $f_RowID);
            $fullDateLink = nc_get_fullDateLink($fullLink, $dateLink);

            // ID объекта в шаблоне
            $f_AdminButtons_id = $f_RowID;

            // Приоритет объекта
            $f_AdminButtons_priority = $f_Priority;

            // ID добавившего пользователя
            $f_AdminButtons_user_add = $f_UserID;

            // ID изменившего пользователя
            $f_AdminButtons_user_change = nc_get_AdminButtons_user_change($f_LastUserID);

            // копировать объект
            $f_AdminButtons_copy = nc_get_AdminButtons_copy($ADMIN_PATH, $catalogue, $sub, $cc, $classID, $f_RowID);

            // изменить
            $f_AdminButtons_change = nc_get_AdminButtons_change($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos);
            $editLink = $f_AdminButtons_change;

            // удалить
            $f_AdminButtons_delete = nc_get_AdminButtons_delete($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos);
            $deleteLink = $f_AdminButtons_delete;
            $dropLink = nc_get_dropLink($deleteLink, $nc_core);

            // включить-выключить
            $f_AdminButtons_check = nc_get_AdminButtons_check($f_Checked, $any_url_prefix, $SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $classID, $f_RowID, $curPos, $admin_mode, $admin_url_prefix, $nc_core);
            $checkedLink = $f_AdminButtons_check;

            // выбрать связанный (JS код!!!) -- когда список вызван в popup для выбора связанного объекта
            $f_AdminButtons_select = nc_get_AdminButtons_select($f_AdminButtons_id);

            if ($list_mode == 'select') {
                $f_AdminButtons_buttons = nc_get_list_mode_select_AdminButtons_buttons($f_AdminButtons_select, $ADMIN_TEMPLATE);
                $f_AdminButtons  = nc_get_list_mode_select($f_Checked, $classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_buttons);
            } else {
                if ($system_env['AdminButtonsType']) {
                    eval("\$f_AdminButtons = \"" . $system_env['AdminButtons'] . "\";");
                } else {
                    $f_AdminButtons_buttons = nc_get_AdminButtons_buttons($f_RowID, $f_Checked, $f_AdminButtons_check, $f_AdminButtons_copy, $f_AdminButtons_change, $f_AdminButtons_delete, $classID);
                    $f_AdminButtons = nc_get_AdminButtons_prefix($f_Checked, $cc);
                    // проверка прав
                    if ($modPerm || ($changePerm && $f_AdminButtons_user_add == $AUTH_USER_ID)) {
                        $f_AdminButtons.= nc_get_AdminButtons_modPerm($classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_priority, $f_AdminInterface_user_add, $f_AdminButtons_user_add, $f_AdminInterface_user_change, $f_AdminButtons_user_change, $f_AdminButtons_buttons, $cc);
                    } else {
                        $f_AdminButtons.= nc_get_AdminButtons_modPerm_else($classID, $f_RowID);
                    }
                    $f_AdminButtons.= nc_get_AdminButtons_suffix();
                }
            }
            if ($user_table_mode)
                $f_AdminButtons = "";
        }
        else {
            $f_AdminButtons_id = "";
            $f_AdminButtons_priority = "";
            $f_AdminButtons_user_add = "";
            $f_AdminButtons_user_change = "";
            $f_AdminButtons_copy = "";
            $f_AdminButtons_change = "";
            $f_AdminButtons_delete = "";
            $f_AdminButtons_check = "";
            $f_AdminButtons_select = "";
            $f_AdminButtons = "";

            if (!isset($f_Keyword)) {
                $f_Keyword = '';
            }

            $msgLink = ($f_Keyword) ? $f_Keyword : $cc_keyword . "_" . $f_RowID;
            $dateLink = '';
            if ($date_field) {
                eval("\$dateLink = \$f_" . $date_field . "_year.\"/\".\$f_" . $date_field . "_month.\"/\".\$f_" . $date_field . "_day.\"/\";");
            }

            if ($catalogue == $current_catalogue['Catalogue_ID']) {
                $fullLink = $subLink . $msgLink . ".html"; // полный вывод
                $fullRSSLink = $cc_env['AllowRSS'] ? $subLink . $msgLink . ".rss" : ""; // rss
                $fullXMLLimk = $cc_env['AllowXML'] ? $subLink . $msgLink . ".xml" : "";
                $fullDateLink = $subLink . $dateLink . $msgLink . ".html"; // полный вывод с датой
                $editLink = $subLink . "edit_" . $msgLink . ".html"; // ccылка для редактирования
                $deleteLink = $subLink . "delete_" . $msgLink . ".html"; // удаления
                $dropLink = $subLink . "drop_" . $msgLink . ".html" . ($nc_core->token->is_use('drop') ? "?" . $nc_core->token->get_url() : ""); // удаления без подтверждения
                $checkedLink = $subLink . "checked_" . $msgLink . ".html"; // включения\выключения
                $subscribeMessageLink = $subLink . "subscribe_" . $msgLink . ".html"; // подписка на объект
            } else {
                $fullLink = $subLink . $msgLink . ".html";
                $fullRSSLink = $cc_env['AllowRSS'] ? $subHost . $subLink . $msgLink . ".html" : "";
                $fullXMLLimk = $cc_env['AllowXML'] ? $subHost . $subLink . $msgLink . ".xml" : "";
                $fullDateLink = $subHost . $subLink . $dateLink . $msgLink . ".html";
                $editLink = $subHost . "edit_" . $msgLink . ".html";
                $deleteLink = $subHost . "delete_" . $msgLink . ".html";
                $dropLink = $subHost . "drop_" . $msgLink . ".html";
                $checkedLink = $subHost . "checked_" . $msgLink . ".html";
                $subscribeMessageLink = $subHost . "subscribe_" . $msgLink . ".html"; // подписка на объект
            }

            // Если это превью данного компонента то, мы добавляем перенменную к ссылкам на полный просмотр объекта
            if ($classPreview == $cc_env["Class_ID"]) {
                $fullLink .= "?classPreview=" . $classPreview;
                $fullDateLink .= "?classPreview=" . $classPreview;
            }
        }

        if (is_object($nc_class_aggregator) && $f_db_Subdivision_ID) {
            $fullLink = $nc_core->subdivision->get_by_id($f_db_Subdivision_ID, 'Hidden_URL')
                      . ($f_db_Keyword ? $f_db_Keyword . '.html'
                                       : $nc_core->sub_class->get_by_id($f_db_Sub_Class_ID, 'EnglishName') . '_' . $f_db_Message_ID . '.html');
        }

        $vars = array();
        $vars['f_RowID'] = $f_RowID;
        $vars['f_AdminInterface_user_add'] = $f_AdminInterface_user_add;
        $vars['f_AdminInterface_user_change'] = $f_AdminInterface_user_change;
        $vars['fullLink'] = $fullLink;
        $vars['fullDateLink'] = $fullDateLink;
        $vars['fullRSSLink'] = $fullRSSLink;
        $vars['fullXMLLimk'] = $fullXMLLimk;
        $vars['editLink'] = $editLink;
        $vars['deleteLink'] = $deleteLink;
        $vars['dropLink'] = $dropLink;
        $vars['checkedLink'] = $checkedLink;
        $vars['subscribeMessageLink'] = $subscribeMessageLink;
        $vars['f_Keyword'] = $f_Keyword;
        $vars['msgLink'] = $msgLink;
        $vars['dateLink'] = $dateLink;
        $vars['date_field'] = $date_field;
        $vars['f_AdminButtons_id'] = $f_AdminButtons_id;
        $vars['f_AdminButtons_priority'] = $f_AdminButtons_priority;
        $vars['f_AdminButtons_user_add'] = $f_AdminButtons_user_add;
        $vars['f_AdminButtons_user_change'] = $f_AdminButtons_user_change;
        $vars['f_AdminButtons_copy'] = $f_AdminButtons_copy;
        $vars['f_AdminButtons_change'] = $f_AdminButtons_change;
        $vars['f_AdminButtons_delete'] = $f_AdminButtons_delete;
        $vars['f_AdminButtons_check'] = $f_AdminButtons_check;
        $vars['f_AdminButtons_select'] = $f_AdminButtons_select;
        $vars['f_AdminButtons'] = $f_AdminButtons;
        $vars['f_PermissionGroup'] = $f_PermissionGroup;
        $vars['f_Created_year'] = $f_Created_year;
        $vars['f_Created_month'] = $f_Created_month;
        $vars['f_Created_day'] = $f_Created_day;
        $vars['f_Created_hours'] = $f_Created_hours;
        $vars['f_Created_minutes'] = $f_Created_minutes;
        $vars['f_Created_seconds'] = $f_Created_seconds;
        $vars['f_Created_date'] = $f_Created_date;
        $vars['f_Created_time'] = $f_Created_time;

        if (isset($f_LastUpdated) && $f_LastUpdated) {   
            $vars['f_LastUpdated'] = $f_LastUpdated;
            $vars['f_LastUpdated_year'] = $f_LastUpdated_year;
            $vars['f_LastUpdated_month'] = $f_LastUpdated_month;
            $vars['f_LastUpdated_day'] = $f_LastUpdated_day;
            $vars['f_LastUpdated_hours'] = $f_LastUpdated_hours;
            $vars['f_LastUpdated_minutes'] = $f_LastUpdated_minutes;
            $vars['f_LastUpdated_seconds'] = $f_LastUpdated_seconds;
        }

        $iteration_RecordTemplate[$f_RowNum]['vars'] = $vars;
        unset($vars);
    }

    $nc_parent_field_path = $file_class->get_parent_fiend_path('RecordTemplate');
    $nc_field_path = $file_class->get_field_path('RecordTemplate');
    // check and include component part
	try {
		if ( nc_check_php_file($nc_field_path) ) {
			ob_start();
			include $nc_field_path;
			$result.= ob_get_clean();
		}
	}
	catch (Exception $e) {
		if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
			// error message
			$result.= sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_OBJECTVIEW);
		}
	}
    $nc_parent_field_path = null;
    $nc_field_path = null;
    unset($iteration_RecordTemplate);

    if (!$ignore_suffix) {
        $nc_parent_field_path = $file_class->get_parent_fiend_path('FormSuffix');
        $nc_field_path = $file_class->get_field_path('FormSuffix');
        // check and include component part
		try {
			if ( nc_check_php_file($nc_field_path) ) {
				ob_start();
				include $nc_field_path;
				$result.= ob_get_clean();
			}
		}
		catch (Exception $e) {
			if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
				// error message
				$result.= sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX);
			}
		}
        $nc_parent_field_path = null;
        $nc_field_path = null;
    }

    // добавить скрипт для D&D

    if ($inside_admin && !$user_table_mode && $perm->isSubClassAdmin($cc)) {
        // приоритет позволять менять только если отсортировано по умолчанию (Priority DESC)
        $change_priority = $cc_env['SortBy'] || $query_order ? 'false' : 'true';
        $result .= "<script type='text/javascript' language='Javascript'>";
        $result .= "if (typeof formAsyncSaveEnabled!='undefined') messageInitDrag(" . nc_array_json(array($classID => $row_ids)) . ", " . $change_priority . ");";
        $result .= "</script>";
    }

    // title
    global $cc_array;
    if ($isMainContent && (!$isSubClassArray || $cc_array[0] == $cc)) {
        $title = '';
        if ($cc_env['TitleList']) {
            eval("\$title = \"" . $cc_env['TitleList'] . "\";");
        }

        if ($title) {
            $nc_core->page->set_metatags('title', $title);
            $cc_env['Cache_Access_ID'] = 2;
        }
    }

    // cache section
    if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && is_object($nc_cache_list) && !$user_table_mode && !$nc_prepared_data) {
        try {
            $bytes = $nc_cache_list->add($sub, $cc, $cache_key, $result, $cache_vars);
            if ($no_cache_marks)
                $result = $nc_cache_list->nocacheClear($result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Writed, sub[" . $sub . "], cc[" . $cc . "], Access_ID[" . $cc_env['Cache_Access_ID'] . "], Lifetime[" . $cc_env['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_list->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            $nc_cache_list->errorMessage($e);
        }
    }

    return $result;
}

function nc_get_arrays_multifield_for_nc_object_list_and_full_php(array $multifile_fileds, array $row_ids) {
    $multifile_filed_id = array();
    $multifile_table = array();
    $multifile_filed_names = array();

    foreach ($multifile_fileds as $multifile_filed) {
        $multifile_filed_id[] = $multifile_filed['id'];
        $multifile_table[$multifile_filed['id']] = $multifile_filed['name'];
        $multifile_filed_names[] = $multifile_filed['name'];
    }

    $SQL = "SELECT `Field_ID`,
                   `Message_ID`,
                   `Priority`,
                   `Name`,
                   `Size`,
                   `Path`,
                   `Preview`,
                   `ID`
                FROM `Multifield`
                    WHERE `Field_ID` IN (" . join(', ', $multifile_filed_id) . ")
                      AND `Message_ID` IN (" . join(', ', $row_ids) . ")
                        ORDER BY `Priority`";
    $filetable = nc_Core::get_object()->db->get_results($SQL, ARRAY_A);
    if ($filetable) {
        $multifiles = array();
        foreach ($filetable as $file) {
            $multifiles[$file['Message_ID']][$multifile_table[$file['Field_ID']]][] = $file;
        }
    }
    return array(
            'multifile_filed_names' => $multifile_filed_names,
            'multifiles' => $multifiles);
}

function nc_AdminCommon($sub, $cc, $cc_env, $f_AdminCommon_package, $f_AdminCommon_add, $f_AdminCommon_delete_all) {
    $nc_core = nc_Core::get_object();
    $system_env = $nc_core->get_settings();
    $ADMIN_TEMPLATE = $nc_core->get_variable("ADMIN_TEMPLATE");
    $f_AdminCommon_cc_name = $cc_env['Sub_Class_Name'];
    $f_AdminCommon_cc = $cc;
    if ($system_env['AdminButtonsType']) {
        eval("\$f_AdminCommon = \"" . $system_env['AdminCommon'] . "\";");
    } else {
        $f_AdminCommon_buttons = "<div>
                                    <div style='top: -4px; font-weight: bold;'>".$cc_env['Sub_Class_ID']."</div>
                                    <div style='top: -4px;'>
                                        <a style='text-decoration: none; color: #1A87C2;' ".(($cc_env['Sub_Class_ID']!=57) ? "onClick='parent.nc_form(this.href); return false;'" : "")." href='$f_AdminCommon_add'>
                                            <div>" . NETCAT_MODERATION_BUTTON_ADD . "</div>
                                        </a>
                                    </div>


                                    " . nc_get_AdminCommon_multiedit_button($cc_env) ."

                                    " . ($nc_core->InsideAdminAccess ? "
                                    <div>
                                        <a onClick='parent.nc_form(this.href); return false;' href='{$nc_core->SUB_FOLDER}admin/class/index.php?phase=4&ClassID=".($cc_env['Class_Template_ID'] ? $cc_env['Class_Template_ID'] : $cc_env['Class_ID'])."'>
                                            <div class='icons icon_class' title ='" . CONTROL_CLASS_DOEDIT . "'></div>
                                        </a>
                                    </div>" : "") ."

                                    <div>
                                        <a onClick='parent.nc_form(this.href); return false;' href='{$nc_core->ADMIN_PATH}subdivision/SubClass.php?SubdivisionID=$sub&sub_class_id=$cc'>
                                            <div class='icons icon_settings' title ='" . CONTROL_CLASS_CLASS_SETTINGS . "'></div>
                                        </a>
                                    </div>

                                    <div>
                                        <a onClick='parent.nc_action_message(this.href); return false;' href='$f_AdminCommon_delete_all'>
                                            <div class='icons icon_delete' title ='" . NETCAT_MODERATION_REMALL . "'></div>
                                        </a>
                                    </div>
                                  </div>";

        $f_AdminCommon = "<div class='nc_idtab nc_admincommon'>";
        if (CheckUserRights($cc, 'add', 1) == 1) {
            $f_AdminCommon = "<div class='nc_AdminCommon_buttons'>".$f_AdminCommon_buttons."</div>
              <div class='ncf_row nc_clear'></div>";
            $f_AdminCommon .= $f_AdminCommon_package;
        } else {
            $f_AdminCommon.= "<div class='nc_idtab_id'>
                                  <div class='nc_idtab_messageid error' title='" . NETCAT_MODERATION_ERROR_NORIGHT . "'>
                                      " . NETCAT_MODERATION_ERROR_NORIGHT . "
                                  </div>
                              </div>
                              <div class='ncf_row nc_clear'></div>";
        }
        $f_AdminCommon.= "<div class='ncf_row nc_clear'></div>";
    }
    return $f_AdminCommon;
}

function nc_get_AdminCommon_multiedit_button($cc_env) {
    $nc_core = nc_Core::get_object();
    $result = '';
    $multi_edit_template_id = nc_get_AdminCommon_multiedit_button_template_id($cc_env['Class_ID']);
    if ($multi_edit_template_id) {
        $result = "
            <div>
                <a onClick='parent.nc_form(this.href); return false;' href='{$nc_core->$SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}index.php?isModal=1&catalogue={$cc_env['Catalogue_ID']}&sub={$cc_env['Subdivision_ID']}&cc={$cc_env['Sub_Class_ID']}&nc_ctpl=$multi_edit_template_id'>
                    <div class='icons icon_pencil' title ='" . CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_MULTI_EDIT . "'></div>
                </a>
            </div>";
    }

    return $result;
}

function nc_get_AdminCommon_multiedit_button_template_id($class_id) {
    static $data = array();

    if (!isset($data[$class_id])) {
        $data[$class_id] = +nc_Core::get_object()->db->get_var("SELECT Class_ID FROM Class WHERE (Class_ID = $class_id OR ClassTemplate = $class_id) AND Type = 'multi_edit'");
    }

    return $data[$class_id];
}

function nc_get_class_template_array_by_id($class_id) {
    $SQL = "SELECT Class_ID,
                   Class_Name
                FROM Class
                    WHERE Class_ID = $class_id
                       OR ClassTemplate = $class_id
                        ORDER BY Class_ID";
    $all_class = (array) nc_Core::get_object()->db->get_results($SQL);
    $result = array();

    foreach($all_class as $class) {
        $result[$class->Class_ID] = $class->Class_Name;
    }

    return $result;
}

function nc_get_class_template_form_select_by_array(array $class_date, $class_current_id) {
    $nc_core = nc_Core::get_object();
    $result = '<div>' . CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE . '</div>';

    $result .= "\n<div>\n";
    $result .= "    <select id='Edit_Class_Template' name='Edit_Class_Template'>
                        <option value='0'>" . CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE_DONT_USE . "</option>\n";
    foreach ($class_date as $class_id => $class_name) {
        $selected = $class_current_id == $class_id ? ' selected' : '';
        $result .= "        <option$selected value='$class_id'>$class_name</option>\n";
    }

    $result .= "    </select><button id='nc_button_Edit_Class_Template' type='button' onclick=\"window.open('{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}admin/#classtemplate".(nc_get_file_mode('Class', $class_id) ? '_fs' : '').".edit(' + document.getElementById('Edit_Class_Template').value + ')', 1)\" id='classtemplateEditLink' >" . CONTROL_CLASS_CLASS_TEMPLATE_BUTTON_EDIT . " </button><br />\n";
    $result .= "</div>\n
        <script type='text/javascript'>
            \$nc('#Edit_Class_Template').change(function() {
                if (\$nc(this).val() == 0) {
                    \$nc('#nc_button_Edit_Class_Template').attr('disabled', 'disabled');
                } else {
                    \$nc('#nc_button_Edit_Class_Template').attr('disabled', '');
                }
            });

            if (\$nc('#Edit_Class_Template').val() == 0) {
                \$nc('#nc_button_Edit_Class_Template').attr('disabled', 'disabled');
            }
        </script>";

    return $result;
}


function nc_get_date_field($date_field) {
    echo "\$dateLink = \"&date=\".\$f_" . $date_field . "_year.\"-\".\$f_" . $date_field . "_month.\"-\".\$f_" . $date_field . "_day;"; exit;
    eval("\$dateLink = \"&date=\".\$f_" . $date_field . "_year.\"-\".\$f_" . $date_field . "_month.\"-\".\$f_" . $date_field . "_day;");
    return $dateLink;
}

function nc_get_fullLink($admin_url_prefix, $catalogue, $sub, $cc, $f_RowID) {
    return $admin_url_prefix . "full.php?sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID;
}

function nc_get_fullDateLink($fullLink, $dateLink) {
    return $fullLink . $dateLink;
}

function nc_get_AdminButtons_user_change($f_LastUserID) {
    return $f_LastUserID ? $f_LastUserID : "";
}

function nc_get_AdminButtons_copy($ADMIN_PATH, $catalogue, $sub, $cc, $classID, $f_RowID) {
    return $ADMIN_PATH . "objects/copy_message.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;message=" . $f_RowID;
}

function nc_get_AdminButtons_change($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos) {
    return $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID . ( $curPos ? "&amp;curPos=" . $curPos : "");
}

function nc_get_AdminButtons_delete($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos) {
    return $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID . "&amp;delete=1" . ( $curPos ? "&amp;curPos=" . $curPos : "");
}

function nc_get_dropLink($deleteLink, $nc_core) {
    return $deleteLink . "&posting=1" . ($nc_core->token->is_use('drop') ? "&" . $nc_core->token->get_url() : "");
}

function nc_get_AdminButtons_check($f_Checked, $any_url_prefix, $SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $classID, $f_RowID, $curPos, $admin_mode, $admin_url_prefix, $nc_core) {
    return ($f_Checked ? $any_url_prefix . $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;message=" . $f_RowID . "&amp;checked=1&amp;posting=1" . ( $curPos ? "&amp;curPos=" . $curPos : "") . ($admin_mode ? "&amp;admin_mode=1" : "") : $admin_url_prefix . "message.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;message=" . $f_RowID . "&amp;checked=2&amp;posting=1" . ( $curPos ? "&amp;curPos=" . $curPos : "") . ($admin_mode ? "&amp;admin_mode=1" : "")) . ( $nc_core->token->is_use('edit') ? "&" . $nc_core->token->get_url() : "");
}

function nc_get_AdminButtons_select($f_AdminButtons_id) {
    return "top.selectItem(" . $f_AdminButtons_id . "); return false;";
}

function nc_get_list_mode_select_AdminButtons_buttons($f_AdminButtons_select, $ADMIN_TEMPLATE) {
    return "<a style='margin-left: 20px;' href='#' onclick='" . $f_AdminButtons_select . "'><img src='" . $ADMIN_TEMPLATE . "img/i_obj_select.gif' alt='" . NETCAT_MODERATION_SELECT_RELATED . "' title='" . NETCAT_MODERATION_SELECT_RELATED . "'></a>";
}

function nc_get_list_mode_select($f_Checked, $classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_buttons) {
    $f_AdminButtons = "<div class='nc_idtab" . ($f_Checked ? "" : " nc_idtab_disabled") . "'>";
    //$f_AdminButtons.= "<div class='nc_idtab_handler' ".(nc_Core::get_object()->inside_admin ? '' : "style='display: none;' ")."id='message" . $classID . "-" . $f_RowID . "_handler'></div>";
    $f_AdminButtons.= "<div class='nc_idtab_id'><div class='nc_idtab_ccid' style='background: none;'><b>" . $f_AdminButtons_id . "</b></div></div>";
    $f_AdminButtons.= "<div class='nc_idtab_buttons'>" . $f_AdminButtons_buttons . "</div>";
    $f_AdminButtons.= "</div>";
    $f_AdminButtons.= "<div class='ncf_row nc_clear'></div>";
    return $f_AdminButtons;
}

function nc_get_AdminButtons_buttons($f_RowID, $f_Checked, $f_AdminButtons_check, $f_AdminButtons_copy, $f_AdminButtons_change, $f_AdminButtons_delete, $classID) {
    if (($classID==51) || ($classID==54)) {
		return "<div class='nc_idtab_buttons_id'>".$f_RowID."</div>
            <div class='nc_idtab_buttons_obj_".($f_Checked ? 'on' : 'off' )."'>
                <a onClick='parent.nc_action_message(this.href); return false;' href='".$f_AdminButtons_check."'>
                  ".($f_Checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF )."
                </a>
            </div>

            <div class='nc_idtab_buttons_copy' style='top: 6px;'>
                <a href='#' onclick=\"window.open('" . $f_AdminButtons_copy . "', 'nc_popup_test1', 'width=800,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">
                    <div class='icons icon_copy' title ='" . NETCAT_MODERATION_COPY_OBJECT . "'></div>
                </a>
            </div>

            <div class='nc_idtab_buttons_change' style='top: 6px;'>
                <a target='_blank' href='" . $f_AdminButtons_change . "'>
                    <div class='icons icon_pencil' title='" . NETCAT_MODERATION_CHANGE . "'></div>
                </a>
            </div>

            <div class='nc_idtab_buttons_delete' style='top: 6px;'>
                <a onClick='parent.nc_action_message(this.href); return false;' href='" . $f_AdminButtons_delete . "'>
                    <div class='icons icon_delete' title='" . NETCAT_MODERATION_DELETE . "'></div>
                </a>
            </div>";
	} else {
		return "<div class='nc_idtab_buttons_id'>".$f_RowID."</div>
            <div class='nc_idtab_buttons_obj_".($f_Checked ? 'on' : 'off' )."'>
                <a onClick='parent.nc_action_message(this.href); return false;' href='".$f_AdminButtons_check."'>
                  ".($f_Checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF )."
                </a>
            </div>

            <div class='nc_idtab_buttons_copy' style='top: 6px;'>
                <a href='#' onclick=\"window.open('" . $f_AdminButtons_copy . "', 'nc_popup_test1', 'width=800,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">
                    <div class='icons icon_copy' title ='" . NETCAT_MODERATION_COPY_OBJECT . "'></div>
                </a>
            </div>

            <div class='nc_idtab_buttons_change' style='top: 6px;'>
                <a onClick='parent.nc_form(this.href); return false;' href='" . $f_AdminButtons_change . "'>
                    <div class='icons icon_pencil' title='" . NETCAT_MODERATION_CHANGE . "'></div>
                </a>
            </div>

            <div class='nc_idtab_buttons_delete' style='top: 6px;'>
                <a onClick='parent.nc_action_message(this.href); return false;' href='" . $f_AdminButtons_delete . "'>
                    <div class='icons icon_delete' title='" . NETCAT_MODERATION_DELETE . "'></div>
                </a>
            </div>";
	}
}

function nc_get_AdminButtons_modPerm($classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_priority, $f_AdminInterface_user_add, $f_AdminButtons_user_add, $f_AdminInterface_user_change, $f_AdminButtons_user_change, $f_AdminButtons_buttons, $cc) {
    $f_AdminButtons = "<div class='nc_idtab_handler' ".(nc_Core::get_object()->inside_admin && nc_show_drag_handler($cc) ? '' : "style='display: none;'")." id='message" . $classID . "-" . $f_RowID . "_handler'><div style='margin-top: 5px; margin-left: 5px;' class=' icons icon_drag'></div></div>";
    $f_AdminButtons.= "<div class='nc_idtab_buttons'>" . $f_AdminButtons_buttons . "</div>";
    $f_AdminButtons.= "<div class='ncf_row nc_clear'></div>";
    return $f_AdminButtons;
}

function nc_get_AdminButtons_modPerm_else($classID, $f_RowID) {
    $f_AdminButtons = "<div class='nc_idtab_handler' id='message" . $classID . "-" . $f_RowID . "_handler'></div>";
    $f_AdminButtons.= "<div class='nc_idtab_id'>";
    $f_AdminButtons.= "<div class='nc_idtab_messageid error' title='" . NETCAT_MODERATION_ERROR_NORIGHT . "'>" . NETCAT_MODERATION_ERROR_NORIGHT . "</div>";
    $f_AdminButtons.= "</div>";
    $f_AdminButtons.= "<div class='ncf_row nc_clear'></div>";
    return $f_AdminButtons;
}

function nc_get_AdminButtons_suffix() {
    return "</div><div class='ncf_row nc_clear'></div>";
}

function nc_get_AdminButtons_prefix($f_Checked, $cc) {
    return "<div class='nc_idtab" . ($f_Checked ? "" : " nc_idtab_disabled") . "'>";
}


function nc_show_drag_handler($cc) {
    $query_order = nc_Core::get_object()->query_order;
    $SortBy = nc_Core::get_object()->sub_class->get_by_id($cc, 'SortBy');
    return !$query_order && !($SortBy && !preg_match('/^[\s]*[a.`]*Priority`?[\s]*(desc|asc)?[\s]*$/i', $SortBy)) ;
}

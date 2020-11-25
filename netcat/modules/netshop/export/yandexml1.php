<?

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

header("Content-type: text/xml");
$catalogue = $nc_core->catalogue->get_by_host_name($HTTP_HOST);
$catalogue = $catalogue["Catalogue_ID"];
if (!$catalogue) $catalogue = 1;

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
    $modules_lang = "Russian";
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
    $modules_lang = "English";
}

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->load_env($modules_lang);

class Netshop_ExportYML extends Netshop {

    private $_CurrencyArray;
    private $_MaxNameLen;
    private $_class_ids;

    public function __construct() {

        $this->Netshop();
        $this->_CurrencyArray = Array('RUR', 'RUB', 'USD', 'EUR', 'UAH');
        $this->_MaxNameLen = 20;
        $nc_core = nc_Core::get_object();
        $this->_class_ids = $nc_core->modules->get_vars('netshop', 'GOODS_TABLE');
    }
	public function convstr_li($str) {
		return iconv("UTF-8","windows-1251//TRANSLIT",$str);
	}
	
	public function convstrw_li($str) {
		return iconv("windows-1251//TRANSLIT","UTF-8",$str);
	}
    /**
     * Экспорт в формате YandexML
     * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
     */
    public function ExportYML($section=0) {
        global $HTTP_HOST, $SUB_FOLDER;
        global $db, $nc_core;
        global $catalogue;
		$view="";

		if ($_GET['view']=="on") {
			$view.="<p>VIEW</p>";
		}

        if (!$this->shop_id) return false;
        $shopName = (nc_strlen($this->ShopName) > $this->_MaxNameLen) ? nc_substr($this->ShopName, 0, $this->_MaxNameLen) : $this->ShopName;
        $default_currency = $this->Currencies[$this->DefaultCurrencyID];

		if ($_GET['view']=="on") {
			header("Content-type: text/html");
		} else {
			header("Content-type: text/xml");
		}
        $ret = "<?xml version=\"1.0\" encoding=\"".$nc_core->NC_CHARSET."\"?>
              <!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">
              <yml_catalog date=\"".(strftime("%Y-%m-%d %H:%M"))."\">
              <shop>
                <name>".xmlspecialchars($shopName)."</name>
                <company>".xmlspecialchars($this->CompanyName)."</company>
                <url>http://".$HTTP_HOST.$SUB_FOLDER."/</url>
                  <currencies>
                    <currency id=\"".$default_currency."\" rate=\"1\" />";
        foreach ((array) $this->Currencies as $k => $v) {
            if ($v != $default_currency && $this->Rates[$k] && in_array($v, $this->_CurrencyArray)) {
                $ret .= "<currency id=\"$v\" rate=\"".$this->Rates[$k]."\" />";
            }
        }

        $ret .= "</currencies>
              <categories>\n";

        // output categories (shop structure) ---------------------------
        // ----------------------------------------
        if (!$section) $section = $this->_class_ids;
        $structure = GetStructureYandexml($section, $catalogue,"");
		//print_r($structure);
        if (!$structure) return;

        $all_sections_ids = array(); // потом вытащим на основе этих данных товары

        foreach ($structure as $row) {
            
			if (($row["Subdivision_ID"]!=153)&&($row["Subdivision_ID"]!=58)
					&&($row["Subdivision_ID"]!=109)
					&&($row["Subdivision_ID"]!=147)
					&&($row["Subdivision_ID"]!=2751)
					&&($row["Subdivision_ID"]!=2747)
					&&($row["Subdivision_ID"]!=2748)
					&&($row["Subdivision_ID"]!=2749)
					&&($row["Subdivision_ID"]!=2750)) {
				$ret .= "<category id=\"{$row['Subdivision_ID']}\"";

				if (array_key_exists($row['Parent_Sub_ID'], $structure)) {
					$ret .= " parentId=\"{$row['Parent_Sub_ID']}\"";
				}

				$ret .= ">".xmlspecialchars($row["Subdivision_Name"])."</category>\n";

				$all_sections_id[] = $row["Subdivision_ID"];
				
				$view .= "<p>&lt;category id=\"{$row['Subdivision_ID']}\"";
				if (array_key_exists($row['Parent_Sub_ID'], $structure)) {
					$view .= " parentId=\"{$row['Parent_Sub_ID']}\"";
				}
				$view .= "&gt;".xmlspecialchars($row["Subdivision_Name"])."&lt;/category&gt;</p>";

			}
        }

        $ret .= "</categories>\n<offers>";

        // GOODS CATALOGUE -----------------------------------------------
        $output = array(
                "URL" => "url",
                "Price" => "price",
                "CurrencyID" => "currencyId",
                "Subdivision_ID" => "categoryId",
                "Image" => "picture",
 //               "Vendor" => "vendor",
 //               "VendorCode" => "vendorCode",
                "Name" => "name",
                "Description" => "description",
                "SalesNotes" => "sales_notes",
        );

        // получить типы товаров
        $goods_class_ids = $this->GuessGoodsTypeIDs();

        // все разделы магазина
        $subdivision_id = join(",", $all_sections_id);

		$ii=1;
        foreach ($goods_class_ids as $class_id) {
            $query = "SELECT m.*,
                         ShopCurrency_Name AS CurrencyID,
                         CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html') as URL,
                         IFNULL(m.$this->PriceColumn, parent.$this->PriceColumn) as Price4User,
                         IF(m.$this->PriceColumn IS NULL, parent.$this->CurrencyColumn, m.$this->CurrencyColumn) as Currency4User

                FROM (`Message".$class_id."` as m, `Subdivision` as u, `Sub_Class` as s)
                  LEFT JOIN Message".$class_id." as parent
                    ON (m.`Parent_Message_ID` != 0 AND m.`Parent_Message_ID` = parent.`Message_ID`)
                  LEFT JOIN `Classificator_ShopCurrency`
                    ON Classificator_ShopCurrency.`ShopCurrency_ID` = m.`Currency`
                WHERE m.`Checked` = 1
                    AND m.`Subdivision_ID` IN (".$subdivision_id.")
                    AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
					AND m.`status`=2 AND m.`Checked`=1
					AND m.`Price`>999 AND m.`yamarket`=1 AND NOT m.`supplier`=74
                HAVING `Price4User` > 0
                    ";
			//echo $query;
            $rows = $db->get_results($query, ARRAY_A);
            foreach ((array) $rows as $row) {

                if (strlen($row["StockUnits"])) {
                    $row["Available"] = ($row["StockUnits"] ? "true" : "false" );
                } else {
                    $row["Available"] = "true";
                }

                // convert to default currency
                $row["Price"] = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
                // we'll need an absolute url
                $row["URL"] = "http://".$HTTP_HOST.$SUB_FOLDER."$row[URL]";
                $row["CurrencyID"] = $row["CurrencyID"] ? $row["CurrencyID"] : $default_currency;

				//echo mb_stripos($row['Name'],$this->convstr_li("финск"))." ".$row['Name']."  ".$this->convstr_li("финск")." \n";
				
				if ((mb_stripos($row['Name'],$this->convstr_li("финск")) === false)&&
						(mb_stripos($row['Name'],$this->convstr_li("финка")) === false)&&
						(mb_stripos($row['Name'],$this->convstr_li("кортик")) === false)&&
						(mb_stripos($row['Name'],$this->convstr_li("стилет")) === false)&&
						(mb_stripos($row['Name'],$this->convstr_li("охотни")) === false)
					
						) {
					if ($row["Image"]) { // replace to image url
						$row["Image"] = "http://".$HTTP_HOST.$SUB_FOLDER.nc_file_path($class_id, $row["Message_ID"], "Image", "h_");
					}
					
					

					$ret .= "<offer id=\"".sprintf("%d%05d", $class_id, $row["Message_ID"])."\"";
					$vendormodel = 0;
					//if ($row['Vendor'] || $row['VendorCode']) {
					//   $ret .= " type=\"vendor.model\"";
					//    $vendormodel = 1; // произвольный товар
					//}
					$ret .= " available=\"$row[Available]\"";
					$ret .= ">\n";
					
					$view.="<p>{$ii}</p>";
					$view .= "&lt;offer id=\"".sprintf("%d%05d", $class_id, $row["Message_ID"])."\"";
					$view .= " available=\"$row[Available]\"";
					$view .= "&gt;<br>";

					//$row['Vendor']="";
					
					$classificators;
					$curr_comp = new nc_Component($class_id);
					$fields = $curr_comp->get_fields();
					foreach ($fields as $f) {
						$fields_assoc[$f['name']] = $f;
					}

					foreach ($output as $idx => $tag) {
						if ($row[$idx]) {

							$value = $row[$idx];

							if ($fields_assoc[$idx]['type'] == 4) {  // список
								$list_name = $db->escape(strtok($fields_assoc[$idx]['format'], ':'));
								if (!isset($classificators[$list_name])) {
									$db->query("SELECT `".$list_name."_ID`, `".$list_name."_Name` FROM `Classificator_".$list_name."`");
									$classificators[$list_name] = array_combine($db->get_col(NULL, 0), $db->get_col(NULL, 1));
								}
								$value = $classificators[$list_name][$value];
							} elseif ($fields_assoc[$idx]['type'] == 10) {  //множественный выбор
								$list_name = $db->escape(strtok($fields_assoc[$idx]['format'], ':'));
								if (!isset($classificators[$list_name])) {
									$db->query("SELECT `".$list_name."_ID`, `".$list_name."_Name` FROM `Classificator_".$list_name."`");
									$classificators[$list_name] = array_combine($db->get_col(NULL, 0), $db->get_col(NULL, 1));
								}
								$value_ids = explode(",", $value);
								$value = '';
								foreach ($value_ids as $val_id) {
									if ($val_id) {
										$value .= $classificators[$list_name][$val_id].", ";
									}
								}
								$value = nc_substr($value, 0, -2);
							}

							
							
							if ($tag == 'name' && $row['GroupName'] != '') {
								$ret .= "<$tag>".xmlspecialchars(strip_tags($row['GroupName']))." - ".xmlspecialchars(strip_tags($value))."</$tag>\n";
							} else {
								if ($vendormodel && $tag == 'name') $tag = 'model';
								$ret .= "<$tag>".xmlspecialchars(strip_tags($value))."</$tag>\n";
							}
							if ($tag == 'name' && $row['GroupName'] != '') {
								$view .= "&lt;$tag&gt;".xmlspecialchars(strip_tags($row['GroupName']))." - ".xmlspecialchars(strip_tags($value))."&lt;/$tag&gt;<br>";
							} else {
								if ($vendormodel && $tag == 'name') $tag = 'model';
								$view .= "&lt;$tag>".xmlspecialchars(strip_tags($value))."&lt;/$tag&gt;<br>";
							}
						}
					}
                }
				$ii=$ii+1;
				$view.="<br><br>";
                $ret .= "</offer>\n";
            }
        }
        // ---------------------------------------------------------------

        $ret .= "</offers>\n</shop>\n</yml_catalog>";
		
		if ($_GET['view']=="on") {
			print $view;
		} else {
			print $ret;
		}
        // return $ret;
    }

}

//End Class Netshop_ExportYML
//---------------

$shop = new NetShop_ExportYML();
$shop->ExportYML();
?>
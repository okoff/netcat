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

    /**
     * Экспорт в формате YandexML
     * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
     */
    public function ExportYML($section=0) {
        global $HTTP_HOST, $SUB_FOLDER;
        global $db, $nc_core;
        global $catalogue;


        if (!$this->shop_id) return false;
        $shopName = (nc_strlen($this->ShopName) > $this->_MaxNameLen) ? nc_substr($this->ShopName, 0, $this->_MaxNameLen) : $this->ShopName;
        $default_currency = $this->Currencies[$this->DefaultCurrencyID];

        header("Content-type: text/xml");
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
        $structure = GetStructureYandexml($section, $catalogue);
		//print_r($structure);
        if (!$structure) return;

        $all_sections_ids = array(); // потом вытащим на основе этих данных товары

        foreach ($structure as $row) {
            $ret .= "<category id=\"{$row['Subdivision_ID']}\"";

            if (array_key_exists($row['Parent_Sub_ID'], $structure)) {
                $ret .= " parentId=\"{$row['Parent_Sub_ID']}\"";
            }

            $ret .= ">".xmlspecialchars($row["Subdivision_Name"])."</category>\n";

            $all_sections_id[] = $row["Subdivision_ID"];
        }

        $ret .= "</categories>\n<offers>";

        // GOODS CATALOGUE -----------------------------------------------
        $output = array(
                "URL" => "url",
                "Price" => "price",
                "CurrencyID" => "currencyId",
                "Subdivision_ID" => "categoryId",
                "Image" => "picture",
                "Vendor" => "vendor",
                "VendorCode" => "vendorCode",
                "Name" => "name",
                "Description" => "description",
                "SalesNotes" => "sales_notes",
        );

        // получить типы товаров
        $goods_class_ids = $this->GuessGoodsTypeIDs();

        // все разделы магазина
        $subdivision_id = join(",", $all_sections_id);

       // foreach ($goods_class_ids as $class_id) {
            $query = "SELECT m . * , CONCAT( u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html' ) AS URL
FROM (`Message57` AS m, `Subdivision` AS u, `Sub_Class` AS s)
LEFT JOIN Message57 AS parent ON ( m.`Parent_Message_ID` !=0 AND m.`Parent_Message_ID` = parent.`Message_ID` )  
                WHERE  m.`Checked`=1 
					AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
			ORDER BY m.StockUnits DESC, m.Name ASC ";
			//echo $query."<br>";
            $rows = $db->get_results($query, ARRAY_A);
			//print_r($rows);
            foreach ((array) $rows as $row) {
				//print_r($row);
				//echo "<br>";
                //if (strlen($row["StockUnits"])) {
                //    $row["Available"] = ($row["StockUnits"] ? "true" : "false" );
                //} else {
                //    $row["Available"] = "true";
                //}

                // convert to default currency
                //$row["Price"] = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
                // we'll need an absolute url
                $row["URL"] = "http://".$HTTP_HOST.$SUB_FOLDER."$row[URL]";
                //$row["CurrencyID"] = $row["CurrencyID"] ? $row["CurrencyID"] : $default_currency;

                if ($row["Image"]) { // replace to image url
                    $row["Image"] = "http://".$HTTP_HOST.$SUB_FOLDER.nc_file_path($class_id, $row["Message_ID"], "Image", "h_");
                }

                $ret .= "<offer id=\"".sprintf("%d%05d", $class_id, $row["Message_ID"])."\"";
                //$vendormodel = 0;
                //if ($row['Vendor'] || $row['VendorCode']) {
                //    $ret .= " type=\"vendor.model\"";
                //    $vendormodel = 1; // произвольный товар
                //}
                //$ret .= " available=\"$row[Available]\"";
                $ret .= ">\n";

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
                    }
                }

                $ret .= "</offer>\n";
            }
       // }
        // ---------------------------------------------------------------

        $ret .= "</offers>\n</shop>\n</yml_catalog>";
        print $ret;
        // return $ret;
    }

}

//End Class Netshop_ExportYML
//---------------

$shop = new NetShop_ExportYML();
$shop->ExportYML();
?>
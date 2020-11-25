<?php
// make user's undivine
@ignore_user_abort(true);

// load system
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$MODULE_VARS = $nc_core->modules->get_module_vars();

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
}

@set_time_limit(0);

include_once($INCLUDE_FOLDER."index.php");

// system superior object
$nc_core = nc_Core::get_object();

$source_data = $nc_core->db->get_row("SELECT *
	FROM `Netshop_ImportSources`
	WHERE `source_id` = '".$source_id."'", ARRAY_A);

// classifier_id catalog_id
list($classifier_id, $catalog_id) = explode(' ', $source_data['external_id']);

if (!function_exists('xmlspecialchars')):

    function xmlspecialchars($text) {
        return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
    }

endif;

// set headers
header("Content-Type: Aplication/xml-file");
header("Content-Disposition: attachment; filename=".$source_data['name']."-offers.xml");

echo '<?xml version="1.0" encoding="'.$nc_core->NC_CHARSET.'"?>'.PHP_EOL;
?>
<КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="<?=date("Y-m-d")
?>T<?=date("H:i:s")
?>">
<ПакетПредложений СодержитТолькоИзменения="false">
<Ид><?=$catalog_id
?></Ид>
<Наименование><?=xmlspecialchars($source_data['name']) ?></Наименование>
<ИдКаталога><?=$catalog_id ?></ИдКаталога>
<ИдКлассификатора><?=$classifier_id ?></ИдКлассификатора>
<?php
// get module's vars
$MODULE_VARS = $nc_core->modules->get_vars('netshop');

$_curr_data = $nc_core->db->get_results("SELECT *
	FROM `Classificator_ShopCurrency`", ARRAY_A);
$curr_data = array();
foreach ($_curr_data as $row) {
    $curr_data[$row['ShopCurrency_ID']] = $row['ShopCurrency_Name'];
}

$_units_data = $nc_core->db->get_results("SELECT *
	FROM `Classificator_ShopUnits`", ARRAY_A);
$units_data = array();
foreach ($_units_data as $row) {
    $units_data[$row['ShopUnits_ID']] = $row['ShopUnits_Name'];
}

$price_data = $nc_core->db->get_results("SELECT nim.*, fld.`Field_Name`, fld.`Description`
	FROM `Netshop_ImportMap` AS nim
	LEFT JOIN `Field` AS fld ON nim.`value` = fld.`Field_Name`
	WHERE nim.`source_id` = '".$source_id."'
		AND nim.`type` = 'price'
		AND fld.`Class_ID` = '".$MODULE_VARS["GOODS_TABLE"]."'", ARRAY_A);
?>
<ТипыЦен>
<?php foreach ($price_data as $row): ?>
    <ТипЦены>
    <Ид><?=$row['source_string'] ?></Ид>
    <Наименование><?=xmlspecialchars($row['Description']) ?></Наименование>
    <Валюта><?=$curr_data[/* sry */$nc_core->db->get_var("SELECT `Currency` FROM `Message".$MODULE_VARS["GOODS_TABLE"]."` WHERE `".$row['Field_Name']."` > 0")] ?></Валюта>
</ТипЦены>
<?php endforeach; ?>
</ТипыЦен>
<?php ?>
<Предложения>
<?php
// , fld.`Description`
$properties_data = $nc_core->db->get_results("SELECT nim.*, fld.`Field_Name`
	FROM `Netshop_ImportMap` AS nim
	LEFT JOIN `Field` AS fld ON nim.`value` = fld.`Field_ID`
	WHERE nim.`source_id` = '".$source_id."'
		AND nim.`type` = 'oproperty'
	ORDER BY nim.`parent_tag`", ARRAY_A);

$goods_data = $nc_core->db->get_results("SELECT *
	FROM `Message".intval($MODULE_VARS["GOODS_TABLE"])."`
	WHERE `ImportSourceID` = '".$source_id."'", ARRAY_A);

foreach ($goods_data as $row):
?>
    <Предложение>
    <Ид><?=$row['ItemID'] ?></Ид>
    <Наименование><?=xmlspecialchars($row['Name'])
?></Наименование>
<?php foreach ($properties_data as $r): ?>
<?php if (!trim($row[$r['Field_Name']]) && $r['source_string'] != 'Количество')
            continue; ?>
    <<?=$r['source_string'] ?>><?=xmlspecialchars($row[$r['Field_Name']]) ?></<?=$r['source_string'] ?>>
<?php endforeach; ?>
<Цены>
<?php foreach ($price_data as $ro): ?>
<?php if ($ro['value'] == -1)
            continue; ?>
    <Цена>
    <Представление><?=$row[$ro['value']] ?> <?=$curr_data[$row['Currency']] ?></Представление>
<ИдТипаЦены><?=$ro['source_string'] ?></ИдТипаЦены>
<ЦенаЗаЕдиницу><?=$row[$ro['value']] ?></ЦенаЗаЕдиницу>
<Валюта><?=$curr_data[$row['Currency']] ?></Валюта>
<?php if (isset($units_data[$row['Units']])): ?>
    <Единица><?=$units_data[$row['Units']] ?></Единица>
<?php endif; ?>
    <Коэффициент>1</Коэффициент>
    </Цена>
<?php endforeach; ?>
    </Цены>
    </Предложение>
<?php endforeach; ?>
</Предложения>
</ПакетПредложений>
</КоммерческаяИнформация>

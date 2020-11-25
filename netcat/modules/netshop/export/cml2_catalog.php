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
header("Content-Disposition: attachment; filename=".$source_data['name']."-catalog.xml");

echo '<?xml version="1.0" encoding="'.$nc_core->NC_CHARSET.'"?>'.PHP_EOL;
?>
<КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="<?=date("Y-m-d")
?>T<?=date("H:i:s")
?>">
<Классификатор>
<Ид><?=$classifier_id ?></Ид>
<Наименование><?=xmlspecialchars($source_data['name']) ?></Наименование>
<?php
$sections_data = $nc_core->db->get_results("SELECT nim.*, sub.`Parent_Sub_ID`, sub.`Subdivision_Name`
	FROM `Netshop_ImportMap` AS nim
	LEFT JOIN `Subdivision` AS sub ON nim.`value` = sub.`Subdivision_ID`
	WHERE nim.`source_id` = '".$source_id."'
		AND nim.`type` = 'section'
	ORDER BY sub.`Parent_Sub_ID`", ARRAY_A);

$sections_tree = array();

foreach ($sections_data as $row) {
    $sections_tree[$row['Parent_Sub_ID']][] = $row;
}

$MODULE_VARS = $nc_core->modules->get_vars('netshop');

// shop's subdivision data
$shop = GetSubdivisionByType($MODULE_VARS["SHOP_TABLE"], "Subdivision_ID, Subdivision_Name", 1);

function export_groups_tree($data, $parent_sub_id = 0, $level = 0) {
    foreach ($data[$parent_sub_id] as $row) {
        echo str_repeat('  ', $level).'      <Группа>'.PHP_EOL;
        echo str_repeat('  ', $level).'        <Ид>'.$row['source_string'].'</Ид>'.PHP_EOL;
        echo str_repeat('  ', $level).'        <Наименование>'.xmlspecialchars($row['Subdivision_Name']).'</Наименование>'.PHP_EOL;
        if (isset($data[$row['value']]))
                export_groups_tree($data, $row['value'], $level + 1);
        echo str_repeat('  ', $level).'      </Группа>'.PHP_EOL;
    }
}
?>
<Группы>
<?php export_groups_tree($sections_tree, $shop['Subdivision_ID']); ?>
</Группы>
<?php ?>
</Классификатор>
<Каталог СодержитТолькоИзменения="false">
<Ид><?=$catalog_id ?></Ид>
<ИдКлассификатора><?=$classifier_id ?></ИдКлассификатора>
<Наименование><?=xmlspecialchars($source_data['name']) ?></Наименование>
<Товары>
<?php
// , fld.`Description`
$properties_data = $nc_core->db->get_results("SELECT nim.*, fld.`Field_Name`
	FROM `Netshop_ImportMap` AS nim
	LEFT JOIN `Field` AS fld ON nim.`value` = fld.`Field_ID`
	WHERE nim.`source_id` = '".$source_id."'
		AND nim.`type` = 'property'
	ORDER BY nim.`parent_tag`", ARRAY_A);

$goods_data = $nc_core->db->get_results("SELECT *
	FROM `Message".intval($MODULE_VARS["GOODS_TABLE"])."`
	WHERE `ImportSourceID` = '".$source_id."'", ARRAY_A);

$sub_1c_rel = array();
foreach ($sections_data as $row) {
    $sub_1c_rel[$row['value']] = $row['source_string'];
}

foreach ($goods_data as $row):
?>
    <Товар>
    <Ид><?=$row['ItemID']
?></Ид>
    <Группы>
    <Ид><?=$sub_1c_rel[$row['Subdivision_ID']] ?></Ид>
    </Группы>
<?php $parent_tag = ''; ?>
<?php foreach ($properties_data as $r): ?>
<?php if (!trim($row[$r['Field_Name']]))
                continue; ?>
<?php if ($parent_tag != $r['parent_tag']): ?>
<?php if ($parent_tag): ?>
                </<?=$parent_tag ?>>
<?php endif; ?>
<?php $parent_tag = $r['parent_tag']; ?>
<<?=$parent_tag ?>>
<?php endif; ?>
<?php if ($parent_tag): ?>
<?php if ($parent_tag == 'ХарактеристикиТовара'): ?>
        <ХарактеристикаТовара>
        <Наименование><?=xmlspecialchars($r['source_string']) ?></Наименование>
<Значение><?=xmlspecialchars($row[$r['Field_Name']]) ?></Значение>
</ХарактеристикаТовара>
<?php endif; ?>
<?php if ($parent_tag == 'ЗначенияРеквизитов'): ?>
    <ЗначениеРеквизита>
    <Наименование><?=xmlspecialchars($r['source_string']) ?></Наименование>
<Значение><?=xmlspecialchars($row[$r['Field_Name']]) ?></Значение>
</ЗначениеРеквизита>
<?php endif; ?>
<?php if ($parent_tag == 'СтавкиНалогов'): ?>
    <СтавкаНалога>
    <Наименование><?=xmlspecialchars($r['source_string']) ?></Наименование>
<Ставка><?=xmlspecialchars($row[$r['Field_Name']]) ?></Ставка>
</СтавкаНалога>
<?php endif; ?>
<?php else: ?>
    <<?=$r['source_string'] ?>><?=xmlspecialchars($row[$r['Field_Name']]) ?></<?=$r['source_string'] ?>>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($parent_tag): ?>
    </<?=$parent_tag ?>>
<?php endif; ?>
    </Товар>
<?php endforeach; ?>
</Товары>
</Каталог>
</КоммерческаяИнформация>

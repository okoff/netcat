<?php
if (!class_exists("nc_system")) {
    die;
}
$ui = $this->get_ui();
$ui->add_settings_toolbar();

$rules = nc_search::load('nc_search_rule', "SELECT * FROM `%t%` ORDER BY `Rule_ID`");

if (count($rules)) {
?>

    <form method="POST" action="?view=rules" onsubmit="return (jQuery('input:checked').size() > 0)">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="data_class" value="nc_search_rule" />
        <table class="list">
            <tr align="left">
                <th align='center'><?=NETCAT_MODULE_SEARCH_ADMIN_ID
?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_NAME
?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_SITE
?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA
?></th>
            <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_SCHEDULE ?></th>
            <th align="center"><?=NETCAT_MODULE_SEARCH_ADMIN_ACTIONS ?></th>
            <th align="center"><div class='icons icon_delete' title="<?=NETCAT_MODULE_SEARCH_ADMIN_DELETE ?>"></div></th>
        </tr>
        <?php
        foreach ($rules as $r) {
            $id = $r->get_id();

            echo "<tr><td align='center'>$id</td>",
            "<td>", $this->if_null($r->get('name'), NETCAT_MODULE_SEARCH_ADMIN_UNNAMED_RULE), "</td>",
            "<td>", $r->get_site_name(), "</td>",
            "<td>", $this->if_null($r->get('area_string'), NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_WHOLE_SITE), "</td>",
            "<td>", $r->get_schedule_string(), "</td>",
            "<td align='center'>",
            "<a href='?view=rules_edit&amp;copy=$id'>",
            "<div class='icons icon_copy' title='".NETCAT_MODULE_SEARCH_ADMIN_COPY."'></div>",
            "</a><span class='icon_spacer'></span><a href='?view=rules_edit&amp;id=$id'>",
            "<div class='icons icon_pencil' title='".NETCAT_MODULE_SEARCH_ADMIN_EDIT."'></div>",
            "</a>",
            "</td>",
            "<td align='center'><input type='checkbox' name='ids[]' value='$id' /></td>",
            "</tr>\n";
        }
        ?>
            </table>
        </form>

<?php
        $ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED);
    } else { // no entries
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EMPTY_LIST, 'info');
    }

    $ui->actionButtons[] = array("id" => "add",
            "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD,
            "location" => "#module.search.rules_edit",
            "align" => "left");




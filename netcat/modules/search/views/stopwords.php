<?php
if (!class_exists("nc_system")) {
    die;
}
$ui = $this->get_ui();
$ui->add_lists_toolbar();

$stopwords = nc_search::load('nc_search_language_stopword', "SELECT * FROM `%t%` ORDER BY `Language`, `Word`");

if (count($stopwords)) {

    // фильтр
    $language_options = array("<option value=''>".NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY_LANGUAGE."</option>");
    foreach ($this->get_language_list() as $code => $lang) {
        if ($stopwords->first('language', $code)) {
            $language_options[] = "<option value='$code'>$lang</option>";
        }
    }

    echo "<div class='live_filter' id='stopword_filter'>",
    "<span class='icon'>", nc_admin_img("i_field_search_off.gif", NETCAT_MODULE_SEARCH_ADMIN_FILTER), "</span>",
    "<select id='filter_language'>", join("\n", $language_options), "</select>",
    "<input type='text' id='filter_word'>",
    "<span class='reset'>", "<div class='icons icon_delete' title='".NETCAT_MODULE_SEARCH_ADMIN_FILTER_RESET."' style='margin-top:5px'></div>", "</span>",
    "</div>";
?>

    <form method="POST" action="?view=stopwords" onsubmit="return (jQuery('input:checked').size() > 0)">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="data_class" value="nc_search_language_stopword" />
        <table class="list">
            <tr align="left">
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE
?></th>
                <th width="75%"><?=NETCAT_MODULE_SEARCH_ADMIN_STOPWORD
?></th>
                <th align="center"><?=NETCAT_MODULE_SEARCH_ADMIN_EDIT ?></th>
            <th align="center"><div class='icons icon_delete' title="<?=NETCAT_MODULE_SEARCH_ADMIN_DELETE ?>"></div></th>
        </tr>
        <?php
        foreach ($stopwords as $s) {
            $id = $s->get_id();
            echo "<tr>",
            "<td class='language'>", $s->get('language'), "</td>",
            "<td class='word'>", $s->get('word'), "</td>",
            "<td align='center'><a href='?view=stopwords_edit&amp;id=$id'>",
            "<div class='icons icon_pencil' title='".NETCAT_MODULE_SEARCH_ADMIN_EDIT."'></div>",
            "</a></td>",
            "<td align='center'><input type='checkbox' name='ids[]' value='$id' /></td>",
            "</tr>\n";
        }
        ?>
    </table>
</form>

<script type="text/javascript">
    jQuery('#stopword_filter').createFilterFor(jQuery('table.list'));
</script>

<?php
        $ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED);
    } else { // no entries
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EMPTY_LIST, 'info');
    }

    $ui->actionButtons[] = array("id" => "add",
            "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD,
            "location" => "#module.search.stopwords_edit",
            "align" => "left");


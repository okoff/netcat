<?php

if (!class_exists("nc_system")) {
    die;
}
$ui = $this->get_ui();
$ui->add_lists_toolbar();

$per_page = 100;
$offset = (int) $this->get_input('offset');
$group_by = $this->get_input('group_by', 'url'); // referrer|url

if ($group_by == 'referrer') {
    $query = "SELECT SQL_CALC_FOUND_ROWS
                   l.`Referrer_URL`,
                   doc.`Title`,
                   doc.`Catalogue_ID`,
                   doc.`Subdivision_ID`
              FROM `Search_BrokenLink` AS l
              LEFT JOIN `Search_Document` AS doc
                     ON (l.`Referrer_Document_ID` = doc.`Document_ID`)
             GROUP BY l.`Referrer_URL`
             ORDER BY l.`Referrer_URL`
             LIMIT $per_page OFFSET $offset";
} else { // 'url' or whatever it was
    $group_by = 'url';
    $query = "SELECT SQL_CALC_FOUND_ROWS
                   `URL`
              FROM `Search_BrokenLink`
             GROUP BY `URL`
             ORDER BY `URL`
             LIMIT $per_page OFFSET $offset";
}

$db = $this->get_db();
$res = $db->get_results($query, ARRAY_A);

if ($res) {
    $found_rows = $this->get_db()->get_var("SELECT FOUND_ROWS()");
    // строка с вариантами сортировки
    $group_link = $this->make_page_query(array('group_by'));

    echo '<br /><div class="link_grouping">',
    NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY, " ",
    $this->link_if($group_by != 'url', "$group_link&amp;group_by=url", NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY_URL),
    " | ",
    $this->link_if($group_by != 'referrer', "$group_link&amp;group_by=referrer", NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY_REFERRER),
    "</div>\n";

    echo "<div class='broken_link_list'><ul>\n";

    // --------------------------------------------------------------- //
    // по-хорошему функционал определения параметров объекта/раздела
    // по его пути должен быть в ядре, но его, вроде бы, нет...
    function _edit_doc_link($row) { // global function inside a method... huh?
        if (!$row["Subdivision_ID"]) {
            return "";
        } // oops

        global $nc_core, $db;

        $url = parse_url($row["Referrer_URL"]);
        $path = $url["path"];
        $path_page = "";
        if (substr($path, -1) != '/') {
            $path_page = basename($path);
        }

        // (A) subdivision; subclass
        if (!preg_match("/_\d+\.\w+$/", $path_page)) { // not smth. like "object_55.html"
            if (isset($url["query"])) {
                parse_str($url["query"], $params);
            } else {
                $params = array();
            }

            $curPos = isset($params["curPos"]) ? "&amp;curPos=$params[curPos]" : "";
            $cc = "";

            if ($path_page) { // subclass
                $cc_name = $db->escape(preg_replace("/\.\w+$/", "", $path_page));
                $cc_id = $db->get_var("SELECT Sub_Class_ID
                                 FROM Sub_Class
                                WHERE EnglishName = '$cc_name'
                                  AND Subdivision_ID = '$row[Subdivision_ID]'");
                $cc = "&amp;cc=$cc_id";
            }

            $href = $url['scheme']."://".$url['host'].$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH.
                    "?catalogue=$row[Catalogue_ID]&amp;sub=$row[Subdivision_ID]$cc$curPos";
        }
        // (B) object page
        else {
            $href = preg_replace("#/([^/]+)$#", "/edit_$1", $row["Referrer_URL"]);
        }

        return "<a href='$href' target='_blank'>".NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_EDIT."</a>";
    }

    // --------------------------------------------------------------- //

    foreach ($res as $row) {
        echo "<li>";
        if ($group_by == 'referrer') {
            echo "<b><a href='", htmlspecialchars($row['Referrer_URL']), "' target='_blank'>",
            $row['Title'] ? $row['Title'] : $row['Referrer_URL'],
            "</a></b> &nbsp; ", _edit_doc_link($row), "\n",
            "<ul>\n";

            $where = ($row["Referrer_Document_ID"] ? "`Referrer_Document_ID` = '$row[Referrer_Document_ID]'" : "`Referrer_URL` = '".$db->escape($row["Referrer_URL"])."'");

            $broken_links = $db->get_col("SELECT `URL`
                                      FROM `Search_BrokenLink`
                                     WHERE $where");
            foreach ($broken_links as $link) {
                echo "<li>".nc_search_util::decode_url($link)."</li>\n";
            }
            echo "</ul>\n";
        } else { // group by broken link URL
            echo "<b>".nc_search_util::decode_url($row['URL'])."</b>\n<ul>";

            $referrers = $db->get_results(
                            "SELECT l.`Referrer_URL`,
                      doc.`Title`,
                      doc.`Catalogue_ID`,
                      doc.`Subdivision_ID`
                 FROM `Search_BrokenLink` AS l
                 LEFT JOIN `Search_Document` AS doc
                      ON (l.`Referrer_Document_ID` = doc.`Document_ID`)
                WHERE l.`URL` = '".$db->escape($row['URL'])."'",
                            ARRAY_A);

            foreach ($referrers as $ref) {
                echo "<li><b><a href='", htmlspecialchars($ref['Referrer_URL']), "' target='_blank'>",
                $ref['Title'] ? $ref['Title'] : $ref['Referrer_URL'],
                "</a></b> &nbsp; ", _edit_doc_link($ref), "</li>\n";
            }

            echo "</ul>\n";
        }

        echo "</li>\n";
    }

    echo "</ul></div>";

    // листалка по страницам
    $page_link = $this->make_page_query(array('offset'), true);
    if ($offset > 0) {
        $prev_page = $page_link."&amp;offset=".($offset - $per_page);
        $ui->actionButtons[] = array("id" => "prev_page",
                "caption" => NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_PREV_PAGE,
                "action" => "mainView.loadIframe('$prev_page')",
                "align" => "left");
    }
    if ($found_rows > $offset + $per_page) {
        $next_page = $page_link."&amp;offset=".($offset + $per_page);
        $ui->actionButtons[] = array("id" => "next_page",
                "caption" => NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_NEXT_PAGE,
                "action" => "mainView.loadIframe('$next_page')");
    }
} else {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_NO_BROKEN_LINKS, 'info');
}

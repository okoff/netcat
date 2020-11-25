<?php

/* $Id: tree_json.php 7781 2012-07-24 14:26:20Z alive $ */

ob_start("ob_gzhandler");

define("NC_ADMIN_ASK_PASSWORD", false);
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

list($node_type, $node_id) = explode("-", $node);
$node_id = (int) $node_id;

// (a) path to the node
// [possible optimization point: output node info instead of ids, thus removing extra 'get path' request]
if ($_GET['action'] == 'get_path' && $node_type == 'sub' && $node_id) {
    $parent_sub_id = $node_id;

    while ($parent_sub_id != 0) {
        $ret[] = "sub-{$parent_sub_id}";
        $row = $db->get_row("SELECT Parent_Sub_ID, Catalogue_ID FROM Subdivision WHERE Subdivision_ID = $parent_sub_id", ARRAY_A);
        if (!$row) exit("[]");
        $parent_sub_id = $row['Parent_Sub_ID'];
    }

    $ret[] = "site-$row[Catalogue_ID]";
    // remove the node itself, as it has not to be expanded
    $ret = array_reverse($ret);
    array_pop($ret);
    print "while(1);".nc_array_json($ret);
    exit;
}

// (b) contents of the node

$ret = array();
$ret_sites = array();
$ret_sub = array();



// список сайтов
if ($node_type == "root") {

    // получим id всех каталогов, к которому пользователь имеет доступ админа\модер
    // или иммет доступ к его разделам, тоже админ\модер
    // если ф-ция вернет не массив, то значит есть достп ко всем
    $array_id = $perm->GetAllowSite(MASK_ADMIN | MASK_MODERATE, true);

    $sites = $db->get_results("SELECT Catalogue_ID, Catalogue_Name, Domain, Mirrors, Checked, ncMobile, ncResponsive
                             FROM Catalogue
                             WHERE ".( (is_array($array_id) && !$perm->isGuest() ) ? "Catalogue_ID IN(".join(',', (array) $array_id).")" : "1" )."
                             ORDER BY Priority ", ARRAY_A);

    $found_current_site = false;

    foreach ((array) $sites as $site) {
        $image = 'icon_site';
        $image .= $site['ncMobile'] ? '_mobile' : '';
        $image .= $site['ncResponsive'] ? '_adapt' : '';
        $image .= $site['Checked'] ? '' : '_disabled';
        $is_site_admin = $perm->isAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADMIN, $site['Catalogue_ID'], 0);

        $buttons = array();
        $buttons[] = array(
                "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
                "action" => "window.open('http://".(($site['Domain']) ? $site['Domain'] : $HTTP_HOST).$SUB_FOLDER."');",
                'icon' => 'icons icon_preview'
        );

        if ($is_site_admin) {
            $buttons[] = array(
                    "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
                    "action" => "parent.location.hash = 'subdivision.add(0,$site[Catalogue_ID])'",
                    'icon' => 'icons icon_folder_add');
        }

        $ret_sites[] = array("nodeId" => "site-$site[Catalogue_ID]",
                "name" => $site[Catalogue_ID].". ".strip_tags($site["Catalogue_Name"]),
                "href" => "#site.map($site[Catalogue_ID])",
                "image" => $image,
                "hasChildren" => true,
                "acceptDropFn" => "treeSitemapAcceptDrop",
                "onDropFn" => "treeSitemapOnDrop",
                "dragEnabled" => $is_site_admin,
                "buttons" => $buttons,
                "checked" => $site['Checked'],
                "className" => "menu_site"
        );

        $domain_name = preg_quote($site['Domain']);
        if ($site['Mirrors']) {
            $site['Mirrors'] = str_replace(array('http://', '/'), '', $site['Mirrors']);
            $domain_name .= "|".nc_preg_replace("/\r\n/", "|", preg_quote($site['Mirrors']));
        }

        if ($domain_name && preg_match("/^(?:$domain_name)$/", $HTTP_HOST)) {
            $ret_sites[sizeof($ret_sites) - 1]["expand"] = true;
            $found_current_site = true;
        }
    }

    if (!$found_current_site) {
        $ret_sites[0]["expand"] = true;
    }
}

// разделы
elseif (($node_type == 'sub' || $node_type == 'site') && $node_id) {

    if ($node_type == 'site') {
        $qry_where = "sub.Parent_Sub_ID=0 AND sub.Catalogue_ID='".$node_id."'";
        $current_site = $node_id;
    } else {
        $qry_where = "sub.Parent_Sub_ID='".$node_id."'";
        $current_site = $nc_core->subdivision->get_by_id($node_id, "Catalogue_ID");
    }

    // Получить разделы, которые пользователь может видеть
    $allow_id = $perm->GetAllowSub($current_site, MASK_ADMIN | MASK_MODERATE, true, true, true);
    $qry_where .= ( is_array($allow_id) && !$perm->isGuest() ) ? " AND sub.Subdivision_ID IN(".join(',', (array) $allow_id).") " : "AND 1";

    $SQL = "SELECT sub.`Subdivision_ID`,
                   sub.`Subdivision_Name`,
                   sub.`Catalogue_ID`,
                   sub.`ExternalURL`,
                   sub.`Hidden_URL`,
                   sub.`Parent_Sub_ID`,
                   sub.`Checked`,
                   catalogue.`Domain`,
                   child.`Subdivision_ID` as hasChildren
                FROM Subdivision AS sub
                  LEFT JOIN `Subdivision` as child ON sub.`Subdivision_ID` = child.`Parent_Sub_ID`
                  LEFT JOIN Catalogue AS catalogue ON catalogue.Catalogue_ID = sub.Catalogue_ID
                    WHERE ".$qry_where."
                        GROUP BY sub.`Subdivision_ID`
                            ORDER BY sub.`Priority`";

    $subdivisions = $db->get_results($SQL, ARRAY_A);
    // получить разделы, кторые пользователь может админить
    $allow_id = $perm->GetAllowSub($current_site, MASK_ADMIN, false, true, false);
    $qry_where = ( is_array($allow_id) && !$perm->isGuest() ) ? " WHERE Subdivision_ID IN(".join(',', (array) $allow_id).") " : "";
    $sub_admin = (array) $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` ".$qry_where);

    $is_site_admin = $perm->isAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADMIN, $site['Catalogue_ID'], 0);

    foreach ((array) $subdivisions as $sub) {
        $is_subdivision_admin = in_array($sub['Subdivision_ID'], $sub_admin);

        $buttons = array();

        $buttons[] = array(
                "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
                "action" => "window.open('http://".(($sub['Domain']) ? $sub['Domain'] : $HTTP_HOST).$SUB_FOLDER.$sub['Hidden_URL']."');",
                'icon' => 'icons icon_preview');

        if ($is_subdivision_admin) {
            $buttons[] = array(
                    "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
                    "action" => "parent.location.hash = 'subdivision.add(".$sub['Subdivision_ID'].")'",
                    'icon' => 'icons icon_folder_add');

            $buttons[] = array(
                    "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_KILL,
                    "action" => "parent.location.hash = 'subdivision.delete(".$sub['Subdivision_ID'].")'",
                    'icon' => 'icons icon_delete');

            $tree_image = "icon_folder".($sub["Checked"] ? "" : "_disabled");
        } else {
            $tree_image = "i_folder_blocked".($sub["Checked"] ? "" : "_disabled").".png";
        }

        $ret_sub[$sub['Subdivision_ID']] = array("nodeId" => "sub-$sub[Subdivision_ID]",
                "parentNodeId" => ($sub['Parent_Sub_ID'] ? "sub-$sub[Parent_Sub_ID]" : "site-$sub[Catalogue_ID]"),
                "name" => $sub[Subdivision_ID].". ".strip_tags(str_replace(array("\r", "\n"), '', $sub["Subdivision_Name"])),
                "href" => $is_subdivision_admin ? "#subdivision.edit($sub[Subdivision_ID])" : "#subdivision.view($sub[Subdivision_ID])",
                "image" => $tree_image,
                //"hasChildren" => false,
                "dragEnabled" => $is_subdivision_admin,
                "buttons" => $buttons,
                "acceptDropFn" => "treeSitemapAcceptDrop",
                "onDropFn" => "treeSitemapOnDrop",
                "className" => ($sub["Checked"] ? "" : "disabled"),
                "checked" => $sub["Checked"],
                "hasChildren" => ($sub['hasChildren'] ? true : false),
                "subclasses" => array());
    } // of foreach subdivision

    /* информация о шаблонах в разделах. необходима для:
     *  1. формирования ссылки на список объектов
     *  2. для определения возможности перемещения объекта в конкретный раздел
     */
    $all_subs = join(",", array_keys($ret_sub));
    if ($all_subs) {
        $subclass_data = $db->get_results("SELECT Sub_Class_ID, Class_ID, Subdivision_ID
                                         FROM Sub_Class
                                        WHERE Subdivision_ID IN ($all_subs)
                                        ORDER BY Priority",
                        ARRAY_A);

        foreach ((array) $subclass_data as $row) { //print "A";
            //Если есть сс и есть право на его модерирование - то ссылка на вкладку "Редактирование"
            if (!$ret_sub[$row['Subdivision_ID']]["subclasses"]
                    && ( $perm->isSubClass($row[Sub_Class_ID], MASK_MODERATE) || $perm->isGuest() )) {
                $ret_sub[$row['Subdivision_ID']]["href"] = "#object.list($row[Sub_Class_ID])";
            }

            $ret_sub[$row['Subdivision_ID']]["subclasses"][] =
                    array("subclassId" => $row["Sub_Class_ID"],
                            "classId" => $row["Class_ID"]);
        }
    }
}

/**
 * выводим результат и свободны
 */
$ret = array_merge(array_values($ret_sites), array_values($ret_sub));
print "while(1);".nc_array_json($ret);
?>
<?php

/**
 * Входящие параметры:
 *  - term
 * 
 * @global $catalogue
 */
$NETCAT_FOLDER = realpath("../../../../");
require_once("$NETCAT_FOLDER/vars.inc.php");
require ($INCLUDE_FOLDER."index.php");

// получение параметров
$input = trim($nc_core->input->fetch_get('term'));
$input = $nc_core->utf8->conv($nc_core->NC_CHARSET, 'utf-8', $input);
if (!nc_search::should('EnableQuerySuggest') ||
        nc_search::get_setting('SuggestMode') != 'queries' ||
        mb_strlen($input) < nc_search::get_setting('SuggestionsMinInputLength')) {
    die("[]");
}

// поиск запросов, начинающихся с указанной подстроки
$db->query("SET NAMES 'utf8'");
$query = "SELECT DISTINCT(`QueryString`) AS `label` FROM `Search_Query` 
           WHERE `QueryString` LIKE '".nc_search_util::db_escape($input)."%'
             AND `ResultsCount` > 0
           ORDER BY `QueryString`
           LIMIT ".(int) nc_search::get_setting('NumberOfSuggestions');

$suggestions = $db->get_results($query, ARRAY_A);
print json_encode($suggestions);

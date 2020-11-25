<?php

/* $Id: stopwords.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Фильтр стоп-слов
 */
class nc_search_language_filter_stopwords extends nc_search_language_filter {

    static protected $lists = array();

    public function filter(array $terms) {
        if (!nc_search::should('RemoveStopwords')) {
            return $terms;
        }

        $language = $this->context->get('language');

        if (!isset(self::$lists[$language])) {
            $query = "SELECT * FROM `%t%` WHERE `Language`='".nc_search_util::db_escape($language)."'";
            self::$lists[$language] = nc_search::load('nc_search_language_stopword', $query, 'word');
        }

        $stoplist = self::$lists[$language];
        $result = array();
        foreach ($terms as $term) {
            if (!$stoplist->has_key($term)) {
                $result[] = $term;
            }
        }

        return $result;
    }

}

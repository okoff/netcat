<?php

/* $Id: link.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_indexer_link extends nc_search_data_persistent {

    protected $options = array(
            'id' => null,
            'url' => '',
            'is_processed' => false,
            'is_broken' => false,
    );
    protected $table_name = 'Search_Link';
    protected $mapping = array(
            'id' => 'Link_ID',
            'url' => 'URL',
            'is_processed' => 'Processed',
            'is_broken' => 'Broken',
    );

    /**
     * Override prepare_set_clause() to set the Hash field
     */
    protected function prepare_set_clause() {
        $set = parent::prepare_set_clause();
        $set .= ", `Hash` = UNHEX(SHA1('".nc_search_util::db_escape($this->get('url'))."'))";
        return $set;
    }

    /**
     * Load link with the specified URL
     * @param string $url
     * @return nc_search_indexer_link|FALSE
     */
    public function load_by_url($url) {
        $link = new self();
        return $link->load_from_query("SELECT ".$this->get_all_column_names().
                "  FROM `$this->table_name`".
                " WHERE `Hash` = UNHEX(SHA1('".nc_search_util::db_escape($url)."'))");
    }

    /**
     * Get first link with 'is_processed'==false
     * @return nc_search_indexer_link|FALSE
     */
    public static function get_first_non_processed() {
        $link = new self();
        return $link->load_where('is_processed', false);
    }

}
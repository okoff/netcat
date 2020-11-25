<?php

/* $Id: stemmer.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Base class for a stemmer.
 * Concrete class must implement stem() method
 */
abstract class nc_search_language_analyzer_stemmer extends nc_search_language_analyzer {

    protected $max_cache_size = 10000;
    protected $cache = array();

    /**
     * @param string $term
     * @return string term after the stemming
     */
    abstract public function stem($term);

    /**
     *
     * @param array $terms
     * @return array
     */
    public function get_base_forms(array $terms) {
        // $result = array_map(array($this, 'stem'), $terms);
        $result = array();
        foreach ($terms as $t) {
            if (isset($this->cache[$t])) {
                $result[] = $this->cache[$t];
            } else {
                $result[] = $this->cache[$t] = $this->stem($t);
                if (sizeof($this->cache) > $this->max_cache_size) {
                    $this->cache = array_slice($this->cache, -$this->max_cache_size / 2);
                }
            }
        }
        return array_unique($result);
    }

    /**
     *
     * @param array $terms
     * @return string
     */
    public function get_highlight_regexp(array $terms) {
        $res = array();
        foreach ($this->get_base_forms($terms) as $base) {
            $res[] = $base."[\pL\d]*";
        }
        return nc_search_util::word_regexp("(".join("|", $res).")", "Si");
    }

}
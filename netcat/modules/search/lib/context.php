<?php

/* $Id: context.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Контекст для менеджера расширений
 */
class nc_search_context extends nc_search_data {

    protected $options = array(
            'search_provider' => null,
            'language' => null,
            'action' => null, // 'searching', 'indexing'
            'content_type' => null, // for selecting an appropriate parser depending on MIME type
    );

    /**
     * Сравнение контекста с правилом
     * @param nc_search_context $rule
     * @return boolean
     */
    public function conforms_to(nc_search_extension_rule $rule) {
        foreach ($this->options as $key => $this_value) {
            if (!$rule->has_option($key)) {
                continue;
            }
            $rule_value = $rule->get($key);
            if ($rule_value !== null && $rule_value != '' && $rule_value != $this_value) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @return string
     */
    public function get_hash() {
        return crc32(serialize($this->options));
    }

}

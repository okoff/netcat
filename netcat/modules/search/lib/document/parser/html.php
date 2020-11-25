<?php

/* $Id: html.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Парсер для HTML-документов.
 */
class nc_search_document_parser_html implements nc_search_document_parser {

    /**
     * @var nc_search_context
     */
    protected $context;
    /**
     * @var array key: field name, value: nc_search_document_parser_html_fragment
     */
    protected $parts = array();

    /**
     *
     * @param nc_search_context $context
     */
    public function __construct(nc_search_context $context) {
        $this->context = $context;
        libxml_use_internal_errors(true);
    }

    // ---------------------------------------------------------------------------
    // nc_search_document_parser interface
    // ---------------------------------------------------------------------------

    /**
     * Предварительная обработка HTML-документа
     */
    public function load(nc_search_indexer_crawler_response $response) {
        libxml_clear_errors();

        // такое преобразование позволяет избавиться от некорректных для UTF-8
        // последовательностей, из-за которых могут возникать неожиданные проблемы:
        $html = mb_convert_encoding($response->get_body(), 'UTF-8', 'UTF-8');

        // это позволит различать предложения, не оканчивающиеся на точку,
        // после того, как будут убраны тэги (в макетах NetCat немало таких мест):
        $html = str_ireplace(
                        array("<div", "</div>", "</p>", "<li>", "<tr", "<td", "<br", "</a><a"),
                        array("\n\n<div", "\n\n</div>", "\n\n</p>", "\n\n<li>", "\n<tr", "\n<td", "\n<br", "</a> <a"),
                        $html);
        // уберем тег script
        $html = preg_replace("#<script(.*?)>(.*?)</script>#siu", '', $html);

        $this->parts = array('document' => new nc_search_document_parser_html_fragment($html));
    }

    /**
     * Если есть мета-тэг robots или с именем бота и значенем 'noindex', 
     * документ не индексируется (внимание: атрибуты case-sensitive)
     * 
     * @return boolean
     */
    public function should_index() {
        if (!nc_search::should('ObeyMetaNoindex')) {
            return true;
        }

        $xpath_query = '//meta[((@name="robots") or (@name="'.
                nc_search::get_setting('CrawlerUserAgent').
                '")) and (contains(@content, "noindex"))]';

        return ($this->xpath($xpath_query)->length == 0);
    }

    /**
     *
     * @return nc_search_document
     */
    public function get_document() {
        $doc = new nc_search_document;
        $fields = $this->get_field_settings();

        // first get fields which are fetched from the 'document' (incl. 'content' field)
        foreach (array('document', 'content') as $scope) {
            foreach ($fields->where('query_scope', $scope) as $field) {
                $this->extract_field_value($field);
            }
            // сохранить полное содержимое <!-- content --> — затем из него могут быть
            // удалены различные части:
            if ($scope == 'document') {
                $doc->set('intact_content', $this->parts['content']->get_text());
            }
        }

        foreach ($fields as $field) { // записать полученные результаты в поля документа
            $field_name = $field->get('name');
            $doc->add_field(clone $field);
            $doc->set_field_value($field_name, $this->parts[$field_name]->get_text());
        }

        return $doc;
    }

    /**
     *
     * @param string $content
     * @return array
     */
    public function extract_links() {
        $res = array();
        foreach ($this->xpath("//a[not(@rel) or @rel != 'nofollow']/@href") as $attr_node) {
            $res[] = $attr_node->nodeValue;
        }
        return $res;
    }

    // ---------------------------------------------------------------------------

    /**
     * Выполнить XPath-запрос над документом.
     * @param string $xpath_query
     * @return DOMNodeList
     */
    protected function xpath($query) {
        return $this->parts['document']->evaluate_xpath($query);
    }

    /**
     *
     * @param nc_search_field $field
     */
    protected function extract_field_value(nc_search_field $field) {
        $source_name = $field->get('query_scope');
        $recipient_name = $field->get('name');

        if (!isset($this->parts[$source_name])) {
            throw new nc_search_exception("Cannot extract field value: '$source_name' part does not exist");
        }
        $source = $this->parts[$source_name];

        if (!isset($this->parts[$recipient_name])) {
            $this->parts[$recipient_name] = new nc_search_document_parser_html_fragment;
        }
        $recipient = $this->parts[$recipient_name];

        $this->execute_queries($field->get('query'),
                $source,
                $recipient,
                $field->get('remove_from_parent'),
                $field->get('query_use_first_matched'));
        // filter
        if ($field->get('filter_content')) {
            $this->execute_queries($field->get('filter_content'), $recipient, null, true, false);
        }
    }

    /**
     *
     * @param string $query_string
     * @param nc_search_document_parser_html_fragment $source
     * @param nc_search_document_parser_html_fragment|null $recipient
     * @param boolean $remove
     * @param boolean $only_first
     * @return boolean success
     */
    protected function execute_queries($query_string, nc_search_document_parser_html_fragment $source, $recipient, $remove, $only_first) {
        //$queries = preg_split("/(?:,\s*|\s+)/s", $query_string);
        preg_match_all("/((?:#.+?#\w*)|[^\s,]+)/", $query_string, $queries);
        $matched = false;
        foreach ($queries[1] as $query) {
            if ($query[0] == "#") {
                $matched = $source->extract_regexp($query, $recipient, $remove);
            } elseif (strpos($query, "/") !== false) {
                $matched = $source->extract_xpath($query, $recipient, $remove);
            } else {
                $matched = $source->extract_tag($query, $recipient, $remove);
            }

            if ($matched && $only_first) {
                break;
            }
        }
        return $matched;
    }

    /**
     * (Separate method to make testing easier)
     * @return nc_search_data_persistent_collection of nc_search_field
     */
    protected function get_field_settings() {
        return nc_search::load_all('nc_search_field');
    }

}

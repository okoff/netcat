<?php

/* $Id: crawler.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Ползуватель
 */
class nc_search_indexer_crawler {

    /**
     * @var HTTP_Client
     */
    protected $http_client;

    public function __construct() {
        require_once('HTTP/Client.php'); // /netcat/require/lib
        require_once('HTTP/Request/Listener.php');

        $headers = array(
                'User-Agent' => nc_search::get_setting('CrawlerUserAgent'),
        );

        $this->http_client = new HTTP_Client(null, $headers);
        $this->http_client->enableHistory(false);
        $this->http_client->setMaxRedirects(nc_search::get_setting('CrawlerMaxRedirects'));

        $max_doc_size = nc_search::get_setting('CrawlerMaxDocumentSize');
        $accepted_mime_types = nc_search::load_all('nc_search_extension_rule')
                        ->where('extension_interface', 'nc_search_document_parser')
                        ->each('get', 'content_type');

        $listener = new nc_search_indexer_crawler_listener($max_doc_size, array_unique($accepted_mime_types));
        $this->http_client->attach($listener, true);
    }

    /**
     *
     * @param string $url
     * @return nc_search_indexer_crawler_response
     */
    public function get($url) {
        return $this->request('get', $url);
    }

    public function head($url) {
        return $this->request('head', $url);
    }

    protected function request($method, $url) {
        try {
            $this->http_client->$method($url);
            $response = $this->http_client->currentResponse();
        } catch (nc_search_indexer_crawler_exception $e) { // слушателю не понравились заголовки
            $response = $e->get_response();
        }
        $response["url"] = $url;

        $response = new nc_search_indexer_crawler_response($response);

//    if (nc_search::will_log(nc_search::LOG_CRAWLER_REQUEST)) {
        $len = $response->get_content_length();
        nc_search::log(nc_search::LOG_CRAWLER_REQUEST,
                        strtoupper($method)." $url\n".
                        "Response: {$response->get_code()}\n".
                        ($response->get_code() < 400 ?
                                "Content-Type: ".$response->get_content_type()."\n".
                                "Content-Length: ".(is_null($len) ? "no" : $len).", received: ".$response->get_body_length()." bytes" : ''));
//    }

        return $response;
    }

}

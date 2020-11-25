<?php

/* $Id: nc_url.class.php 5960 2012-01-17 17:25:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Url extends nc_System {

    private $_parsed_url;


    /*
     * Class constructor method
     */

    public function __construct() {
        // load parent constructor
        parent::__construct();
    }

    /*
     * Build url method
     *
     * @param array query parameters
     *
     * @return mixed result
     */

    public function build_url($query_arr) {
        // build query string
        if (!empty($query_arr)) {
            return http_build_query($query_arr, "", "&");
        }
        // no result
        return false;
    }

    /*
     * Parse REQUEST_URI method
     * and set internal variable $_parsed_url
     *
     * @return string parsed url
     */

    public function parse_url() {
        global $NC_UNICODE;
        // system superior object
        $nc_core = nc_Core::get_object();
        // надо сохранить get-параметры из окружения
        $uri = urldecode($nc_core->REQUEST_URI);
        $get_env = '';
        if (($start = strpos(getenv("REQUEST_URI"), '?')) !== false)
                $get_env = '&'.nc_substr(urldecode(getenv("REQUEST_URI")), $start + 1);

        //if ( !$NC_UNICODE) $uri = $nc_core->utf8->utf2win($uri);
        $url = "http".( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? "s" : "")."://".getenv("HTTP_HOST").$uri;

        // parse entire url
        $parsed_url = @parse_url($url);
        //$parsestr = (isset($parsed_url['query']) ? $parsed_url['query'] : "").$get_env;
        $parsestr = (isset($parsed_url['query']) ? $parsed_url['query'] : $get_env);

        // validate query parameter
        if ($parsestr) {
            parse_str($parsestr, $parsed_query_arr);
            // validate
            $parsed_query_arr = $nc_core->input->clear_system_vars($parsed_query_arr);
            // in error_document $_GET is empty, so set them at this line
            $_GET = $parsed_query_arr ? $parsed_query_arr : array();
            // build new query
            $parsed_url['query'] = $this->build_url($parsed_query_arr);
        }

        // for other functions
        $this->_parsed_url = $parsed_url;

        // return array
        return $this->_parsed_url;
    }

    /*
     * Get internal variable $_parsed_url method
     *
     * @return string parsed url
     */

    public function get_parsed_url($item = "") {

        if (empty($this->_parsed_url)) return false;

        if ($item) {
            return array_key_exists($item, $this->_parsed_url) ? $this->_parsed_url[$item] : "";
        } else {
            return $this->_parsed_url;
        }
    }

    /*
     * Set internal variable $_parsed_url method
     *
     * @return bool setted or not
     */

    public function set_parsed_url_item($item, $value) {

        if (empty($this->_parsed_url)) return false;

        $this->_parsed_url[$item] = $value;

        return true;
    }

    /*
     * Get date from $_parsed_url method
     *
     * @return string date if exist
     */

    public function get_uri_date($timestamp = false) {
        $date = '';
        // find date in url
        nc_preg_match("|
      / (\d{4}) /
      (?:
        (\d{2}) /
      )?
      (?:
        (\d{2}) /
      )?
    |x", $this->get_parsed_url('path'), $regs);
        // date found
        if ($regs) {
            array_shift($regs);
            $date = join("-", $regs);
        }
        // convert to the timestamp
        if ($timestamp) $date = strtotime($date);
        // return result date
        return $date ? $date : false;
    }

    /*
     * Get source url from $_parsed_url method
     *
     * @return string source url
     */

    public function source_url() {
        // compile client source url
        return $this->_parsed_url['scheme']."://".
        ( isset($this->_parsed_url['user']) ? $this->_parsed_url['user'] : "").
        ( isset($this->_parsed_url['pass']) ? ":".$this->_parsed_url['pass'] : "").
        (( isset($this->_parsed_url['user']) || isset($this->_parsed_url['pass'])) ? "@" : "").
        $this->_parsed_url['host'].
        ( isset($this->_parsed_url['port']) ? ":".$this->_parsed_url['port'] : "").
        $this->_parsed_url['path'];
    }

}
?>
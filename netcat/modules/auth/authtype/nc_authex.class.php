<?php

/* $Id$ */

class nc_authEx {

    protected $name;
    protected $auth;

    public function __construct() {
        $this->auth = nc_auth::get_object();
    }

    static public function get_object($name) {
        $classname = 'nc_authEx_'.$name;
        return new $classname;
    }

    public function enabled() {
        static $ex_enabled = array();
        $nc_core = nc_Core::get_object();
        if (!$ex_enabled) {
            $ex_enabled = unserialize($nc_core->get_settings('ex_enabled', 'auth'));
        }
        return $nc_core->php_ext('json') && $nc_core->php_ext('curl') && $ex_enabled[$this->name];
    }

    public function get_app_id() {
        $nc_core = nc_Core::get_object();
        static $ex_apps = array();
        if (!$ex_apps) {
            $ex_apps = unserialize($nc_core->get_settings('ex_apps', 'auth'));
        }
        return $ex_apps[$this->name]['app_id'];
    }

    public function get_app_key() {
        $nc_core = nc_Core::get_object();
        static $ex_apps = array();
        if (!$ex_apps) {
            $ex_apps = unserialize($nc_core->get_settings('ex_apps', 'auth'));
        }
        return $ex_apps[$this->name]['app_key'];
    }

    public function make_user($userinfo, $ex_user_id) {
        $nc_core = nc_Core::get_object();

        if (is_object($userinfo)) {
            $res = array();
            foreach ($userinfo as $k => $v)
                $res[$k] = $v;
            $userinfo = $res;
        }

        // соответствие полей
        $mapping = unserialize($nc_core->get_settings('ex_fields', 'auth'));
        $mapping = $mapping[$this->name];
        if (!empty($mapping))
                foreach ($mapping as $nc_field => $field) {
                $fl[$nc_field] = $userinfo[$field];
            }

        // группы
        $groups = unserialize($nc_core->get_settings('ex_group', 'auth'));
        $groups = $groups[$this->name];
        if (!$groups) $groups = $nc_core->get_settings('group', 'auth');


        $add_fields['UserType'] = $this->name;
        $password = md5(rand(6, 100).time());

        if (!$nc_core->NC_UNICODE) $fl = $nc_core->utf8->array_utf2win($fl);
        $user_id = $nc_core->user->add($fl, $groups, $password, $add_fields);
        $nc_core->db->query("INSERT INTO `Auth_ExternalAuth` (User_ID, Service,ExternalUser ) VALUES ('".$user_id."', '".$this->name."', '".$nc_core->db->escape($ex_user_id)."' ) ");

        $this->eval_addaction($user_id, $userinfo, $ex_user_id);

        return $user_id;
    }

    protected function eval_addaction($user_id, $userinfo, $ex_user_id) {
        global $nc_core, $db;
        $ex_addaction = unserialize($nc_core->get_settings('ex_addaction', 'auth'));
        $act = $ex_addaction[$this->name];
        if ($act) eval($act.';');

        return 0;
    }

}

class nc_authEx_fb extends nc_authEx {

    public function __construct() {
        parent::__construct();
        $this->name = 'fb';
    }

    public function get_token() {
        $app_id = $this->get_app_id();
        $application_secret = $this->get_app_key();
        $args = array();
        parse_str(trim($_COOKIE['fbs_'.$app_id], '\\"'), $args);
        ksort($args);
        $payload = '';
        foreach ($args as $key => $value) {
            if ($key != 'sig') {
                $payload .= $key.'='.$value;
            }
        }
        if (md5($payload.$application_secret) != $args['sig']) {
            return null;
        }
        return $args['access_token'];
    }

}

class nc_authEx_twitter extends nc_authEx {

    public function __construct() {
        parent::__construct();
        $this->name = 'twitter';
    }

    public function do_includes() {
        $nc_core = nc_Core::get_object();
        require_once($nc_core->INCLUDE_FOLDER.'lib/Auth/twitteroauth/OAuth.php');
        require_once($nc_core->INCLUDE_FOLDER.'lib/Auth/twitteroauth/twitteroauth.php');
    }

    public function get_connection() {
        $connection = new TwitterOAuth($this->get_app_id(),
                        $this->get_app_key(),
                        $_SESSION['oauth_token'],
                        $_SESSION['oauth_token_secret']);

        return $connection;
    }

}

class nc_authEx_vk extends nc_authEx {

    public function __construct() {
        parent::__construct();
        $this->name = 'vk';
    }

    public function is_member() {
        $app_id = $this->get_app_id();
        $session = array();
        $member = false;
        $valid_keys = array('expire', 'mid', 'secret', 'sid', 'sig');
        $app_cookie = $_COOKIE['vk_app_'.$app_id];

        if ($app_cookie) {
            $session_data = explode('&', $app_cookie, 10);
            foreach ($session_data as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                if (empty($key) || empty($value) || !in_array($key, $valid_keys))
                        continue;
                $session[$key] = $value;
            }

            foreach ($valid_keys as $key) {
                if (!isset($session[$key])) return $member;
            }

            ksort($session);

            $sign = '';
            foreach ($session as $key => $value) {
                if ($key != 'sig') {
                    $sign .= ( $key.'='.$value);
                }
            }
            $sign .= $this->get_app_key();
            $sign = md5($sign);
            if ($session['sig'] == $sign && $session['expire'] > time()) {
                $member = intval($session['mid']);
            }
        }
        return $member;
    }

    public function get_info() {
        $params['api_id'] = $this->get_app_id();
        $params['v'] = '3.0';
        $params['method'] = 'getProfiles';
        $params['timestamp'] = time();
        $params['format'] = 'json';
        $params['random'] = rand(0, 10000);
        $params['uids'] = $this->is_member();
        $params['fields'] = 'nickname,photo_big,email';
        ksort($params);
        $sig = ''; //$u;
        foreach ($params as $k => $v) {
            $sig .= $k.'='.$v;
        }
        $sig .= $this->get_app_key();
        $params['sig'] = md5($sig);

        $pice = array();
        foreach ($params as $k => $v) {
            $pice[] = $k.'='.urlencode($v);
        }
        $str = implode('&', $pice);

        $query = 'https://api.vk.com/method/getProfiles?'.$str;

        $res = file_get_contents($query);
        $res = json_decode($res, true);

        return $res['response'][0];
    }

}

class nc_authEx_openid extends nc_authEx {

    public function __construct() {
        parent::__construct();
        $this->name = 'openid';
    }

    public function do_includes() {
        $nc_core = nc_Core::get_object();

        // set include path
        $old_path = ini_get('include_path');
        ini_set('include_path', $nc_core->INCLUDE_FOLDER."lib");
        //set_include_path($nc_core->INCLUDE_FOLDER."lib");
        // Require the OpenID consumer code.
        require_once "Auth/OpenID/Consumer.php";
        // Require the "file store" module, which we'll need to store OpenID information.
        require_once "Auth/OpenID/FileStore.php";
        // Require the Simple Registration extension API.
        require_once "Auth/OpenID/SReg.php";
        // Require the PAPE extension module.
        require_once "Auth/OpenID/PAPE.php";
        ini_set('include_path', $old_path);
    }

    protected function get_store() {
        $nc_core = nc_Core::get_object();

        $store_path = $nc_core->TMP_FOLDER."_openid";

        if (!file_exists($store_path) && !mkdir($store_path)) {
            print "Could not create the FileStore directory '".$store_path."'. Please check the effective permissions.";
            exit(0);
        }

        return new Auth_OpenID_FileStore($store_path);
    }

    public function get_consumer() {
        $store = $this->get_store();
        $consumer = new Auth_OpenID_Consumer($store);

        return $consumer;
    }

    public function get_return_to() {

        $result = sprintf("%s://%s:%s%s/",
                        nc_get_scheme(), $_SERVER['SERVER_NAME'],
                        $_SERVER['SERVER_PORT'],
                        dirname($_SERVER['PHP_SELF'])
        );

        return $result;
    }

    public function get_trust_root() {

        $result = sprintf("%s://%s:%s%s/",
                        nc_get_scheme(), $_SERVER['SERVER_NAME'],
                        $_SERVER['SERVER_PORT'],
                        dirname($_SERVER['PHP_SELF'])
        );

        return $result;
    }

    public function normalize_openid_url($url) {
        $t_url = parse_url($url);
        $t_url['host'] = nc_preg_replace("/.*www\.(.*)/i", "\$1", $t_url['host']);
        $t_url['path'] = nc_preg_replace("/.*www\.(.*)/i", "\$1", $t_url['path']);
        $url = ($t_url['scheme'] ? $t_url['scheme']."://" : "").$t_url['host'].($t_url['path'] != "/" ? $t_url['path'] : NULL).($t_url['query'] ? '?'.$t_url['query'] : '');
        return $url;
    }

}
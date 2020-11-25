<?php

/*
 * Функции для работы с многобайтовыми строками
 */

function nc_preg_split() {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if ($nc_core->NC_UNICODE) $args[0] .= "u";

    return call_user_func_array('preg_split', $args);
}

function nc_preg_match($pattern, $subject, array &$matches = null, $flags = null, $offset = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) $pattern .= "u";
    return preg_match($pattern, $subject, $matches, $flags, $offset);
}

function nc_preg_match_all($pattern, $subject, array &$matches = null, $flags = null, $offset = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) $pattern .= "u";
    return preg_match_all($pattern, $subject, $matches, $flags, $offset);
}

function nc_preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) {
        if (is_array($pattern) && !empty($pattern))
                foreach ($pattern as $k => $v) {
                $pattern[$k] .= "u";
            } else {
            $pattern .= "u";
        }
    }
    return preg_replace($pattern, $replacement, $subject, $limit, $count);
}

function nc_preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) {
        if (is_array($pattern) && !empty($pattern))
                foreach ($pattern as $k => $v) {
                $pattern[$k] .= "u";
            } else {
            $pattern .= "u";
        }
    }

    return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
}

function nc_preg_grep() {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if ($nc_core->NC_UNICODE) $args[0] .= "u";

    return call_user_func_array('preg_grep', $args);
}

/**
 * Аналог strlen
 *
 * @return int длина строки
 */
function nc_strlen() {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if (!$nc_core->NC_UNICODE) return call_user_func_array('strlen', $args);

    if ($nc_core->utf8->mbstring_ext()) {
        return call_user_func_array('mb_strlen', $args);
    } else {
        return strlen(utf8_decode($args[0]));
    }
}

/**
 * Аналог substr
 *
 * @return string
 */
function nc_substr() {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if (!$nc_core->NC_UNICODE) return call_user_func_array('substr', $args);

    if ($nc_core->utf8->mbstring_ext()) {
        return call_user_func_array('mb_substr', $args);
    } else {
        preg_match_all("/./su", $args[0], $ar);

        if (func_num_args() >= 3) {
            return join("", array_slice($ar[0], $args[1], $args[2]));
        } else {
            return join("", array_slice($ar[0], $args[1]));
        }
    }
}

/**
 * Аналог strpos
 *
 * @return int or false
 */
function nc_strpos($haystack, $needle) {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if (!$nc_core->NC_UNICODE) return call_user_func_array('strpos', $args);

    if ($nc_core->utf8->mbstring_ext()) {
        return call_user_func_array('mb_strpos', $args);
    }

    $comp = 0;

    while (!isset($length) || $length < $offset) {
        $pos = strpos($haystack, $needle, $offset + $comp);
        if ($pos === false) return false;
        $length = nc_strlen(substr($haystack, 0, $pos));
        if ($length < $offset) $comp = $pos - $length;
    }

    return $length;
}

/**
 * Аналог strpos
 *
 * @return int or false
 */
function nc_strrpos($haystack, $needle) {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if (!$nc_core->NC_UNICODE) return call_user_func_array('strrpos', $args);

    if ($nc_core->utf8->mbstring_ext()) {
        return call_user_func_array($nc_core->utf8->func_overload() ? 'strrpos' : 'mb_strrpos', $args);
    }

    $pos = strrpos($haystack, $needle);

    if ($pos === false) return false;

    return nc_strlen(substr($haystack, 0, $pos));
}
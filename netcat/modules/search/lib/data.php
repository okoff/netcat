<?php

/* $Id: data.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Базовый класс для работы со структурированными данными
 */
abstract class nc_search_data implements ArrayAccess, Iterator {

    /**
     * Массив для хранения опций
     */
    protected $options = array();
    /**
     * Режим работы с опциями. Если $options_mode == 'strict', то при обращении
     * к несуществующему в массиве $options ключу будет выброшено исключение
     */
    protected $options_mode = 'strict';

    /**
     * Конструктор по умолчанию. Для всех наследуемых классов рекомендуется
     * использовать данный конструктор
     * @param array $values
     */
    public function __construct(array $values = null) {
        if ($values) {
            $this->set_values($values);
        }
    }

    /**
     *
     * @param string $options ключ в массиве $this->options
     * @param mixed $value новое значение
     * @param boolean $add_new_option добавить опцию, если она не была ранее определена
     * @return nc_search_options self
     * @throws nc_search_data_exception
     */
    public function set($option, $value, $add_new_option = false) {
        if (!$add_new_option) {
            $this->check_option($option);
        }
        $this->options[$option] = $value;
        return $this;
    }

    /**
     *
     * @param string $option ключ в массиве $this->options
     * @return mixed значение
     * @throws nc_search_data_exception
     */
    public function get($option) {
        $this->check_option($option);
        return isset($this->options[$option]) ? $this->options[$option] : NULL;
    }

    /**
     * Проверка наличия свойства
     * @param string $option
     * @return boolean
     */
    public function has_option($option) {
        return array_key_exists($option, $this->options);
    }

    /**
     * Бросает исключение, если нет свойства $option
     * @throws nc_search_data_exception
     * @param string $name
     */
    public function check_option($option) {
        if ($this->options_mode == 'strict' && !array_key_exists($option, $this->options)) {
            throw new nc_search_data_exception("Invalid options key '$option' (class ".get_class($this).")");
        }
    }

    /**
     * Установка параметров из массива
     * @param array $values
     * @param boolean $ignore_unknown skip options which are not present in the current class
     * @return self
     */
    public function set_values(array $values, $ignore_unknown = false) {
        foreach ($values as $k => $v) {
            if (!$ignore_unknown || $this->has_option($k)) {
                $this->set($k, $v);
            }
        }
        return $this;
    }

    /**
     * Добавление элемента к массиву $option
     * @param string $option   option key
     * @param mixed $value     новое значение в массиве
     * @param boolean $only_if_unique   для обеспечения уникальности значений.
     *                                  значение должно быть string/integer!
     */
    public function push_to($option, $value, $only_if_unique = false) {
        $this->check_option($option);
        if (!is_array($this->options[$option])) {
            $this->options[$option] = array($value);
        } elseif (!$only_if_unique || !in_array($value, $this->options[$option])) {
            $this->options[$option][] = $value;
        }
        return $this;
    }

    /**
     * Получить все значения $options в виде массива
     * @return array
     */
    public function to_array() {
        return $this->options;
    }

    // -------- ArrayAccess interface -----------
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    public function offsetExists($offset) {
        return $this->has_option($offset);
    }

    public function offsetUnset($offset) {
        unset($this->options[$offset]);
    }

    // --------- Iterator interface -----------
    public function rewind() {
        reset($this->options);
    }

    public function current() {
        return $this->offsetGet(key($this->options));
    }

    public function key() {
        return key($this->options);
    }

    public function next() {
        return next($this->options);
    }

    public function valid() {
        return (key($this->options) !== null);
    }

}
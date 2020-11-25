<?php

/* $Id: collection.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * 
 */
abstract class nc_search_data_collection implements ArrayAccess, Countable, Iterator {

    protected $data_class = 'nc_search_data';
    protected $items = array();
    /**
     * Total item count for partial collections
     * @var integer|null
     */
    protected $total_count;
    /**
     * присвоить ключам элементов коллекции значение опции $index_by
     * @var string
     */
    protected $index_by;

    /**
     *
     * @param array $items initial values
     */
    public function __construct(array $items = null, $total_count = null, $data_class = null) {
        if ($data_class) {
            $this->set_data_class($data_class);
        }
        if ($items) {
            $this->add_items($items);
        }
        $this->total_count = $total_count;
    }

    /**
     *
     * @param string $data_class
     * @return nc_search_data_collection
     */
    public function set_data_class($data_class) {
        $this->data_class = $data_class;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_data_class() {
        if (!class_exists($this->data_class)) {
            throw new nc_search_data_exception(get_class($this).": wrong data class '$this->data_class'");
        }
        return $this->data_class;
    }

    /**
     * Установить опцию, значение которой является ключом в массиве $this->items
     * (значения, ясное дело, должны быть уникальными)
     * @param string $option_name
     * @return nc_search_data_collection
     */
    public function set_index_option($option_name) {
        if ($this->index_by != $option_name && sizeof($this->items)) { // need to reindex
            if ($option_name) {
                $reindexed = array();
                foreach ($this->items as $item) {
                    $reindexed[$item->get($option_name)] = $item;
                }
            } else { // $option_name == NULL?
                $reindexed = array_values($this->items);
            }
            $this->items = $reindexed;
        }
        $this->index_by = $option_name;
        return $this;
    }

    /**
     *
     * @param object $item
     * @return nc_search_collection
     */
    public function add($item, $offset = null) {
        if (!is_a($item, $this->data_class)) {
            $msg = "This collection accepts only instances of '".
                    $this->data_class."', '".(is_object($item) ? get_class($item) : gettype($item)).
                    "' passed";
            throw new nc_search_data_exception($msg);
        }

        if ($this->index_by) {
            $offset = $item->get($this->index_by);
        }

        if (is_null($offset)) {
            $this->items[] = $item;
        } else {
            $this->items[$offset] = $item;
        }

        return $this;
    }

    /**
     *
     * @param array $items
     * @return nc_search_collection
     */
    public function add_items(array $items) {
        foreach ($items as $item) {
            $this->add($item);
        }
        return $this;
    }

    /**
     * Создает коллекцию с того же типа, что и текущая
     * @return self
     */
    protected function make_new_collection() {
        $class_name = get_class($this); // PHP 5.3 is SO MUCH cooler... :(
        $result = new $class_name;
        $result->set_data_class($this->get_data_class());
        return $result;
    }

    /**
     * Возвращает новую коллекцию с элементами, имеющими указанное значение
     *   $collection->where('site_id', 1)
     * @param string $option_name
     * @param mixed $option_value
     * @return nc_search_collection
     */
    public function where($option_name, $option_value) {
        $result = $this->make_new_collection();
        foreach ($this->items as $item) {
            if ($item->get($option_name) == $option_value) {
                $result->add($item);
            }
        }
        return $result;
    }

    /**
     * Возвращающает первый подходящий элемент коллекции
     * (или первый элемент, если параметры не заданы).
     * Если нет первого [подходящего] элемента, возвращает NULL.
     * @param string $option_name
     * @param mixed $option_value
     * @param nc_search_data|NULL
     */
    public function first($option_name = null, $option_value = null) {
        foreach ($this->items as $item) {
            if ($option_name == null) {
                return $item;
            }
            if ($item->get($option_name) == $option_value) {
                return $item;
            }
        }
        return null;
    }

    /**
     *
     * @param integer|string $key
     * @return integer
     */
    public function has_key($key) {
        return $this->offsetExists($key);
    }

    /**
     * Выполнить действие с каждым элементом коллекции, вернуть результат в виде массива,
     * в котором каждый элемент соответствует результату применения метода к элементу
     * @param string $method_name   method name
     * @param mixed $argument1      variable number of arguments
     * @example  $all_values = $collection->each('get', 'some_option');
     * @return array
     */
    public function each() {
        $args = func_get_args();
        $method = array_shift($args);
        $result = array();
        foreach ($this->items as $item) {
            $result[] = call_user_func_array(array($item, $method), $args);
        }
        return $result;
    }

    /**
     * @return integer
     */
    public function get_total_count() {
        return ($this->total_count === null ? $this->count() : $this->total_count);
    }

    //////////////////////////// ArrayAccess Interface ///////////////////////////
    function offsetGet($offset) {
        return (isset($this->items[$offset]) ? $this->items[$offset] : null);
    }

    function offsetSet($offset, $item) {
        $this->add($offset, $item);
    }

    function offsetExists($offset) {
        return isset($this->items[$offset]);
    }

    function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    ////////////////////////////// Iterator Interface ////////////////////////////
    public function rewind() {
        reset($this->items);
    }

    public function current() {
        return $this->offsetGet(key($this->items));
    }

    public function key() {
        return key($this->items);
    }

    public function next() {
        return next($this->items);
    }

    public function valid() {
        return (key($this->items) !== null);
    }

    ///////////////////////////// Countable Interface ////////////////////////////
    function count() {
        return count($this->items);
    }

}

<?php

if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для реализации "разделителя"
 */
class nc_a2f_field_divider extends nc_a2f_field {

    function render_value_field($html = true) {
        return "";
    }

}
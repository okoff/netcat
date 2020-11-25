<?php

/* $Id: ui_config.php 6208 2012-02-10 10:21:43Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_netshop extends ui_config_module {

    function ui_config_module_netshop($active_tab = 'admin', $toolbar_action = 'setup') {
        global $db;
        global $MODULE_FOLDER;

        $this->ui_config_module('netshop', $active_tab);

        if ($active_tab = 'admin') {

            $this->toolbar[] = array('id' => "import",
                    'caption' => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML,
                    'location' => "module.netshop.import",
                    'group' => "grp1"
            );
            $this->toolbar[] = array('id' => "setup",
                    'caption' => NETCAT_MODULE_NETSHOP_SETUP,
                    'location' => "module.netshop.setup",
                    'group' => "grp1"
            );

            $this->locationHash = "module.netshop.$toolbar_action";
            $this->activeToolbarButtons[] = $toolbar_action;
        }
    }

}
?>
<?php

if (!class_exists("nc_system")) {
    die;
}
$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);

$input = $this->get_input('settings');
if ($input) {
    foreach ($input as $k => $v) {
        nc_search::save_setting($k, $v);
    }
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVED, 'ok');
}

$settings = array(
        'ComponentID',
        'SearchProvider',
        'IndexerSecretKey',
        'IndexerSaveTaskEveryNthCycle',
        'IndexerRemoveIdleTasksAfter',
        'IndexerTimeThreshold',
        'IndexerMemoryThreshold',
        'IndexerNormalizeLinks',
        'IndexerConsoleSlowdownDelay',
        'IndexerInBrowserSlowdownDelay',
        'MinScheduleInterval',
        'CrawlerMaxRedirects',
        'NumberOfEntriesPerSitemap',
        'MaxTermsPerQuery',
        'MaxTermsPerField',
        'ZendSearchLucene_MaxBufferedDocs',
        'ZendSearchLucene_MaxMergeDocs',
        'ZendSearchLucene_MergeFactor',
);

$form_description = array();
foreach ($settings as $s) {
    $form_description[$s] = array('type' => 'string',
            'caption' => $s,
            'value' => nc_search::get_setting($s));
}


$form = new nc_a2f($form_description, "settings");
echo "<form class='settings system_settings' method='POST'>",
 "<input type='hidden' name='view' value='systemsettings' />",
 $form->render("<div>", "", "</div>", ""),
 "</form>";

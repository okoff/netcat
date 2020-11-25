<?php

/* $Id: settings.php 7752 2012-07-23 10:37:13Z lemonade $ */

/**
 * Общие методы для получения настроек модуля. Singleton.
 */
class nc_search_settings extends nc_search_data {

    // DEFAULTS
    protected $options = array(
            'EnableSearch' => true,
            'SearchProvider' => 'nc_search_provider_zend',
            'LogLevel' => nc_search::LOG_ALL_ERRORS,
            'DaysToKeepEventLog' => 365,
            'FilterStringCase' => MB_CASE_UPPER, // NB: в словарях phpMorphy используется верхний регистр

            'ExcludeUrlRegexps' => '',
            'RemoveStopwords' => true,
            // параметры Zend Search Lucene
            'ZendSearchLucene_IndexPath' => '%FILES%/Search/Lucene',
            // these are actually default Zend_Search_Lucene values:
            'ZendSearchLucene_ResultSetLimit' => 0,
            'ZendSearchLucene_MaxBufferedDocs' => 10,
            'ZendSearchLucene_MaxMergeDocs' => PHP_INT_MAX,
            'ZendSearchLucene_MergeFactor' => 10,
            // максимальное число терминов (слов и чисел) в одном поле
            // (использовать когда не хватает памяти или времени для обработки больших документов)
            'MaxTermsPerField' => 0, // 0 == unlimited
            // параметры запросов
            'MaxTermsPerQuery' => 2048,
            'IgnoreNumbers' => false,
            'MinWordLength' => 0, // 0 == don't check

            'DefaultBooleanOperator' => 'AND',
            'AllowTermBoost' => true,
            'AllowProximitySearch' => true,
            'AllowWildcardSearch' => false,
            'AllowRangeSearch' => false,
            'AllowFuzzySearch' => true,
            'AllowFieldSearch' => true,
            // настройки http-бота
            'CrawlerUserAgent' => 'Netcat Bot',
            'CrawlerDelay' => 0,
            'CrawlerObeyRobotsTxt' => true,
            'CrawlerCheckLinks' => true,
            'CrawlerCheckOutsideLinks' => false,
            'CrawlerMaxDocumentSize' => 5242880, // 5Mb
            'CrawlerMaxRedirects' => 0, // из-за редиректов может уйти на другой сайт!
            'ObeyMetaNoindex' => true,
            'NumberOfEntriesPerSitemap' => 1000, // количество ссылок в sitemap.xml
            // настройки индексатора
            'IndexerSecretKey' => '',
            'MinScheduleInterval' => 300, // (5 минут) не ставить в очередь, если в указанный промежуток времени уже запланирован запуск [той же области]

            'IndexerSaveTaskEveryNthCycle' => 20,
            'IndexerRemoveIdleTasksAfter' => 900, // считать задачу подвисшей, если от нее нет вестей в течение 15 минут
            // Управление перезапуском скрипта индексирования в браузере по времени
            // Значение <= 1: когда прошло X*100% времени от max_execution_time
            // Значение  > 1: когда от запуска скрипта прошло X секунд
            // 0: отключить
            'IndexerTimeThreshold' => 0.7,
            // Управление перезапуском скрипта индексирования в браузере по использованной памяти
            // Значение <= 1: когда израсходовано X*100% памяти от memory_limit
            // Значение  > 1: когда потребление памяти достигло X *байт*
            // 0: отключить
            'IndexerMemoryThreshold' => 0.8,
            'IndexerNormalizeLinks' => true,
            // Задержка в секундах после выполнения каждых 10000 операций (ticks,
            // см. http://ru.php.net/manual/en/control-structures.declare.php#control-structures.declare.ticks).
            // Для использования в случае, когда требуется снижение нагрузки на процессор
            // при индексировании из cron’а.
            // Может быть задано дробное значение (разделитель — точка), напр. "0.25" (250 мс)
            'IndexerConsoleSlowdownDelay' => 0,
            // то же при запуске из браузера:
            'IndexerInBrowserSlowdownDelay' => 0,
            // настройки форм поиска на сайте
            'ComponentID' => 60,
            'SearchFormTemplate' => '',
            'AdvancedSearchFormTemplate' => '',
            
            
            'web_SearchFormTemplate' => '',
            'web_AdvancedSearchFormTemplate' => '',
            'mobile_SearchFormTemplate' => '',
            'mobile_AdvancedSearchFormTemplate' => '',
            'responsive_SearchFormTemplate' => '',
            'responsive_AdvancedSearchFormTemplate' => '',
            
            
            'EnableAdvancedSearchForm' => true,
            'ShowAdvancedFormExcludeField' => true,
            'ShowAdvancedFormFieldSearch' => true,
            'ShowAdvancedFormTimeIntervals' => true,
            // параметры отображения результатов поиска
            'ResultTitleMaxNumberOfWords' => 25,
            'ResultContextMaxNumberOfWords' => 25,
            'AllowFieldSort' => true,
            'OpenLinksInNewWindow' => false,
            'ShowMatchedFragment' => true,
            'HighlightMatchedWords' => true,
            // пареметры автозаполнения для поля поиска
            'EnableQuerySuggest' => true,
            'SuggestionsMinInputLength' => 3, // в символах
            'NumberOfSuggestions' => 10, // количество "подсказок"
            'SuggestMode' => 'queries', // допустимые значения: titles, queries
            'SearchTitleBaseformsForSuggestions' => true, // искать в индексе (базовые формы)
            'SearchTitleAsPhraseForSuggestions' => true, // искать в индексе как фразу
            // исправление запросов, когда они не дали результата
            'TryToCorrectQueries' => true,
            'MaxQueryLengthForCorrection' => 5, // чтобы сложнее было положить сервер
            'RemovePhrasesOnEmptyResult' => true,
            'ChangeLayoutOnEmptyResult' => true,
            'BreakUpWordsOnEmptyResult' => true,
            'PerformFuzzySearchOnEmptyResult' => true,
            'FuzzySearchOnEmptyResultSimilarityFactor' => "0.8",
            // история запросов
            'SaveQueryHistory' => true,
            'AutoPurgeHistory' => false, // автоматическая очистка истории запросов
            'AutoPurgeHistoryIntervalValue' => '', // ''|0 == не очищать историю запросов
            'AutoPurgeHistoryIntervalUnit' => 'months', // hours, days, months
    );

}
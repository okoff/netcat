<?php

/* $Id: web.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 *
 */
class nc_search_indexer_runner_web implements nc_search_indexer_runner {

    protected $cycle_number = 0;
    protected $start_time;
    protected $delay;
    protected $memory_threshold;
    protected $memory_limit;
    protected $time_threshold;
    protected $time_limit;

    /**
     *
     */
    public function __construct() {
        $this->start_time = time();

        $this->time_threshold = nc_search::get_setting('IndexerTimeThreshold');
        $this->time_limit = ini_get('max_execution_time');

        $this->memory_threshold = nc_search::get_setting('IndexerMemoryThreshold');
        $this->memory_limit = nc_search_util::int_from_bytes_string(ini_get('memory_limit'));

        $this->delay = nc_search::get_setting('CrawlerDelay');

        @set_time_limit(0);
        ignore_user_abort(true);
        nc_Core::get_object()->db->query("SET wait_timeout=900"); // might loose connection when running in slow mode
    }

    /**
     *
     * @return boolean TRUE: остановка для перезапуска, FALSE: продолжение выполнения
     */
    protected function interrupt_if_needed(nc_search_indexer $indexer) {
        $memory_use = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;
        $time_use = time() + $this->delay - $this->start_time;

        $out_of_memory = $out_of_time = false;

        // проверяем память
        if ($this->memory_threshold > 0) {
            if ($this->memory_threshold <= 1) { // относительные значения
                $out_of_memory = (($memory_use / $this->memory_limit) >= $this->memory_threshold);
            } else { // абсолютные значения
                $out_of_memory = ($memory_use >= $this->memory_threshold);
            }
        }

        // проверяем время
        if ($this->time_threshold > 0) {
            if ($this->time_threshold <= 1) { // относительные значения
                $out_of_time = (($time_use / $this->time_limit) >= $this->time_threshold);
            } else { // абсолютные значения
                $out_of_time = ($time_use >= $this->time_threshold);
            }
        }

        // останавливаемся, когда достигли лимита
        if ($out_of_memory || $out_of_time) {
            $indexer->interrupt("mem: $memory_use bytes; time: $time_use s");
            return true;
        }

        return false;
    }

    /**
     *
     * @param nc_search_indexer $indexer
     * @return boolean is task finished
     */
    public function loop(nc_search_indexer $indexer) {
        $cycle_number = 0;
        $save_cycles = nc_search::get_setting('IndexerSaveTaskEveryNthCycle');

        while (true) {
            // stop prematurely:
            if (connection_status() !== CONNECTION_NORMAL) {
                $indexer->cancel();
                return true; // nobody listens anyway
            }

            if ($this->interrupt_if_needed($indexer)) {
                return false;
            }

            // сохранять задачу каждые X циклов
            if ($cycle_number % $save_cycles == 0) {
                $indexer->save_task();
            }

            switch ($indexer->next()) {
                case nc_search_indexer::TASK_FINISHED:
                    return true; // we're done
                case nc_search_indexer::TASK_STEP_FINISHED:
                    if ($this->delay) {
                        if ($this->interrupt_if_needed($indexer)) {
                            return false;
                        }
                        sleep($this->delay);
                    }
                    break;
                case nc_search_indexer::TASK_STEP_SKIPPED:
                    break;
                default:
                    throw new nc_search_exception("Incorrect return value from nc_search_indexer::next()");
            }

            $cycle_number++;
        }
    }

}

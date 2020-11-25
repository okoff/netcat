<?php

/* $Id: nc_db.class.php 8410 2012-11-13 14:05:57Z lemonade $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Db extends nc_Ezsqlcore {

    public function __construct() {
        $nc_core = nc_Core::get_object();
        parent::__construct();

        $this->quick_connect($nc_core->MYSQL_USER, $nc_core->MYSQL_PASSWORD, $nc_core->MYSQL_DB_NAME, $nc_core->MYSQL_HOST);

        // set default names
        if ((float) mysql_get_server_info($this->dbh) >= 4.1) {
            if (!$nc_core->MYSQL_CHARSET) $nc_core->MYSQL_CHARSET = 'cp1251';
            $this->query("SET NAMES '".$nc_core->MYSQL_CHARSET."'");
            $this->query("SET sql_mode=''");
            if ($nc_core->MYSQL_TIMEZONE)
                    $this->query("SET TIME_ZONE = '".$this->escape($nc_core->MYSQL_TIMEZONE)."'");
        }

        // what to do when with MySQL errors
        $this->show_errors = $nc_core->SHOW_MYSQL_ERRORS == "on";
    }

    public function quick_connect($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost') {
        $return_val = false;
        if (!$this->connect($dbuser, $dbpassword, $dbhost, true) || !$this->select($dbname)) {
			// probably system was not installed
			if ( $this->check_system_install() ) {
				// DB connection error
				throw new Exception("Unable to connect to the database. Check connection settings");
			}
        }

        return true;
    }

    /*     * ********************************************************************
     *  Try to connect to mySQL database server
     */

    public function connect($dbuser='', $dbpassword='', $dbhost='localhost') {
        return ( $this->dbh = @mysql_connect($dbhost, $dbuser, $dbpassword) );
    }

    /*     * ********************************************************************
     *  Try to select a mySQL database
     */

    public function select($dbname='') {
        return ( @mysql_select_db($dbname, $this->dbh) );
    }

    /*     * ********************************************************************
     *  Format a mySQL string correctly for safe mySQL insert
     *  (no mater if magic quotes are on or not)
     */

    public function escape($str) {
        return mysql_real_escape_string(stripslashes($str), $this->dbh);
    }

    public function prepare($str) {
        return mysql_real_escape_string($str, $this->dbh);
    }

    /*     * ********************************************************************
     *  Return mySQL specific system date syntax
     *  i.e. Oracle: SYSDATE Mysql: NOW()
     */

    public function sysdate() {
        return 'NOW()';
    }

    /*     * ********************************************************************
     *  Perform mySQL query and try to detirmin result value
     */

    public function query($query, $output=OBJECT) {
        global $MODULE_VARS;

        $sql_time = is_array($MODULE_VARS['default']) && array_key_exists('NC_DEBUG_SQL_TIME', $MODULE_VARS['default']) && $MODULE_VARS['default']['NC_DEBUG_SQL_TIME'];
        $sql_func = is_array($MODULE_VARS['default']) && array_key_exists('NC_DEBUG_SQL_FUNC', $MODULE_VARS['default']) && $MODULE_VARS['default']['NC_DEBUG_SQL_FUNC'];

        if ($sql_time && !class_exists('Benchmark_Timer'))
                require_once("Benchmark/Timer.php");
        if ($this->benchmark || $sql_time) {
            $timer = new Benchmark_Timer();
            $timer->start();
        }

        // For reg expressions
        $query = trim($query);

        // Initialise return
        $return_val = 0;
        $this->is_error = 0;
        $func = '';
        $this->errno = 0;

        // Flush cached values..
        $this->flush();

        // Log how the function was called
        $this->func_call = "\$db->query(\"$query\")";

        // Keep track of the last query for debug..
        $this->last_query = $query;
        // Perform the query via std mysql_query function..
        $this->result = @mysql_query($query, $this->dbh);

        $this->num_queries++;
        // таймер
        if ($this->benchmark || $sql_time) {
            $timer->stop();
            if ($this->benchmark) $timer->display();
            $sql_time = $timer->timeElapsed();
        }

        if ($sql_func) {
            $backtrace = debug_backtrace();
            $func = ($backtrace[2]['class'] ? $backtrace[2]['class'].'::' : '').$backtrace[2]['function'];
        }


        // If there is an error then take note of it..
        if (($str = @mysql_error($this->dbh))) {
            $this->register_error($str);
            $this->is_error = 1;
            $this->show_errors ? trigger_error($str, E_USER_WARNING) : null;
            $this->errno = mysql_errno();

            if (nc_Core::get_object()->beta) {
                echo "<div style='border: 2pt solid red; margin: 10px; padding:10px; font-size:13px; color:black;'><br/>\n";
                echo "Query: <b>".$query."</b><br/>\n";
                echo "Error: <b>".$str."</b><br/>\n";
                echo "</div>\n";
            }
        }

        $this->debugMessage($this->num_queries.". ".$query, $func, $sql_time, $this->is_error ? 'error' : 'ok');

        if ($this->is_error) return false;

        // Query was an insert, delete, update, replace
        if (preg_match("/^(insert|delete|update|replace)\s+/i", $query)) {
            $this->rows_affected = @mysql_affected_rows($this->dbh);

            // Take note of the insert_id
            // NB: не нужно заменять на nc_preg_match(), поскольку запрос не обязательно
            // является корректной UTF строкой - в этом случае условие не будет выполнено!
            if (preg_match("/^(insert|replace)\s+/i", $query)) {
                $this->insert_id = @mysql_insert_id($this->dbh);
            }

            // Return number fo rows affected
            $return_val = $this->rows_affected;
        }
        // Query was a select
        else {
            // Take note of column info
            $i = 0;
            while ($i < @mysql_num_fields($this->result)) {
                $this->col_info[$i] = @mysql_fetch_field($this->result);
                $i++;
            }

            // Store Query Results
            $num_rows = 0;

            // NETCAT_PATCH
            if ($output == ARRAY_N) {
                while ($row = mysql_fetch_row($this->result)) {
                    $this->last_result[$num_rows] = $row;
                    $num_rows++;
                }
            } else {
                while ($row = @mysql_fetch_object($this->result)) {
                    // Store relults as an objects within main array
                    $this->last_result[$num_rows] = $row;
                    $num_rows++;
                }
            }

            @mysql_free_result($this->result);

            // Log number of rows the query returned
            $this->num_rows = $num_rows;

            // Return number of rows selected
            $return_val = $this->num_rows;
        }

        // If debug ALL queries
        $this->trace || $this->debug_all ? $this->debug() : null;



        if ($this->debug_all) {
            nc_preg_match("/(from\s+\w+)/si", $query, $regs);
            $from = nc_preg_replace("/\s+/s", " ", $regs[1]);
            $from = nc_preg_replace("/from /i", "FROM ", $from);
            $this->groupped_queries[$from][$this->num_queries] = $query;
        }

        if ($this->benchmark && $GLOBALS["nccttimer"]) {
            $GLOBALS["nccttimer"]->setMarker("QRY $this->num_queries<br />");
        }




        return $return_val;
    }

}

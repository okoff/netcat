<?php
if (!class_exists("nc_System")) die("Unable to load file.");
require_once("Benchmark/Timer.php");

/**
 * Показывает форму для SQL-запроса
 *
 * @global $nc_core, $UI_CONFIG
 *
 */
function ShowSQLForm() {
    global $nc_core, $UI_CONFIG;

    $db = $nc_core->db;
    $Query = $nc_core->input->fetch_post('Query');
?>

    <form action='index.php' method='post'>
          <?=nc_admin_textarea_resize('Query', stripslashes($Query), '', 10, 60) ?>
        <br /><br />
        <input type='hidden' name='phase' value='2'>
    <?php print $nc_core->token->get_input(); ?>
    </form>

<!-- Binding event_handlers to textarea resize link -->
<script>
    (function (){
        var links = document.getElementsByTagName("A");
        var onclicker_grow   = function (event) { ShrinkArea(event, 50);  }
        var onclicker_shrink = function (event) { ShrinkArea(event,-50);  }
        for (var i = 0 ; i < links.length ; i++){
            if (links[i].className.search('textarea_shrink') != -1 ) {
                bindEvent(links[i], 'click', onclicker_grow );
            }
            if (links[i].className.search('textarea_grow') != -1 ) {
                bindEvent(links[i], 'click', onclicker_shrink);
            }
        }
    }) ();
</script>

    <div><?=TOOLS_SQL_HISTORY ?>:</div>
    <ul id='sqlHistory'>
    <?php
    $i = 0;
    $history = $db->get_col("SELECT `SQL_text` FROM `SQLQueries` ORDER BY `SQL_ID` DESC");

    if (!empty($history)) {
        foreach ($history as $query) {
            ++$i; ?>
            <li>
				<a href='#'><?=htmlspecialchars($query, ENT_QUOTES)?></a><br/>
			</li>
            <?php
        }
    }
    ?>
	</ul>
<br>
	<script type='text/javascript'>
        $nc('#sqlHistory li a').click(function () {
            $nc('#Query').text( $nc(this).text() );
            $nc('textarea.has_codemirror').each(function() {
				$nc(this).codemirror('setValue');
			});
        });
	</script>

    <div><?=TOOLS_SQL_HELP; ?>:</div>
    <li><b>SHOW TABLES</b> - <?=TOOLS_SQL_HELP_SHOW
    ?>
    <li><b>EXPLAIN `User`</b> - <? printf(TOOLS_SQL_HELP_EXPLAIN, "User"); ?>
    <li><b>SELECT COUNT(*) FROM `Subdivision`</b> -  <? printf(TOOLS_SQL_HELP_SELECT, "Subdivision"); ?>
        <br><br>

      <?=TOOLS_SQL_HELP_DOCS ?>

    <?
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => TOOLS_SQL_SEND,
            "action" => "mainView.submitIframeForm()"
    );
    return 0;
}

/**
 * Выполнение sql-запроса
 *
 * @param string запрос
 *
 * @global $nc_core
 *
 * @return bool выполнился запрос или нет
 */
function ExecuteSQLQuery($Query) {
    global $nc_core;

    $SHOW_MYSQL_ERRORS = $nc_core->SHOW_MYSQL_ERRORS;
    $db = $nc_core->db;
    // таймер
    $nccttimer = new Benchmark_Timer();

    $Query = trim(stripslashes($Query));

    $db->query("DELETE FROM `SQLQueries` WHERE MD5(`SQL_text`) = '".md5($Query)."' ");

    // если в истории запросов больше 15, то нужно удалить
    if ($db->get_var("SELECT COUNT(`SQL_ID`) FROM `SQLQueries`") >= 15) {
        $db->query("DELETE FROM `SQLQueries` ORDER BY `SQL_ID` LIMIT 1");
    }
    $db->query("INSERT INTO SQLQueries (SQL_ID, SQL_text) VALUES ('', '".$db->escape($Query)."')");

    // скроем ошибки в случае неправильного запроса, чтобы вывести свое сообщение об ошибке
    $db->hide_errors();
    // выполение запроса
    $nccttimer->start();
    $res = $db->get_results(stripslashes($Query), ARRAY_A);
    $nccttimer->stop();

    // если показ ошибок MySQL включен, то включим его обратно
    if ($SHOW_MYSQL_ERRORS == 'on') $db->show_errors();

    if ($db->captured_errors) {
        echo "<br /><b>Query:</b> ".$db->captured_errors[0][query]."<br><br><b>Error:</b> ".$db->captured_errors[0][error_str]."<br /><br />";
        return false;
    }


    $count = $db->num_rows;

    // вывод таблицы с результатом, если нет ошибок
    if ($res && $count) {
        echo "<br /><b>".htmlspecialchars(stripslashes($Query))."</b><br /><br />";
        $data = $res;

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td>
              <table class='admin_table sql_table' width='100%'><tr>";

        //вывод полей
        while (list($key, $val) = each($res[0])) {
            echo "<td><font>".$key."</td>";
        }
        echo "</tr>";

        reset($res[0]);

        for ($i = 0; $i < $count; $i++) {
            echo "<tr>";
            while (list($key, $val) = each($res[$i])) {
                echo "<td><font> ".htmlspecialchars($res[$i][$key])."</td>";
            }
            echo "</tr>";
        }

        echo "</table></td></tr></table><br>";

        $res_num = $count ? $count : $db->rows_affected;
    } elseif (!$res) {
        if (preg_match("/^(insert|delete|update|replace)\s+/i", $db->last_query)) {
            $res_num = $db->rows_affected;
        } else {
            $res_num = $db->num_rows;
        }
    }

    echo "<div>" . TOOLS_SQL_OK . "</div>";
    echo "<div>" . TOOLS_SQL_TOTROWS.": ".$res_num."</div>";
    echo "<div>" . TOOLS_SQL_BENCHMARK.": ".$nccttimer->timeElapsed() . "</div>";
    echo "<br />";

    
}

function nc_parse_queries_string_to_array($queries_string) {
    $queries_string .= '';
    $queries_array = array();
    $count_queries = 0;
    $count_quotes = 0;
    $count_double_quotes = 0;
    $i = -1;

    while(isset($queries_string[++$i])) {
        $replace = false;
        if ($queries_string[$i] == '"') {
            if (!($count_quotes & 1)) {
                ++$count_double_quotes;
            }
        } else if ($queries_string[$i] == "'") {
            if (!($count_double_quotes & 1)) {
                ++$count_quotes;
            }
        } else if (!($count_quotes & 1) && !($count_double_quotes & 1) && $queries_string[$i] == ';') {
            $replace = true;
        }
        $queries_array[$replace ? $count_queries++ : $count_queries] .= $queries_string[$i];
    }
    return $queries_array;
}
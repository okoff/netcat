<?php
/* $Id: dump.inc.php 8384 2012-11-09 10:11:12Z vadim $ */

function getFile($filename, $filename_name) {
    global $TMP_FOLDER, $DOCUMENT_ROOT, $SUB_FOLDER, $HTTP_ROOT_PATH, $HTTP_FILES_PATH;

    $fp = fopen($filename, "rb");
    $content = fread($fp, filesize($filename));
    fclose($fp);
    $fp2 = fopen($TMP_FOLDER . $filename_name, "wb");
    fwrite($fp2, $content);
    fclose($fp2);

    return $filename_name;
}

function showUploadForm() {
    $maxfilesize = min(ini_get('upload_max_filesize'), ini_get('upload_post_max_size') - 100);
    global $maxfilesize, $HTTP_ROOT_PATH, $HTTP_FILES_PATH, $HTTP_IMAGES_PATH, $HTTP_TEMPLATE_PATH;
	?>
    <form enctype='multipart/form-data' action='dump.php' method='post'>
        <input type='hidden' name='MAX_FILE_SIZE' value='<?= $maxfilesize
    ?>'>
        <fieldset>
            <legend><?= TOOLS_DUMP_INC_TITLE ?></legend>
            <div style='margin:10px;'>
                <input size='40' name='filename' type='file'>
                <input type='submit' value='<?=TOOLS_DUMP_INC_DORESTORE?>' title='<?=TOOLS_DUMP_INC_DORESTORE?>'>
            </div>
            <input type='hidden' name='phase' value='7'>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <font color='gray'>
                        <?= nc_admin_checkbox_simple('what[]', 'database', TOOLS_DUMP_INC_DBDUMP, true, 'database') ?><br />
                        <?= nc_admin_checkbox_simple('what[]', 'netcat_template', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_TEMPLATE_PATH . '</b>', true, 'netcat_template') ?><br />
                        <?= nc_admin_checkbox_simple('what[]', 'netcat_files', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_FILES_PATH . '</b>', true, 'netcat_files') ?><br />
                        <?= nc_admin_checkbox_simple('what[]', 'images', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_IMAGES_PATH . '</b>', true, 'images') ?><br />
                        <?= nc_admin_checkbox_simple('what[]', 'modules', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_ROOT_PATH . '</b>', true, 'modules') ?>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
    <?php
}

function GetRandom($length) {
    srand((double) microtime() * 1000000);
    while (1) {
        $val = rand(65, 122);
        if (!($val > 90 && $val < 97)) {
            $len++;
            $Ret.= chr($val);
            if ($len >= $length)
                break;
        }
    }
    return $Ret;
}

# удаление файла дампа с диска
# $file - полное название файла

function DeleteDump($file) {
    global $db_path, $DUMP_FOLDER;

    $count_file = count($file);

    for ($i = 0; $i < $count_file; $i++) {
        $arr = explode("/", $file[$i]);
        $arr2 = explode("\\", $file[$i]);
        if (count($arr) == 1 && count($arr2) == 1) {
            $file_deleted = @unlink($DUMP_FOLDER . $file[$i]);
            if ($file_deleted)
                nc_print_status(str_replace("%FILE", $file[$i], TOOLS_DUMP_DELETED), "ok");
        }
        else {
            nc_print_status(str_replace("%FILE", $file[$i], TOOLS_DUMP_ERROR_CANTDELETE), "error");
        }
    }
}

# создание дампа БД
# $mysql_dump в ./dump.inc.php

function MakeBackUp() {
    global $db_path, $MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_CHARSET, $DOCUMENT_ROOT, $SUB_FOLDER, $TMP_FOLDER;

// try to exec mysqldump
    $err_code = 127;
    if (strpos($MYSQL_HOST, ":")) {
        list($host, $port) = explode(":", $MYSQL_HOST);
        $host = "--host=$host --port=$port";
    } else {
        $host = "--host=$MYSQL_HOST";
    }

    @exec("mysqldump $host -u $MYSQL_USER " . ($MYSQL_PASSWORD ? "-p$MYSQL_PASSWORD " : "") .
                    ((float) mysql_get_server_info() > 4 ? " --default-character-set=$MYSQL_CHARSET --compatible=mysql40 " : "") .
                    " --add-drop-table --disable-keys --quick" .
                    " --result-file=" . $TMP_FOLDER . "netcat.sql $MYSQL_DB_NAME 2>&1", $output, $err_code);

    if (!$err_code) {
        if (34 > file_put_contents($TMP_FOLDER . "netcat.sql", "SET NAMES '" . $MYSQL_CHARSET . "';\n\n" . file_get_contents($TMP_FOLDER . "netcat.sql"))) {
            $err_code = 1;
        }
    }
    if ($err_code) {
        $mysql_dump = new MYSQL_DUMP($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_CHARSET);
        $sql = $mysql_dump->dumpDB($MYSQL_DB_NAME);

        if ($sql == false)
            echo $mysql_dump->error();
        $mysql_dump->save_sql($sql, $TMP_FOLDER . "netcat.sql");
    }
}

function DumpQuery($file) {
    global $HTTP_HOST, $ROOT_FOLDER, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH, $DUMP_FOLDER, $HTTP_DUMP_PATH;
    global $UI_CONFIG;
    global $HTTP_ROOT_PATH, $HTTP_FILES_PATH, $HTTP_IMAGES_PATH, $HTTP_TEMPLATE_PATH;
    ?>
    <table  class='admin_table' width='100%'>
        <tr>
            <td>
                <?= TOOLS_DUMP_INC_ARCHIVE ?>:
            </td>
            <td>
                <b><?= $file ?></b> [<a href='<?= $SUB_FOLDER . $HTTP_DUMP_PATH . $file ?>'><?= TOOLS_DUMP_INC_DOWNLOAD ?></a>]
            </td>
        </tr>
        <tr>
            <td>
                <?= TOOLS_DUMP_INC_DATE ?>:
            </td>
            <td>
				<?= substr($file, strlen($file) - 20, 10) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= TOOLS_DUMP_INC_SIZE ?>:
            </td>
            <td>
				<?= nc_bytes2size( filesize($DUMP_FOLDER . $file) )?>
            </td>
        </tr>
    </table>
    <br />
    <form method='post' action='<?= $ADMIN_PATH ?>dump.php'>
        <font color='gray'>
        <?= nc_admin_checkbox_simple('what[]', 'database', TOOLS_DUMP_INC_DBDUMP, true, 'database')
        ?><br />
        <?= nc_admin_checkbox_simple('what[]', 'netcat_template', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_TEMPLATE_PATH . '</b>', true, 'netcat_template') ?><br />
        <?= nc_admin_checkbox_simple('what[]', 'netcat_files', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_FILES_PATH . '</b>', true, 'netcat_files') ?><br />
		<?= nc_admin_checkbox_simple('what[]', 'images', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_IMAGES_PATH . '</b>', true, 'images') ?><br />
		<?= nc_admin_checkbox_simple('what[]', 'modules', TOOLS_DUMP_INC_FOLDER . ' <b>' . $HTTP_ROOT_PATH . '</b>', true, 'modules') ?><br><br>
        <input type='hidden' name='file' value='<?= $file ?>'>
        <input type='hidden' name='phase' value='6'>
        <input type='submit' class='hidden'>
    </form>
    <?php
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => TOOLS_DUMP_INC_DORESTORE,
            "action" => "mainView.submitIframeForm()");
}

# не используется

function mkArch($Folder1, $FolderToArch, $DestFile) {
    global $isWin, $Wrar;

    echo "$Folder1, $FolderToArch, $DestFile<br>";
    # $newfile = new gzip_file($DestFile.".tgz");
    $newfile = new tar_file($DestFile . ".tar");
    $newfile->set_options(array('basedir' => $Folder1, 'overwrite' => 1));
    $newfile->add_files($FolderToArch);
    $newfile->create_archive();
    if (count($newfile->errors) > 0)
        print ("Errors occurred.");
}

# сборка архива проекта в файлы, затем в один файл tgz

function mkDump() {
    global $DOCUMENT_ROOT, $SUB_FOLDER, $DOMAIN_NAME, $HTTP_FILES_PATH, $HTTP_ROOT_PATH, $HTTP_DUMP_PATH, $TMP_FOLDER, $HTTP_IMAGES_PATH, $DUMP_FOLDER, $HTTP_TEMPLATE_PATH;
	
    if (!checkPermissions($HTTP_DUMP_PATH, $DOCUMENT_ROOT))
        return;
    if (!checkPermissions($HTTP_ROOT_PATH . "tmp/", $DOCUMENT_ROOT))
        return;

    DeleteFilesInDirectory($TMP_FOLDER);

    $dump_file = array();
    # pack
    $dump_file[] = nc_tgz_create($TMP_FOLDER . "netcat_template.tgz", ltrim($HTTP_TEMPLATE_PATH, "/"));
    $dump_file[] = nc_tgz_create($TMP_FOLDER . "netcat_files.tgz", ltrim($HTTP_FILES_PATH, "/"));
    $dump_file[] = nc_tgz_create($TMP_FOLDER . "images.tgz", trim($HTTP_IMAGES_PATH, "/"));
    $dump_file[] = nc_tgz_create($TMP_FOLDER . "modules.tgz", ltrim($HTTP_ROOT_PATH, "/") . "modules");
    MakeBackUp();
    $file = $DOMAIN_NAME . date("Y.m.d") . "_" . GetRandom(5);
    $dump_file[] = nc_tgz_create($DUMP_FOLDER . "$file.tgz", ltrim($HTTP_ROOT_PATH, "/") . "tmp");

    DeleteFilesInDirectory($TMP_FOLDER);

    if (sizeof($dump_file == 4)) {
        nc_print_status(str_replace("%FILE", "$file.tgz", TOOLS_DUMP_CREATED), "ok");
    } else {
        nc_print_status(TOOLS_DUMP_CREATION_FAILED, "error");
    }
}

# покажем список имеющихся архивов проекта

function ShowBackUps() {
    global $db_path, $ADMIN_PATH, $ADMIN_TEMPLATE, $DUMP_FOLDER, $UI_CONFIG;

    $dir_read = dir($DUMP_FOLDER);
    $dir_count = dir($DUMP_FOLDER);

    $total = 0;
    $read = 0;
    while (($entry = $dir_count->read()) !== false) {
        $total++;
    }
    $total -= 2;
    $dir_count->close();

    while (($entry = $dir_read->read()) !== false) {
        $entry_str = substr($entry, -4);
        if ($entry != "." && $entry != ".." && ($entry_str == ".tgz" || $entry_str == ".rar")) {
            if (($total - 1) > $read)
                $read++;
            $countDumps = 1;
            $filename = substr($entry, 0, strlen($entry) - 20);
            $filesize = filesize($DUMP_FOLDER . $entry);
            $filetime = filemtime($DUMP_FOLDER . $entry);
            $table = "";
            $table.= "<tr>";
            $table.= "<td><font size='-1'><b><a href='" . $ADMIN_PATH . "dump.php?phase=3&file=" . $entry . "'>" . $filename . "</a></b></td>\r\n";
            $table.= "<td><font size='-1'>" . date("Y-m-d H:i:s", $filetime) . "</td>";
            $table.= "<td><font size='-1'>" . nc_bytes2size($filesize) . "</td>";
            $table.= "<td align='center'>" . nc_admin_checkbox_simple('del[]', $entry) . "</td>";
            $table.= "</tr>";
            $table_arr[$filetime] = $table;
        }
    }
    $dir_read->close();

    if ($countDumps != 1) {
        nc_print_status(TOOLS_DUMP_NOONE, "info");
    } else {
        ?>
        <form id='backups_form' method='post'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td >
                        <table  class='admin_table' width='100%'>
                            <tr>
                                <th width='45%'><?= TOOLS_DUMP_PROJECT ?></th>
                                <th width='25%'><?= TOOLS_DUMP_DATE ?></th>
                                <th width='20%'><?= TOOLS_DUMP_SIZE ?></th>
                                <td class='align-center'><div class='icons icon_delete' title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div></td>
                            </tr>
        <?php
        if (is_array($table_arr) && !empty($table_arr)) {
            ksort($table_arr);
            echo join("", $table_arr);
        }
        ?>
                        </table>
                    </td>
                </tr>
            </table>
            <br>
            <?php
        }

        $UI_CONFIG->actionButtons[] = array("id" => "create",
                "caption" => TOOLS_DUMP_CREATEAP,
                "action" => "urlDispatcher.load('tools.backup(1)')");

        if ($countDumps) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SAVE,
                    "action" => "mainView.submitIframeForm('backups_form')",
                    "align" => "left");
            ?>		
            <input type='hidden' name='phase' value='2'>
            <input type='submit' class='hidden'>
        </form>
        <?php
    }
}

# распаковка дампа в БД
# $mysql_dump в ./dump.inc.php

function SQLFromFile($file) {
    global $db, $MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_CHARSET;

    // try to upload dump via exec("mysql")
    $err_code = 127;

    @exec("mysql --host=$MYSQL_HOST --user=$MYSQL_USER" . ($MYSQL_PASSWORD ? " --password=$MYSQL_PASSWORD " : "") . ((float) mysql_get_server_info() > 4 ? " --default-character-set=$MYSQL_CHARSET " : "") . "  $MYSQL_DB_NAME < $file 2>&1", $output, $err_code);

    // exec failed
    if ($err_code) {
        $mysql_dump = new MYSQL_DUMP($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_CHARSET);
        $sql = $mysql_dump->dumpDB($MYSQL_DB_NAME);

        if ($mysql_dump->restoreDB($file) == false) {
            echo $mysql_dump->error();
            return false;
        }
    }

    return true;
}

# распаковка дампа, закаченного через WEB

function decompressDumpTGZ2($file) {
    global $DOCUMENT_ROOT, $SUB_FOLDER, $TMP_FOLDER;

    $err = 0;
    if (!nc_tgz_extract($TMP_FOLDER . $file, $DOCUMENT_ROOT . $SUB_FOLDER))
        $err = "Error while dump file extracting";

    return $err;
}

# распаковка дампа из директории дампов

function decompressDumpTGZ1($file) {
    global $DOCUMENT_ROOT, $SUB_FOLDER, $DUMP_FOLDER;

    $err = 0;
    if (!nc_tgz_extract($DUMP_FOLDER . $file, $DOCUMENT_ROOT . $SUB_FOLDER))
        $err = "Error while dump file extracting";

    return $err;
}

# распаковка архива проекта по нужным местам

function ReadBackUP($backupfile, $images, $netcat_files, $sqldump, $modules, $dump, $netcat_template) {
    global $HTTP_TEMPLATE_PATH, $DOCUMENT_ROOT, $SUB_FOLDER, $HTTP_FILES_PATH, $HTTP_ROOT_PATH, $HTTP_IMAGES_PATH, $TMP_FOLDER;
	
    if (!checkPermissions($HTTP_ROOT_PATH . "tmp/", $DOCUMENT_ROOT))
        return $err = ".";

    $err = 0;

    if (!$dump) {
        if ($err = decompressDumpTGZ1($backupfile))
            return $err;
    }
    else {
        if ($err = decompressDumpTGZ2($backupfile))
            return $err;
    }

    //Unpack images
    if ($images) {
        if (!checkPermissions($HTTP_IMAGES_PATH, $DOCUMENT_ROOT))
            return $err = ".";
        if (!nc_tgz_extract($TMP_FOLDER . "images.tgz", $DOCUMENT_ROOT . $SUB_FOLDER))
            $err = "Error while images extracting";
    }

    //Unpack netcat_files
    if ($netcat_files) {
        if (!checkPermissions($HTTP_FILES_PATH, $DOCUMENT_ROOT))
            return $err = ".";
        if (!nc_tgz_extract($TMP_FOLDER . "netcat_files.tgz", $DOCUMENT_ROOT . $SUB_FOLDER))
            $err = "Error while netcat_files extracting";
    }

    if ($netcat_template) {
        if (!checkPermissions($HTTP_TEMPLATE_PATH, $DOCUMENT_ROOT))
            return $err = ".";
        if (!nc_tgz_extract($TMP_FOLDER . "netcat_template.tgz", $DOCUMENT_ROOT . $SUB_FOLDER))
            $err = "Error while netcat_template extracting";
    }
    
    //Restore MySQL dump
    if ($sqldump) {
        if (!SQLFromFile($TMP_FOLDER . "netcat.sql"))
            $err = "Error while MySQL dump extracting";
    }

    //Unpack modules
    if ($modules) {
        if (!checkPermissions($HTTP_ROOT_PATH . "modules/", $DOCUMENT_ROOT))
            return $err = ".";
        if (!nc_tgz_extract($TMP_FOLDER . "modules.tgz", $DOCUMENT_ROOT . $SUB_FOLDER))
            $err = "Error while modules extracting";
    }

    DeleteFilesInDirectory($TMP_FOLDER);

    return $err;
}

function checkBox($box, $value) {

    $box_count = count($box);

    for ($i = 0; $i < $box_count; $i++) {
        if ($box[$i] == $value)
            return 1;
    }

    return 0;
}

                                    function AskDump() {
                                        global $ADMIN_PATH;
                                            ?>
	<?=TOOLS_DUMP_CONFIRM ?>
                                            <form method='post' action='<?=$ADMIN_PATH ?>dump.php'>
                                                <input type='hidden' name='phase' value='1'>
                                                <input type='submit' value='<?=TOOLS_DUMP_CREATEAP ?>'  title='<?=TOOLS_DUMP_CREATEAP ?>'>
                                            </form>
                                            <?php
                                        }

##################
# Backup of mysql database

define("HAR_LOCK_TABLE", 1);
define("HAR_FULL_SYNTAX", 2);
define("HAR_DROP_TABLE", 4);
define("HAR_NO_STRUCT", 8);
define("HAR_NO_DATA", 16);
define("HAR_ALL_OPTIONS", HAR_LOCK_TABLE | HAR_FULL_SYNTAX | HAR_DROP_TABLE);

define("HAR_ALL_DB", 1);
define("HAR_ALL_TABLES", 1);

define('OS_Unix', 'u');
define('OS_Windows', 'w');
define('OS_Mac', 'm');

class MYSQL_DUMP {

    var $dbhost = "";
    var $dbuser = "";
    var $dbpwd = "";
    var $database = null;
    var $charset = null;
    var $tables = null;
    var $conn = null;
    var $result = null;
    var $error = "";
    var $OS_FullName = null;
    var $lineEnd = null;
    var $OS_local = "";

    /**
     * Class Object
     *
     * @param String: $host
     * @param String: $user
     * @param String: $dbpwd
     * @return MYSQL_DUMP
     */
    function MYSQL_DUMP($host = "", $user = "", $dbpwd = "", $charset = "") {
        $this->setDBHost($host, $user, $dbpwd, $charset);

        $this->OS_FullName = array(OS_Unix => 'UNIX', OS_Windows => 'WINDOWS', OS_Mac => 'MACOS');
        $this->lineEnd = array(OS_Unix => "\n", OS_Mac => "\r", OS_Windows => "\r\n");

        $this->OS_local = OS_Unix;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            $this->OS_local = OS_Windows;
        elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'MAC')
            $this->OS_local = OS_Mac;
    }

    /**
     * Set the database connection parameters
     *
     * @param String: $host
     * @param String: $user
     * @param String: $dbpwd
     */
    function setDBHost($host, $user, $dbpwd, $charset) {
        $this->dbhost = $host;
        $this->dbuser = $user;
        $this->dbpwd = $dbpwd;
        $this->charset = $charset;
    }

    /**
     * Return last error
     *
     * @return String
     */
    function error() {
        return $this->error;
    }

    /**
     * Take backup of the database
     *
     * @param Mixed $database (It can be string seperated by coma (,) or single database name on an array of database names
     * @param Mixed $tables (It can be string seperated by coma (,) or single table name on an array of table names
     * @param Int $options
     * @return String SQL Commands
     */
    function dumpDB($database = HAR_ALL_DB, $tables = HAR_ALL_TABLES, $options = HAR_ALL_OPTIONS) {
        $this->_connect();

        if (empty($database)) {
            $this->error = "Specify the database.";
            return false;
        }

        if (empty($tables)) {
            $this->error = "Specify the tables.";
            return false;
        }

        if ($database == HAR_ALL_DB) {
            $sql = "SHOW DATABASES";
            $this->result = @mysql_query($sql, $this->conn);
            if (mysql_error() !== "") {
                $this->error = "Error : " . mysql_error();
                return false;
            }

            while ($row = mysql_fetch_array($this->result, MYSQL_NUM)) {
                $this->database[] = $row[0];
            }
        } else if (is_string($database)) {
            $this->database = @explode(",", $database);
        }

        $lineEnd = $this->lineEnd[$this->OS_local];
        $returnSql = "# MySql Dump" . $lineEnd;
        $returnSql .= "# Host: " . $this->dbhost . $lineEnd;
        $returnSql .= "# Time: " . date("Y.m.d H:i:s") . $lineEnd;

        $sql = "SELECT VERSION()";
        $this->result = mysql_query($sql, $this->conn);
        $row = mysql_fetch_array($this->result, MYSQL_NUM);
        $returnSql .= "# Server version " . $row[0] . $lineEnd;
        $returnSql .= "# -------------------------------------------------" . $lineEnd . $lineEnd;

        $returnSql .= "SET NAMES '" . $this->charset . "'" . $lineEnd . $lineEnd;

        for ($i = 0; $i < count($this->database); $i++) {
            if (count($this->database) > 1)
                $returnSql.= "USE `" . $this->database[$i] . "`;" . $lineEnd . $lineEnd;

            $this->result = @mysql_query("USE `" . $this->database[$i] . "`", $this->conn);

            if (mysql_error() !== "") {
                $this->error = "Error : " . mysql_error();
                return false;
            }

            $this->tables = array();
            if ($tables == HAR_ALL_TABLES) {
                $sql = "SHOW Tables";
                $this->result = @mysql_query($sql, $this->conn);
                if (mysql_error() !== "") {
                    $this->error = "Error : " . mysql_error();
                    return false;
                }

                while ($row = mysql_fetch_array($this->result, MYSQL_NUM)) {
                    $this->tables[] = $row[0];
                }
            } else if (is_string($tables)) {
                $this->tables = @explode(",", $tables);
            }
            for ($j = 0; $j < count($this->tables); $j++) {
                if (($options & HAR_NO_STRUCT ) != HAR_NO_STRUCT) {
                    $sql = "SHOW CREATE TABLE `" . $this->tables[$j] . "`";
                    $this->result = @mysql_query($sql, $this->conn);
                    if (mysql_error() !== "") {
                        $this->error = "Error : " . mysql_error();
                        return false;
                    }
                    $row = mysql_fetch_array($this->result, MYSQL_NUM);


                    $returnSql .= " #" . $lineEnd;
                    $returnSql .= " # Table structure for table '" . $this->tables[$j] . "'" . $lineEnd;
                    $returnSql .= " #" . $lineEnd . $lineEnd;

                    if (($options & HAR_DROP_TABLE) == HAR_DROP_TABLE)
                        $returnSql .= "DROP TABLE IF EXISTS `" . $this->tables[$j] . "`;" . $lineEnd;
                    $returnSql .= $row[1] . ";" . $lineEnd . $lineEnd . $lineEnd;
                }

                if (($options & HAR_NO_DATA ) != HAR_NO_DATA) {
                    $returnSql .= " #" . $lineEnd;
                    $returnSql .= " # Dumping data for table '" . $this->tables[$j] . "'" . $lineEnd;
                    $returnSql .= " #" . $lineEnd . $lineEnd;

                    if (($options & HAR_LOCK_TABLE ) == HAR_LOCK_TABLE)
                        $returnSql .= "LOCK TABLES `" . $this->tables[$j] . "` WRITE;" . $lineEnd;

                    $temp_sql = "INSERT INTO `" . $this->tables[$j];
                    if (($options & HAR_FULL_SYNTAX == HAR_FULL_SYNTAX)) {
                        $sql = "SHOW COLUMNS FROM " . $this->tables[$j];
                        $this->result = @mysql_query($sql, $this->conn);
                        if (mysql_error() !== "") {
                            $this->error = "Error : " . mysql_error();
                            return false;
                        }
                        $fields = array();
                        $fields_null = array();
                        while ($row = mysql_fetch_array($this->result, MYSQL_NUM)) {
                            $fields[] = $row[0];
                            $fields_null[] = $row[2];
                        }
                        $temp_sql.='` (`' . @implode('`,`', $fields) . '`)';
                    }

                    $sql = "SELECT * FROM " . $this->tables[$j];
                    $this->result = @mysql_query($sql, $this->conn);
                    if (mysql_error() !== "") {
                        $this->error = "Error : " . mysql_error();
                        return false;
                    }
                    while ($row = mysql_fetch_array($this->result, MYSQL_NUM)) {
                        foreach ($row as $key => $value) {
                            $row[$key] = mysql_real_escape_string($value);
                        }

                        $returnSql .=$temp_sql . ' VALUES (';
                        foreach ($row as $key => $value) {
                            $returnSql .= $key != 0 ? ',' : '';
                            if ($fields_null[$key] == 'YES' && !$row[$key])
                                $returnSql .= 'NULL';
                            else
                                $returnSql .= '"' . $row[$key] . '"';
                        }
                        $returnSql .= ');' . $lineEnd;
#							if ($this->tables[$j]!='Sub_Class') $returnSql="";
#							if ($this->tables[$j]=='Sub_Class') echo $returnSql."<br><br>";
                        #$returnSql .=$temp_sql.' VALUES ("'.@implode('","',$row).'");'.$lineEnd;
                    }
                    if (($options & HAR_LOCK_TABLE ) == HAR_LOCK_TABLE)
                        $returnSql .= "UNLOCK TABLES;" . $lineEnd;
                }
                $returnSql .=$lineEnd . $lineEnd;
            }
        }
        return $returnSql;
    }

    /**
     * Save the sql file on server
     *
     * @param String $sql
     * @param String $sqlfile
     * @return Boolean
     */
    function save_sql($sql, $sqlfile = "") {
        if (empty($sqlfile)) {
            $sqlfile = @implode("_", $this->database) . ".sql";
        }
        $fp = @fopen($sqlfile, "wb");
        if (!is_resource($fp)) {
            $this->error = "Error: Unable to save file.";
            return false;
        }
        @fwrite($fp, $sql);
        @fclose($fp);
        return true;
    }

    /**
     * force to download the sql file
     *
     * @param String $sql
     * @param String $sqlfile
     * @return Boolean
     */
    function download_sql($sql, $sqlfile = "") {
        if (empty($sqlfile)) {
            $sqlfile = @implode("_", $this->database) . ".sql";
        }
        @header("Cache-Control: "); // leave blank to avoid IE errors
        @header("Pragma: "); // leave blank to avoid IE errors
        @header("Content-type: application/octet-stream");
        @header("Content-type: application/octet-stream");
        @header("Content-Disposition: attachment; filename=" . $sqlfile);
        echo $sql;
    }

    /**
     * Restore the backup file
     *
     * @param String $sqlfile
     * @return Boolean
     */
    function restoreDB($sqlfile) {
        $this->error = "";
        $this->_connect();

        if (!is_file($sqlfile)) {
            $this->error = "Error : Not a valid file.";
            return false;
        }

//			$lines=@file($sqlfile);

        $file_size = filesize($sqlfile);
        if (!$file_size) {
            $uploadMsg = "Sql File is empty.";
        } else {
            $fp = fopen($sqlfile, "r");
//				foreach($lines as $line)
            while (!feof($fp)) {
                $line = fgets($fp, $file_size);
                $sql.=trim($line);
                if (empty($sql)) {
                    $sql = "";
                    continue;
                } elseif (preg_match("/^[#-].*+\r?\n?/i", trim($line))) {
                    $sql = "";
                    continue;
                } elseif (!preg_match("/;[\r\n]+/", $line))
                    continue;

                mysql_query($sql, $this->conn);
                if (mysql_error() != "") {
                    $this->error.="<br>" . mysql_error();
                }

                $sql = "";
            }
            fclose($fp);
            if (!empty($this->error))
                return false;
            return true;
        }
    }

    function _connect() {
        if (!is_resource($this->conn))
            $this->conn = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);
        if (!is_resource($this->conn)) {
            $this->error = mysql_error();
            return false;
        }

        mysql_query("SET NAMES '" . $this->charset . "'");
        mysql_query("SET SQL_QUOTE_SHOW_CREATE = 1");

        return $this->conn;
    }

}

# End of Backup of mysql database
?>

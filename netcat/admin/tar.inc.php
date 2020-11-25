<?php

require ("Tar.php");  // /netcat/require/lib/

nc_tgz_check_exec();

// проверить, есть ли внешний tar и возможность его запустить
function nc_tgz_check_exec() {
    // Global setting: DISABLE_TGZ_EXEC -- установить в true если не работает system("tar")
    // check whether to use system() call to tar [faster]
    if (!$GLOBALS["DISABLE_TGZ_EXEC"] && !preg_match("/Windows/i", php_uname())) {  // it's not Windows
        $err_code = 127;
        $tgz_version = @exec("tar --version", $output, $err_code);
        define("SYSTEM_TAR", ($err_code ? false : true));
    } else {
        define("SYSTEM_TAR", false);
    }
}

// извлечь файл из архива
function nc_tgz_extract($archive_name, $dst_path) {
    global $DOCUMENT_ROOT;

    @set_time_limit(0);
    if (SYSTEM_TAR) {
        exec("cd $DOCUMENT_ROOT; tar -zxf $archive_name -C $dst_path 2>&1", $output, $err_code);
        if ($err_code && !strpos($output[0], "time")) { // ignore "can't utime, permission denied"
            trigger_error("$output[0]", E_USER_WARNING);
            return false;
        }
        return true;
    } else {
        $tar_object = new Archive_Tar($archive_name, "gz");
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
        return $tar_object->extract($dst_path);
    }
}

// создать архив
function nc_tgz_create($archive_name, $file_name, $additional_path = '') {
    global $DOCUMENT_ROOT, $SUB_FOLDER;

    @set_time_limit(0);

    $path = $DOCUMENT_ROOT.$SUB_FOLDER.$additional_path;
    if (SYSTEM_TAR) {
        exec("cd $path; tar -zcf '$archive_name' $file_name 2>&1", $output, $err_code);
        if ($err_code) {
            trigger_error("$output[0]", E_USER_WARNING);
            return false;
        }
        return true;
    } else {
        $tar_object = new Archive_Tar($archive_name, "gz");
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
        chdir($path);
        return $tar_object->create(array($file_name));
    }
}
?>
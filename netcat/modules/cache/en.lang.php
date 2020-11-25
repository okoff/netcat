<?php

/* $Id: en.lang.php 6265 2012-02-21 13:53:01Z nastya $ */

// main
define("NETCAT_MODULE_CACHE_DESCRIPTION", "This is cache organization module.");
// catalogue form
define("CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE", "Cache");
define("CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_ALLOW", "Allow");
define("CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_DENY", "Deny");
define("CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_LIFETIME", "Duration (minutes)");
define("CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_STATUS", "Cache status");
define("CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_CLEAR", "Clear cache");
// subdivision form
define("CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE", "Cache");
define("CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_ALLOW", "Allow");
define("CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_DENY", "Deny");
define("CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_LIFETIME", "Duration (minutes)");
define("CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_STATUS", "Cache status");
define("CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_CLEAR", "Clear cache");
// subclass form
define("CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE", "Cache");
define("CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_ALLOW", "Allow");
define("CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_DENY", "Deny");
define("CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_LIFETIME", "Currency (minutes)");
define("CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_STATUS", "Cache status");
define("CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_CLEAR", "Clear cache");
// admin interface
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS", "Cache settings");
define("NETCAT_MODULE_CACHE_ADMIN_CACHE", "Cache");
define("NETCAT_MODULE_CACHE_ADMIN_INFO", "Information");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT", "Audit data");
define("NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_TITLE", "Object list");
define("NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_SAVE_BUTTON", "Save");
define("NETCAT_MODULE_CACHE_ADMIN_SAVE_OK", "Cache settings successfully saved");
define("NETCAT_MODULE_CACHE_ADMIN_TYPE_LIST", "Objects list");
define("NETCAT_MODULE_CACHE_ADMIN_TYPE_FULL", "Detailed view");
define("NETCAT_MODULE_CACHE_ADMIN_TYPE_BROWSE", "Browse functions");
define("NETCAT_MODULE_CACHE_ADMIN_TYPE_FUNCTION", "Functions results");
// modules type
define("NETCAT_MODULE_CACHE_ADMIN_TYPE_CALENDAR", "Calendar view");
// admin interface / cache settings
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CATALOGUE", "Site for settings");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_TYPE", "Cache type");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_ON", "Enable");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_OFF", "Disable");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT", "Audit settings");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_ON", "Enable audit mode");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_BEGIN", "Audit begin time");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_END", "Audit end time");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_TIME", "Audit duration (hours)");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_SAVE_TIME", "Saved from time");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_TITLE", "Function results");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT", "Quota overdraft");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT_NOCACHE", "No cache");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT_DROP", "Delete low efficiency cache");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_CACHE", "Cache");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_SIZE", "Cache max size (MB)");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_CLEAR", "Clear");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_INFO_DELETED", "%SIZE cache data \"%TYPE\" deleted");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED", "Memcached");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ON", "Use memcached");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_HOST", "host");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_PORT", "port");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ERROR", "Unable to connect to memcached server");
define("NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_DOESNT_EXIST", "Extension memcache doesn't install.");
// admin interface / information
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_TITLE", "Lumpsum information");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_CACHE", "Cache");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_FILES", "Files");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_DIRS", "Dirs");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_SIZE", "Size");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_TOTAL", "total");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_CLEAR_TABLE", "Clear table");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_CACHE_COUNT", "Records count");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_CACHE_AVERAGE_EFFICIENCY", "Middle efficiency");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_UPDATE_BUTTON", "Update information");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_DROP_CLEAR_BUTTON", "Drop clear data");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_TYPE", "Cache type");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_COUNT", "Records in base");
define("NETCAT_MODULE_CACHE_ADMIN_MAININFO_DROP_CLEAR_OK", "Clear table data deleted");
// admin interface / audit data
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_DATA", "Audit data");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_COUNT", "Records");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_CATALOGUE", "Site");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_SUBDIVISION", "Division");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_SUBCLASS", "Division component");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_EFFICIENCY", "Efficiency");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_NODATA", "No data");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_SAVE_CLEAR_BUTTON", "Save in clear table");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_SAVE_CLEAR_OK", "Audit data successfully saved");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_DROP_BUTTON", "Drop audit data from base");
define("NETCAT_MODULE_CACHE_ADMIN_AUDIT_DROP_OK", "Audit data deleted");
// classes constants
define("NETCAT_MODULE_CACHE_CLASS_UNRECOGNIZED_OBJECT_CALLING", "Unrecognized cache object calling");
define("NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT", "Uncorrect data format!");
define("NETCAT_MODULE_CACHE_CLASS_CANNOT_CREATE_FILE", "Can not create cache file %FILE");
?>
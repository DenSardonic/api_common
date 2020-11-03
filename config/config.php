<?php

if(!defined('AccessProtect')) {
    die('Direct access not permitted');
}
// db for API 
define("DB_HOST", "localhost");

define("DB_USER", "test");
define("DB_PASS", "test");
define("DB_NAME", "test");
define("DB_PORT", "3306");
define("DB_CHARSET", "utf8");
define("DB_STR", "mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=".DB_PORT.";charset=".DB_CHARSET);

// db for resources
define("IBASE_HOST", "192.168.0.1");
define("IBASE_USER", "test");
define("IBASE_PASS", "test");
define("IBASE_NAME", "test");
define("IBASE_CHARSET", "utf8");
define("IBASE_STR", "firebird:dbname=".IBASE_NAME.";charset=".IBASE_CHARSET.";");

define("SOAP_STR", "192.168.0.1");
define("SOAP_USER", "test");
define("SOAP_PASS", "test");

define("JSON_STR", "192.168.0.1");

define("STS_HOST", "192.168.0.1");
define("STS_BASE_NAME", "test");
define("STS_PORT", "5432");

define("STS_USER", "test");
define("STS_PASS", "test");
define("STS_CHARSET", "utf8");

define("STS_STR", "pgsql:host=".STS_HOST.";dbname=".STS_BASE_NAME.";port=".STS_PORT);

define("MY_HOST", "192.168.0.1");
define("MY_USER", "test");
define("MY_PASS", "test");

define("MY_NAME", "test");
define("MY_PORT", "3306");
define("MY_CHARSET", "utf8");
define("MY_STR", "mysql:host=".MY_HOST.";dbname=".MY_NAME.";port=".MY_PORT.";charset=".MY_CHARSET);

;
define("REFRESH_TOKEN_SALT", "test")

?>
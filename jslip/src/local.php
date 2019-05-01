<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

define('TARGET',   "..."); // dev, staging or product
define('TMP_DIR',  "zAArCsCzgF");

define('RCLICK',   true);
define('MAX_SLIP', 10000); // As you like
define('EXPIRE',   "2030-05-31");
define('YEARS',    [2001, 2999]);
define('ROLE',     ['user', 'root']);

define('DEF_FUNC', "Login");
define('URL_BASE', "/dev/jslip/");

if (TARGET == "dev") {

    define('DB_HOST',      "xxx.xxx.xxx.xxx");
    define('DB_USER',      "xxxx");
    define('DB_PASS',      "xxxx");
    define('DB_NAME',      "datagram_js");

    define('UTL_PHP',      "/.../bin/php");
    define('UTL_DELETE',   "/.../bin/rm");
    define('UTL_PLATEX',   "/.../bin/x86_64-linux/platex");
    define('UTL_DVIPDFMX', "/.../bin/x86_64-linux/dvipdfmx");

} elseif (TARGET == "staging") {

    define('DB_HOST',      "xxx.xxx.xxx.xxx");
    define('DB_USER',      "xxxx");
    define('DB_PASS',      "xxxx");
    define('DB_NAME',      "datagram_js");

    define('UTL_PHP',      "/.../bin/php");
    define('UTL_DELETE',   "/.../bin/rm");
    define('UTL_PLATEX',   "/.../bin/x86_64-linux/platex");
    define('UTL_DVIPDFMX', "/.../bin/x86_64-linux/dvipdfmx");

} else { // TARGET == "product"

    define('DB_HOST',      "xxx.xxx.xxx.xxx");
    define('DB_USER',      "xxxx");
    define('DB_PASS',      "xxxx");
    define('DB_NAME',      "datagram_js");

    define('UTL_PHP',      "/.../bin/php");
    define('UTL_DELETE',   "/.../bin/rm");
    define('UTL_PLATEX',   "/.../bin/x86_64-linux/platex");
    define('UTL_DVIPDFMX', "/.../bin/x86_64-linux/dvipdfmx");
}

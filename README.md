# BKSJ-JSlip

Double-entry bookkeeping tool  
**BKSJ** : Bookkeeping System for Japanese  
**JSlip** : Japanese Accounting Slip Tool

## How to implement

### Preparation

- Environment

    LAMP (Linux, Apache, MySQL, PHP)

- PHP

    Version 7 or later

- Database

    MariaDB 5.5, MySQL 5.5 or 5.7

- TeX

    platex, dvipdfmx

### Implement

1. Create Database

    Create Schema. For example, **jslipdb**.

1. Prepare a secret keywords

    For example, **ndvjksahdCSkdd**.

1. Edit a file ''jslip/src/local.php''

    ```
    define('TARGET', "dev"); // dev, staging or product

    define('RCLICK',   true);                                    // Enable/Disable the right click
    define('MAX_SLIP', 10000);                                   // Maximum number of slips
    define('EXPIRE',   "2030-05-31");                            // Service deadline
    define('YEARS',    [2001, 2999]);
    define('ROLE',     ['user', 'root']);
    define('TMP_DIR',  "ndvjksahdCSkdd");                        // The important secret keyword

    define('DEF_FUNC', "Login");
    define('URL_BASE', "/dev/jslip/");                           // Implementation dependent

    if (TARGET == "dev") {

        define('DB_HOST',      "... DB server IP address ...");  // DB server IP address
        define('DB_USER',      "... DB account ...");            // DB account
        define('DB_PASS',      "... DB password ...");           // DB password
        define('DB_NAME',      "jslipdb");                       // DB database name (DB schema name)

        define('UTL_PHP',      "/... anywhere .../php");         // Implementation dependent
        define('UTL_DELETE',   "/... anywhere .../rm");          // Implementation dependent
        define('UTL_PLATEX',   "/... anywhere .../platex");      // Implementation dependent
        define('UTL_DVIPDFMX', "/... anywhere .../dvipdfmx");    // Implementation dependent

    } elseif (TARGET == "staging") {
    ```

1. Rename the file

    The file ''chk_pw.php'' contains the initial password.  
    ddl/chk_pw.php  --->  ddl/chk_pw_ndvjksahdCSkdd.php

1. Rename the directory

    tmp/zAArCsCzgF  --->  tmp/ndvjksahdCSkdd

1. Edit the SQL files.

    ddl_jslip.sql  
    ddl_jslip_c.sql  
    ddl_jslip_t.sql  
    ddl_jslip_w.sql

        use `datagram_js`  ---> use `jslipdb`

1. Execute SQL (Create tables)

    MariaDB [jslipdb]> \. ddl_jslip.sql

1. Initial login

    At first, log in as root.

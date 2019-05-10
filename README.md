# jslip
Double-entry bookkeeping tool

## How to implement

### Preparation

* Environment

	LAMP (Linux, Apache, MySQL, PHP)

* PHP

	Version 7 or later

* Database

	MySQL 5.5 or 5.7, MariaDB 5.5

* TeX

	platex, dvipdfmx

### Implement

* Create Database

	Create Schema. For example, **jslipdb**.

* Prepare a secret keywords

	For example, **ndvjksahdCSkdd**.

* Edit a file ''jslip/src/local.php''

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

* Rename the file

	The file ''chk_pw.php'' contains the initial password.  
	ddl/chk_pw.php  --->  ddl/chk_pw_ndvjksahdCSkdd.php

* Rename the directory

	tmp/zAArCsCzgF  --->  tmp/ndvjksahdCSkdd

* Edit the SQL files.

	ddl_jslip.sql  
	ddl_jslip_c.sql  
	ddl_jslip_t.sql  
	ddl_jslip_w.sql

		use `datagram_js`  ---> use `jslipdb`

* Execute SQL (Create tables)

	MariaDB [jslipdb]> \. ddl_jslip.sql

* Initial login

	At first, log in as root.

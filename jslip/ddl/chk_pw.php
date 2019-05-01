<?php
function chkPasswd($passwd) {
    echo $passwd . ' : ' . password_hash($passwd, PASSWORD_DEFAULT) . "\n";
}

chkPasswd('root66');
chkPasswd('guest');

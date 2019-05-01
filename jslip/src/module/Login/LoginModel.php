<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class LoginModel extends Model
{
    public function chkPasswd($account, $passwd) {

        $this->connect();
        $sql = "SELECT `password` FROM `t_auth` WHERE `login_id` = '" . $this->esc($account) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? false : password_verify($passwd, $rec[0]['password']);
    }

    public function getMemberInfo($account) {

        $this->connect();
        $sql =  "SELECT `m`.`mid`, `m`.`name`, `m`.`role`"
             . " FROM `t_auth` `a`"
             . " INNER JOIN `t_member` `m` ON `a`.`aid` = `m`.`aid`"
             . " WHERE `a`.`login_id` = '" . $this->esc($account) . "'"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function cntBasic($mid) {

        $this->connect();
        $sql =  "SELECT COUNT(*) AS `cnt`"
             . " FROM `t_basic`"
             . " WHERE `mid` = '" . $this->esc($mid) . "'"
             .   " AND `valid_flg` IS TRUE"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec[0]['cnt'] * 1;
    }

    public function getBid($mid) {

        $this->connect();
        $sql =  "SELECT `id`"
             . " FROM `t_basic`"
             . " WHERE `mid` = '" . $this->esc($mid) . "'"
             . " AND `valid_flg` IS TRUE"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0]['id'])) ? -1 : $rec[0]['id'];
    }
}

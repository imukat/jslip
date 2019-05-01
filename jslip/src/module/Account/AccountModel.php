<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class AccountModel extends Model
{

    public function getList($cnd) {

        $this->connect();

        $where = $this->_getListWhere($cnd);
        $cnt   = $this->_getListCnt($where);
        $list  = $this->_getListDat($where, $cnt, $cnd['pager']);

        $this->close();

        return $list;
    }

    private function _getListWhere($cnd) {

        $where = " WHERE (TRUE)";

        if ($cnd['cnd_login_id'] != '') {
            $where .= " AND `a`.`login_id` LIKE '%" . $this->esc($cnd['cnd_login_id']) . "%'";
        }

        if ($cnd['cnd_name'] != '') {
            $where .= " AND `m`.`name` LIKE '%" . $this->esc($cnd['cnd_name']) . "%'";
        }

        return $where;
    }

    private function _getListCnt($where) {

        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_auth` `a` INNER JOIN `t_member` `m` ON `a`.`aid` = `m`.`aid`" . $where;
        $rec = $this->getRecord($sql);

        return $rec[0]['cnt'];
    }

    private function _getListDat($where, $cnt, $pager) {

        $pg  = $this->getPaging($cnt, $pager['page'], $pager['rpp']);

        if ($cnt < 0) {
            $rec = [];
        } else {
            $sql =  "SELECT"
                 . " `a`.`login_id`, `m`.*"
                 . " FROM `t_auth` `a` INNER JOIN `t_member` `m` ON `a`.`aid` = `m`.`aid`"
                 . $where
                 . " ORDER BY `m`.`name`, `a`.`login_id`"
                 . " LIMIT " . $pg['ofst'] . ", " . $pg['rpp']
                 ;
            $rec = $this->getRecord($sql);
        }

        return [
            'cnt'  => $pg['cnt'],
            'rpp'  => $pg['rpp'],
            'last' => $pg['last'],
            'page' => $pg['page'],
            'rec'  => $rec,
        ];
    }

    public function getData($mid) {

        $this->connect();
        $sql =  "SELECT `a`.`login_id`, `m`.*"
             . " FROM `t_auth` `a` INNER JOIN `t_member` `m` ON `a`.`aid` = `m`.`aid`"
             . " WHERE `m`.`mid` = '" . $this->esc($mid) . "'"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            if (empty($param['passwd1'])) {
                $sql =  "UPDATE `t_auth` SET"
                     .  " `login_id`"      . " = '" . $this->esc($param['login_id']) . "'"
                     . ", `update_person`" . " = " . $_SESSION['minfo']['mid']
                     . " WHERE `aid` = '" . $this->esc($param['aid']) . "'"
                     ;
            } else {
                $sql =  "UPDATE `t_auth` SET"
                     .  " `login_id`"      . " = '" . $this->esc($param['login_id']) . "'"
                     . ", `password`"      . " = '" . password_hash($param['passwd1'], PASSWORD_DEFAULT) . "'"
                     . ", `update_person`" . " = " . $_SESSION['minfo']['mid']
                     . " WHERE `aid` = '" . $this->esc($param['aid']) . "'"
                     ;
            }

            $ans = $this->query($sql);

            $sql =  "UPDATE `t_member` SET"
                 .  " `name`"          . " = '" . $this->esc($param['name']) . "'"
                 . ", `role`"          . " = '" . $this->esc($param['role']) . "'"
                 . ", `email`"         . " = '" . $this->esc($param['email']) . "'"
                 . ", `tel`"           . " = '" . $this->esc($param['tel']) . "'"
                 . ", `update_person`" . " = " . $_SESSION['minfo']['mid']
                 . " WHERE `mid` = '" . $this->esc($param['mid']) . "'"
                 ;
            $ans = $this->query($sql);

        } catch(Exception $e) {
            $err = $e->getMessage();
        }

        if (empty($err)) {
            $this->commit();
        } else {
            $this->rollback();
        }

        $this->close();

        return $err;
    }

    public function insert($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "INSERT INTO `t_auth`"
                 . " (`login_id`, `password`, `update_person`)"
                 . " VALUES"
                 . " ('" . $this->esc($param['login_id']) . "'"
                 . ", '" . password_hash($param['passwd1'], PASSWORD_DEFAULT) . "'"
                 . ", '" . $_SESSION['minfo']['mid']. "'"
                 .  ")"
                 ;
            $ans = $this->query($sql);
            $aid = $this->insert_id();

            $sql =  "INSERT INTO `t_member`"
                 . " (`aid`, `name`, `role`, `email`, `tel`, `update_person`)"
                 . " VALUES"
                 . " ('" . $aid . "'"
                 . ", '" . $this->esc($param['name']) . "'"
                 . ", '" . $this->esc($param['role']) . "'"
                 . ", '" . $this->esc($param['email']) . "'"
                 . ", '" . $this->esc($param['tel']) . "'"
                 . ", '" . $_SESSION['minfo']['mid']. "'"
                 .  ")"
                 ;
            $ans = $this->query($sql);

        } catch(Exception $e) {
            $err = $e->getMessage();
        }

        if (empty($err)) {
            $this->commit();
        } else {
            $this->rollback();
        }

        $this->close();

        return $err;
    }

    public function delete($param) {

        $err = '';
        $aid = $param['aid'];
        $mid = $param['mid'];

        $this->connect();
        $this->begin();

        try {

            $sql = "DELETE FROM `t_member` WHERE `mid` = '" . $this->esc($mid) . "'";
            $ans = $this->query($sql);

            $sql = "DELETE FROM `t_auth` WHERE `aid` = '" . $this->esc($aid) . "'";
            $ans = $this->query($sql);

        } catch(Exception $e) {
            $err = $e->getMessage();
        }

        if (empty($err)) {
            $this->commit();
        } else {
            $this->rollback();
        }

        $this->close();

        return $err;
    }

    public function chkDup($login_id) {

        $this->connect();
        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_auth` WHERE `login_id` = '" . $this->esc($login_id) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec[0]['cnt'];
    }
}

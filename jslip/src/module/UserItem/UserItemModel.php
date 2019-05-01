<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserItemModel extends Model
{
    public $bid;

    function __construct($bid) {
        $this->bid = $bid;
    }

    public function getList($cnd) {

        $this->connect();

        $where = $this->_getListWhere($cnd);
        $cnt   = $this->_getListCnt($where);
        $list  = $this->_getListDat($where, $cnt, $cnd['pager']);

        $this->close();

        return $list;
    }

    private function _getListWhere($cnd) {

        $where = " WHERE `bid` = '" . $this->esc($this->bid) . "'";

        if ($cnd['cnd_kana'] != '') {
            $where .= " AND `kana` LIKE '%" . $this->esc($cnd['cnd_kana']) . "%'";
        }

        if ($cnd['cnd_name'] != '') {
            $where .= " AND `name` LIKE '%" . $this->esc($cnd['cnd_name']) . "%'";
        }

        return $where;
    }

    private function _getListCnt($where) {

        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_item`" . $where;
        $rec = $this->getRecord($sql);

        return $rec[0]['cnt'];
    }

    private function _getListDat($where, $cnt, $pager) {

        $pg  = $this->getPaging($cnt, $pager['page'], $pager['rpp']);

        if ($cnt < 0) {
            $rec = [];
        } else {
            $sql =  "SELECT * FROM `t_item`" . $where
                 . " ORDER BY `ccd`, `account`, `item`, `kana`"
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

    public function getData($id) {

        $this->connect();
        $sql = "SELECT * FROM `t_item` WHERE `id` = '" . $this->esc($id) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        $ccd     = substr($param['kcode'], 0, 4);
        $account = substr($param['kcode'], 4, 2);

        try {

            $sql =  "UPDATE `t_item` SET"
                 .  " `ccd`"           . " = '" . $this->esc($ccd) . "'"
                 . ", `account`"       . " = '" . $this->esc($account) . "'"
                 . ", `item`"          . " = '" . $this->esc($param['item']) . "'"
                 . ", `kana`"          . " = '" . $this->esc($param['kana']) . "'"
                 . ", `name`"          . " = '" . $this->esc($param['name']) . "'"
                 . ", `valid_flg`"     . " = "  . $this->esc($param['valid_flg'])
                 . ", `update_person`" . " = "  . $_SESSION['minfo']['mid']
                 . " WHERE `id` = '" . $this->esc($param['id']) . "'"
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

        $ccd     = substr($param['kcode'], 0, 4);
        $account = substr($param['kcode'], 4, 2);

        try {

            $sql =  "INSERT INTO `t_item`"
                 . " (`bid`, `kcd`, `ccd`, `account`, `item`, `kana`, `name`, `valid_flg`, `delete_flg`, `edit_flg`, `update_person`)"
                 . " VALUES"
                 . " ('" . $this->esc($param['bid']) . "'"
                 . ", '" . sprintf("%s%s%02d", $ccd, $account, $param['item']) . "'"
                 . ", '" . $this->esc($ccd) . "'"
                 . ", '" . $this->esc($account) . "'"
                 . ", '" . $this->esc($param['item']) . "'"
                 . ", '" . $this->esc($param['kana']) . "'"
                 . ", '" . $this->esc($param['name']) . "'"
                 . ", "  . $param['valid_flg']
                 . ", TRUE"
                 . ", TRUE"
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
        $id  = $param['id'];

        $this->connect();
        $this->begin();

        try {

            $sql = "DELETE FROM `t_item` WHERE `id` = '" . $this->esc($id) . "'";
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

    public function chkDup($param) {

        $ccd     = substr($param['kcode'], 0, 4);
        $account = substr($param['kcode'], 4, 2);

        $this->connect();

        $sql =  "SELECT COUNT(*) AS `cnt`"
             . " FROM `t_item`"
             . " WHERE `bid` = '" . $this->esc($param['bid']) . "'"
             .   " AND `ccd` = '" . $this->esc($ccd) . "'"
             .   " AND `account` = '" . $this->esc($account) . "'"
             .   " AND `item` = '" . $this->esc($param['item']) . "'"
             ;

        if (empty($param['insert'])) {
            $sql .= " AND `id` != '" . $this->esc($param['id']) . "'";
        }

        $rec = $this->getRecord($sql);

        $this->close();

        return $rec[0]['cnt'];
    }

    public function setValidFlg($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_item` SET"
                 .  " `valid_flg` = " . $param['valid_flg']
                 . " WHERE `id` = '" . $this->esc($param['id']) . "'"
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
}

<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserSectionModel extends Model
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

        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_section`" . $where;
        $rec = $this->getRecord($sql);

        return $rec[0]['cnt'];
    }

    private function _getListDat($where, $cnt, $pager) {

        $pg  = $this->getPaging($cnt, $pager['page'], $pager['rpp']);

        if ($cnt < 0) {
            $rec = [];
        } else {
            $sql =  "SELECT * FROM `t_section`" . $where
                 . " ORDER BY `kana`"
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
        $sql = "SELECT * FROM `t_section` WHERE `id` = '" . $this->esc($id) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_section` SET"
                 .  " `bid`"           . " = '" . $this->esc($param['bid']) . "'"
                 . ", `kana`"          . " = '" . $this->esc($param['kana']) . "'"
                 . ", `name`"          . " = '" . $this->esc($param['name']) . "'"
                 . ", `update_person`" . " = " . $_SESSION['minfo']['mid']
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

        try {

            $sql =  "INSERT INTO `t_section`"
                 . " (`bid`, `kana`, `name`, `update_person`)"
                 . " VALUES"
                 . " ('" . $this->esc($param['bid']) . "'"
                 . ", '" . $this->esc($param['kana']) . "'"
                 . ", '" . $this->esc($param['name']) . "'"
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

            $sql = "DELETE FROM `t_section` WHERE `id` = '" . $this->esc($id) . "'";
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

    public function chkDup($name) {

        $this->connect();
        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_section` WHERE `name` = '" . $this->esc($name) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec[0]['cnt'];
    }
}

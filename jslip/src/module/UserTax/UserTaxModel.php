<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserTaxModel extends Model
{
    public $bid;

    function __construct($bid) {
        $this->bid = $bid;
    }

    public function getList() {

        $this->connect();

        $sql =  "SELECT * FROM `t_tax`"
             . " WHERE `bid` = " . $this->esc($this->bid) . " ORDER BY `rate` ASC";
        $rec = $this->getRecord($sql);

        $this->close();

        return $rec;
    }

    public function chkDup($name) {

        $this->connect();
        $sql =  "SELECT COUNT(*) AS `cnt` FROM `t_tax`"
             . " WHERE `bid` = " . $this->esc($this->bid) . " AND `name` = '" . $this->esc($name) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec[0]['cnt'];
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_tax` SET"
                 .  " `bid`"       . " = '" . $this->esc($this->bid) . "'"
                 . ", `name`"      . " = '" . $this->esc($param['name']) . "'"
                 . ", `rate`"      . " = '" . $this->esc($param['rate']) . "'"
                 . ", `valid_flg`" . " = "  . $this->esc($param['valid_flg'])
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

            $sql =  "INSERT INTO `t_tax`"
                 . " (`bid`, `name`, `rate`, `valid_flg`)"
                 . " VALUES"
                 . " ('" . $this->esc($this->bid) . "'"
                 . ", '" . $this->esc($param['name']) . "'"
                 . ", '" . $this->esc($param['rate']) . "'"
                 . ", " . $this->esc($param['valid_flg'])
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

            $sql = "DELETE FROM `t_tax` WHERE `id` = '" . $this->esc($id) . "'";
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

    public function getData($id) {

        $this->connect();
        $sql = "SELECT * FROM `t_tax` WHERE `id` = '" . $this->esc($id) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function setValidFlg($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_tax` SET"
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

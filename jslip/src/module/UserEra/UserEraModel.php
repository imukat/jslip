<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserEraModel extends Model
{
    public $bid;

    function __construct($bid) {
        $this->bid = $bid;
    }

    public function getList() {

        $this->connect();

        $sql =  "SELECT * FROM `t_era`"
             . " WHERE `bid` = " . $this->esc($this->bid) . " ORDER BY `ymd` ASC";
        $rec = $this->getRecord($sql);

        $this->close();

        return $rec;
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_era` SET"
                 .  " `bid`" . " = '" . $this->esc($this->bid) . "'"
                 . ", `ymd`" . " = '" . $this->esc($param['ymd']) . "'"
                 . ", `era`" . " = '" . $this->esc($param['era']) . "'"
                 . ", `abr`" . " = '" . $this->esc($param['abr']) . "'"
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

            $sql =  "INSERT INTO `t_era`"
                 . " (`bid`, `ymd`, `era`, `abr`, `delete_flg`)"
                 . " VALUES"
                 . " ('" . $this->esc($this->bid) . "'"
                 . ", '" . $this->esc($param['ymd']) . "'"
                 . ", '" . $this->esc($param['era']) . "'"
                 . ", '" . $this->esc($param['abr']) . "'"
                 . ", TRUE"
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

            $sql = "DELETE FROM `t_era` WHERE `id` = '" . $this->esc($id) . "'";
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
        $sql = "SELECT * FROM `t_era` WHERE `id` = '" . $this->esc($id) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }
}

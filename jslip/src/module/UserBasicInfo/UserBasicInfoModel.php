<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserBasicInfoModel extends Model
{
    public function getData($bid) {

        $this->connect();
        $sql = "SELECT * FROM `t_basic` WHERE `id` = '" . $this->esc($bid) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function getRound() {

        $this->connect();
        $sql = "SELECT * FROM `c_round` ORDER BY `c0`";
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec;
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_basic` SET"
                 .  " `disp_name`"     . " = '" . $this->esc($param['disp_name']) . "'"
                 . ", `term_year`"     . " = '" . $this->esc($param['term_year']) . "'"
                 . ", `term_begin`"    . " = '" . $this->esc($param['term_begin']) . "'"
                 . ", `term_end`"      . " = '" . $this->esc($param['term_end']) . "'"
                 . ", `round`"         . " = '" . $this->esc($param['round']) . "'"
                 . ", `calendar`"      . " = '" . $this->esc($param['calendar']) . "'"
                 . ", `update_person`" . " = "  . $_SESSION['minfo']['mid']
                 . " WHERE `id` = '" . $this->esc($param['bid']) . "'"
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

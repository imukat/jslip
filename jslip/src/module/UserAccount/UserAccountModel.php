<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserAccountModel extends Model
{
    public function getData($bid) {

        $this->connect();
        $sql =  "SELECT `a`.`login_id`, `m`.*"
             . " FROM `t_basic` `b`"
             . " INNER JOIN `t_member` `m` ON `b`.`mid` = `m`.`mid`"
             . " INNER JOIN `t_auth`   `a` ON `m`.`aid` = `a`.`aid`"
             . " WHERE `b`.`id` = '" . $this->esc($bid) . "'"
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

            if (!empty($param['passwd1'])) {
                $sql =  "UPDATE `t_auth` SET"
                     .  " `password`"      . " = '" . password_hash($param['passwd1'], PASSWORD_DEFAULT) . "'"
                     . ", `update_person`" . " = " . $_SESSION['minfo']['mid']
                     . " WHERE `aid` = '" . $this->esc($param['aid']) . "'"
                     ;
                $ans = $this->query($sql);
            }

            $sql =  "UPDATE `t_member` SET"
                 .  " `name`"          . " = '" . $this->esc($param['name']) . "'"
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
}

<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserJournalModel extends Model
{
    public $bid;

    function __construct($bid) {
        $this->bid = $bid;
    }

    public function getLimit() {

        $limit = [];
        $limit['max_slip'] = MAX_SLIP;

        $this->connect();

        $fmt =  "SELECT COUNT(`s`.`id`) AS `cnt`"
             . " FROM `t_journal` `j`"
             . " INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`"
             . " WHERE `j`.`bid` = '%s'"
             ;
        $sql = sprintf($fmt, $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        $limit['cnt_slip'] = $rec[0]['cnt'];
        $limit['chk_slip'] = ($limit['cnt_slip'] > $limit['max_slip']) ? 'NG' : 'OK';

        return $limit;
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

        $where = " WHERE `j`.`bid` = '" . $this->esc($this->bid) . "'"
               . " AND `j`.`ymd` >= '" . $this->esc($cnd['cnd_begin']) . "'"
               . " AND `j`.`ymd` <= '" . $this->esc($cnd['cnd_end']) . "'"
               ;

        if ($cnd['cnd_scd'] != '-1') {
            $where .= " AND `j`.`scd` = '" . $this->esc($cnd['cnd_scd']) . "'";
        }

        if ($cnd['cnd_denpyo'] != '') {
            $where .= " AND `j`.`id` = '" . $this->esc($cnd['cnd_denpyo']) . "'";
        }

        if ($cnd['cnd_kcd'] != '-1') {
            $where .= " AND (`s`.`debit`  = '" . $this->esc($cnd['cnd_kcd']) . "'"
                   .         " OR"
                  .        " `s`.`credit` = '" . $this->esc($cnd['cnd_kcd']) . "'"
                   .       ")"
                   ;
        }

        if ($cnd['cnd_remark'] != '') {
            $where .= " AND `s`.`remark` LIKE '%" . $this->esc($cnd['cnd_remark']) . "%'";
        }

        if ($cnd['cnd_stflg'] != '-2') {
            $where .= " AND `j`.`settled_flg` = '" . $this->esc($cnd['cnd_stflg']) . "'";
        }

        if ($cnd['cnd_nuflg'] != '-1') {
            $where .= " AND `j`.`not_use_flg` = '" . $this->esc($cnd['cnd_nuflg']) . "'";
        }

        return $where;
    }

    private function _getListCnt($where) {

        $sql =  "SELECT DISTINCT `j`.`id`"
             . " FROM `t_journal` `j`"
             . " INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`"
             . $where
             ;
        $rec = $this->getRecord($sql);
        $cnt = (empty($rec)) ? 0 : count($rec);

        return $cnt;
    }

    private function _getListDat($where, $cnt, $pager) {

        $pg  = $this->getPaging($cnt, $pager['page'], $pager['rpp']);

        if ($cnt < 0) {
            $rec = [];
        } else {
            $sql =  "SELECT DISTINCT"
                 .   " `j`.`id`"          . "AS id"
                 .  ", `j`.`scd`"         . "AS scd"
                 .  ", `j`.`ymd`"         . "AS ymd"
                 .  ", `j`.`settled_flg`" . "AS settled_flg"
                 .  ", `j`.`not_use_flg`" . "AS not_use_flg"
                 . " FROM `t_journal` `j`"
                 . " INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`"
                 . $where
                 . " ORDER BY `ymd`, `id`"
                 . " LIMIT " . $pg['ofst'] . ", " . $pg['rpp']
                 ;
            $jod = $this->getRecord($sql);
            $jsd = [];
            foreach ($jod as $j) {
                $sql =  "SELECT"
                     .   " `id`"
                     .  ", `line`"
                     .  ", `debit`"
                     .  ", `credit`"
                     .  ", `amount`"
                     .  ", `remark`"
                     . " FROM `t_jslip`"
                     . " WHERE `jid` = '" . $j['id'] . "'"
                     . " ORDER BY `line`"
                     ;
                $rec = $this->getRecord($sql);

                foreach ($rec as $d) {
                    $jsd[$j['id']][$d['id']] = [
                        'line'   => $d['line'],
                        'debit'  => $d['debit'],
                        'credit' => $d['credit'],
                        'amount' => $d['amount'],
                        'remark' => $d['remark'],
                    ];
                }
            }
        }

        return [
            'cnt'  => $pg['cnt'],
            'rpp'  => $pg['rpp'],
            'last' => $pg['last'],
            'page' => $pg['page'],
            'rec'  => ['journal' => $jod, 'slip' => $jsd],
        ];
    }

    public function getData($id) {

        $this->connect();

        $sql = "SELECT * FROM `t_journal` WHERE `id` = '" . $this->esc($id) . "'";
        $jod = $this->getRecord($sql);

        if (empty($jod[0])) {
            $this->close();
            return [];
        }

        $sql =  "SELECT * FROM `t_jslip`"
             . " WHERE `jid` = '" . $this->esc($id) . "'"
             . " ORDER BY `line`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        return (empty($rec[0])) ? [] : ['journal' => $jod[0], 'slip' => $rec];
    }

    public function regist($param) {

        $err = '';

        $bid = $param['bid'];
        $arg = $param['arg'];
        $jid = $arg['jid'];

        $this->connect();
        $this->begin();

        try {

            $sql = "DELETE FROM `t_jslip` WHERE `jid` = '" . $this->esc($jid) . "'";
            $ans = $this->query($sql);

            $sql =  "UPDATE `t_journal` SET"
                 .  " `bid`           = '" . $this->esc($bid) . "'"
                 . ", `scd`           = '" . $this->esc($arg['scd']) . "'"
                 . ", `ymd`           = '" . $this->esc($arg['ymd']) . "'"
                 . ", `settled_flg`   = '" . $this->esc($arg['settled_flg']) . "'"
                 . ", `not_use_flg`   = '" . $this->esc($arg['not_use_flg']) . "'"
                 . ", `update_person` = '" . $_SESSION['minfo']['mid'] . "'"
                 . " WHERE `id` = '" . $this->esc($arg['jid']) . "'"
                 ;
            $ans = $this->query($sql);
            $n   = 0;
            $kcd = [];
            foreach ($arg['dat'] as $k => $d) {

                if ($d['deb_name'] == -1 && $d['cre_name'] == -1) {
                    continue;
                }

                $kcd[] = $d['deb_name'];
                $kcd[] = $d['cre_name'];

                $amt = ($d['deb_amount'] > 0) ? $d['deb_amount'] : $d['cre_amount'];
                $amt = str_replace(',', '', $amt);
                $n++;
                $sql =  "INSERT INTO `t_jslip`"
                     . " (`jid`, `line`, `debit`, `credit`, `amount`, `remark`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($jid) . "'"
                     . ", '" . $this->esc($n) . "'"
                     . ", '" . $this->esc($d['deb_name']) . "'"
                     . ", '" . $this->esc($d['cre_name']) . "'"
                     . ", '" . $this->esc($amt) . "'"
                     . ", '" . $this->esc($d['remark']) . "'"
                     . ", '" . $_SESSION['minfo']['mid']. "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

            foreach ($kcd as $d) {
                $sql =  "UPDATE `t_item` SET"
                     . " `dummy` = '" . date('YmdJis') . "'"
                     . " WHERE `bid` = '" . $this->esc($bid) . "'"
                     .   " AND `kcd` = '" . $this->esc($d) . "'"
                     ;
                $ans = $this->query($sql);
            }

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

        $bid = $param['bid'];
        $arg = $param['arg'];

        $this->connect();
        $this->begin();

        try {

            $sql =  "INSERT INTO `t_journal`"
                 . " (`bid`, `scd`, `ymd`, `settled_flg`, `not_use_flg`, `update_person`)"
                 . " VALUES"
                 . " ('" . $this->esc($bid) . "'"
                 . ", '" . $this->esc($arg['scd']) . "'"
                 . ", '" . $this->esc($arg['ymd']) . "'"
                 . ", "  . $this->esc($arg['settled_flg'])
                 . ", "  . $this->esc($arg['not_use_flg'])
                 . ", '" . $_SESSION['minfo']['mid']. "'"
                 .  ")"
                 ;
            $ans = $this->query($sql);
            $jid = $this->insert_id();
            $n   = 0;
            $kcd = [];
            foreach ($arg['dat'] as $k => $d) {

                if ($d['deb_name'] == -1 && $d['cre_name'] == -1) {
                    continue;
                }

                $kcd[] = $d['deb_name'];
                $kcd[] = $d['cre_name'];

                $amt = ($d['deb_amount'] > 0) ? $d['deb_amount'] : $d['cre_amount'];
                $amt = str_replace(',', '', $amt);
                $n++;
                $sql =  "INSERT INTO `t_jslip`"
                     . " (`jid`, `line`, `debit`, `credit`, `amount`, `remark`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($jid) . "'"
                     . ", '" . $this->esc($n) . "'"
                     . ", '" . $this->esc($d['deb_name']) . "'"
                     . ", '" . $this->esc($d['cre_name']) . "'"
                     . ", '" . $this->esc($amt) . "'"
                     . ", '" . $this->esc($d['remark']) . "'"
                     . ", '" . $_SESSION['minfo']['mid']. "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

            foreach ($kcd as $d) {
                $sql =  "UPDATE `t_item` SET"
                     . " `dummy` = '" . date('YmdJis') . "'"
                     . " WHERE `bid` = '" . $this->esc($bid) . "'"
                     .   " AND `kcd` = '" . $this->esc($d) . "'"
                     ;
                $ans = $this->query($sql);
            }

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
        $dno = $param['dno'];

        $this->connect();
        $this->begin();

        try {

            $sql = "DELETE FROM `t_jslip` WHERE `jid` = '" . $this->esc($dno) . "'";
            $ans = $this->query($sql);

            $sql = "DELETE FROM `t_journal` WHERE `id` = '" . $this->esc($dno) . "'";
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

    public function getRound($id) {

        $this->connect();

        $sql =  "SELECT `r`.*"
             . " FROM `t_basic` `b`"
             . " INNER JOIN `c_round` `r` ON `b`.`round` = `r`.`c0`"
             . " WHERE `b`.`id` = '" . $this->esc($id) . "'"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function getTax() {

        $this->connect();

        $sql = "SELECT `rate`, `name` FROM `t_tax` WHERE `bid` = '1' AND `valid_flg` IS TRUE ORDER BY `rate`";
        $rec = $this->getRecord($sql);

        $this->close();

        return $rec;
    }
}

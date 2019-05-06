<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserCalcModel extends Model
{
    public $bid;
    public $basic;
    public $lastCalculatedDate;

    function __construct($bid) {
        $this->bid = $bid;
        $this->lastCalculatedDate = $this->_getLastCalculatedDate();
    }

    private function _getLastCalculatedDate() {

        $this->connect();

        $sql = "SELECT `last` FROM `w_calc` WHERE `bid` = '" . $this->esc($this->bid) . "'";
        $rec = $this->getRecord($sql);

        $this->close();

        return (empty($rec[0]['last'])) ? '' : $rec[0]['last'];
    }

    public function calculate($basic) {

        $err = '';

        $this->basic = $basic;

        $this->connect();
        $this->begin();

        try {

            $this->_calc_ini();       // Initialize tables
            $this->_calc_slip();      // 仕訳整理
            $this->_calc_account();   // 勘定科目整理
            $this->_calc_item();      // 伝票科目整理
            $this->_calc_ledger_1();  // 総勘定元帳整理 #1　（収集）
            $this->_calc_ledger_2();  // 総勘定元帳整理 #2　（加工）
            $this->_calc_tb();        // 試算表
            $this->_calc_sa();        // 決算 settlement of account

        } catch(Exception $e) {
            $err = $e->getMessage();
        }

        if (empty($err)) {
            $this->commit();
        } else {
            $this->rollback();
        }

        $this->close();

        $this->lastCalculatedDate = $this->_getLastCalculatedDate();

        return $err;
    }

    private function _delete_table($tableName) {

        try {
            $qry =  "DELETE FROM `" . $tableName . "`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 ;
            $ans = $this->query($qry);
        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_ini() {

        try {
            $this->_delete_table('w_account');
            $this->_delete_table('w_aicd');
            $this->_delete_table('w_calc');
            $this->_delete_table('w_efy');
            $this->_delete_table('w_item');
            $this->_delete_table('w_ledger');
            $this->_delete_table('w_sa_a');
            $this->_delete_table('w_sa_b');
            $this->_delete_table('w_sa_bsc');
            $this->_delete_table('w_sa_bsd');
            $this->_delete_table('w_sa_c');
            $this->_delete_table('w_sa_d');
            $this->_delete_table('w_sa_e');
            $this->_delete_table('w_sa_f');
            $this->_delete_table('w_sa_g');
            $this->_delete_table('w_sa_h');
            $this->_delete_table('w_sa_plc');
            $this->_delete_table('w_sa_pld');
            $this->_delete_table('w_sa_rslt');
            $this->_delete_table('w_slip');
            $this->_delete_table('w_tb_a');
            $this->_delete_table('w_tb_b');
            $this->_delete_table('w_tb_c');
            $this->_delete_table('w_tb_rslt1');
            $this->_delete_table('w_tb_rslt2');
        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_slip() {

        try {

            $qry =  "INSERT INTO `w_slip`"
                 . " SELECT"
                 .   " `j`.`bid`"          . " AS `bid`,"            // 基本情報ID
                 .   " `j`.`id`"           . " AS `id`,"             // 伝票番号
                 .   " `j`.`scd`"          . " AS `scd`,"            // 部門ID
                 .   " `j`.`ymd`"          . " AS `ymd`,"            // 伝票日付
                 .   " `s`.`line`"         . " AS `line`,"           // 行番号
                 .   " `s`.`debit`"        . " AS `debit`,"          // 借方科目
                 .   " `s`.`credit`"       . " AS `credit`,"         // 貸方科目
                 .   " `i1`.`name`"        . " AS `debit_name`,"     // 借方科目名
                 .   " `i2`.`name`"        . " AS `credit_name`,"    // 貸方科目名
                 .   " CAST((`s`.`debit`  / 100) AS SIGNED)" . " AS `debit_account`,"  // 借方勘定科目
                 .   " CAST((`s`.`credit` / 100) AS SIGNED)" . " AS `credit_account`," // 貸方勘定科目
                 .   " CASE"
                 .     " WHEN `s`.`debit` = 0 THEN 0 ELSE `s`.`amount`"
                 .   " END "               . " AS `debit_amount`,"   // 借方金額
                 .   " CASE"
                 .     " WHEN `s`.`credit` = 0 THEN 0 ELSE `s`.`amount`"
                 .   " END "               . " AS `credit_amount`,"  // 貸方金額
                 .   " `s`.`amount`"       . " AS `amount`,"         // 金額
                 .   " `s`.`remark`"       . " AS `remark`,"         // 摘要
                 .   " `j`.`settled_flg`"  . " AS `settled_flg`"     // 決算データ
                 . " FROM"
                 .   " `t_journal` `j`"
                 .   " INNER JOIN `t_jslip` `s`"
                 .     " ON `j`.`id` = `s`.`jid`"
                 .   " INNER JOIN"
                 .     " (SELECT `kcd`, `name` FROM `t_item` WHERE `bid` = '" . $this->esc($this->bid) . "') `i1`"
                 .     " ON `i1`.`kcd` = `s`.`debit`"
                 .   " INNER JOIN"
                 .     " (SELECT `kcd`, `name` FROM `t_item` WHERE `bid` = '" . $this->esc($this->bid) . "') `i2`"
                 .     " ON `i2`.`kcd` = `s`.`credit`"
                 . " WHERE"
                 .   " (`j`.`bid` = '" . $this->esc($this->bid) . "')"
                 .   " AND (`j`.`not_use_flg` IS FALSE)"
                 . " ORDER BY"
                 .   " `j`.`id`, `s`.`line`"
                 ;

            $ans = $this->query($qry);

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_account() {

        try {

            $qry =  "INSERT INTO `w_account`"
                 . " SELECT"
                 .   " `i`.`bid`"       . " AS `bid`,"
                 .   " `i`.`ccd`"       . " AS `ccd`,"
                 .   " `i`.`account`"   . " AS `account`,"
                 .   " `i`.`item`"      . " AS `item`,"
                 .   " CAST((`i`.`kcd` / 100) AS SIGNED)" . " AS `account_cd`,"
                 .   " `i`.`kcd`"       . " AS `item_cd`,"
                 .   " `a`.`item_ccd`"  . " AS `account_ccd`,"
                 .   " `a`.`division`"  . " AS `division`"
                 . " FROM"
                 .   " `t_item` `i`"
                 .   " INNER JOIN `t_account` `a`"
                 .   " ON `i`.`account` = `a`.`item` AND `i`.`ccd` = `a`.`ccd`"
                 . " WHERE"
                 .   " `i`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "'"
                 ;

            $ans = $this->query($qry);

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_item() {

        try {

            $qry =  "INSERT INTO `w_item`"
                 . " SELECT"
                 .   " `d`.*"
                 . " FROM"
                 . " ("
                 .   " SELECT"
                 .     " `s`.`bid`"           . " AS `bid`,"
                 .     " `s`.`debit`"         . " AS `item`,"
                 .     " `s`.`debit_account`" . " AS `account`,"
                 .     " `a`.`account_ccd`"   . " AS `ccd`,"
                 .     " `a`.`division`"      . " AS `division`"
                 .   " FROM"
                 .     " `w_slip` `s`"
                 .   " INNER JOIN `w_account` `a`"
                 .     " ON `s`.`debit` = `a`.`item_cd`"
                 .   " WHERE"
                 .     " (`s`.`bid` = '" . $this->esc($this->bid) . "') AND"
                 .     " (`a`.`bid` = '" . $this->esc($this->bid) . "') AND"
                 .     " (`s`.`debit` != 0) AND"
                 .     " (`a`.`account_ccd` NOT BETWEEN 11 AND 14)"
                 .   " GROUP BY"
                 .     " `s`.`bid`, `s`.`debit`, `s`.`debit_account`, `a`.`account_ccd`, `a`.`division`"
                 . " ) `d`"
                 . " UNION SELECT"
                 .   " `c`.*"
                 . " FROM"
                 . " ("
                 .   " SELECT"
                 .     " `s`.`bid`"            . " AS `bid`,"
                 .     " `s`.`credit`"         . " AS `item`,"
                 .     " `s`.`credit_account`" . " AS `account`,"
                 .     " `a`.`account_ccd`"    . " AS `ccd`,"
                 .     " `a`.`division`"       . " AS `division`"
                 .   " FROM"
                 .     " `w_slip` `s`"
                 .   " INNER JOIN `w_account` `a`"
                 .     " ON `s`.`credit` = `a`.`item_cd`"
                 .   " WHERE"
                 .     " (`s`.`bid` = '" . $this->esc($this->bid) . "') AND"
                 .     " (`a`.`bid` = '" . $this->esc($this->bid) . "') AND"
                 .     " (`s`.`credit` != 0) AND"
                 .     " (`a`.`account_ccd` NOT BETWEEN 11 AND 14)"
                 .   " GROUP BY"
                 .     " `s`.`bid`, `s`.`credit`, `s`.`credit_account`, `a`.`account_ccd`, `a`.`division`"
                 . " ) `c`"
                 . " ORDER BY"
                 .   " `item`"
                 ;

            $ans = $this->query($qry);

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _insert_w_ledger($dat) {

        try {

            $qry = "INSERT INTO `w_ledger` ("
                 .  "`bid`,"
                 . " `account`,"
                 . " `typ`,"
                 . " `item`,"
                 . " `ymd`,"
                 . " `id`,"
                 . " `line`,"
                 . " `m`,"
                 . " `other`,"
                 . " `memo`,"
                 . " `settled_flg`,"
                 . " `amount`,"
                 . " `amount0`,"
                 . " `amount1`,"
                 . " `remain`,"
                 . " `division`,"
                 . " `mmdd`"
                 . ") VALUES ("
                 .   "'" . $this->esc($dat['bid'])         . "'"
                 . ", '" . $this->esc($dat['account'])     . "'"
                 . ", '" . $this->esc($dat['typ'])         . "'"
                 . ", '" . $this->esc($dat['item'])        . "'"
                 . ", '" . $this->esc($dat['ymd'])         . "'"
                 . ", '" . $this->esc($dat['id'])          . "'"
                 . ", '" . $this->esc($dat['line'])        . "'"
                 . ", '" . $this->esc($dat['m'])           . "'"
                 . ", '" . $this->esc($dat['other'])       . "'"
                 . ", '" . $this->esc($dat['memo'])        . "'"
                 . ", '" . $this->esc($dat['settled_flg']) . "'"
                 . ", '" . $this->esc($dat['amount'])      . "'"
                 . ", '" . $this->esc($dat['amount0'])     . "'"
                 . ", '" . $this->esc($dat['amount1'])     . "'"
                 . ", '" . $this->esc($dat['remain'])      . "'"
                 . ", '" . $this->esc($dat['division'])    . "'"
                 . ", '" . $this->esc($dat['mmdd'])        . "'"
                 . ")"
                 ;
            $ans = $this->query($qry);

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_ledger_1() {

        try {

            // 総勘定元帳
            $qry =  "INSERT INTO `w_ledger`"
                 . " SELECT"
                 .   " `d`.*"
                 . " FROM"
                 . " ("
                 .   " SELECT"
                 .   " `s1`.`bid`"          . " AS `bid`,"
                 .   " `i1`.`account`"      . " AS `account`,"
                 .   " 1"                   . " AS `typ`,"
                 .   " `s1`.`debit`"        . " AS `item`,"
                 .   " `s1`.`ymd`"          . " AS `ymd`,"
                 .   " `s1`.`id`"           . " AS `id`,"
                 .   " `s1`.`line`"         . " AS `line`,"
                 .   " 0"                   . " AS `m`,"
                 .   " `s1`.`credit`"       . " AS `other`,"
                 .   " `s1`.`remark`"       . " AS `memo`,"
                 .   " `s1`.`settled_flg`"  . " AS `settled_flg`,"
                 .   " `s1`.`amount`"       . " AS `amount`,"
                 .   " 0"                   . " AS `amount0`,"
                 .   " 0"                   . " AS `amount1`,"
                 .   " 0"                   . " AS `remain`,"
                 .   " `i1`.`division`"     . " AS `division`,"
                 .   " ''"                  . " AS `mmdd`"
                 .   " FROM"
                 .     " `w_slip` `s1`"
                 .     " LEFT JOIN `w_item` `i1`"
                 .     " ON `s1`.`debit` = `i1`.`item`"
                 .   " WHERE"
                 .     " `s1`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .     " `i1`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .     " `s1`.`settled_flg` > -1"
                 . " ) `d`"
                 . " UNION SELECT"
                 . " `c`.*"
                 . " FROM"
                 . " ("
                 .   " SELECT"
                 .     " `s2`.`bid`"           . " AS `bid`,"
                 .     " `i2`.`account`"       . " AS `account`,"
                 .     " 2"                    . " AS `typ`,"
                 .     " `s2`.`credit`"        . " AS `item`,"
                 .     " `s2`.`ymd`"           . " AS `ymd`,"
                 .     " `s2`.`id`"            . " AS `id`,"
                 .     " `s2`.`line`"          . " AS `line`,"
                 .     " 0"                    . " AS `m`,"
                 .     " `s2`.`debit`"         . " AS `other`,"
                 .     " `s2`.`remark`"        . " AS `memo`,"
                 .     " `s2`.`settled_flg`"   . " AS `settled_flg`,"
                 .     " `s2`.`amount`"        . " AS `amount`,"
                 .     " 0"                    . " AS `amount0`,"
                 .     " 0"                    . " AS `amount1`,"
                 .     " 0"                    . " AS `remain`,"
                 .     " `i2`.`division`"      . " AS `division`,"
                 .     " ''"                   . " AS `mmdd`"
                 .   " FROM"
                 .     " `w_slip` `s2`"
                 .     " LEFT JOIN `w_item` `i2`"
                 .     " ON `s2`.`credit` = `i2`.`item`"
                 .   " WHERE"
                 .     " `s2`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .     " `i2`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .     " `s2`.`settled_flg` > -1"
                 . " ) `c`"
                 . " ORDER BY"
                 .   " `account`, `item`, `ymd`, `id`, `line`, `typ`"
                 ;

            $ans = $this->query($qry);

            // 総勘定元帳（繰越分）
	        $qry =  "SELECT *"
                 . " FROM `w_slip`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `settled_flg` < 0"
                 ;
            $znk = $this->getRecord($qry);

	        $qry =  "SELECT `bid`, `item`, `account`, `ccd`, `division`"
                 . " FROM `w_item`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " ORDER BY `account`"
                 ;
            $rec = $this->getRecord($qry);
            $cnt = (empty($rec)) ? 0 : count($rec);

            $r   = [];
            $n   = 0;
            $ymd = explode('-', $this->basic['term_begin']);
            $y   = $ymd[0] * 1;
            $m   = $ymd[1] * 1;
            $d   = $ymd[2] * 1;
            for ($i = 0; $i < $cnt; $i++) {
                for ($j = 0; $j < 12; $j++) {
                    $md = ($m + $j > 12)
                        ? date('Y/m/d', mktime(0, 0, 0, $m + $j - 12, $d, $y + 1))
                        : date('Y/m/d', mktime(0, 0, 0, $m + $j, $d, $y))
                        ;
                    $memo = ($j == 0) ? '前期繰越' : '前月繰越';

                    $zflg = false;
                    foreach ($znk as $z) {

                        if ($z['credit'] == 0) { // 貸方が諸口?
                            $typ = 1;
                            $itm = $z['debit'];
                            $amt = $z['amount'];
                            $am0 = 0;
                            $am1 = 0;
                        } else {
                            $typ = 2;
                            $itm = $z['credit'];
                            $amt = $z['amount'];
                            $am0 = 0;
                            $am1 = 0;
                        }

                        if ($itm == $rec[$i]['item']) {
                            $zflg = true;
                            break;
                        }
                    }

                    if ($memo == '前期繰越' && $zflg == true) {
                        $r[$n]['typ']         = $typ;
                        $r[$n]['item']        = $itm;
                        $r[$n]['settled_flg'] = -1;
                        $r[$n]['amount']      = $amt;
                        $r[$n]['amount0']     = $am0;
                        $r[$n]['amount1']     = $am1;
                    } else {
                        $r[$n]['typ']         = 0;
                        $r[$n]['item']        = $rec[$i]['item'];
                        $r[$n]['settled_flg'] = 0;
                        $r[$n]['amount']      = 0;
                        $r[$n]['amount0']     = 0;
                        $r[$n]['amount1']     = 0;
                    }

                    $r[$n]['bid']         = $this->bid;
                    $r[$n]['account']     = $rec[$i]['account'];
                  //$r[$n]['typ']
                  //$r[$n]['item']
                    $r[$n]['ymd']         = $md;
                    $r[$n]['id']          = -1;
                    $r[$n]['line']        = 0;
                    $r[$n]['m']           = 0;
                    $r[$n]['other']       = 0;    // 諸口
                    $r[$n]['memo']        = $memo;
                  //$r[$n]['settled_flg']
                  //$r[$n]['amount']
                  //$r[$n]['amount0']
                  //$r[$n]['amount1']
                    $r[$n]['remain']      = 0;
                    $r[$n]['division']    = $rec[$i]['division'];
                    $r[$n]['mmdd']        = '';
                    $n++;
                }
            }

            for ($i = 0; $i < $n; $i++) {
                $this->_insert_w_ledger($r[$i]);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_ledger_2() {

        try {

            // 総勘定元帳（全体）
            $qry =  "SELECT"
                 .   " `bid`"
                 .  ", `account`"
                 .  ", `typ`"
                 .  ", `item`"
                 .  ", `ymd`"
                 .  ", `id`"
                 .  ", `line`"
                 .  ", `m`"
                 .  ", `other`"
                 .  ", `memo`"
                 .  ", `settled_flg`"
                 .  ", `amount`"
                 .  ", `amount0`"
                 .  ", `amount1`"
                 .  ", `remain`"
                 .  ", `division`"
                 .  ", `mmdd`"
                 . " FROM"
                 . " `w_ledger`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " (`item` != 0 OR `item` IS NULL) AND"
                 .   " `ymd` BETWEEN '" . $this->esc($this->basic['term_begin']) . "' AND '" . $this->esc($this->basic['term_end']) . "'"
                 . " ORDER BY"
                 . " `account`, `item`, `ymd`, `id`, `line`"
                 ;
            $rec = $this->getRecord($qry);
            $cnt = (empty($rec)) ? 0 : count($rec);

            $k = '';   // 科目
            $z = 0;    // 残高
            for ($i = 0; $i < $cnt; $i++) {
                switch ($rec[$i]['typ'] ) {
                    case 1: $rec[$i]['amount0'] = $rec[$i]['amount']; break;
                    case 2: $rec[$i]['amount1'] = $rec[$i]['amount']; break;
                }

                if ($rec[$i]['item'] != $k) {
                    $k = $rec[$i]['item'];
                    $z = 0;
                }

                switch ($rec[$i]['division']) {
                    case 0: $z += $rec[$i]['amount0'] - $rec[$i]['amount1']; break;
                    case 1: $z += $rec[$i]['amount1'] - $rec[$i]['amount0']; break;
                }

                $rec[$i]['remain'] = $z;

                $a = explode('-', $rec[$i]['ymd']);

                $rec[$i]['m']    = intval($a[0] . $a[1]);
                $rec[$i]['mmdd'] = $a[1] . '/' . $a[2];
            }

            $t  = '';
            for ($i = $cnt - 1; $i >= 0; $i--) {
                switch ($rec[$i]['memo']) {
                    case '前期繰越':
                    case '前月繰越':
                        break;
                    default:
                        if ($t != $rec[$i]['mmdd'])
                            $t = $rec[$i]['mmdd'];
                        else
                            $rec[$i]['remain'] = 0;
                        break;
                }
            }

            $t = '';   // 月日
            for ($i = 0; $i < $cnt; $i++) {
                $ymd = $rec[$i]['ymd'];
                $a   = explode('-', $ymd);

                if ($ymd != $t) {
                    $t = $ymd;
                    $rec[$i]['mmdd'] = $a[1] . '/' . $a[2];
                } else {
                    $rec[$i]['mmdd'] = '〃';
                }
            }

            $qry = "DELETE FROM `w_ledger` WHERE `bid` = '" . $this->esc($this->bid) . "'";
            $ans = $this->query($qry);

            for ($i = 0; $i < $cnt; $i++) {
                if ($rec[$i]['ymd'] >= $this->basic['term_begin']) {
                    $this->_insert_w_ledger($rec[$i]);
                }
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_tb() {

        try {

            // 勘定科目コード分類
            $qry = "INSERT INTO `w_aicd`"
                 . " SELECT"
                 .   " '" . $this->esc($this->bid) . "' AS `bid`"
                 .  ", `c1`, 0 AS `c2`, 0 AS `c3`, 0 AS `c4`, `c1` * 1000 AS `ctg`, 0 AS `div`, `name`"
                 . " FROM `c_c1`"
                 . " UNION SELECT"
                 .   " '" . $this->esc($this->bid) . "' AS `bid`"
                 .  ", `c1`, `c2`, 0 AS `c3`, 0 AS `c4`, `c1` * 1000 + `c2` * 100 AS `ctg`, 0 AS `div`, `name`"
                 . " FROM `c_c2`"
                 . " UNION SELECT"
                 .   " '" . $this->esc($this->bid) . "' AS `bid`"
                 .  ", `c1`, `c2`, `c3`, 0 AS `c4`, `c1` * 1000 + `c2` * 100 + `c3` * 10 AS `ctg`, `div`, `name`"
                 . " FROM `c_c3`"
                 . " UNION SELECT"
                 .   " '" . $this->esc($this->bid) . "' AS `bid`"
                 .  ", `c1`, `c2`, `c3`, `c4`, `c1` * 1000 + `c2` * 100 + `c3` * 10 + `c4` AS `ctg`, 0 AS `div`, `name`"
                 . " FROM `c_c4`"
                 ;
            $ans = $this->query($qry);

            // 試算表 #1（繰越分）
            $qry = "INSERT INTO `w_tb_a`"
                 . " SELECT"
                 .   " `l`.`bid`      AS `bid`"
                 .  ", `l`.`item`     AS `item`"
                 .  ", `l`.`m`        AS `m`"
                 .  ", `i`.`name`     AS `name`"
                 .  ", `l`.`remain`   AS `remain`"
                 .  ", CASE"
                 .     " WHEN `l`.`division` = 0 THEN `l`.`remain` ELSE 0"
                 .   " END            AS `debit_amount`"
                 .  ", CASE"
                 .     " WHEN `l`.`division` = 1 THEN `l`.`remain` ELSE 0"
                 .   " END            AS `credit_amount`"
                 .  ", `l`.`division` AS `division`"
                 . " FROM"
                 .   " `w_ledger` `l` INNER JOIN `t_item` `i` ON `l`.`item` = `i`.`kcd`"
                 . " WHERE"
                 .   " `l`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `i`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " ((`l`.`memo` = '前期繰越') OR (`l`.`memo` = '前月繰越'))"
                 . " ORDER BY"
                 .   " `l`.`item`, `l`.`m`"
                 ;
            $ans = $this->query($qry);
            
            // 試算表 #2（貸借金額月度合計）
            $qry = "INSERT INTO `w_tb_b`"
                 . " SELECT"
                 .   " `a`.`bid`             AS `bid`"
                 .  ", `a`.`item`            AS `item`"
                 .  ", `a`.`m`               AS `m`"
                 .  ", SUM(`a`.`debit_sum`)  AS `debit_sum`"
                 .  ", SUM(`a`.`credit_sum`) AS `credit_sum`"
                 . " FROM ("
                 . " SELECT DISTINCT"
                 .   " `bid`"
                 .  ", `item`"
                 .  ", `m`"
                 .  ", CASE"
                 .     " WHEN SUM(`amount0`) IS NULL"
                 .     " THEN 0"
                 .     " ELSE SUM(`amount0`)"
                 .   " END AS `debit_sum`"
                 .  ", CASE"
                 .     " WHEN SUM(`amount1`) IS NULL"
                 .     " THEN 0"
                 .     " ELSE SUM(`amount1`)"
                 .   " END AS `credit_sum`"
                 . " FROM"
                 .   " `w_ledger`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `settled_flg` > -1"
                 . " GROUP BY"
                 .   " `bid`, `item`, `m`"
                 . " UNION SELECT DISTINCT"
                 .   " `bid`"
                 .  ", `item`"
                 .  ", `m`"
                 .  ", 0 AS `debit_sum`"
                 .  ", 0 AS `credit_sum`"
                 . " FROM"
                 .   " `w_ledger`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `settled_flg` < 0"
                 . " GROUP BY"
                 .   " `bid`, `item`, `m`"
                 . ") `a`"
                 . " GROUP BY"
                 .   " `a`.`bid`, `a`.`item`, `a`.`m`"
                 . " ORDER BY"
                 .   " `a`.`item`, `a`.`m`"
                 ;
            $ans = $this->query($qry);

	        // 試算表 #3（繰越分・貸借金額月度合計）
            $qry = "INSERT INTO `w_tb_c`"
                 . " SELECT"
                 .   " `a`.`bid`           AS `bid`"
                 .  ", `a`.`item`          AS `item`"
                 .  ", `a`.`m`             AS `m`"
                 .  ", `a`.`name`          AS `name`"
                 .  ", `a`.`debit_amount`  AS `debit_amount`"
                 .  ", `a`.`credit_amount` AS `credit_amount`"
                 .  ", `a`.`division`      AS `division`"
                 .  ", CASE"
                 .     " WHEN `b`.`debit_sum` IS NULL"
                 .     " THEN 0"
                 .     " ELSE `b`.`debit_sum`"
                 .   " END                 AS `debit_sum`"
                 .  ", CASE"
                 .     " WHEN `b`.`credit_sum` IS NULL"
                 .     " THEN 0"
                 .   " ELSE `b`.`credit_sum`"
                 .   " END                 AS `credit_sum`"
                 .  ", CASE"
                 .     " WHEN `a`.`division` = 0"
                 .     " THEN (`a`.`debit_amount`  - `a`.`credit_amount`) + (`b`.`debit_sum`  - `b`.`credit_sum`)"
                 .     " ELSE (`a`.`credit_amount` - `a`.`debit_amount` ) + (`b`.`credit_sum` - `b`.`debit_sum` )"
                 .   " END                 AS `remain`"
                 .  ", CAST((`a`.`item` / 100000) AS SIGNED) * 10  AS `ctg3`"
                 .  ", CAST((`a`.`item` /  10000) AS SIGNED)       AS `ctg4`"
                 . " FROM"
                 .   " `w_tb_a` `a` LEFT JOIN `w_tb_b` `b` ON (`a`.`item` = `b`.`item` AND `a`.`m` = `b`.`m`)"
                 . " WHERE"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `b`.`bid` = '" . $this->esc($this->bid) . "'"
                 . " ORDER BY"
                 .   " `a`.`item`, `a`.`m`"
                 ;
            $ans = $this->query($qry);

            // 試算表（結果：個別科目別）参照：t_item
            $qry = "INSERT INTO `w_tb_rslt1`"
                 . " SELECT"
                 .   " `c`.`bid`                  AS `bid`"
                 .  ", `c`.`m`                    AS `m`"
                 .  ", `c`.`item`                 AS `item`"
                 .  ", CASE"
                 .     " WHEN `c`.`division` = 0 THEN `c`.`remain` ELSE 0"
                 .   " END                        AS `debit_remain`"
                 .  ", `c`.`debit_sum`            AS `debit_sum`"
                 .  ", `c`.`name`                 AS `name`"
                 .  ", `c`.`credit_sum`           AS `credit_sum`"
                 .  ", CASE"
                 .     " WHEN `c`.`division` = 0 THEN 0 ELSE `c`.`remain`"
                 .   " END                        AS `credit_remain`"
                 .  ", `c`.`m` % 100              AS `mm`"
                 .  ", `c`.`division`             AS `division`"
                 .  ", `i`.`div`                  AS `ctg_div`"
                 .  ", CAST((`c`.`ctg3` / 1000) AS SIGNED) * 1000 AS `ctg1`"
                 .  ", CAST((`c`.`ctg3` /  100) AS SIGNED) *  100 AS `ctg2`"
                 .  ", `c`.`ctg3`                 AS `ctg3`"
                 .  ", `c`.`ctg4`                 AS `ctg4`"
                 .  ", CAST((`c`.`item` /  100) AS SIGNED) %  100 AS `ctg5`"
                 . " FROM"
                 .   " `w_tb_c` `c` INNER JOIN `w_aicd` `i` ON `c`.`ctg3` = `i`.`ctg`"
                 . " WHERE"
                 .   " `c`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `i`.`bid` = '" . $this->esc($this->bid) . "'"
                 . " ORDER BY"
                 .   " `c`.`m`, `c`.`item`"
                 ;
            $ans = $this->query($qry);

            // 試算表（結果：勘定科目別）参照：t_account
            $qry = "INSERT INTO `w_tb_rslt2`"
                 . " SELECT DISTINCT"
                 .   " `bid`                AS `bid`"
                 .  ", `m`                  AS `m`"
                 .  ", `mm`                 AS `mm`"
                 .  ", `ctg4`               AS `ccd`"
                 .  ", `ctg5`               AS `item`"
                 .  ", `division`           AS `division`"
                 .  ", `ctg_div`            AS `ctg_div`"
                 .  ", SUM(`debit_remain`)  AS `debit_remain`"
                 .  ", SUM(`debit_sum`)     AS `debit_sum`"
                 .  ", SUM(`credit_sum`)    AS `credit_sum`"
                 .  ", SUM(`credit_remain`) AS `credit_remain`"
                 . " FROM"
                 .   " `w_tb_rslt1`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "'"
                 . " GROUP BY"
                 .   " `bid`, `m`, `mm`, `ctg4`, `ctg5`, `division`, `ctg_div`"
                 . " ORDER BY"
                 . " `bid`, `m`, `ctg4`, `ctg5`"
                 ;
            $ans = $this->query($qry);

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _calc_sa() {

        try {

            // 決算（精算表）
            $qry = "INSERT INTO `w_sa_a`"
                 . " SELECT"
                 .   " `bid`           AS `bid`"
                 .  ", `m`             AS `m`"
                 .  ", `mm`            AS `mm`"
                 .  ", `ccd`           AS `ccd`"
                 .  ", `item`          AS `item`"
                 .  ", `debit_remain`  AS `debit_remain`"
                 .  ", `credit_remain` AS `credit_remain`"
                 .  ", CASE WHEN `ctg_div` = 1 THEN `debit_remain`  ELSE 0 END AS `bsd`"
                 .  ", CASE WHEN `ctg_div` = 1 THEN `credit_remain` ELSE 0 END AS `bsc`"
                 .  ", CASE WHEN `ctg_div` = 2 THEN `debit_remain`  ELSE 0 END AS `pld`"
                 .  ", CASE WHEN `ctg_div` = 2 THEN `credit_remain` ELSE 0 END AS `plc`"
                 .  ", CASE WHEN `ccd` = 9910  THEN `debit_remain`  ELSE 0 END AS `cod`"
                 .  ", CASE WHEN `ccd` = 9910  THEN `credit_remain` ELSE 0 END AS `coc`"
                 . " FROM"
                 .   " `w_tb_rslt2`"
                 . " WHERE"
                 .   " bid = '" . $this->esc($this->bid) . "'"
                 ;
            $ans = $this->query($qry);

            // 決算（精算表月次合計）
            $qry = "INSERT INTO `w_sa_b`"
                 . " SELECT DISTINCT"
                 .   " `bid`                AS `bid`"
                 .  ", `m`                  AS `m`"
                 .  ", `mm`                 AS `mm`"
                 .  ", SUM(`debit_remain`)  AS `debit_remain`"
                 .  ", SUM(`credit_remain`) AS `credit_remain`"
                 .  ", SUM(`bsd`)           AS `bsd`"
                 .  ", SUM(`bsc`)           AS `bsc`"
                 .  ", SUM(`pld`)           AS `pld`"
                 .  ", SUM(`plc`)           AS `plc`"
                 .  ", SUM(`cod`)           AS `cod`"
                 .  ", SUM(`coc`)           AS `coc`"
                 . " FROM"
                 .   " `w_sa_a`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "'"
                 . " GROUP BY"
                 .   " `bid`, `m`, `mm`"
                 . " ORDER BY"
                 .   " `bid`, `m`"
                 ;
            $ans = $this->query($qry);

            // 決算（決算利益）
            $qry = "INSERT INTO `w_sa_c`"
                 . " SELECT"
                 .   " `bid`         AS `bid`"
                 .  ", `m`           AS `m`"
                 .  ", `mm`          AS `mm`"
                 .  ", '当期利益'    AS `name`"
                 .  ", `plc` - `pld` AS `remain`"
                 .  ", 999999        AS `account_cd`"
                 . " FROM"
                 .   " `w_sa_b`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "'"
                 . " ORDER BY"
                 .   " `bid`, `m`"
                 ;
            $ans = $this->query($qry);

            // 決算（決算貸借対照表勘定借方）   勘定分類コード = 1 and 貸借区分 = 0
            $qry = "INSERT INTO `w_sa_d`"
                 . " SELECT"
                 .   " `t`.`bid`                    AS `bid`"
                 .  ", `t`.`m`                      AS `m`"
                 .  ", `t`.`mm`                     AS `mm`"
                 .  ", `a`.`name`                   AS `name`"
                 .  ", `t`.`debit_remain`           AS `remain`"
                 .  ", `t`.`ccd` * 100 + `t`.`item` AS `account_cd`"
                 . " FROM"
                 .   " `w_tb_rslt2` `t`"
                 .   " INNER JOIN `t_account` `a`"
                 .   " ON `t`.`ccd` = `a`.`ccd` AND `t`.`item` = `a`.`item`"
                 . " WHERE"
                 .   " `t`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `t`.`ctg_div` = 1 AND"
                 .   " `t`.`division` = 0"
                 . " ORDER BY"
                 .   "`m`, `t`.`ccd`, `t`.`item`"
                 ;
            $ans = $this->query($qry);

            // 決算（決算貸借対照表勘定貸方）   勘定分類コード = 1 and 貸借区分 = 1
            $qry = "INSERT INTO `w_sa_e`"
                 . " SELECT"
                 .   " `t`.`bid`                    AS `bid`"
                 .  ", `t`.`m`                      AS `m`"
                 .  ", `t`.`mm`                     AS `mm`"
                 .  ", `a`.`name`                   AS `name`"
                 .  ", `t`.`credit_remain`          AS `remain`"
                 .  ", `t`.`ccd` * 100 + `t`.`item` AS `account_cd`"
                 . " FROM"
                 .   " `w_tb_rslt2` `t`"
                 .   " INNER JOIN `t_account` `a`"
                 .   " ON `t`.`ccd` = `a`.`ccd` AND `t`.`item` = `a`.`item`"
                 . " WHERE"
                 .   " `t`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `t`.`ctg_div` = 1 AND"
                 .   " `t`.`division` = 1"
                 . " ORDER BY"
                 .   " `t`.`m`, `t`.`ccd`, `t`.`item`"
                 ;
            $ans = $this->query($qry);

            // 決算（決算損益勘定借方）
            $qry = "INSERT INTO `w_sa_f`"
                 . " SELECT"
                 .   " `t`.`bid`                    AS `bid`"
                 .  ", `t`.`m`                      AS `m`"
                 .  ", `t`.`mm`                     AS `mm`"
                 .  ", `a`.`name`                   AS `name`"
                 .  ", `t`.`debit_remain`           AS `remain`"
                 .  ", `t`.`ccd` * 100 + `t`.`item` AS `account_cd`"
                 . " FROM"
                 .   " `w_tb_rslt2` `t`"
                 .   " INNER JOIN `t_account` `a`"
                 .   " ON `t`.`ccd` = `a`.`ccd` AND `t`.`item` = `a`.`item`"
                 . " WHERE"
                 .   " `t`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `t`.`ctg_div` = 2 AND"
                 .   " `t`.`division` = 0"
                 . " ORDER BY"
                 .   " `t`.`m`, `t`.`ccd`, `t`.`item`"
                 ;
            $ans = $this->query($qry);

            // 決算（決算損益勘定貸方）
            $qry = "INSERT INTO `w_sa_g`"
                 . " SELECT"
                 .   " `t`.`bid`                    AS `bid`"
                 .  ", `t`.`m`                      AS `m`"
                 .  ", `t`.`mm`                     AS `mm`"
                 .  ", `a`.`name`                   AS `name`"
                 .  ", `t`.`credit_remain`          AS `remain`"
                 .  ", `t`.`ccd` * 100 + `t`.`item` AS `account_cd`"
                 . " FROM"
                 .   " `w_tb_rslt2` `t`"
                 .   " INNER JOIN `t_account` `a`"
                 .   " ON `t`.`ccd` = `a`.`ccd` AND `t`.`item` = `a`.`item`"
                 . " WHERE"
                 .   " `t`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `t`.`ctg_div` = 2 AND"
                 .   " `t`.`division` = 1"
                 . " ORDER BY"
                 .   "`t`.`m`, `t`.`ccd`, `t`.`item`"
                 ;
            $ans = $this->query($qry);

            // 決算（決算損益勘定借方原価）
            $qry = "INSERT INTO `w_sa_h`"
                 . " SELECT"
                 .   " `t`.`bid`          AS `bid`"
                 .  ", `t`.`m`            AS `m`"
                 .  ", `t`.`mm`           AS `mm`"
                 .  ", `a`.`name`         AS `name`"
                 .  ", `t`.`debit_remain` AS `remain`"
                 .  ", `t`.`ccd` * 100 + `t`.`item` AS `account_cd`"
                 . " FROM"
                 .   " `w_tb_rslt2` `t`"
                 .   " INNER JOIN `t_account` `a`"
                 .   " ON `t`.`ccd` = `a`.`ccd` AND `t`.`item` = `a`.`item`"
                 . " WHERE"
                 .   " `t`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `a`.`bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `t`.`ctg_div` = 2 AND"
                 .   " `t`.`division` = 0"
                 . " ORDER BY"
                 .   "`t`.`m`, `t`.`ccd`, `t`.`item`"
                 ;
            $ans = $this->query($qry);

            // 決算（結果）
            $qry = "INSERT INTO `w_sa_rslt`"
                 . " SELECT       *, 1 AS `ctg_div`, 0 AS `division` FROM `w_sa_d`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " UNION SELECT *, 1 AS `ctg_div`, 1 AS `division` FROM `w_sa_e`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " UNION SELECT *, 1 AS `ctg_div`, 1 AS `division` FROM `w_sa_c`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " UNION SELECT *, 2 AS `ctg_div`, 0 AS `division` FROM `w_sa_c`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " UNION SELECT *, 2 AS `ctg_div`, 0 AS `division` FROM `w_sa_f`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " UNION SELECT *, 2 AS `ctg_div`, 1 AS `division` FROM `w_sa_g`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " UNION SELECT *, 2 AS `ctg_div`, 0 AS `division` FROM `w_sa_h`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 ;
            $ans = $this->query($qry);

            // 決算（貸借借方）
            $qry = "INSERT INTO `w_sa_bsd`"
                 . " SELECT *"
                 . " FROM `w_sa_rslt`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `ctg_div` = 1 AND"
                 .   " `division` = 0"
                 . " ORDER BY"
                 .   " `bid`, `m`, `account_cd`"
                 ;
            $ans = $this->query($qry);

            // 決算（貸借貸方）
            $qry = "INSERT INTO `w_sa_bsc`"
                 . " SELECT *"
                 . " FROM `w_sa_rslt`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `ctg_div` = 1 AND"
                 .   " `division` = 1"
                 . " ORDER BY"
                 .   " `bid`, `m`, `account_cd`"
                 ;
            $ans = $this->query($qry);

            // 決算（損益借方）
            $qry = "INSERT INTO `w_sa_pld`"
                 . " SELECT *"
                 . " FROM `w_sa_rslt`"
                 . " WHERE"
                 .   " `bid` = '" . $this->esc($this->bid) . "' AND"
                 .   " `ctg_div` = 2 AND"
                 .   " `division` = 0"
                 . " ORDER BY"
                 .   " bid, m, account_cd"
                 ;
            $ans = $this->query($qry);

            // 決算（損益貸方）
            $qry = "INSERT INTO `w_sa_plc`"
                 . " SELECT *"
                 . " FROM w_sa_rslt"
                 . " WHERE"
                 .   " bid = '" . $this->esc($this->bid) . "' AND"
                 .   " ctg_div = 2 AND"
                 .   " division = 1"
                 . " ORDER BY"
                 .   " bid, m, account_cd"
                 ;
            $ans = $this->query($qry);

            // 年度末
            $qry = "INSERT INTO `w_efy`"
                 . " SELECT `bid`, MAX(m) AS `m`"
                 . " FROM `w_sa_rslt`"
                 . " WHERE `bid` = '" . $this->esc($this->bid) . "'"
                 . " GROUP BY `bid`"
                 ;
            $ans = $this->query($qry);

            // 計算
            $qry = "INSERT INTO `w_calc` ("
                 .   "`bid`"
                 . ", `last`"
                 . ") VALUES ("
                 .   "'" . $this->esc($this->bid) . "'"
                 . ", '" . date('Y-m-d H:i:s') . "'"
                 . ")"
                 ;
            $ans = $this->query($qry);

        } catch(Exception $e) {
            throw $e;
        }
    }

    public function setTsvSlip($basic, $tsvfn) {
        $rec  = $this->_setTsvSlip_getRec();
        $cnt  = count($rec);
        $name = $basic['disp_name'];
        $era  = $basic['era']['abbr'];
        $tsv  = "title\t仕訳帳\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "field\t連番\t伝票番号\t部門ID\t部門名\t参考\t伝票日付\t行番号\t借方科目\t貸方科目\t借方科目名\t貸方科目名\t借方勘定科目\t貸方勘定科目\t借方金額\t貸方金額\t金額\t摘要\t備考\t決算フラグ\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                                  . "\t"
                 . $this->texStr($rec[$i]['id'])             . "\t"
                 . $this->texStr($rec[$i]['scd'])            . "\t"
                 . $this->texStr($rec[$i]['name'])           . "\t"
                 . $this->texStr($rec[$i]['ymd'])            . "\t"
                 . $this->texStr($rec[$i]['line'])           . "\t"
                 . $this->texStr($rec[$i]['debit'])          . "\t"
                 . $this->texStr($rec[$i]['credit'])         . "\t"
                 . $this->texStr($rec[$i]['debit_name'])     . "\t"
                 . $this->texStr($rec[$i]['credit_name'])    . "\t"
                 . $this->texStr($rec[$i]['debit_account'])  . "\t"
                 . $this->texStr($rec[$i]['credit_account']) . "\t"
                 . $this->texStr($rec[$i]['debit_amount'])   . "\t"
                 . $this->texStr($rec[$i]['credit_amount'])  . "\t"
                 . $this->texStr($rec[$i]['amount'])         . "\t"
                 . $this->texStr($rec[$i]['remark'])         . "\t"
                 . $this->texStr($rec[$i]['settled_flg'])    . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvSlip_getRec() {

        $this->connect();

        $fmt = "SELECT
            `sl`.`id`             AS `id`,             -- 伝票番号
            `sl`.`scd`            AS `scd`,            -- 部門ID
            `se`.`name`           AS `name`,           -- 部門名
            `sl`.`ymd`            AS `ymd`,            -- 伝票日付
            `sl`.`line`           AS `line`,           -- 行番号
            `sl`.`debit`          AS `debit`,          -- 借方科目
            `sl`.`credit`         AS `credit`,         -- 貸方科目
            `sl`.`debit_name`     AS `debit_name`,     -- 借方科目名
            `sl`.`credit_name`    AS `credit_name`,    -- 貸方科目名
            `sl`.`debit_account`  AS `debit_account`,  -- 借方勘定科目
            `sl`.`credit_account` AS `credit_account`, -- 貸方勘定科目
            `sl`.`debit_amount`   AS `debit_amount`,   -- 借方金額
            `sl`.`credit_amount`  AS `credit_amount`,  -- 貸方金額
            `sl`.`amount`         AS `amount`,         -- 金額
            `sl`.`remark`         AS `remark`,         -- 摘要
            `sl`.`settled_flg`    AS `settled_flg`     -- 決算フラグ
        FROM
            `w_slip`               `sl`
            INNER JOIN `t_section` `se` ON `sl`.`scd` = `se`.`id`
        WHERE
            `sl`.`bid` = '%s' AND `se`.`bid` = '%s'
        ORDER BY
            `sl`.`ymd`, `sl`.`scd`, `sl`.`id`, `sl`.`line`
        ";
        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    public function setTsvLedger($basic, $tsvfn) {
        $rec  = $this->_setTsvLedger_getRec();
        $cnt  = count($rec);
        $name = $basic['disp_name'];
        $era  = $basic['era']['abbr'];
        $tsv  = "title\t総勘定元帳\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "field\t連番\t年月度\t日付\t摘要\t科目\t相手方科目\t借方\t貸方\t差引残高\t科目名\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                           . "\t"
                 . $this->texStr($rec[$i]['m'])       . "\t"
                 . $this->texStr($rec[$i]['mmdd'])    . "\t"
                 . $this->texStr($rec[$i]['memo'])    . "\t"
                 . $this->texStr($rec[$i]['item'])    . "\t"
                 . $this->texStr($rec[$i]['other'])   . "\t"
                 . $this->texStr($rec[$i]['amount0']) . "\t"
                 . $this->texStr($rec[$i]['amount1']) . "\t"
                 . $this->texStr($rec[$i]['remain'])  . "\t"
                 . $this->texStr($rec[$i]['name'])    . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvLedger_getRec() {

        $this->connect();

        $fmt = "SELECT
            `l`.`m`         AS `m`,       -- 年月度
            `l`.`mmdd`      AS `mmdd`,    -- 日付
            `l`.`memo`      AS `memo`,    -- 摘要
            `l`.`item`      AS `item`,    -- 科目
            `l`.`other`     AS `other`,   -- 相手方科目
            `l`.`amount0`   AS `amount0`, -- 借方
            `l`.`amount1`   AS `amount1`, -- 貸方
            `l`.`remain`    AS `remain`,  -- 差引残高
            `i`.`name`      AS `name`     -- 科目名
        FROM
            `w_ledger` `l` INNER JOIN `t_item` `i` ON `l`.`item` = `i`.`kcd`
        WHERE
            `l`.`bid` = '%s' AND `i`.`bid` = '%s'
        ORDER BY
            `l`.`item`, `l`.`ymd`, `l`.`other`
        ";
        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    public function setTsvTbDetail($basic, $tsvfn) {
        $rec  = $this->_setTsvTbDetail_getRec();
        $cnt  = count($rec);
        $name = $basic['disp_name'];
        $era  = $basic['era']['abbr'];
        $bymd = str_replace('-', '', $basic['term_begin']);
        $tsv  = "title\t試算表（詳細）\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "bymd\t" . $bymd . "\n"
              . "field\t連番\t年月度\t月度\t勘定分類コード\t貸借区分\t勘定科目コード\t借方残高\t借方月度合計\t個別科目名\t貸方月度合計\t貸方残高\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                                 . "\t"
                 . $this->texStr($rec[$i]['m'])             . "\t"
                 . $this->texStr($rec[$i]['mm'])            . "\t"
                 . $this->texStr($rec[$i]['ctg_div'])       . "\t"
                 . $this->texStr($rec[$i]['division'])      . "\t"
                 . $this->texStr($rec[$i]['item'])          . "\t"
                 . $this->texStr($rec[$i]['debit_remain'])  . "\t"
                 . $this->texStr($rec[$i]['debit_sum'])     . "\t"
                 . $this->texStr($rec[$i]['name'])          . "\t"
                 . $this->texStr($rec[$i]['credit_sum'])    . "\t"
                 . $this->texStr($rec[$i]['credit_remain']) . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvTbDetail_getRec() {

        $this->connect();

        $fmt = "SELECT
            `m`,            -- 年月度
            `mm`,           -- 月度
            `ctg_div`,      -- 勘定分類コード
            `division`,     -- 貸借区分
            `item`,         -- 勘定科目コード
            `debit_remain`, -- 借方残高
            `debit_sum`,    -- 借方月度合計
            `name`,         -- 個別科目名
            `credit_sum`,   -- 貸方月度合計
            `credit_remain` -- 貸方残高
        FROM
            `w_tb_rslt1`
        WHERE
            `bid` = '%s'
        ORDER BY
            `m`, `ctg_div`, `item`
        ";
        $sql = sprintf($fmt, $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    public function setTsvTb($basic, $tsvfn) {
        $rec  = $this->_setTsvTb_getRec();
        $cnt  = count($rec);
        $name = $basic['disp_name'];
        $era  = $basic['era']['abbr'];
        $bymd = str_replace('-', '', $basic['term_begin']);

        $tsv  = "title\t試算表\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "bymd\t" . $bymd . "\n"
              . "field\t連番\t年月度\t月度\t勘定分類コード\t貸借区分\t分類コード\t勘定科目\t借方残高\t借方月度合計\t個別科目名\t貸方月度合計\t貸方残高\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                                 . "\t"
                 . $this->texStr($rec[$i]['m'])             . "\t"
                 . $this->texStr($rec[$i]['mm'])            . "\t"
                 . $this->texStr($rec[$i]['ctg_div'])       . "\t"
                 . $this->texStr($rec[$i]['division'])      . "\t"
                 . $this->texStr($rec[$i]['ccd'])           . "\t"
                 . $this->texStr($rec[$i]['item'])          . "\t"
                 . $this->texStr($rec[$i]['debit_remain'])  . "\t"
                 . $this->texStr($rec[$i]['debit_sum'])     . "\t"
                 . $this->texStr($rec[$i]['name'])          . "\t"
                 . $this->texStr($rec[$i]['credit_sum'])    . "\t"
                 . $this->texStr($rec[$i]['credit_remain']) . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvTb_getRec() {

        $this->connect();

        $fmt = "SELECT
            `t`.`m`,            -- 年月度
            `t`.`mm`,           -- 月度
            `t`.`ctg_div`,      -- 勘定分類コード
            `t`.`division`,     -- 貸借区分
            `t`.`ccd`,          -- 分類コード
            `t`.`item`,         -- 勘定科目
            `t`.`debit_remain`, -- 借方残高
            `t`.`debit_sum`,    -- 借方月度合計
            `a`.`name`,         -- 個別科目名
            `t`.`credit_sum`,   -- 貸方月度合計
            `t`.`credit_remain` -- 貸方残高
        FROM
            `w_tb_rslt2`           `t`
            INNER JOIN `t_account` `a`
            ON `t`.`ccd` = `a`.`ccd` AND `t`.`item` = `a`.`item`
        WHERE
            `t`.`bid` = '%s' AND `a`.`bid` = '%s'
        ORDER BY
            `t`.`m`, `t`.`ctg_div`, `t`.`ccd`
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    public function setTsvPl($basic, $tsvfn) {
        $rec  = $this->_setTsvPl_getRec();
        $cnt  = count($rec);
        $name = $basic['disp_name'];
        $era  = $basic['era']['abbr'];
        $bymd = str_replace('-', '', $basic['term_begin']);

        $tsv  = "title\t損益計算書\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "bymd\t" . $bymd . "\n"
              . "field\t連番\t年月度\t月度\t勘定科目コード\t勘定科目名\t残高\t範疇\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                              . "\t"
                 . $this->texStr($rec[$i]['m'])          . "\t"
                 . $this->texStr($rec[$i]['mm'])         . "\t"
                 . $this->texStr($rec[$i]['account_cd']) . "\t"
                 . $this->texStr($rec[$i]['name'])       . "\t"
                 . $this->texStr($rec[$i]['remain'])     . "\t"
                 . $this->texStr($rec[$i]['division'])   . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvPl_getRec() {

        $this->connect();

        $fmt = "SELECT
            `m`,          -- 年月度
            `mm`,         -- 月度
            `account_cd`, -- 勘定科目コード
            `name`,       -- 勘定科目名
            `remain`,     -- 残高
            `division`    -- 範疇
        FROM
            `w_sa_rslt`
        WHERE
            `bid` = '%s' AND `ctg_div` = 2
        ORDER BY
            `m`, `account_cd`
        ";

        $sql = sprintf($fmt, $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    public function setTsvBs($basic, $tsvfn) {
        $rec  = $this->_setTsvBs_getRec();
        $cnt  = count($rec);
        $name = $basic['disp_name'];
        $era  = $basic['era']['abbr'];
        $bymd = str_replace('-', '', $basic['term_begin']);

        $tsv  = "title\t貸借対照表\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "bymd\t" . $bymd . "\n"
              . "field\t連番\t年月度\t月度\t勘定科目コード\t勘定科目名\t残高\t範疇\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                              . "\t"
                 . $this->texStr($rec[$i]['m'])          . "\t"
                 . $this->texStr($rec[$i]['mm'])         . "\t"
                 . $this->texStr($rec[$i]['account_cd']) . "\t"
                 . $this->texStr($rec[$i]['name'])       . "\t"
                 . $this->texStr($rec[$i]['remain'])     . "\t"
                 . $this->texStr($rec[$i]['division'])   . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvBs_getRec() {

        $this->connect();

        $fmt = "SELECT
            `m`,          -- 年月度
            `mm`,         -- 月度
            `account_cd`, -- 勘定科目コード
            `name`,       -- 勘定科目名
            `remain`,     -- 残高
            `division`    -- 範疇
        FROM
            `w_sa_rslt`
        WHERE
            `bid` = '%s' AND `ctg_div` = 1
        ORDER BY
            `m`, `account_cd`
        ";

        $sql = sprintf($fmt, $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    public function setTsvPls($basic, $tsvfn) {

        $rec   = $this->_setTsvPls_getRec();       // 損益計算書（決算）
        $cnt   = count($rec);
        $name  = $basic['disp_name'];
        $era   = $basic['era']['abbr'];
        $bymd  = str_replace('-', '', $basic['term_begin']);
        $ty    = $basic['term_year'];

        $bps   = $this->_setTsvPls_getBps();       // 期首製品棚卸高金額
        $eps   = $this->_setTsvPls_getEps();       // 期末製品棚卸高金額
        $bgs   = $this->_setTsvPls_getBgs();       // 期首商品棚卸高金額
        $egs   = $this->_setTsvPls_getEgs();       // 期末商品棚卸高金額
        $pcost = $this->_setTsvPls_getPcost($rec); // 当期製品製造原価金額

        $tsv  = "title\t損益計算書（決算）\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "bymd\t" . $bymd . "\n"
              . "ty\t" . $ty . "\n"
              . "bps\t" . $bps . "\n"
              . "eps\t" . $eps . "\n"
              . "bgs\t" . $bgs . "\n"
              . "egs\t" . $egs . "\n"
              . "pcost\t" . $pcost . "\n"
              . "field\t連番\t年月度\t月度\t勘定科目コード\t勘定科目名\t残高\t範疇\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                              . "\t"
                 . $this->texStr($rec[$i]['m'])          . "\t"
                 . $this->texStr($rec[$i]['mm'])         . "\t"
                 . $this->texStr($rec[$i]['account_cd']) . "\t"
                 . $this->texStr($rec[$i]['name'])       . "\t"
                 . $this->texStr($rec[$i]['remain'])     . "\t"
                 . $this->texStr($rec[$i]['division'])   . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvPls_getRec() {

        $this->connect();

        // 損益計算書（決算）
        $fmt = "SELECT
            `m`,          -- 年月度
            `mm`,         -- 月度
            `account_cd`, -- 勘定科目コード
            `name`,       -- 勘定科目名
            `remain`,     -- 残高
            `division`    -- 範疇
        FROM
            `w_sa_rslt`
        WHERE
            `bid`     = '%s' AND
            `ctg_div` = 2    AND
            `m`       = (SELECT MAX(`m`) FROM `w_sa_rslt` WHERE `bid` = '%s')
        ORDER BY
            `m`, `account_cd`
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    private function _setTsvPls_getBps() {

        $this->connect();

        // 期首製品棚卸高金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'            AND
            `j`.`not_use_flg` IS FALSE  AND
            `s`.`debit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '期首製品棚卸高')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvPls_getEps() {

        $this->connect();

        // 期末製品棚卸高金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'           AND
            `j`.`not_use_flg` IS FALSE AND
            `s`.`credit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '期末製品棚卸高')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvPls_getBgs() {

        $this->connect();

        // 期首商品棚卸高金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'           AND
            `j`.`not_use_flg` IS FALSE AND
            `s`.`debit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '期首商品棚卸高')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvPls_getEgs() {

        $this->connect();

        // 期末商品棚卸高金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'           AND
            `j`.`not_use_flg` IS FALSE AND
            `s`.`credit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = %s AND `name` = '期末商品棚卸高')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvPls_getPcost($rec) {

        // 当期製品製造原価金額
        $ans = 0;
        $cnt = count($rec);
        for ($i = 0; $i < $cnt; $i++)
            if (substr($rec[$i]["account_cd"], 0, 4) == "8230")
                $ans = $rec[$i]["remain"];

        return $ans;
    }

    public function setTsvBss($basic, $tsvfn) {

        $rec    = $this->_setTsvBss_getRec();        // 貸借対照表（決算）
        $cnt    = count($rec);
        $name   = $basic['disp_name'];
        $era    = $basic['era']['abbr'];
        $bymd   = str_replace('-', '', $basic['term_begin']);
        $ty     = $basic['term_year'];

        $tax1   = $this->_setTsvBss_getTax1();       // 未払法人税等金額
        $tax2   = $this->_setTsvBss_getTax2();       // 未払消費税金額
        $pprof  = $this->_setTsvBss_getPprof();      // 前期繰越利益金額
        $ploss  = $this->_setTsvBss_getPloss();      // 前期繰越損失金額
        $profit = $this->_setTsvBss_getProfit($rec); // 当期利益

        $tsv  = "title\t貸借対照表（決算）\n"
              . "name\t" . $name . "\n"
              . "era\t" . $era . "\n"
              . "bymd\t" . $bymd . "\n"
              . "ty\t" . $ty . "\n"
              . "tax1\t" . $tax1 . "\n"
              . "tax2\t" . $tax2 . "\n"
              . "pprof\t" . $pprof . "\n"
              . "ploss\t" . $ploss . "\n"
              . "profit\t" . $profit . "\n"
              . "field\t連番\t年月度\t月度\t勘定科目コード\t勘定科目名\t残高\t範疇\n"
              . "rows\t" . $cnt . "\n"
              ;

        for ($i = 0; $i < $cnt; $i++)
        {
            $tsv .= "data\t"
                 . ($i + 1)                              . "\t"
                 . $this->texStr($rec[$i]['m'])          . "\t"
                 . $this->texStr($rec[$i]['mm'])         . "\t"
                 . $this->texStr($rec[$i]['account_cd']) . "\t"
                 . $this->texStr($rec[$i]['name'])       . "\t"
                 . $this->texStr($rec[$i]['remain'])     . "\t"
                 . $this->texStr($rec[$i]['division'])   . "\n"
                 ;
        }
        $tsv .= "eod\n";
        file_put_contents($tsvfn, $tsv);
    }

    private function _setTsvBss_getRec() {

        $this->connect();

        // 貸借対照表（決算）
        $fmt = "SELECT
            `m`,          -- 年月度
            `mm`,         -- 月度
            `account_cd`, -- 勘定科目コード
            `name`,       -- 勘定科目名
            `remain`,     -- 残高
            `division`    -- 範疇
        FROM
            `w_sa_rslt`
        WHERE
            `bid` = '%s'  AND
            `ctg_div` = 1 AND
            `m` = (SELECT MAX(`m`) FROM `w_sa_rslt` WHERE `bid` = '%s')
        ORDER BY
            `m`, `account_cd`
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();
        return $rec;
    }

    private function _setTsvBss_getTax1() {

        $this->connect();

        // 未払法人税等金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'      AND
            `j`.`not_use_flg` IS FALSE  AND
            `s`.`debit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '未払法人税等')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvBss_getTax2() {

        $this->connect();

        // 未払消費税金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'      AND
            `j`.`not_use_flg` IS FALSE  AND
            `s`.`debit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '未払消費税')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvBss_getPprof() {

        $this->connect();

        // 前期繰越利益金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'      AND
            `j`.`not_use_flg` IS FALSE  AND
            `s`.`credit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '前期繰越利益')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvBss_getPloss() {

        $this->connect();

        // 前期繰越損失金額
        $fmt = "SELECT
            `amount`
        FROM
            `t_journal`          `j`
            INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`
        WHERE
            `j`.`bid` = '%s'           AND
            `j`.`not_use_flg` IS FALSE AND
            `s`.`credit` = (SELECT `kcd` FROM `t_item` WHERE `bid` = '%s' AND `name` = '前期繰越損失')
        ";

        $sql = sprintf($fmt, $this->esc($this->bid), $this->esc($this->bid));
        $rec = $this->getRecord($sql);

        $this->close();

        if (empty($rec)) {
            $ans = 0;
        } else {
            $ans = $rec[0]['amount'];
        }

        return $ans;
    }

    private function _setTsvBss_getProfit($rec) {

        $ans = 0;
        $cnt = count($rec);
        for ($i = 0; $i < $cnt; $i++)
            if ($rec[$i]['name'] == '当期利益')
                $ans = $rec[$i]['remain'];

        return $ans;
    }
}

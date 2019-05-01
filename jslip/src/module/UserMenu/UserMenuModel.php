<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class UserMenuModel extends Model
{
    public function getLast($bid) {

        $this->connect();

        $sql = "SELECT `name`, `term_year` FROM `t_basic` wHERE `id` = '" . $this->esc($bid) . "'";
        $rec = $this->getRecord($sql);

        $name      = (empty($rec[0]['name'])) ? '' : $rec[0]['name'];
        $term_year = (empty($rec[0]['term_year'])) ? -1 : $rec[0]['term_year'] * 1;
        $last_year = $term_year - 1;

        $sql =  "SELECT `id` FROM `t_basic`"
             . " wHERE `name` = '" . $this->esc($name) . "'"
             .   " AND `term_year` = '" . $this->esc($last_year) . "'"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        return (empty($rec[0]['id'])) ? -1 : $rec[0]['id'];
    }

    public function useLast($mid, $src, $dst) {

        $err = [];

        $this->connect();
        $this->begin();

        try {

            $this->_useSection($mid, $src, $dst);
            $this->_useAccount($mid, $src, $dst);
            $this->_useItem($mid, $src, $dst);

        } catch(Exception $e) {
            $err['msg'] = $e->getMessage();
        }

        if (empty($err)) {
            $this->commit();
        } else {
            $this->rollback();
        }

        $this->close();

        return $err;
    }

    private function _useSection($mid, $src, $dst) {

        try {

            $s = "SELECT * FROM `t_section` WHERE `bid` = '%s' ORDER BY `id`";
            $r = $this->getRecord(sprintf($s, $this->esc($src)));

            $sql = "DELETE FROM `t_section` WHERE `bid` = '" . $this->esc($dst) . "'";
            $ans = $this->query($sql);

            foreach ($r as $d) {

                $kana = $d['kana'];
                $name = $d['name'];

                $sql =  "INSERT INTO `t_section`"
                     . " (`bid`, `kana`, `name`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($dst) . "'"
                     . ", '" . $this->esc($kana) . "'"
                     . ", '" . $this->esc($name) . "'"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _useAccount($mid, $src, $dst) {

        try {

            $s = "SELECT * FROM `t_account` WHERE `bid` = '%s' AND `delete_flg` is TRUE ORDER BY `id`";
            $r = $this->getRecord(sprintf($s, $this->esc($src)));

            $sql =  "DELETE FROM `t_account`"
                 . " WHERE `bid` = '" . $this->esc($dst) . "'"
                 .   " AND `delete_flg` = TRUE"
                 ;
            $ans = $this->query($sql);

            foreach ($r as $d) {

                $ccd      = $d['ccd'];
                $item     = $d['item'];
                $item_ccd = $d['item_ccd'];
                $division = $d['division'];
                $kana     = $d['kana'];
                $name     = $d['name'];

                $sql =  "INSERT INTO `t_account`"
                     . " (`bid`, `ccd`, `item`, `item_ccd`, `division`, `kana`, `name`, `delete_flg`, `edit_flg`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($dst) . "'"
                     . ", '" . $this->esc($ccd) . "'"
                     . ", '" . $this->esc($item) . "'"
                     . ", '" . $this->esc($item_ccd) . "'"
                     . ", '" . $this->esc($division) . "'"
                     . ", '" . $this->esc($kana) . "'"
                     . ", '" . $this->esc($name) . "'"
                     . ", TRUE"
                     . ", TRUE"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _useItem($mid, $src, $dst) {

        try {

            $s = "SELECT * FROM `t_item` WHERE `bid` = '%s' AND `delete_flg` is TRUE ORDER BY `id`";
            $r = $this->getRecord(sprintf($s, $this->esc($src)));

            $sql =  "DELETE FROM `t_item`"
                 . " WHERE `bid` = '" . $this->esc($dst) . "'"
                 .   " AND `delete_flg` = TRUE"
                 ;
            $ans = $this->query($sql);

            foreach ($r as $d) {

                $kcd  = $d['kcd'];
                $kana = $d['kana'];
                $name = $d['name'];

                $ccd     = $d['ccd'];
                $account = $d['account'];
                $item    = $d['item'];

                $sql =  "INSERT INTO `t_item`"
                     . " (`bid`, `kcd`, `ccd`, `account`, `item`, `kana`, `name`, `valid_flg`, `delete_flg`, `edit_flg`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($dst) . "'"
                     . ", '" . $this->esc($kcd) . "'"
                     . ", '" . $this->esc($ccd) . "'"
                     . ", '" . $this->esc($account) . "'"
                     . ", '" . $this->esc($item) . "'"
                     . ", '" . $this->esc($kana) . "'"
                     . ", '" . $this->esc($name) . "'"
                     . ", TRUE"
                     . ", TRUE"
                     . ", TRUE"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    public function cntJournal($bid) {

        $this->connect();
        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_journal` wHERE `bid` = '" . $this->esc($bid) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec[0]['cnt'] * 1;
    }

    public function getBasicByMid($mid) {

        $this->connect();
        $sql =  "SELECT"
             . " `id`, `name`, `disp_name`, `term_year`, `term_begin`, `term_end`"
             . " FROM `t_basic`"
             . " WHERE `valid_flg` = TRUE"
             . " AND `mid` = '" . $this->esc($mid) . "'"
             . " ORDER BY `term_year` DESC, `term_begin` DESC, `disp_name` ASC"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        if (empty($rec[0])) {
            return $rec;
        }

        $rec[0]['era'] = $this->era($rec[0]['id'], $rec[0]['term_begin']);

        return $rec;
    }

    public function makeCsv($bid, $fname) {

        $d = [];
        $n = 0;
        $f = new SplFileObject($fname, 'w');

        $this->connect();

        // t_basic

        $s = "SELECT `term_year`, `term_begin`, `term_end` FROM `t_basic` WHERE `id` = '%s'";
        $r = $this->getRecord(sprintf($s, $this->esc($bid)));

        $d[$n++] = ['HEADER', 'term_year', 'term_begin', 'term_end'];
        $d[$n++] = ['BASIC', $r[0]['term_year'], $r[0]['term_begin'], $r[0]['term_end']]; 

        // t_section

        $s = "SELECT `id`, `kana`, `name` FROM `t_section` WHERE `bid` = '%s'";
        $r = $this->getRecord(sprintf($s, $this->esc($bid)));

        $sect = [];
        foreach ($r as $v) {
            $sect[$v['id']] = $v['name'];
        }

        $d[$n++] = ['HEADER', 'kana', 'name'];
        if (empty($r)) {
            $d[$n++] = ['SECTION', '--none--'];
        } else {
            foreach ($r as $v) {
                $d[$n++] = ['SECTION', $v['kana'], $v['name']];
            }
        }

        // t_account

        $s =  "SELECT `ccd`, `item`, `item_ccd`, `division`, `kana`, `name` FROM `t_account`"
           . " WHERE `bid` = '%s' AND `delete_flg` = 1";
        $r = $this->getRecord(sprintf($s, $this->esc($bid)));

        $d[$n++] = ['HEADER', 'ccd', 'item', 'item_ccd', 'division', 'kana', 'name'];
        if (empty($r)) {
            $d[$n++] = ['ACCOUNT', '--none--'];
        } else {
            foreach ($r as $v) {
                $d[$n++] = ['ACCOUNT', $v['ccd'], $v['item'], $v['item_ccd'], $v['division'], $v['kana'], $v['name']];
            }
        }

        // t_item

        $s = "SELECT `kcd`, `kana`, `name` FROM `t_item` WHERE `bid` = '%s' AND `delete_flg` = 1";
        $r = $this->getRecord(sprintf($s, $this->esc($bid)));

        $d[$n++] = ['HEADER', 'kcd', 'kana', 'name'];
        if (empty($r)) {
            $d[$n++] = ['ITEM', '--none--'];
        } else {
            foreach ($r as $v) {
                $d[$n++] = ['ITEM', $v['kcd'], $v['kana'], $v['name']];
            }
        }

        // t_journal, t_jslip

        $s =  "SELECT `j`.`id`, `j`.`scd`, `j`.`settled_flg` AS `flg`, `j`.`ymd`,"
           .        " `s`.`line`, `s`.`debit`, `s`.`credit`, `s`.`amount`, `s`.`remark`"
           . " FROM `t_journal` `j` INNER JOIN `t_jslip` `s` ON `j`.`id` = `s`.`jid`"
           . " WHERE `j`.`bid` = '%s'"
           . " ORDER BY `j`.`scd`, `j`.`settled_flg`, `j`.`ymd`, `j`.`id`, `s`.`line`"
           ;
        $r = $this->getRecord(sprintf($s, $this->esc($bid)));

        $d[$n++] = ['HEADER', 'id', 'scd', 'flg', 'ymd', 'line', 'debit', 'credit', 'amount', 'remark'];
        if (empty($r)) {
            $d[$n++] = ['SLIP', '--none--'];
        } else {
            foreach ($r as $v) {
                $d[$n++] = ['SLIP', $v['id'], $sect[$v['scd']], $v['flg'], $v['ymd'], $v['line'], $v['debit'], $v['credit'], $v['amount'], $v['remark']];
            }
        }

        // Make a CSV file.

        foreach ($d as $line) {
            $f->fputcsv($line);
        }

        $this->close();
    }

    public function setImportedCsvData($mid, $bid, $dat) {

        $err     = [];

        $h       = [];
        $basic   = [];
        $section = [];
        $account = [];
        $item    = [];
        $slip    = [];

        foreach ($dat as $d) {
           switch ($d[0]) {
               case 'HEADER':
                   $h = $d;
                   break;
               case 'BASIC':
                   $basic = [$h[1] => $d[1], $h[2] => $d[2], $h[3] => $d[3]];
                   break;
               case 'SECTION':
                   if ($d[1] != '--none--') {
                       $section[] = [$h[1] => $d[1], $h[2] => $d[2]];
                   }
                   break;
               case 'ACCOUNT':
                   if ($d[1] != '--none--') {
                       $account[] = [$h[1] => $d[1], $h[2] => $d[2], $h[3] => $d[3], $h[4] => $d[4], $h[5] => $d[5], $h[6] => $d[6]];
                   }
                   break;
               case 'ITEM':
                   if ($d[1] != '--none--') {
                       $item[] = [$h[1] => $d[1], $h[2] => $d[2], $h[3] => $d[3]];
                   }
                   break;
               case 'SLIP':
                   if ($d[1] != '--none--') {
                       $slip[] = [$h[1] => $d[1], $h[2] => $d[2], $h[3] => $d[3], $h[4] => $d[4], $h[5] => $d[5], $h[6] => $d[6], $h[7] => $d[7], $h[8] => $d[8], $h[9] => $d[9]];
                   }
                   break;
           }
        }

        if (empty($err)) {
            $err = $this->_updImportedData($mid, $bid, $basic, $section, $account, $item, $slip);
        }

        return $err;
    }

    private function _updImportedData($mid, $bid, $basic, $section, $account, $item, $slip) {

        $err = [];

        $this->connect();
        $this->begin();

        try {

            if (!empty($basic))   $this->_updBasic($bid, $basic);
            if (!empty($section)) $this->_updSection($mid, $bid, $section);
            if (!empty($account)) $this->_updAccount($mid, $bid, $account);
            if (!empty($item))    $this->_updItem($mid, $bid, $item);
            if (!empty($slip))    $this->_updSlip($mid, $bid, $slip);

        } catch(Exception $e) {
            $err['SQL'] = $e->getMessage();
        }

        if (empty($err)) {
            $this->commit();
        } else {
            $this->rollback();
        }

        $this->close();

        return $err;
    }

    private function _updBasic($bid, $dat) {

        try {

            $sql =  "UPDATE `t_basic` SET"
                 .  " `term_year`  = '" . $this->esc($dat['term_year']) . "'"
                 . ", `term_begin` = '" . $this->esc($dat['term_begin']) . "'"
                 . ", `term_end`   = '" . $this->esc($dat['term_end']) . "'"
                 . " WHERE `id` = '" . $this->esc($bid) . "'"
                 ;
            $ans = $this->query($sql);

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _updSection($mid, $bid, $dat) {

        try {
            $sql = "DELETE FROM `t_section` WHERE `bid` = '" . $this->esc($bid) . "'";
            $ans = $this->query($sql);

            foreach ($dat as $d) {

                $kana = $d['kana'];
                $name = $d['name'];

                $sql =  "INSERT INTO `t_section`"
                     . " (`bid`, `kana`, `name`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($bid) . "'"
                     . ", '" . $this->esc($kana) . "'"
                     . ", '" . $this->esc($name) . "'"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _updAccount($mid, $bid, $dat) {

        try {

            $sql =  "DELETE FROM `t_account`"
                 . " WHERE `bid` = '" . $this->esc($bid) . "'"
                 .   " AND `delete_flg` = TRUE"
                 ;
            $ans = $this->query($sql);

            foreach ($dat as $d) {

                $ccd      = $d['ccd'];
                $item     = $d['item'];
                $item_ccd = $d['item_ccd'];
                $division = $d['division'];
                $kana     = $d['kana'];
                $name     = $d['name'];

                $sql =  "INSERT INTO `t_account`"
                     . " (`bid`, `ccd`, `item`, `item_ccd`, `division`, `kana`, `name`, `delete_flg`, `edit_flg`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($bid) . "'"
                     . ", '" . $this->esc($ccd) . "'"
                     . ", '" . $this->esc($item) . "'"
                     . ", '" . $this->esc($item_ccd) . "'"
                     . ", '" . $this->esc($division) . "'"
                     . ", '" . $this->esc($kana) . "'"
                     . ", '" . $this->esc($name) . "'"
                     . ", TRUE"
                     . ", TRUE"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _updItem($mid, $bid, $dat) {

        try {
            $sql =  "DELETE FROM `t_item`"
                 . " WHERE `bid` = '" . $this->esc($bid) . "'"
                 .   " AND `delete_flg` = TRUE"
                 ;
            $ans = $this->query($sql);

            foreach ($dat as $d) {

                $kcd  = $d['kcd'];
                $kana = $d['kana'];
                $name = $d['name'];

                $ccd     = substr($kcd, 0, 4) * 1;
                $account = substr($kcd, 4, 2) * 1;
                $item    = substr($kcd, 6, 2) * 1;

                $sql =  "INSERT INTO `t_item`"
                     . " (`bid`, `kcd`, `ccd`, `account`, `item`, `kana`, `name`, `valid_flg`, `delete_flg`, `edit_flg`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($bid) . "'"
                     . ", '" . $this->esc($kcd) . "'"
                     . ", '" . $this->esc($ccd) . "'"
                     . ", '" . $this->esc($account) . "'"
                     . ", '" . $this->esc($item) . "'"
                     . ", '" . $this->esc($kana) . "'"
                     . ", '" . $this->esc($name) . "'"
                     . ", TRUE"
                     . ", TRUE"
                     . ", TRUE"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }

    private function _updSlip($mid, $bid, $dat) {

        try {

            $sql = "SELECT `id`, `name` FROM `t_section` WHERE `bid` = '" . $this->esc($bid) . "'";
            $rec = $this->getRecord($sql);

            $scd = [];
            foreach ($rec as $r) {
                $scd[$r['name']] = $r['id'];
            }

            $sql = "SELECT `id` FROM `t_journal` WHERE `bid` = '" . $this->esc($bid) . "'";
            $rec = $this->getRecord($sql);

            foreach ($rec as $r) {
                $sql = "DELETE FROM `t_jslip` WHERE `jid` = '" . $this->esc($r['id']) . "'";
                $ans = $this->query($sql);
                $sql = "DELETE FROM `t_journal` WHERE `id` = '" . $this->esc($r['id']) . "'";
                $ans = $this->query($sql);
            }

            $j = [];
            foreach ($dat as $d) {
                $j[$d['id'] * 1] = [
                    'scd' => $d['scd'],
                    'ymd' => $d['ymd'],
                    'flg' => $d['flg'],
                ];
            }

            $jid = [];
            foreach ($j as $k => $d) {
                $sql =  "INSERT INTO `t_journal`"
                     . " (`bid`, `scd`, `ymd`, `settled_flg`, `not_use_flg`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($bid) . "'"
                     . ", '" . $this->esc($scd[$d['scd']]) . "'"
                     . ", '" . $this->esc($d['ymd']) . "'"
                     . ", '" . $this->esc($d['flg']) . "'"
                     . ", 0"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);

                $jid[$k] = $this->insert_id();
            }

            foreach ($dat as $d) {
                $sql =  "INSERT INTO `t_jslip`"
                     . " (`jid`, `line`, `debit`, `credit`, `amount`, `remark`, `update_person`)"
                     . " VALUES"
                     . " ('" . $this->esc($jid[$d['id'] * 1]) . "'"
                     . ", '" . $this->esc($d['line']) . "'"
                     . ", '" . $this->esc($d['debit']) . "'"
                     . ", '" . $this->esc($d['credit']) . "'"
                     . ", '" . $this->esc($d['amount']) . "'"
                     . ", '" . $this->esc($d['remark']) . "'"
                     . ", '" . $this->esc($mid) . "'"
                     .  ")"
                     ;
                $ans = $this->query($sql);
            }

        } catch(Exception $e) {
            throw $e;
        }
    }
}

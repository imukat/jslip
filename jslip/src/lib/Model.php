<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

class Model
{
    private $_mysqli;

    public function connect() {

        $this->_mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->_mysqli->connect_error) {
            return $this->_mysqli->connect_error;
        } else {
            $this->_mysqli->set_charset('utf8');
            $this->_mysqli->autocommit(FALSE);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        return '';
    }

    public function close() {
        $this->_mysqli->close();
    }

    public function begin() {
        $this->_mysqli->begin_transaction();
    }

    public function commit() {
        $this->_mysqli->commit();
    }

    public function rollback() {
        $this->_mysqli->rollback();
    }

    public function esc($str) {
        return $this->_mysqli->real_escape_string($str);
    }

    public function query($sql) {
        return $this->_mysqli->query($sql);
    }

    public function insert_id() {
        return $this->_mysqli->insert_id;
    }

    public function getRecord($sql) {

        $ans = [];

        if ($result = $this->_mysqli->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $ans[] = $row;
            }
            $result->close();
        }

        return $ans;
    }

    public function getPaging($cnt, $page, $rpp) {

        $mod  = $cnt % $rpp;
        $last = ($cnt - $mod) / $rpp;

        if ($mod > 0) {
            $last++;
        }

        if ($page > $last) {
            $page = $last;
        }

        if ($page < 1) {
            $page = 1;
        }

        $ofst = ($page - 1) * $rpp;

        return [
            'cnt'  => $cnt,   // count of all
            'page' => $page,  // page
            'rpp'  => $rpp,   // record par page
            'mod'  => $mod,   // mpdulus
            'last' => $last,  // last page
            'ofst' => $ofst,  // offset
        ];
    }

    public function getBasicByBid($bid) {

        $this->connect();
        $sql =  "SELECT"
             . " `id`, `name`, `disp_name`, `term_year`, `term_begin`, `term_end`"
             . " FROM `t_basic`"
             . " WHERE `id` = '" . $this->esc($bid) . "'"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        if (empty($rec[0])) {
            return $rec;
        }

        $rec[0]['era'] = $this->era($bid, $rec[0]['term_begin']);

        return $rec;
    }

    public function getConst($name) {

        switch ($name) {
            case 'c_c0':         return $this->_getC0();        break;
            case 'c_c1':         return $this->_getC1();        break;
            case 'c_c2':         return $this->_getC2();        break;
            case 'c_c3':         return $this->_getC3();        break;
            case 'c_c4':         return $this->_getC4();        break;
            case 'c_deb_cre':    return $this->_getDebCre();    break;
            case 'c_item_class': return $this->_getItemClass(); break;
            case 'c_round':      return $this->_getRount();     break;
            case 'c_settled':    return $this->_getSettled();   break;
        }

        return [];
    }

    private function _getC0() {

        $this->connect();
        $sql = "SELECT * FROM `c_c0` ORDER BY `c0`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c0']] = $r['name'];
        }

        return $ans;
    }

    private function _getC1() {

        $this->connect();
        $sql = "SELECT * FROM `c_c1` ORDER BY `c1`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c1']] = $r['name'];
        }

        return $ans;
    }

    private function _getC2() {

        $this->connect();
        $sql = "SELECT * FROM `c_c2` ORDER BY `c1`, `c2`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c1']][$r['c2']] = $r['name'];
        }

        return $ans;
    }

    private function _getC3() {

        $this->connect();
        $sql = "SELECT * FROM `c_c3` ORDER BY `c1`, `c2`, `c3`, `div`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c1']][$r['c2']][$r['c3']] = ['div' => $r['div'], 'name' => $r['name']];
        }

        return $ans;
    }

    private function _getC4() {

        $this->connect();
        $sql = "SELECT * FROM `c_c4` ORDER BY `c1`, `c2`, `c3`, `c4`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c1']][$r['c2']][$r['c3']][$r['c4']] = $r['name'];
        }

        return $ans;
    }

    private function _getDebCre() {

        $this->connect();
        $sql = "SELECT * FROM `c_deb_cre` ORDER BY `c0`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c0']] = ['abbr' => $r['abbr'], 'name' => $r['name']];
        }

        return $ans;
    }

    private function _getItemClass() {

        $this->connect();
        $sql = "SELECT * FROM `c_item_class` ORDER BY `c0`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c0']] = $r['name'];
        }

        return $ans;
    }

    private function _getRound() {

        $this->connect();
        $sql = "SELECT * FROM `c_round` ORDER BY `c0`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c0']] = $r['name'];
        }

        return $ans;
    }

    private function _getSettled() {

        $this->connect();
        $sql = "SELECT * FROM `c_settled` ORDER BY `c0`";
        $rec = $this->getRecord($sql);
        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['c0']] = $r['name'];
        }

        return $ans;
    }

    public function getC34() {

        $this->connect();

        $sql =  "SELECT"
             .  " `t0`.`ctg`"  . " AS " . "`ctg`"
             . ", `t3`.`div`"  . " AS " . "`div`"
             . ", `t0`.`name`" . " AS " . "`name`"
             . " FROM"
             . " ("
             .     "SELECT `c1`, `c2`, `c3`, `c1` * 1000 + `c2` * 100 + `c3` * 10 AS `ctg`, `name`"
             .    " FROM   `c_c3`"
             .    " UNION"
             .    " SELECT `c1`, `c2`, `c3`, `c1` * 1000 + `c2` * 100 + `c3` * 10 + `c4` AS `ctg`, `name`"
             .    " FROM   `c_c4`"
             .  ") `t0`"
             . " INNER JOIN `c_c3` `t3` ON `t0`.`c1` = `t3`.`c1` AND `t0`.`c2` = `t3`.`c2` AND `t0`.`c3` = `t3`.`c3`"
             . " ORDER BY `t0`.`ctg`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['ctg']] = ['div' => $r['div'], 'name' => $r['name']];
        }

        return $ans;
    }

    public function getAcitm($bid) {

        $this->connect();

        $sql =  "SELECT `ccd`, `item`, `name`"
             . " FROM `t_account`"
             . " WHERE `bid` = '" . $this->esc($bid) . "'"
             . " ORDER BY `ccd`, `item`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['ccd']][$r['item']] = $r['name'];
        }

        return $ans;
    }

    public function getKcode($bid) {

        $this->connect();

        $sql =  "SELECT `ccd`, `item`, `name`"
             . " FROM `t_account`"
             . " WHERE `bid` = '" . $this->esc($bid) . "'"
             . "   AND `ccd` > 0"
             . " ORDER BY `ccd`, `item`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['ccd'] * 100 + $r['item']] = $r['name'];
        }

        return $ans;
    }

    public function getKcd($bid) {

        $this->connect();

        $sql =  "SELECT `kcd`, `name`, `kana`"
             . " FROM `t_item`"
             . " WHERE `bid` = '" . $this->esc($bid) . "'"
             . " ORDER BY `kana`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['kcd']] = ['name' => $r['name'], 'kana' => $r['kana']];
        }

        return $ans;
    }

    public function getItemByName($bid) {

        $this->connect();

        $sql =  "SELECT `kcd`, `name`, `kana`"
             . " FROM `t_item`"
             . " WHERE `valid_flg` = TRUE AND `bid` = '" . $this->esc($bid) . "'"
             . " ORDER BY `kana`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['kcd']] = ['name' => $r['name'], 'kana' => $r['kana']];
        }

        return $ans;
    }

    public function getItemByTime($bid) {

        $this->connect();

        $sql =  "SELECT `kcd`, `name`"
             . " FROM `t_item`"
             . " WHERE `valid_flg` = TRUE AND `bid` = '" . $this->esc($bid) . "'"
             . " ORDER BY `update_time` DESC, `kana` ASC"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['kcd']] = $r['name'];
        }

        return $ans;
    }

    public function touchItem($id) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_item` SET"
                 . " `dummy` = '" . date('YmdHis') . "'"
                 . " WHERE `id` = '" . $this->esc($id) . "'"
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

    public function getSection($bid) {

        $this->connect();

        $sql =  "SELECT `id`, `name`"
             . " FROM `t_section`"
             . " WHERE `bid` = '" . $this->esc($bid) . "'"
             . " ORDER BY `kana`"
             ;
        $rec = $this->getRecord($sql);

        $this->close();

        $ans = [];
        foreach ($rec as $r) {
            $ans[$r['id']] = $r['name'];
        }

        return $ans;
    }

    public function texStr($s) {
        $s = str_replace("_", "\\_", $s);
        $s = str_replace("$", "\\$", $s);
        return $s;
    }

    public function era($bid, $day)
    {
        $this->connect();
        $sql = "SELECT `ymd`, `era`, `abr` FROM `t_era` WHERE `bid` = " . $this->esc($bid) . " ORDER BY `ymd`";
        $rec = $this->getRecord($sql);
        $this->close();

        $era = [];
        $n   = 0;
        $era[$n++] = ['？？', '?', '0000-00-00'];
        foreach ($rec as $r) {
            $era[$n++] = [$r['era'], $r['abr'], $r['ymd']];
        }

        for ($i = 0; $i < $n; $i++) {
            if ($day >= $era[$i][2]) {
                $chk = $era[$i];
            }
        }

        $ymd = explode('-', $day);
        $a   = explode('-', $chk[2]);
        $nen = $ymd[0] - $a[0] + 1;

        $ans["name"] = $chk[0] . $nen;
        $ans["abbr"] = $chk[1] . $nen;

        return  $ans;
    }

    public function cntJournal($bid)
    {
        $this->connect();
        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_journal` WHERE `bid` = " . $this->esc($bid);
        $rec = $this->getRecord($sql);
        $this->close();

        return  $rec[0]['cnt'];
    }
}

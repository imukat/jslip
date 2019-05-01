<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Model.php');

class BasicInfoModel extends Model
{
    public function getList($cnd) {

        $this->connect();

        $where = $this->_getListWhere($cnd);
        $cnt   = $this->_getListCnt($where);
        $list  = $this->_getListDat($where, $cnt, $cnd['pager']);

        $this->close();

        $dat = [];
        foreach ($list['rec'] as $k => $d) {
            $dat[$k]         = $d;
            $dat[$k]['era']  = $this->era($d['id'], $d['term_begin']);
            $dat[$k]['jcnt'] = $this->cntJournal($d['id']);
        }

        $list['rec'] = $dat;

        return $list;
    }

    private function _getListWhere($cnd) {

        $where = " WHERE (TRUE)";

        if (trim($cnd['cnd_year']) != '') {
            $where .= " AND `term_year` = '" . $this->esc($cnd['cnd_year']) . "'";
        }

        if ($cnd['cnd_name'] != '') {
            $where .= " AND ((`name` LIKE '%" . $this->esc($cnd['cnd_name']) . "%') OR (`disp_name` LIKE '%" . $this->esc($cnd['cnd_name']) . "%'))";
        }

        return $where;
    }

    private function _getListCnt($where) {

        $sql = "SELECT COUNT(*) AS `cnt` FROM `t_basic`" . $where;
        $rec = $this->getRecord($sql);

        return $rec[0]['cnt'];
    }

    private function _getListDat($where, $cnt, $pager) {

        $pg  = $this->getPaging($cnt, $pager['page'], $pager['rpp']);

        if ($cnt < 0) {
            $rec = [];
        } else {
            $sql =  "SELECT"
                 . " `id`, `valid_flg`, `name`, `disp_name`, `term_year`, `term_begin`, `term_end`, `mid`"
                 . " FROM `t_basic`"
                 . $where
                 . " ORDER BY `id` ASC"
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

    public function getMember() {

        $this->connect();
        $sql =  "SELECT"
             . " `m`.`mid`, `a`.`login_id`, `m`.`name`, `m`.`role`"
             . " FROM `t_auth` `a` INNER JOIN `t_member` `m` ON `a`.`aid` = `m`.`aid`"
             . " ORDER BY `m`.`name`"
             ;
        $rec = $this->getRecord($sql);
        $this->close();

        return $rec;
    }

    public function getData($bid) {

        $this->connect();
        $sql = "SELECT * FROM `t_basic` WHERE `id` = '" . $this->esc($bid) . "'";
        $rec = $this->getRecord($sql);
        $this->close();

        return (empty($rec[0])) ? [] : $rec[0];
    }

    public function regist($param) {

        $err = '';

        $this->connect();
        $this->begin();

        try {

            $sql =  "UPDATE `t_basic` SET"
                 .  " `name`"          . " = '" . $this->esc($param['name']) . "'"
                 . ", `disp_name`"     . " = '" . $this->esc($param['disp_name']) . "'"
                 . ", `term_year`"     . " = '" . $this->esc($param['term_year']) . "'"
                 . ", `term_begin`"    . " = '" . $this->esc($param['term_begin']) . "'"
                 . ", `term_end`"      . " = '" . $this->esc($param['term_end']) . "'"
                 . ", `mid`"           . " = '" . $this->esc($param['mid']) . "'"
                 . ", `valid_flg`"     . " = "  . $param['valid_flg']
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

    public function getMemberList($list) {

        $mid = [];
        foreach ($list as $rec) {
            if (!empty($rec['mid'])) {
                $mid[] = $rec['mid'];
            }
        }

        $ans = [];

        if (!empty($mid)) {

            $this->connect();

            $sql = "SELECT `mid`, `name` FROM `t_member` WHERE `mid` IN (" . implode(',', $mid) . ")";
            $rec = $this->getRecord($sql);

            $this->close();

            foreach ($rec as $r) {
                $ans[$r['mid']] = $r['name'];
            }
        }

        return $ans;
    }
}

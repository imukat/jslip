<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserJournalModel.php');

define('PAGER_RPP', 3);

class UserJournalController extends Controller
{
    public $rest;
    public $param;
    public $model;
    public $view;
    public $viewName;
    public $simpleName;
    public $dat;
    public $pager;
    public $err;
    public $bid;
    public $basic;
    public $mode;
    public $item;
    public $sect;
    public $stld;
    public $kcd;
    public $section;
    public $settled;
    public $begin;
    public $end;

    public function main($param) {

        $this->rest  = '';
        $this->param = $param;
        $this->bid   = $_SESSION['minfo']['bid'];
        $this->model = new UserJournalModel($this->bid);
        $this->view  = new View();
        $this->dat   = [];

        $this->param['base'] = dirname(__FILE__);

        $basic = $this->model->getBasicByBid($this->bid);

        if (empty($basic[0])) {
            $this->_error('基本情報が見つかりません。');
            return;
        } else {
            $this->basic = $basic[0];
        }

        $this->begin = str_replace('-', '/', $this->basic['term_begin']);
        $this->end   = str_replace('-', '/', $this->basic['term_end']);

        $this->dat['limit'] = $this->model->getLimit();

        if (empty($this->param['act'])) {
            $this->_list();
        } else {
            switch ($this->param['act']) {
                case 'create':    $this->_create();    break;
                case 'drop':      $this->_drop();      break;
                case 'edit':      $this->_edit();      break;
                case 'duplicate': $this->_duplicate(); break;
                case 'tax':       $this->_tax();       break;
                case 'remember':  $this->_remember();  break;
                case 'search':    $this->_search();    break;
                case 'check':     $this->_check();     break;
                case 'regist':    $this->_regist();    break;
                default:          $this->_list();      break;
            }
        }
    }

    private function _error($err) {

        $this->viewName = 'user_journal_err';
        $this->err      = $err;
    }

    private function _list() {

        $this->viewName = 'user_journal_list';

        $this->dat['cnd'] = [
            'cnd_scd'    => '-1',
            'cnd_begin'  => $this->begin,
            'cnd_end'    => $this->end,
            'cnd_denpyo' => '',
            'cnd_kcd'    => '-1',
            'cnd_remark' => '',
            'cnd_stflg'  => '-2', // setteled flag
            'cnd_nuflg'  => '-1', // not use flag
            'pager'    => ['page' => 1, 'rpp' => PAGER_RPP],
        ];

        $this->dat['list']  = $this->model->getList($this->dat['cnd']);
        $this->kcd          = $this->model->getKcd($this->bid);
        $this->section      = $this->model->getSection($this->bid);
        $this->settled      = $this->model->getConst('c_settled');

        $_SESSION['user_journal_list_cnd'] = $this->dat['cnd'];
    }

    private function _search() {

        $this->viewName = 'user_journal_list';

        $this->dat['cnd'] = [
            'cnd_scd'    => $this->param['cnd_scd'],
            'cnd_begin'  => $this->param['cnd_begin'],
            'cnd_end'    => $this->param['cnd_end'],
            'cnd_denpyo' => $this->param['cnd_denpyo'],
            'cnd_kcd'    => $this->param['cnd_kcd'],
            'cnd_remark' => $this->param['cnd_remark'],
            'cnd_stflg'  => $this->param['cnd_stflg'],
            'cnd_nuflg'  => $this->param['cnd_nuflg'],
            'pager'      => ['page' => $this->param['page_curr'], 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);
        $this->kcd         = $this->model->getKcd($this->bid);
        $this->section     = $this->model->getSection($this->bid);
        $this->settled     = $this->model->getConst('c_settled');

        $_SESSION['user_journal_list_cnd'] = $this->dat['cnd'];
    }

    private function _remember() {

        $this->viewName    = 'user_journal_list';
        $this->dat['cnd']  = $_SESSION['user_journal_list_cnd'];
        $this->dat['list'] = $this->model->getList($this->dat['cnd']);
        $this->kcd         = $this->model->getKcd($this->bid);
        $this->section     = $this->model->getSection($this->bid);
        $this->settled     = $this->model->getConst('c_settled');
    }

    private function _edit() {

        $this->viewName = 'user_journal_edit';

        $this->mode         = 'edit';
        $this->dat          = $this->model->getData($this->param['id']);
        $this->item['name'] = $this->model->getItemByName($this->bid);
        $this->item['time'] = $this->model->getItemByTime($this->bid);
        $this->sect         = $this->model->getSection($this->bid);
        $this->stld         = $this->model->getConst('c_settled');
    }

    private function _duplicate() {

        $this->viewName = 'user_journal_edit';

        $this->mode         = 'duplicate';
        $this->dat          = $this->model->getData($this->param['id']);
        $this->item['name'] = $this->model->getItemByName($this->bid);
        $this->item['time'] = $this->model->getItemByTime($this->bid);
        $this->sect         = $this->model->getSection($this->bid);
        $this->stld         = $this->model->getConst('c_settled');
    }

    private function _tax() {

        $this->singleViewName = 'user_journal_tax';

        $round = $this->model->getRound($this->bid);
        $tax   = $this->model->getTax($this->bid);
        $val   = $this->param['val'] * 1.0;

        $rid   = $round['c0'];
        $rname = $round['name'];

        $ans = [];
        $ans['val']   = $val;
        $ans['rname'] = $rname;
        $ans['otax']  = [];
        $ans['itax']  = [];

        // 外税
        foreach ($tax as $k => $d) {
            $n = $d['name'];
            $r = $d['rate'] * 1.0;
            $t = $val * $r;
            switch ($rid) {
                case -1: $t = floor($t); break; // 切捨て
                case  0: $t = round($t); break; // 四捨五入
                case  1: $t = ceil($t);  break; // 切上げ
            }
            $ans['otax'][$k] = ['name' => $n, 'tax' => $t];
        }

        // 内税
        foreach ($tax as $k => $d) {
            $n = $d['name'];
            $r = $d['rate'] * 1.0;
            $t = ($val * $r) / (1.0 + $r);
            switch ($rid) {
                case -1: $t = floor($t); break; // 切捨て
                case  0: $t = round($t); break; // 四捨五入
                case  1: $t = ceil($t);  break; // 切上げ
            }
            $ans['itax'][$k] = ['name' => $n, 'tax' => $t];
        }

        $this->ans = $ans;
    }

    private function _check() {

        $err    = [];

        $insert = (empty($this->param['insert'])) ? false : true;
        $ymd    = $this->param['arg']['ymd'];
        $dat    = $this->param['arg']['dat'];

        if (empty($ymd)) {
            $err[] = '伝票日付は必須です。';
        } else {
            if (!$this->chkYmd($ymd)) {
                $err[] = '不正な伝票日付です。';
            } else {
                if ($ymd < $this->begin || $this->end < $ymd) {
                    $err[] = '伝票日付が範囲外です。';
                }
            }
        }

        $deb_sum = 0;
        $cre_sum = 0;
        $n       = 0;

        foreach ($dat as $k => $d) {

            if ($d['deb_name'] == -1 && $d['cre_name'] == -1) {
                continue;
            }

            $n++;

            if ($d['deb_name'] == -1 || $d['cre_name'] == -1) {
                $err[] = $k . '行目：借方科目または貸方科目が指定されていません。';
            }

            if ($d['deb_name'] == $d['cre_name']) {
                $err[] = $k . '行目：借方科目と貸方科目が同じです。';
            }

            if ($d['deb_amount'] == 0 && $d['cre_amount'] == 0) {
                $err[] = $k . '行目：金額が入力されていません。';
            }

            if ($d['remark'] == '') {
                $err[] = $k . '行目：摘要が入力されていません。';
            }

            $deb_sum += $d['deb_amount'];
            $cre_sum += $d['cre_amount'];
        }

        if ($deb_sum != $cre_sum) {
            $err[] = '合計が等しくありません。';
        }

        if ($n < 1) {
            $err[] = '不正な伝票です。';
        }

        if (empty($err)) {
            $this->rest = json_encode(['sts' => 'OK']);
        } else {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
        }
    }

    private function _regist() {

        if ($this->param['arg']['settled_flg'] == -1) {
            $this->param['arg']['ymd'] = $this->begin;
        }

        $insert = (empty($this->param['insert'])) ? false : true;

        if ($insert) {
            $err = $this->model->insert($this->param);
        } else {
            $err = $this->model->regist($this->param);
        }

        if (!empty($err)) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
            return;
        }

        $this->rest = json_encode(['sts' => 'OK']);
    }

    private function _create() {

        $this->viewName = 'user_journal_create';
        $this->item['name'] = $this->model->getItemByName($this->bid);
        $this->item['time'] = $this->model->getItemByTime($this->bid);
        $this->sect         = $this->model->getSection($this->bid);
        $this->stld         = $this->model->getConst('c_settled');
    }

    private function _drop() {

        $err = $this->model->delete($this->param);

        if (!empty($err)) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
            return;
        }

        $this->rest = json_encode(['sts' => 'OK']);
    }
}

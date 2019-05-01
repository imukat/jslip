<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserTaxModel.php');

class UserTaxController extends Controller
{
    public $rest;
    public $param;
    public $model;
    public $view;
    public $viewName;
    public $dat;
    public $err;
    public $bid;
    public $basic;

    public function main($param) {

        $this->rest  = '';
        $this->param = $param;
        $this->bid   = $_SESSION['minfo']['bid'];
        $this->model = new UserTaxModel($this->bid);
        $this->view  = new View();

        $this->param['base'] = dirname(__FILE__);

        $basic = $this->model->getBasicByBid($this->bid);

        if (empty($basic[0])) {
            $this->_error('基本情報が見つかりません。');
            return;
        } else {
            $this->basic = $basic[0];
        }

        if (empty($this->param['act'])) {
            $this->_list();
        } else {
            switch ($this->param['act']) {
                case 'create':   $this->_create();   break;
                case 'drop':     $this->_drop();     break;
                case 'edit':     $this->_edit();     break;
                case 'use':      $this->_use();      break;
                case 'check':    $this->_check();    break;
                case 'regist':   $this->_regist();   break;
                default:         $this->_list();     break;
            }
        }
    }

    private function _error($err) {

        $this->viewName = 'user_tax_err';
        $this->err      = $err;
    }

    private function _list() {

        $this->viewName = 'user_tax_list';

        $this->dat['list'] = $this->model->getList();
    }

    private function _create() {

        $this->viewName = 'user_tax_create';
    }

    private function _edit() {

        $this->viewName = 'user_tax_edit';
        $this->dat = $this->model->getData($this->param['id']);
    }

    private function _use() {

        $err = $this->model->setValidFlg($this->param);

        if (empty($err)) {
            $this->rest = json_encode(['sts' => 'OK']);
        } else {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
        }
    }

    private function _check() {

        $err    = [];

        $insert = (empty($this->param['insert'])) ? false : true;
        $name   = $this->param['name'];
       @$rate   = $this->param['rate'] * 1.0;

        if (empty($name)) {
            $err[] = '名称は必須です。';
        }

        if ($rate < 0.0001 || $rate >= 100.0) {
            $err[] = '税率が不正です。rateは、0.0001以上で100未満の間で指定します。';
        }

        if ($insert && $this->model->chkDup($name) > 0) {
            $err[] = '名称は既に存在します。';
        }

        if (empty($err)) {
            $this->rest = json_encode(['sts' => 'OK']);
        } else {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
        }
    }

    private function _regist() {

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

    private function _drop() {

        $err = $this->model->delete($this->param);

        if (!empty($err)) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
            return;
        }

        $this->rest = json_encode(['sts' => 'OK']);
    }
}

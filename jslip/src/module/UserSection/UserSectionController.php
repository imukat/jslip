<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserSectionModel.php');

define('PAGER_RPP', 10);

class UserSectionController extends Controller
{
    public $rest;
    public $param;
    public $model;
    public $view;
    public $viewName;
    public $dat;
    public $pager;
    public $err;
    public $bid;
    public $basic;

    public function main($param) {

        $this->rest  = '';
        $this->param = $param;
        $this->bid   = $_SESSION['minfo']['bid'];
        $this->model = new UserSectionModel($this->bid);
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
                case 'remember': $this->_remember(); break;
                case 'search':   $this->_search();   break;
                case 'check':    $this->_check();    break;
                case 'regist':   $this->_regist();   break;
                default:         $this->_list();     break;
            }
        }
    }

    private function _error($err) {

        $this->viewName = 'user_section_err';
        $this->err      = $err;
    }

    private function _list() {

        $this->viewName = 'user_section_list';

        $this->dat['cnd'] = [
            'cnd_kana' => '',
            'cnd_name' => '',
            'pager'    => ['page' => 1, 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);

        $_SESSION['user_section_list_cnd'] = $this->dat['cnd'];
    }

    private function _search() {

        $this->viewName = 'user_section_list';

        $this->dat['cnd'] = [
            'cnd_kana' => $this->param['cnd_kana'],
            'cnd_name' => $this->param['cnd_name'],
            'pager'    => ['page' => $this->param['page_curr'], 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);

        $_SESSION['user_section_list_cnd'] = $this->dat['cnd'];
    }

    private function _remember() {

        $this->viewName      = 'user_section_list';
        $this->dat['cnd']    = $_SESSION['user_section_list_cnd'];
        $this->dat['list']   = $this->model->getList($this->dat['cnd']);
    }

    private function _edit() {

        $this->viewName = 'user_section_edit';
        $this->dat = $this->model->getData($this->param['id']);
    }

    private function _check() {

        $err    = [];

        $insert = (empty($this->param['insert'])) ? false : true;
        $kana   = $this->param['kana'];
        $name   = $this->param['name'];

        if (empty($kana)) {
            $err[] = '部門（かな）は必須です。';
        }

        if (empty($name)) {
            $err[] = '部門は必須です。';
        }

        if ($this->model->chkDup($name) > 0) {
            $err[] = '部門名は既に存在します。';
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

    private function _create() {

        $this->viewName = 'user_section_create';
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

<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/AccountModel.php');

define('PAGER_RPP', 10);

class AccountController extends Controller
{
    public $param;
    public $rest;
    public $viewName;
    public $dat;
    public $pager;
    public $model;
    public $view;

    public function main($param) {

        if ($_SESSION['minfo']['role'] != 'root') {
            $this->viewName = 'account_err';
            return;
        }

        $this->rest  = '';
        $this->param = $param;
        $this->model = new AccountModel();
        $this->view  = new View();

        $this->param['base'] = dirname(__FILE__);

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

    private function _list() {

        $this->viewName = 'account_list';

        $this->dat['cnd'] = [
            'cnd_login_id' => '',
            'cnd_name'     => '',
            'pager'        => ['page' => 1, 'rpp' => PAGER_RPP],
        ];

        $this->dat['list']   = $this->model->getList($this->dat['cnd']);

        $_SESSION['account_list_cnd'] = $this->dat['cnd'];
    }

    private function _search() {

        $this->viewName = 'account_list';

        $this->dat['cnd'] = [
            'cnd_name'     => $this->param['cnd_name'],
            'cnd_login_id' => $this->param['cnd_login_id'],
            'pager'        => ['page' => $this->param['page_curr'], 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);

        $_SESSION['account_list_cnd'] = $this->dat['cnd'];
    }

    private function _remember() {

        $this->viewName      = 'account_list';
        $this->dat['cnd']    = $_SESSION['account_list_cnd'];
        $this->dat['list']   = $this->model->getList($this->dat['cnd']);
    }

    private function _edit() {

        $this->viewName = 'account_edit';
        $this->dat = $this->model->getData($this->param['mid']);
    }

    private function _check() {

        $err      = [];

        $insert   = (empty($this->param['insert'])) ? false : true;
        $name     = $this->param['name'];
        $role     = $this->param['role'];
        $email    = $this->param['email'];
        $tel      = $this->param['tel'];
        $login_id = $this->param['login_id'];
        $passwd0  = $this->param['passwd0'];
        $passwd1  = $this->param['passwd1'];

        if (empty($name)) {
            $err[] = 'メンバー名は必須です。';
        }

        if (empty($login_id)) {
            $err[] = 'アカウントは必須です。';
        } else {
            if ($insert) {
                if ($this->model->chkDup($login_id)) {
                    $err[] = '既に存在するアカウントです。';
                }
            }
        }

        if ($insert) {
            if (empty($passwd0)) {
                $err[] = 'パスワードは必須です。';
            }
        }

        if (!empty($passwd1)) {
            if ($passwd0 != $passwd1) {
                $err[] = 'パスワードが不正です。';
            }
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

        $this->viewName = 'account_create';
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

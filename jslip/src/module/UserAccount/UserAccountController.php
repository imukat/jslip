<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserAccountModel.php');

class UserAccountController extends Controller
{
    public $param;
    public $rest;
    public $model;
    public $view;
    public $viewName;
    public $dat;
    public $bid;

    public function main($param) {

        $this->rest  = '';
        $this->param = $param;
        $this->model = new UserAccountModel();
        $this->view  = new View();
        $this->bid   = $_SESSION['minfo']['bid'];

        $this->param['base'] = dirname(__FILE__);

        if (empty($this->param['act'])) {
            $this->_edit();
        } else {
            switch ($this->param['act']) {
                case 'check':    $this->_check();    break;
                case 'regist':   $this->_regist();   break;
                default:         $this->_edit();     break;
            }
        }
    }

    private function _edit() {

        $this->viewName = 'user_account_edit';
        $this->dat = $this->model->getData($this->bid);
    }

    private function _check() {

        $err      = [];

        $name     = $this->param['name'];
        $email    = $this->param['email'];
        $tel      = $this->param['tel'];
        $passwd0  = $this->param['passwd0'];
        $passwd1  = $this->param['passwd1'];

        if (empty($name)) {
            $err[] = 'メンバー名は必須です。';
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

        $err = $this->model->regist($this->param);

        if (!empty($err)) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => $err]);
            return;
        }

        $this->rest = json_encode(['sts' => 'OK']);
    }
}

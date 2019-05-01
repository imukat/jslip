<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/LoginModel.php');

class LoginController extends Controller
{
    public $param;
    public $rest;
    public $viewName;
    public $token;
    public $model;

    function __construct() {
        $this->model = new LoginModel();
    }

    public function main($param) {

        $this->rest  = '';
        $this->param = $param;

        $this->param['base'] = dirname(__FILE__);

        if (empty($this->param['act'])) {
            $this->_init();
        } else {
            switch ($this->param['act']) {
                case 'check': $this->_check(); break;
                default:      $this->_init();  break;
            }
        }
    }

    private function _init() {

        // Member Information
        $_SESSION['minfo'] = [];

        // A Token Seed
        $_SESSION['tseed'] = (string)random_int(1111111111, 9999999999);

        $this->viewName    = 'login';
        $this->token       = password_hash($_SESSION['tseed'], PASSWORD_DEFAULT);
    }

    private function _check() {

        if ((int)date('Ymd') > (int)str_replace('-', '', EXPIRE)) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => '有効期限切れです。']);
            return;
        }

        if (!password_verify($_SESSION['tseed'], $this->param['token'])) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => '不正トークン']);
            return;
        }

        if (!$this->model->chkPasswd($this->param['account'], $this->param['passwd'])) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => 'アカウン名またはパスワードに誤りがあります。']);
            return;
        }

        $info = $this->model->getMemberInfo($this->param['account']);

        if (empty($info['role'])) {
            $this->rest = json_encode(['sts' => 'NG', 'err' => 'メンバー情報が見つかりません。']);
            return;
        }

        $info['bcnt'] = $this->model->cntBasic($info['mid']);

        if ($info['bcnt'] == 1) {
            $info['bid']  = $this->model->getBid($info['mid']);
        } else {
            $info['bid']  = -1;
        }

        $_SESSION['minfo'] = $info;

        if ($info['role'] == 'root') {
            $this->rest = json_encode(['sts' => 'OK', 'url' => $this->param['url_base'], 'func' => 'RootMenu']);
        } else {
            $this->rest = json_encode(['sts' => 'OK', 'url' => $this->param['url_base'], 'func' => 'UserMenu']);
        }
    }
}

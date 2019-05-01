<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserAcitmModel.php');

define('PAGER_RPP', 10);

class UserAcitmController extends Controller
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
    public $const;

    public function main($param) {

        $this->rest  = '';
        $this->param = $param;
        $this->bid   = $_SESSION['minfo']['bid'];
        $this->model = new UserAcitmModel($this->bid);
        $this->view  = new View();

        $this->param['base'] = dirname(__FILE__);

        $basic = $this->model->getBasicByBid($this->bid);
        $this->_getConst();

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

        $this->viewName = 'user_acitm_err';
        $this->err      = $err;
    }

    private function _list() {

        $this->viewName = 'user_acitm_list';

        $this->dat['cnd'] = [
            'cnd_name' => '',
            'cnd_kana' => '',
            'pager'    => ['page' => 1, 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);

        $_SESSION['user_acitm_list_cnd'] = $this->dat['cnd'];
    }

    private function _getConst() {
        $this->const['c_c0']         = $this->model->getConst('c_c0');
        $this->const['c_c1']         = $this->model->getConst('c_c1');
        $this->const['c_c2']         = $this->model->getConst('c_c2');
        $this->const['c_c3']         = $this->model->getConst('c_c3');
        $this->const['c_c4']         = $this->model->getConst('c_c4');
        $this->const['c_deb_cre']    = $this->model->getConst('c_deb_cre');
        $this->const['c_item_class'] = $this->model->getConst('c_item_class');
        $this->const['c34']          = $this->model->getC34();
    }

    private function _search() {

        $this->viewName = 'user_acitm_list';

        $this->dat['cnd'] = [
            'cnd_name' => $this->param['cnd_name'],
            'cnd_kana' => $this->param['cnd_kana'],
            'pager'    => ['page' => $this->param['page_curr'], 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);

        $_SESSION['user_acitm_list_cnd'] = $this->dat['cnd'];
    }

    private function _remember() {

        $this->viewName      = 'user_acitm_list';
        $this->dat['cnd']    = $_SESSION['user_acitm_list_cnd'];
        $this->dat['list']   = $this->model->getList($this->dat['cnd']);
    }

    private function _edit() {

        $this->viewName = 'user_acitm_edit';
        $this->dat = $this->model->getData($this->param['id']);
    }

    private function _check() {

        $err    = [];

        $insert = (empty($this->param['insert'])) ? false : true;
        $item   = $this->param['item'];
        $kana   = $this->param['kana'];
        $name   = $this->param['name'];

        if (empty($kana)) {
            $err[] = '勘定名（かな）は必須です。';
        }

        if (empty($name)) {
            $err[] = '勘定名は必須です。';
        }

        if ($item == '') {
            $err[] = '勘定細分コードは必須です。';
        } else {
            $v = (int)$item;
            if ($v < 0 || $v > 99) {
                $err[] = '勘定細分コードが不正です。';
            }
        }

        if (empty($err)) {
            if (!empty($this->param['insert'])) {
                if ($this->model->chkDup($this->param) > 0) {
                    $err[] = 'コードが重複しています。';
                }
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

        $this->viewName = 'user_acitm_create';
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

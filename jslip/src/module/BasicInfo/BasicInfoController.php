<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/BasicInfoModel.php');

define('PAGER_RPP', 10);

class BasicInfoController extends Controller
{
    public $param;
    public $rest;
    public $viewName;
    public $dat;
    public $mem;
    public $pager;
    public $model;
    public $view;

    public function main($param) {

        if ($_SESSION['minfo']['role'] != 'root') {
            $this->viewName = 'basic_info_err';
            return;
        }

        $this->rest  = '';
        $this->param = $param;
        $this->model = new BasicInfoModel();
        $this->view  = new View();

        $this->param['base'] = dirname(__FILE__);

        if (empty($this->param['act'])) {
            $this->_list();
        } else {
            switch ($this->param['act']) {
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

        $this->viewName = 'basic_info_list';

        $this->dat['cnd'] = [
            'cnd_name' => '',
            'cnd_year' => '',
            'pager'    => ['page' => 1, 'rpp' => PAGER_RPP],
        ];

        $this->dat['list']   = $this->model->getList($this->dat['cnd']);
        $this->dat['member'] = $this->model->getMemberList($this->dat['list']['rec']);

        $_SESSION['basic_info_list_cnd'] = $this->dat['cnd'];
    }

    private function _search() {

        $this->viewName = 'basic_info_list';

        $this->dat['cnd'] = [
            'cnd_name' => $this->param['cnd_name'],
            'cnd_year' => $this->param['cnd_year'],
            'pager'    => ['page' => $this->param['page_curr'], 'rpp' => PAGER_RPP],
        ];

        $this->dat['list'] = $this->model->getList($this->dat['cnd']);
        $this->dat['member'] = $this->model->getMemberList($this->dat['list']['rec']);

        $_SESSION['basic_info_list_cnd'] = $this->dat['cnd'];
    }

    private function _remember() {

        $this->viewName      = 'basic_info_list';
        $this->dat['cnd']    = $_SESSION['basic_info_list_cnd'];
        $this->dat['list']   = $this->model->getList($this->dat['cnd']);
        $this->dat['member'] = $this->model->getMemberList($this->dat['list']['rec']);
    }

    private function _edit() {

        $this->viewName = 'basic_info_edit';
        $this->dat = $this->model->getData($this->param['bid']);
        $this->mem = $this->model->getMember();
    }

    private function _check() {

        $err        = [];
        $name       = $this->param['name'];
        $disp_name  = $this->param['disp_name'];
        $term_year  = $this->param['term_year'] + 0;
        $term_begin = $this->param['term_begin'];
        $term_end   = $this->param['term_end'];

        if (empty($name)) {
            $err[] = '名称は必須です。';
        }

        if (empty($disp_name)) {
            $err[] = '表示名称は必須です。';
        }

        if (empty($term_year)) {
            $err[] = '年度は必須です。';
        } else {
            if ($term_year < YEARS[0] || YEARS[1] < $term_year) {
                $err[] = '不正な年度です。';
            }
        }

        if (empty($term_begin)) {
            $err[] = '期首は必須です。';
        } else {
            if (!$this->chkYmd($term_begin)) {
                $err[] = '不正な期首です。';
            }
        }

        if (empty($term_end)) {
            $err[] = '期末は必須です。';
        } else {
            if (!$this->chkYmd($term_end)) {
                $err[] = '不正な期末です。';
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

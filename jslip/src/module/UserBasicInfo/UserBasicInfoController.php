<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserBasicInfoModel.php');

class UserBasicInfoController extends Controller
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
        $this->model = new UserBasicInfoModel();
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

        $this->viewName = 'user_basic_info_edit';
        $this->dat   = $this->model->getData($this->bid);
        $this->round = $this->model->getRound();
    }

    private function _check() {

        $err        = [];
        $disp_name  = $this->param['disp_name'];
        $term_year  = $this->param['term_year'] + 0;
        $term_begin = $this->param['term_begin'];
        $term_end   = $this->param['term_end'];

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

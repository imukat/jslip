<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/RootMenuModel.php');

class RootMenuController extends Controller
{
    public $param;
    public $rest;
    public $viewName;
    public $model;
    public $view;
    public $err;

    public function main($param) {

        if ($_SESSION['minfo']['role'] != 'root') {
            $this->viewName = 'root_menu_err';
            return;
        }

        $this->rest  = '';
        $this->param = $param;
        $this->model = new RootMenuModel();
        $this->view  = new View();

        $this->param['base'] = dirname(__FILE__);

        $this->err = $this->model->chkDatabse();

        if (empty($this->err)) {
            $this->viewName = 'root_menu';
        } else {
            $this->viewName = 'root_menu_err';
        }
    }
}

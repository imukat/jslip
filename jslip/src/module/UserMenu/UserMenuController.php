<?php
/**
 * @link      https://datagram.co.jp/source/bksj for the canonical source repository
 * @copyright Copyright (c) 2006-2019 Datagram Ltd. (https://datagram.co.jp)
 * @license   https://datagram.co.jp/source/bksj/license.txt
 */

require_once(dirname(__FILE__) . '/../../lib/View.php');
require_once(dirname(__FILE__) . '/../../lib/Controller.php');
require_once(dirname(__FILE__) . '/UserMenuModel.php');

class UserMenuController extends Controller
{
    public $param;
    public $rest;
    public $viewName;
    public $model;
    public $view;
    public $dat;
    public $csv;

    public function main($param) {

        $this->rest  = '';
        $this->csv   = '';
        $this->param = $param;
        $this->model = new UserMenuModel();
        $this->view  = new View();

        $this->param['base'] = dirname(__FILE__);

        if (empty($this->param['act'])) {
            $this->_menu();
        } else {
            switch ($this->param['act']) {
                case 'useLast':    $this->_useLast();    break;
                case 'cntJournal': $this->_cntJournal(); break;
                case 'setBid':     $this->_setBid();     break;
                case 'exportCsv':  $this->_exportCsv();  break;
                case 'importCsv':  $this->_importCsv();  break;
                default:           $this->_menu();       break;
            }
        }
    }

    public function dispCsv() {
        $this->_dispCsv();
        $this->_clearFiles();
    }

    private function _menu() {

        if ($_SESSION['minfo']['role'] == 'root' && !empty($this->param['bid'])) {
            $this->dat = ['basic' => $this->model->getBasicByBid($this->param['bid'])];
            $this->viewName = 'user_menu';
        } elseif (empty($_SESSION['minfo']['mid']) || empty($_SESSION['minfo']['bcnt'])) {
            $this->dat      = ['err' => 'Out of service.'];
            $this->viewName = 'user_menu_err';
        } elseif ($_SESSION['minfo']['bid'] > 0) {
            $this->dat = ['basic' => $this->model->getBasicByBid($_SESSION['minfo']['bid'])];
            $this->viewName = 'user_menu';
        } else {
            $this->dat = ['basic' => $this->model->getBasicByMid($_SESSION['minfo']['mid'])];
            $this->viewName = 'user_menu_select';
        }
    }

    private function _useLast() {

        $bid  = $this->param['bid'];
        $last = $this->model->getLast($bid);

        if ($last < 0) {
            $ans = ['sts' => 'NG', 'err' => '前年度のデータが見つかりません。'];
        } else {
            $err = $this->model->useLast($_SESSION['minfo']['mid'], $last, $bid);
            if (empty($err)) {
               $ans = ['sts' => 'OK', 'err' => ''];
            } else {
               $ans = ['sts' => 'NG', 'err' => $err['msg']];
            }
        }

        $this->rest = json_encode($ans);
    }

    private function _cntJournal() {
        $cnt = $this->model->cntJournal($this->param['bid']);
        $this->rest = json_encode(['sts' => 'OK', 'cnt' => $cnt]);
    }

    private function _setBid() {
        $_SESSION['minfo']['bid'] = $this->param['bid'];
        $this->dat = ['basic' => $this->model->getBasicByBid($_SESSION['minfo']['bid'])];
        $this->viewName = 'user_menu';
    }

    private function _exportCsv() {
        $this->csv = $this->param['tmp_dir'] . '/slip_' . $_SESSION['minfo']['bid'] . '_' . date('YmdHis') . '.csv';
        $this->model->makeCsv($_SESSION['minfo']['bid'], $this->csv);
    }

    private function _importCsv() {

        $err     = [];
        $records = [];

        $this->viewName = 'user_menu';

        $upfile = $this->param['tmp_dir'] . '/' . basename($_FILES['upfile']['name']);

        if (empty($_FILES['upfile']['name'])) {
            $err['File Name'] = 'ファイル名が指定されていません。';
        } elseif (!move_uploaded_file($_FILES['upfile']['tmp_name'], $upfile)) {
            $err['Upload'] = 'ファイルのアップロードに失敗しました。';
        } else {
            $file = new SplFileObject($upfile); 
            $file->setFlags(SplFileObject::READ_CSV); 
            foreach ($file as $line) {
                if (!is_null($line[0])) {
                    $records[] = $line;
                }
            } 
        }

        if (empty($err)) {
            $validErr = $this->_ValidImportCsv($_SESSION['minfo']['bid'], $records);
            if (empty($validErr)) {
                $err = $this->model->setImportedCsvData($_SESSION['minfo']['mid'], $_SESSION['minfo']['bid'], $records);
            } else {
                $err['Valid'] = $validErr;
            }
        }

        $basic = $this->model->getBasicByBid($this->param['bid']);
        $this->dat = ['err' => $err, 'basic' => $basic];
    }

    private function _ValidImportCsv($bid, $rec) {

        $r0 = (empty($rec[0][0])) ? '' : $rec[0][0];
        $r1 = (empty($rec[1][0])) ? '' : $rec[1][0];
        $r2 = (empty($rec[2][0])) ? '' : $rec[2][0];
        $r3 = (empty($rec[3][0])) ? '' : $rec[3][0];
        $r  = $r0 . $r1 . $r2 . $r3;

        if ($r != 'HEADERBASICHEADERSECTION') {
            return 'データ・フォーマットが違います。';
        }

        $cnt = $this->model->cntJournal($bid);

        if ($cnt > 0) {
            return '伝票が入力されています。';
        }

        return '';
    }

    private function _dispCsv() {
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($this->csv));
        header('Content-Disposition: inline; filename=' . basename($this->csv));
        readfile($this->csv);
    }

    private function _clearFiles() {
        exec(UTL_DELETE . ' -f ' . $this->csv);
    }
}
